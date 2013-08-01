<?php

// Copyright (C) 2013 Robert Rossmann
//
// Permission is hereby granted, free of charge, to any person obtaining a
// copy of this software and associated documentation files (the "Software"),
// to deal in the Software without restriction, including without limitation the
// rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
//  copies of the Software, and to permit persons to whom the Software is furnished
// to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included in all
// copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
// INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A
// PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
// HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
// CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE
// OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.


namespace ADX\Core;
use ADX\Enums;

/**
 * Represents a connection to an ldap server
 *
 * This class is an encapsulation of the Resource ldap_link, returned by php's
 * <a href="http://www.php.net/manual/en/function.ldap-connect.php">ldap_connect()</a> function and
 * serves to simplify the configuration tasks of this link. You perform operations like connecting,
 * binding, configuring the connection and reading the RootDSE entry using this object.
 * <br>
 * <br>
 * If you typecast this object into a string you will get the current domain name the link is connected to.
 *
 * <h3>Method Chaining</h3>
 * Methods may be chained together to simplify configuration. Ensure that the methods you wish to chain
 * together return the {@link self} object to avoid issues. See the example code below.
 *
 * <h3>Serialisation support</h3>
 * You can serialise / unserialise the object to preserve the configuration across server requests.
 * This allows you to configure a link with the needed features, perform an operation on the server,
 * then store the link to i.e. a database for later re-use. If the user requests next operation, you can
 * restore the object from the serialised data and continue using the link without any further configuration
 * ( you will have to {@link self::bind()} again with the same credentials, though ).<br>
 * This is especially useful when performing paged search results - you get the first page, store the link in
 * database, present the data to user and if user requests the next page, you use the same link to retrieve
 * next batch of results.<br>
 * <br>
 * <p class="alert">Make sure you import the class into current namespace before trying to use it:<br>
 * <code>use ADX\Core\Link;</code><br>
 * To simplify the examples, the above line is not present in the examples but it is assumed you have
 * it in your implementation.</p>
 *
 * <h2>Example:</h2>
 * <code>
 * $link = new Link( 'example.com' );	// Connect to example.com over standard port 389
 * // Typecasting to string gives you the domain name this link is connected to
 * echo "$link";	// example.com
 *
 * // Chaining methods...
 * $link->use_tls()->bind( 'user@example.com', 'MySecretPwd' );
 *
 * // Serialisation support
 * $serialised = serialize( $link );
 * // Save $serialised to a database...
 * // WARNING - $link is no longer usable after being serialised!
 *
 * // In a later server request...
 * $link = unserialize( $serialised ); // Retrieve $serialised from your storage first
 * $link->bind( 'user@example.com', 'MySecretPwd' );	// Use the link with previously configured features
 * </code>
 *
 * @see			{@link Task} - perform lookup operations on directory servers
 */
class Link
{
	/**
	 * {@link Object} that represents the RootDSE of a directory server.
	 *
	 * This information is generated automatically for each new Link object and the information
	 * is used across the library. You should not attempt to modify the object.
	 *
	 * These attributes ( represented as {@link Attribute} ) are available by default:<br>
	 * - dnshostname
	 * - defaultnamingcontext
	 * - highestcommittedusn
	 * - supportedcontrol
	 * - supportedldapversion
	 * - supportedsaslmechanisms
	 * - rootdomainnamingcontext
	 * - configurationnamingcontext
	 * - schemanamingcontext
	 * - namingcontexts
	 * - currenttime
	 *
	 * <h4>Example</h4>
	 * <code>
	 * // Read the current time of the directory server
	 * $link		= new Link( 'example.com' );
	 * $server_time	= $link->rootDSE->currenttime->value( 0 );
	 * // Do something with $server_time...
	 * </code>
	 *
	 * @var			Object
	 * @see			<a href="http://msdn.microsoft.com/en-us/library/windows/desktop/ms684291%28v=vs.85%29.aspx">MSDN - RootDSE</a>
	 */
	public $rootDSE;

