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
 * Performs lookup operations on ldap server
 *
 * You can use Task to perform searches on the ldap server according to options you
 * specify.<br>
 * This class serves as a wrapper for php functions that retrieve and process
 * data from directory servers.
 *
 * <h2>Features:</h2>
 * <h3>Method Chaining</h3>
 * Methods may be chained together to simplify configuration. Ensure that the methods you wish to chain
 * together return the {@link self} object to avoid issues. See the example code below.<br>
 * <br>
 * <p class="alert">Make sure you import the class into current namespace before trying to use it:<br>
 * <code>use ADX\Core\Task;</code><br>
 * To simplify the examples, the above line is not present in the examples but it is assumed you have
 * it in your implementation.</p>
 *
 * <h2>Example:</h2>
 * <code>
 * use ADX\Enums;							// Import the Enums namespace
 *
 * $link = new Link( 'example.com' );				// Connect to server
 * $link->bind( 'user@example.com', 'MySecretPwd' );		// Authenticate
 *
 * $task = new Task( Enums\Operation::OpSearch, $link );		// Create a search operation
 * $task	->attributes( ['cn', 'givenname', 'sn', 'mail'] )	// Load these attributes for the objects
 * 	->filter( '(&(objectclass=user)(mail=*))' )		// Get all users that have a mail attribute
 * 	->base( 'OU=Users,DC=example,DC=com' );			// Limit the search only to the Users OU in the domain
 *
 * // Get the results!
 * $result = $task->run();
 * </code>
 *
 * @see			{@link Link} - Use the Link object to establish and configure connection to directory servers
 * @see			{@link Enums\Operation} - Specify the scope of the lookup operation
 * @see			<a href="http://msdn.microsoft.com/en-us/library/windows/desktop/ms678001%28v=vs.85%29.aspx">MSDN - Deciding Where To Search</a>
 *
 * @todo		Move all lookup error checking from {@link Object} to {@link self}
 * @todo		The connection pooling feature might need a rewrite - if I start a lookup operation with an account that has lower access rights than the one I used previously, AD-X might fail to perform the requested operation.
 */
class Task
{
	protected static $connection_pool = array();	// Contains link objects to which this task has been referred to during lookup
	protected static $allowedScopes	= [
			Enums\Operation::OpSearch,
			Enums\Operation::OpList,
			Enums\Operation::OpRead,
	];

	/**
	 * Specify the maximum number of attempts to follow a referral
	 *
	 * Set this property to a desired number of attempts to follow a referral. If the
	 * number of encountered referrals is larger than this number, the lookup operation
	 * will fail.
	 *
	 * Default is to follow up to 3 referrals.
	 *
	 * @var			integer
	 *
	 * @see			<a href="http://msdn.microsoft.com/en-us/library/windows/desktop/ms677913%28v=vs.85%29.aspx">MSDN - Referrals</a>
	 */
	public $max_referrals		= 3;				// Maximum allowed number of followed referrals

	/**
	 * Is there more data to be retrieved? Yes -> false / No -> true
	 *
	 * This flag tells you if the result of your lookup operation is complete.
	 * Note that this is only applicable for paged lookups - if you do not
	 * enable pagination, this flag will always be true even if there would be
	 * more data to be retrieved with pagination enabled.
	 *
	 * @var			bool
	 *
	 * @see			self::use_pages()
	 */
	public $complete;


	protected $adxLink;

	protected $operationType;						// The operation to be performed ( one of consts with name OperationType* defined in this class )
	protected $dn;									// baseDN override to be used for the operation
	protected $attributes		= ['*'];			// Attributes to be fetched
	protected $filter			= '(objectclass=*)';// Filter for the lookup operation

	protected $page_size;							// If paged results have been enabled, this specifies the size of a single page
	protected $cookie;								// If paged results have been enabled, this will contain the cookie returned by server

	protected $referral_counter	= 0;				// Number of times a referral was chased

	/**
	 * Create a new directory lookup task
	 *
	 * <h4>Example</h4>
	 * <code>
	 * use ADX\Enums;				// Import the Enums namespace
	 *
	 * $link = new Link( 'example.com' );	// Connect to server
	 *
	 * // Create a search task, performing the operation on the $link connection
	 * $task = new Task( Enums\Operation::OpSearch, $link );
	 * </code>
	 *
	 * @uses		Link
	 * @uses		Enums\Operation
	 * @param		string		The type of lookup operation you wish to perform, defined in {@link Enums\Operation}. While you can pass an explicit string as an argument, you are strongly discouraged from doing that.
	 * @param		Link		The Link the operation will be performed on
	 */
	public function __construct( $operationType, Link $adxLink )
	{
		if ( ! in_array( $operationType, $this::$allowedScopes ) ) throw new IncorrectParameterException( 'Invalid Operation supplied for directory lookup - see the Operation enumeration for allowed values' );

		$this->operationType	= $operationType;
		$this->adxLink			= $adxLink;
	}