	protected $link_id;				// Resource link_identifier ( as returned by ldap_connect() )

	protected $domain;				// Domain to which this Link is connected
	protected $port;				// Port for the connection
	protected $username;			// Username used for binding
	protected $password;			// Password used for binding

	protected $isBound	= false;	// Is the connection already bound using ldap_bind() ?
	protected $use_tls	= false;	// Is the connection TLS-enabled?

	protected $options	= array();	// Array with user-supplied ( or default ) ldap options

	/**
	 * Create a new connection to a directory server
	 *
	 * Connects to a directory server and reads the RootDSE entry ( via anonymous bind ).
	 * Note that <b>ldap v3 is enforced</b> for all operations and cannot be overriden.
	 *
	 * <h4>Example</h4>
	 * <code>
	 * $link = new Link( 'example.com' );	// Uses default ldap port 389
	 * $link = new Link( 'example.com', 636 );	// Uses ssl-specific port 636
	 * $link = new Link( 'example.com', 389, [ServerControl::ShowDeleted] );	// Allows listing of deleted objects
	 * </code>
	 *
	 * @param		string		DNS name of the directory server, i.e. <i>example.com</i>
	 * @param		integer		Port to use for connection
	 * @param		array		Optional list of options to use. See {@link Enums\ServerControl} for available options
	 *
	 * @see			<a href="http://www.php.net/manual/en/function.ldap-connect.php">PHP - ldap_connect()</a>
	 * @see			<a href="http://www.php.net/manual/en/function.ldap-set-option.php">PHP - ldap_set_option()</a>
	 */
	public function __construct( $domain, $port = 389, $options = array() )
	{
		// Store the provided options in the options property of this object
		foreach ( $options as $option => $value ) $this->options[$option] = $value;

		$this->domain	= $domain;
		$this->port		= $port;
		$this->link_id	= ldap_connect( $domain, $port );	// Connect to ldap

		// Load the rootDSE object
		if ( ! $this->rootDSE )
		{
			$get = [
				'dnshostname',
				'defaultnamingcontext',
				'highestcommittedusn',
				'supportedcontrol',
				'supportedldapversion',
				'supportedsaslmechanisms',
				'rootdomainnamingcontext',
				'configurationnamingcontext',
				'schemanamingcontext',
				'namingcontexts',
				'currenttime',
			];

			$this->rootDSE = $this->rootDSE( $get );
		}

		// Make sure that the server supports ldap v3 protocol, otherwise no need to continue
		if ( ! in_array( 3, $this->rootDSE->supportedldapversion() ) ) throw new UnsupportedPlatformException();

		// Force the link to use ldap v3 and disable native referrals handling as it is required by this implementation
		ldap_set_option( $this->link_id, LDAP_OPT_PROTOCOL_VERSION,	3 );
		ldap_set_option( $this->link_id, LDAP_OPT_REFERRALS,		0 );
	}

	/**
	 * Bind to the directory server using provided credentials ( or anonymously if none provided )
	 *
	 * This method performs the standard <code>ldap_bind()</code> operation on the ldap link. Before that, it performs
	 * the link configuration, like setting the protocol version and applying user-specific server controls.
	 *
	 * <h4>Example</h4>
	 * <code>
	 * $link = new Link( 'example.com' );	// Connect to server
	 * $link->bind();	// Perform anonymous bind attempt
	 * $link->bind( 'user@example.com', 'MySecretPassword' );	// Perform authenticated bind attempt
	 * </code>
	 *
	 * @param		string		The username to be used for binding ( Visit <a href="http://msdn.microsoft.com/en-us/library/cc223499.aspx">MSDN</a> for possible formats )
	 * @param		string		The password to be used for binding
	 * @return		self
	 *
	 * @see			<a href="http://php.net/manual/en/function.ldap-bind.php">PHP - ldap_bind()</a>
	 */
	public function bind( $username = null, $password = null )
	{
		// Set the ldap options on the link_id
		$this->_set_ldap_options( $this->options );

		// Attempt bind operation...
		$this->_int_bind( $username, $password );

		// Successfully bound to server
		$this->username = $username;
		$this->password = $password;

		return $this;
	}

	/**
	 * Bind to the directory using a sasl mechanism
	 *
	 * @todo		Implementation is missing at this moment; keeping the method here for future development
	 * @return		self
	 *
	 * @see			<a href="http://www.php.net/manual/en/function.ldap-sasl-bind.php">PHP - ldap_sasl_bind()</a>
	 */
	public function sasl_bind()
	{

		return $this;
	}

	/**
	 * Enable TLS security layer for this connection
	 *
	 * Use this method to enable Transport Layer Security on the connection. You should use this <b>before</b>
	 * binding to the directory server, otherwise you risk compromising authentication data.
	 *
	 * This method does not check if you have a valid certificate installed on the machine - it only
	 * invokes the standard <code>ldap_start_tls()</code> function - the host machine's configuration necessary for
	 * a successful TLS connection is up to you.
	 *
	 * <h4>Example</h4>
	 * <code>
	 * $link = new Link( 'example.com' );	// Connect to server
	 * $link->use_tls();			// Enable TLS BEFORE binding
	 * $link->bind( 'user@example.com', 'MySecretPassword' );	// Perform authenticated bind attempt
	 * </code>
	 *
	 * @return		self
	 *
	 * @see			<a href="http://www.php.net/manual/en/function.ldap-start-tls.php">PHP - ldap_start_tls()</a>
	 */
	public function use_tls()
	{
		if ( ! @ldap_start_tls( $this->link_id ) ) throw new LdapNativeException( $this->link_id );

		$this->use_tls = true;

		return $this;
	}

	/**
	 * Read information from the RootDSE that is not available by default
	 *
	 * Use this method to read additional information from the RootDSE entry if
	 * the information you require is not available in {@link self::$rootDSE} by default.
	 *
	 * Returns all available information when called without any parameter.
	 *
	 * <h4>Example</h4>
	 * <code>
	 * $link	= new Link( 'example.com' );	// Connect to server
	 * $object	= $link->rootDSE();		// Load ALL attributes from the RootDSE
	 * // Do something with Object...
	 * $isSynchronised = $object->isSynchronized->value( 0 );
	 * </code>
	 *
	 * @uses		Object::read()
	 * @param		array		Array with the attributes you wish to have retrieved
	 * @return		Object		The Object representing the RootDSE entry with requested attributes
	 *
	 * @todo		Make the information requested via this method available in the {@link self::$rootDSE} property for subsequent reuse
	 */
	public function rootDSE( $attributes = ['*', '+'] )
	{
		// Attempt anonymous bind
		try
		{
			$this->_int_bind();
		}
		catch ( InvalidCredentialsException $e )
		{
			// Attempt failed, nothing much to do then...
			throw new Exception( "Your ldap server must allow anonymous bind requests to the rootDSE" );

		}

		// Successfully bound anonymously!
		$attributes = (array)$attributes;

		// Read the rootDSE
		$rootDSE = Object::read( '', $attributes, $this );

		return $rootDSE;
	}

	/**
	 * Show deleted objects in the search results
	 *
	 * Invoke this method to inform the directory server that it should also include
	 * deleted objects in the search result. This is a shorthand way of doing the following:<br>
	 * <code>
	 * use ADX\Enums;				// Import the Enums namespace
	 * $link = new Link( 'example.com' );	// Connect to server
	 * $link->use_extended_control( Enums\ServerControl::ShowDeleted, false ); // Enable as non-critical feature
	 * </code>
	 *
	 * @uses		self::use_extended_control()
	 * @uses		Enums\ServerControl::ShowDeleted
	 * @param		bool		If set to true, the feature is critical and server will not perform any operation if the feature is not available
	 * @return		self
	 *
	 * @see			self::use_extended_control()
	 * @see			<a href="http://www.php.net/manual/en/function.ldap-set-option.php">PHP - ldap_set_option()</a> ( LDAP_OPT_SERVER_CONTROLS )
	 */
	public function show_deleted( $critical = false )
	{
		$this->use_extended_control( Enums\ServerControl::ShowDeleted, $critical );

		return $this;
	}