	/**
	 * Enable the results to be retrieved from directory server in pages
	 *
	 * This allows you to retrieve complete search results for queries that would otherwise
	 * hit the maximum limit for a single search ( Active Directory limits a single non-paged search
	 * to 1000 objects by default ). Enable this functionality by invoking this
	 * method on the Task and perform search queries using the same search criteria
	 * using {@link Task::run()}. You can also avoid the overhead of calling this method multiple times
	 * by invoking {@link Task::get_all_pages()} and you will get all the data with a single command.
	 *
	 * @uses		Enums\ServerControl::PagedResults
	 * @param		int		Number of objects in a single page ( default is 1000 )
	 * @param		string		Base64-encoded pagination cookie to be re-used. If not specified, an empty cookie will be used
	 * @return		self
	 *
	 * @see			<a href="http://www.php.net/manual/en/function.ldap-control-paged-result.php">PHP - ldap_control_paged_result()</a>
	 * @see			<a href="http://msdn.microsoft.com/en-us/library/windows/desktop/aa746459%28v=vs.85%29.aspx">MSDN - Retrieving Large Results Sets</a>
	 */
	public function use_pages( $page_size = 1000, $encoded_cookie = '' )
	{
		if ( in_array( Enums\ServerControl::PagedResults, $this->adxLink->rootDSE->supportedcontrol() ) )
		{
			$this->page_size	= $page_size;
			$this->cookie		= $encoded_cookie === '' ? $encoded_cookie : base64_decode( $encoded_cookie );
		}
		else throw new Exception( "$this->adxLink does not support paged results" );

		return $this;
	}

	/**
	 * Configure which attributes should be retrieved from the directory server
	 *
	 * If you do not pass any parameters to this function, it will return an array
	 * of all currently requested attributes to be loaded from server.
	 *
	 * <p class="alert"><i>objectclass</i> and <i>dn</i> are <b>always</b> returned.</p>
	 *
	 * @param		array|string		The attribute or attributes to be retrieved<br><b>Default:</b> <code>['*']</code>
	 * @return		self
	 *
	 */
	public function attributes( $attributes = null )
	{
		if ( is_null( $attributes ) ) return $this->attributes;

		// Make sure this param is an array
		if ( ! is_array( $attributes ) ) $attributes = [$attributes];

		$this->attributes = $attributes;

		return $this;
	}

	/**
	 * Get or set the ldap filter for the task
	 *
	 * @param		string		A valid ldap filter string<br><b>Default:</b> <code>"(objectclass=*)"</code>
	 * @return		self
	 *
	 * @see			<a href="http://msdn.microsoft.com/en-us/library/windows/desktop/aa746475%28v=vs.85%29.aspx">MSDN - Search Filter Syntax</a>
	 * @see			{@link Query}
	 * @todo		Perform some filter validation?
	 */
	public function filter( $filter = null )
	{
		if ( is_null( $filter ) ) return $this->filter;

		$this->filter = $filter;

		return $this;
	}

	/**
	 * Get or set the base DN as the starting point for the lookup operation
	 *
	 * You can limit the scope of the lookup operation by setting the search base to any
	 * valid distinguished name that exists in the directory structure. By doing so, your
	 * lookup operation will only return objects that fall under this DN.
	 * <p class="alert">If you specify a non-existing distinguished name as the base DN
	 * you will receive {@link Enums\ServerResponse::NoSuchObject} error when executing the
	 * lookup operation.</p>
	 *
	 * @param		string		The distinguished name of an existing directory object / container<br><b>Default:</b> the base DN of the current domain
	 * @return		self
	 */
	public function base( $dn = null )
	{
		if ( is_null( $dn ) ) return $this->dn;

		$this->dn = $dn;

		return $this;
	}