	/**
	 * Enable an extended ldap control
	 *
	 * Use this method to enable any of the ldap server controls defined in {@link Enums\ServerControl}
	 * or by specifying your own control oid as the method's parameter.<br>
	 * Please note that the $value parameter is not supported ( and possibly never will be )
	 * due to lack of knowledge about BER encoding in php...
	 *
	 * @param		string		The control OID of the server control to be used
	 * @param		bool		If set to true, the feature is critical and server will not perform any operation if the feature is not available
	 * @param		mixed		Value to be sent along the control ( not supported yet )
	 * @return		self|false	Returns FALSE if the feature could not be enabled
	 *
	 * @see			Enums\ServerControl
	 *
	 * @todo		Implement the $value for the control request
	 */
	public function use_extended_control( $control_oid, $critical = false, $value = null )
	{
		if ( in_array( $control_oid, $this->rootDSE->supportedcontrol() ) )
		{
			$control = [
				'oid'			=> $control_oid,
				'value'			=> $value,
				'iscritical'	=> $critical
			];

			$options = array();
			if ( isset( $this->options[LDAP_OPT_SERVER_CONTROLS] ) ) $options = $this->options[LDAP_OPT_SERVER_CONTROLS];
			$options[] = $control;

			$this->_set_ldap_options( [LDAP_OPT_SERVER_CONTROLS => $options] );

			return $this;
		}
		else return false;
	}

	/**
	 * Returns the configured Resource ldap link to be used in ldap operations
	 *
	 * This method is used internally - you should not have any need to call it explicitly
	 * unless you deliberately want to do something special with the Resource object.
	 *
	 * @return		Resource		ldap link object
	 * @see			<a href="http://www.php.net/manual/en/resource.php">PHP - Resources</a> ( ldap link )
	 */
	public function get_link()
	{
		// Hand over the configured connection
		return $this->link_id;
	}


	/**
	 * Create a new link that points to the specified domain using the same configuration
	 * and credentials as in the original link.
	 *
	 * @internal
	 * @param		string		The DNS domain name, as returned by directory server
	 * @return		self		The new Link object pointing to the new server
	 */
	public function _redirect( $domain )
	{
		$link = new Link( $domain, $this->port, $this->options );

		if ( $this->use_tls ) $link->use_tls();
		$link->bind( $this->username, $this->password );

		return $link;
	}


	protected function _int_bind( $username = null, $password = null )
	{
		if ( strlen( $username ) > 0 && strlen( $password ) === 0 ) throw new IncorrectParameterException( 'You must supply a password if you supply a username' );

		if ( ! @ldap_bind( $this->link_id, $username, $password ) )
		{
			$adxError = ldap_errno( $this->link_id );

			switch ( $adxError )
			{
				case 0:
					break;	// No error occured

				// Throw a specific exception if the bind failed due to invalid credentials
				// or other account-related issues
				case Enums\ServerResponse::InappropriateAuth				:
				case Enums\ServerResponse::InvalidCredentials				:
				case Enums\ServerResponse::InsufficientAccess				:
				case Enums\ServerResponse::UserNotFound						:
				case Enums\ServerResponse::NotPermittedToLogonAtThisTime	:
				case Enums\ServerResponse::RestrictedToSpecificMachines		:
				case Enums\ServerResponse::PasswordExpired					:
				case Enums\ServerResponse::AccountDisabled					:
				case Enums\ServerResponse::AccountExpired					:
				case Enums\ServerResponse::UserMustResetPassword			:
					throw new InvalidCredentialsException( $this->link_id );
					break;

				// Throw a specific exception if the ldap server is unreachable
				case -1:
					throw new ServerUnreachableException( $this->link_id );
					break;

				// For all other cases, throw a generic exception
				default:
					throw new LdapNativeException( $this->link_id );
					break;
			}
		}

		// Bind successful!
		return $this;
	}