	/**
	 * Perform the lookup operation on server and return the resultset
	 *
	 * This method sends the lookup request to the server with the
	 * configuration you provided. If the {@link Link} has pagination enabled,
	 * it will return a single page. To get the subsequent pages, call this method
	 * again on the object, without modifying the lookup criteria.
	 *
	 * @uses		Link::$rootDSE		to get the domain base dn ( from <i>defaultnamingcontext</i> ) if no override has been specified
	 * @return		Result|false		The Result object with returned Objects or FALSE if maximum number of referrals was chased
	 */
	public function run()
	{
		// Prepare the information needed for the operation
		$link_id	= $this->adxLink->get_link();
		$baseDN		= ( $this->dn || $this->dn === '' ) ? $this->dn : $this->adxLink->rootDSE->defaultnamingcontext(0);
		if ( $this->page_size && $this->complete ) $this->cookie = '';	// Reset the cookie if the operation is started again
		$this->complete = false;										// Reset the completion status

		// Send the pagination control cookie if present
		// I must check explicitly for NULL bcause '' also evaluates to FALSE
		if ( $this->cookie !== null ) ldap_control_paged_result( $link_id, $this->page_size, true, $this->cookie );

		// Perform the lookup operation
		// All exceptions thrown here will bubble up!
		$result_id = $this->_directory_lookup( $this->operationType, $link_id, $baseDN, $this->filter, $this->attributes );

		// Get the new cookie from the response if pagination is enabled
		// I must check explicitly for NULL bcause '' also evaluates to FALSE
		if ( $this->cookie !== null ) ldap_control_paged_result_response( $link_id, $result_id, $this->cookie );

		// Lookup operation successful - continue processing...
		$referral_link = $this->_parse_referrals( $result_id );

		// Check for referrals
		if ( $referral_link )										// We have been referred to a new Link
		{
			if ( $this->referral_counter <= $this->max_referrals )	// Are we still allowed to chase referrals?
			{
				// Do we have this connection in the pool already?
				if ( isset( static::$connection_pool[$referral_link] ) )
				{
					$link = static::$connection_pool[$referral_link];	// Use the existing link to perform the lookup
				}
				else
				{
					$link = $this->adxLink->_redirect( $referral_link );	// Reconnect to the new server
					static::$connection_pool[$referral_link] = $link;		// Save the connection in the link pool
				}

				$this->referral_counter++;
				$this->adxLink = $link;										// Replace the current link with the new link

				return $this->run();	// Only those calls that actually succeed in getting data should continue with code below
			} else return false;		// Reached maximum number of referrals -> do not continue
		}

		// Successfully retrieved data from server
		$this->referral_counter = 0;

		$result = ldap_get_entries( $this->adxLink->get_link(), $result_id );

		unset( $result['count'] );	// Clean up some unneeded mess ( more cleanup happens at object instantiation )

		$data = array();

		// Create new objects from result, converting the values to php-compatible data formats on the way
		foreach ( $result as $objectData ) $data[] = new Object( $this->adxLink, $objectData, true );

		$this->complete = ! $this->cookie;	// As long as cookie is present, the result cannot be complete

		return new Result( $data );
	}

	/**
	 * Return all pages at once when doing paged search operations
	 *
	 * Use this method to get a complete resultset with all pages at once.
	 *
	 * @param		int			Optional size of objects per single page
	 * @return		Result		A Result object containing the objects on the server
	 *
	 * @see			self::use_pages()
	 */
	public function run_paged( $page_size = null )
	{
		isset( $page_size ) ? $this->use_pages( $page_size ) : $this->use_pages();

		$resultset = array();

		do
		{
			$data = $this->run();

			if ( $data instanceof Result )
			{
				$resultset = array_merge( $resultset, $data->to_array() );
			}
			else return false;
		}
		while ( ! $this->complete );

		return new Result( $resultset );
	}


	protected function _parse_referrals( $result_id )
	{
		if ( ! ldap_parse_result( $this->adxLink->get_link(), $result_id, $code, $matchedDN, $errMsg, $referrals ) ) return;

		if ( count( $referrals ) == 0 ) return;

		// Referral looks like this:
		// ldap://example.com/DC=example,DC=com
		//
		// Extract the protocol and url from the referral string
		if ( preg_match( "/^ldaps?:\/\/[a-zA-Z0-9_\-.]*/", $referrals[0], $matches ) !== 1 ) throw new Exception( "Unable to parse referral - {$referrals[0]}" );

		// Match found? Return it!
		if ( isset( $matches[0] ) ) return $matches[0];
	}


	protected function _directory_lookup( $operation, $link_id, $dn, $filter, $attributes )
	{
		// Ensure that attributes required by the library to work correctly are always present in the resultset
		$mandatory = [
			'objectclass',
		];

		$attributes = array_merge( $attributes, $mandatory );

		// php's internal mechanisms should take care of that... Keeping this here only for
		// a possible future reference.
		// $attributes = array_map( 'strtolower', $attributes );
		// $attributes = array_unique( $attributes );

		// Perform the operation
		$result_id = $operation( $link_id, $dn, $filter, $attributes );

		if ( $result_id === false )	// Operation was unsuccessful
		{
			// Check for any possible errors and handle them ( probably a TODO )
			$code = ldap_errno( $link_id );

			switch( $code )
			{
				case Enums\ServerResponse::InvalidDnSyntax:

					throw new IncorrectParameterException( "The DN was of incorrect syntax: $dn." );
					break;

				default:

					throw new LdapNativeException( $link_id );
					break;
			}
		}

		// return the resource result_id
		return $result_id;
	}
}