	protected function _set_ldap_options( $options = array() )
	{
		// Set the ldap options on the link_id
		foreach ( $options as $option => $value )
		{
			// Skip protocol version setting ( ldap v3 is enforced ) and referral handling settings
			if ( in_array( $option, [LDAP_OPT_PROTOCOL_VERSION, LDAP_OPT_REFERRALS] ) ) continue;

			@ldap_set_option( $this->link_id, $option, $value );

			$adxError = ldap_errno( $this->link_id );

			switch ( $adxError )
			{
				case Enums\ServerResponse::UnavailableCriticalExtension:
					throw new LdapNativeException( $this->link_id );
					break;
			}

			// Store the option for future reuse
			$this->options[$option] = $value;
		}

		return $this;
	}


	// Magic methods for magical functionality!

	/**
	 * Return the domain name this link is connected to when typcasting to string
	 *
	 * <h4>Example</h4>
	 * <code>
	 * $link = new Link( 'example.com' );
	 * // Typecasting to string gives you the domain name this link is connected to
	 * echo "$link";	// example.com
	 * </code>
	 *
	 * @internal
	 * @return		string		The domain name this link is connected to
	 */
	public function __toString()
	{
		return $this->domain;
	}

	/**
	 * Perform cleanup before serialisation
	 *
	 * Closes the Resource ldap link, clears username and password used for binding
	 * and base64-encodes the pagination cookie to avoid data corruption when
	 * saving the serialised string to database.<br>
	 *
	 * Serialising a Link renders the object unusable!
	 *
	 * @internal
	 * @return		array		The object's properties to be serialised
	 *
	 * @see			<a href="http://www.php.net/manual/en/language.oop5.magic.php#object.sleep">PHP - __sleep()</a>
	 * @see			<a href="http://php.net/manual/en/function.base64-encode.php">PHP - base64_encode()</a>
	 */
	public function __sleep()
	{
		// Release the ldap link_identifier
		unset( $this->link_id );

		// Clear the username and password for security reasons
		unset( $this->username );
		unset( $this->password );

		// Return all non-empty object attributes as array for serialisation (required by method definition)
		return array_keys( get_object_vars( $this ) );
	}

	/**
	 * Initialise the connection to the server after restoring from serialised state
	 *
	 * Decodes the cookie from a base64-encoded value ( if present ), reconnects to the same domain controller
	 * it was connected before and re-enables TLS if it was enabled.
	 *
	 * @internal
	 * @uses		self::$rootDSE
	 * @uses		self::use_tls()
	 * @return		void
	 *
	 * @see			<a href="http://www.php.net/manual/en/language.oop5.magic.php#object.wakeup">PHP - __wakeup()</a>
	 * @see			<a href="http://php.net/manual/en/function.base64-decode.php">PHP - base64_decode()</a>
	 */
	public function __wakeup()
	{
		// Reconnect to the domain
		$this->_connect( $this->domain, $this->port );	// Connect to the server
		if ( $this->use_tls ) $this->use_tls();			// Use TLS connection if previously enabled
	}

	/**
	 * Unbinds from directory server when the object is destroyed
	 *
	 * @internal
	 * @return		void
	 *
	 * @see			<a href="http://www.php.net/manual/en/function.ldap-unbind.php">PHP - ldap_unbind()</a>
	 */
	public function __destruct()
	{
		if ( $this->link_id ) ldap_unbind( $this->link_id );
	}
}