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
 * Represents any entity in the ldap server
 *
 * Any object returned by lookup operations will be of this class or will inherit from it. It
 * contains all the object's data, represented by instances of
 * the {@link Attribute} class.
 *
 * @see		{@link Attribute} - Attributes of an entity / object are represented by this class
 *
 * @property-read		string	$dn			The Distinguished name of the object ( available only for objects already stored in ldap database )
 */
class Object implements \ArrayAccess, \Iterator, \Countable, \JsonSerializable
{
	use	Jsonizer;

	protected $adxLink;							// Link object that stores the link_id resource
	protected $dn;								// The distinguished name of the object ( empty in case of new object )
	protected $data					= array();	// Contains all properties and their values for the ldap object
	protected $changedAttributes	= array();	// Contains attribute objects that were changed after the initial load from server
	// Schema-loaded properties of the objectclass
	protected $rdnAttId;						// The attribute to be used in the relative distinguished name

	// Iterator interface properties
	protected $iteratorPosition	= 0;
	protected $iteratorKeys;

	/**
	 * Read a single object from the server
	 *
	 * Use this method to read the specified attributes of a single entity in
	 * the directory server if you know the distinguishe name of the object,
	 * or if you can provide a search filter that returns exactly zero or one object.
	 *
	 * <h2>Example:</h2>
	 * <code>
	 * use ADX\Core\Object;		// Import the class into the current namespace
	 *
	 * // Read the object with specified distinguished name
	 * $user = Object::read(	"CN=Karen Berge,OU=admins,DC=corp,DC=company,DC=com",
	 * 			['name', 'mail'],
	 * 			$link );		// $link is a fully configured {@link Link} object
	 *
	 * // Read an object specified by the search filter
	 * try
	 * {
	 * 	$user = Object::read( "(&(samaccountname=jsmith))", ['name', 'mail'], $link );
	 * }
	 * catch ( ADX\Core\Exception $e )
	 * {
	 * 	// More than one object found - refine your search query!
	 * 	echo $e->getMessage();
	 * 	exit;
	 * }
	 *
	 * // Use the object somehow...
	 * echo $user->mail->value(0);
	 * </code>
	 *
	 * @throws		Exception		If the lookup operation returns more than one object from the server
	 * @param		string			The distinguished name of the entity to be loaded or an ldap filter that reaturns exactly one object
	 * @param		array			Array of attributes to be loaded from the server
	 * @param		Link			The configured and bound Link to server
	 * @return		self			Object containing the requested attributes
	 */
	public static function read( $dnOrFilter, $attributes, Link $adxLink )
	{
		// Check if we have DN or a search filter
		if ( ! is_string( $dnOrFilter ) ) throw new IncorrectParameterException( 'Invalid search filter supplied - you must provide a valid ldap filter' );

		if ( $dnOrFilter === '' || preg_match( '/^[^(].*DC={1}.*[^)]$/i', $dnOrFilter ) === 1 )	// It's a DN or rootDSE
		{
			$task = new Task( Enums\Operation::OpRead, $adxLink );
			$task	->attributes( $attributes )
					->base( $dnOrFilter );

			return $task->run()->first();
		}
		else	// It's a filter
		{
			$task = new Task( Enums\Operation::OpSearch, $adxLink );
			$task	->attributes( $attributes )
					->filter( $dnOrFilter );

			$result = $task->run();

			if ( count( $result ) > 1 ) throw new Exception( 'Ambiguous results returned - please refine your search filter' );

			return $result->first();
		}
	}

	/**
	 * Restore an Object from a json string
	 *
	 * If you previously jsonized an instance of the {@link self} class,
	 * you can convert it to an object again using this method.
	 *
	 * <p class="alert">All data that the json string holds will be
	 * considered as locally modified! That means, if you call {@link self::update()}
	 * on a restored object, all data will be sent to the server. As such, it
	 * is recommended that you use this method only with modified data
	 * if you plan to call {@link self::update()}.</p>
	 * <br>
	 * <p class="alert"> This method is currently experimental.</p>
	 *
	 * @param		string		The json string, as returned by {@link self::json()}
	 * @param		Link		The Link to be used for later server-side operations
	 * @return		Object		The restored object
	 */
	public static function restore( $json, Link $adxLink )
	{
		$data = json_decode( $json, true, 512, JSON_BIGINT_AS_STRING );

		return new static( $adxLink, $data );
	}


	/**
	 * Create a brand new object to be potentially saved to the server later on
	 *
	 * Create a new object, modify its attributes and optionally store
	 * the object on the directory server.
	 *
	 * <h2>Example:</h2>
	 * <code>
	 * use ADX\Core\Object;	// Import the class into the current namespace
	 *
	 * // Create the instance and specify the 'objectclass' and 'mail' attributes immediately
	 * $object = new Object( $link, ['objectclass' => 'user', 'mail' => 'robert@company.com'] );
	 *
	 * // Optionally, further modify the object as needed
	 * $object->givenName->set( 'Robert' );
	 *
	 * // Save the object to the server
	 * $object->create( 'OU=admins,DC=corp,DC=company,DC=com' );
	 * </code>
	 *
	 * @param		Link		A configured and bound {@link Link} object
	 * @param		array		A named array containing the attribute names as indexes and their values
	 * @return		self		A new instance of this class
	 */
	public function __construct( Link $adxLink, $attributes = array() )
	{
		$args = func_get_args();
		$comesFromLdap = isset( $args[2] ) ? $args[2] : false;	// This parameter is hidden

		$this->adxLink = $adxLink;

		if ( isset( $attributes['dn'] ) )
		{
			$this->dn = $attributes['dn'];
			unset( $attributes['dn'] );
		}

		// Check for presence of the schema definition for the current class
		if ( isset( $attributes['objectclass'] ) )
		{
			$class	= end( $attributes['objectclass'] );
			$schema	= Schema::get( $class );
		}

		if ( isset( $schema ) && $schema )
		{
			$this->rdnAttId = isset( $schema['rdnattid'] ) ? $schema['rdnattid'][0] : 'cn';	// If the RdnAttId could not be found, assume cn as default
		}

		if ( count( $attributes ) > 0 ) $this->data = $this->_filter_ldap_result( $attributes, $comesFromLdap );

		// Consider all values as locally modified if this is a new object
		if ( ! $comesFromLdap )
		{
			foreach ( $this->data as $attribute => $adxAttribute ) $this->_register_change( $adxAttribute );
		}
	}

	/**
	 * Create the object on the server
	 *
	 * Use this method to save a newly created object on the directory server.
	 * To update an existing object, refer to the {@link self::update()} method for
	 * more information.
	 *
	 * @param		string		The distinguished name of the parent container where this object should be stored
	 * @return		self
	 */
	public function create( $dn )
	{
		// Make sure that we have the RDN attribute present
		if ( ! array_key_exists( $this->rdnAttId, $this->changedAttributes ) ) throw new Exception( "The mandatory attribute $this->rdnAttId is missing" );

		$link_id = $this->adxLink->get_link();
		$dn = "$this->rdnAttId=" . $this->changedAttributes[$this->rdnAttId][0] . ",$dn";

		if ( ldap_add( $link_id, $dn, $this->_get_changed_data() ) )
		{
			// Successfully created the object
			$this->changedAttributes = array();
			$this->dn = $dn;	// Store the DN with the object ( the object will then be considered as if loaded from server )

			return $this;
		}
		else $this->_handle_last_error(); // Could not create object
	}

	/**
	 * Update the object on the server with the locally made changes
	 *
	 * Use this method to save the local changes to an existing ldap entity
	 * to the server.
	 *
	 * @return		self
	 */
	public function update()
	{
		if ( ! isset( $this->dn ) ) throw new Exception( 'You cannot call update() on a newly created object' );

		$link_id	= $this->adxLink->get_link();
		$changes	= $this->_get_changed_data();

		if ( count( $changes ) === 0 ) return $this;	// Nothing to be updated

		if ( ldap_modify( $link_id, $this->dn, $changes ) )
		{
			// Successfully modified the object
			$this->changedAttributes = array();

			return $this;
		}
		else $this->_handle_last_error(); // Could not update object
	}

	/**
	 * Delete the object from ldap server
	 *
	 * Once you delete the object from server you should release the
	 * php instance and not use it again to prevent unexpected errors.
	 *
	 * @return		void
	 */
	public function delete()
	{
		if ( ldap_delete( $this->adxLink->get_link(), $this->dn ) )
		{
			// Successfully deleted the object. Take care of data in php now...
			unset( $this->data );
		}
		else $this->_handle_last_error(); // Could not delete object
	}

	/**
	 * Get the specified attribute of the object
	 *
	 * This is one of the many ways how to access an object's attribute.
	 * <h2>Example:</h2>
	 * <code>
	 * use ADX\Core\Object;	// Import the class into the current namespace
	 * // $link is a fully configured and bound {@link Link} object
	 *
	 * // Retrieve or create an object
	 * $object = Object::read( 'OU=admins,DC=corp,DC=company,DC=com', ['mail', 'givenname'], $link );
	 *
	 * // Access the object's attribute
	 * print_r( $object->get( 'mail' )->value() );
	 * </code>
	 *
	 * @param		string			The name of the attribute, as defined on the ldap server
	 * @return		Attribute		An instance of Attribute class, holding the attribute's value(s)
	 */
	public function get( $attribute )
	{
		$attribute = strtolower( $attribute );

		if ( isset( $this->data[$attribute] ) )
		{
			return $this->data[$attribute];
		}
		elseif ( $attribute == 'dn' )
		{
			return $this->dn;
		}
		else
		{
			// If someone does something like $adxObject->samaccountname->set('val') but samaccountname is not
			// set in the object this will ensure that the script will not fail
			// and data modification can continue as necessary.
			$attribute = new Attribute( $attribute );
			$attribute->belongs_to( $this );

			$this->data["$attribute"] = $attribute;

			return $attribute;
		}
	}

	/**
	 * Set the specified attribute's value
	 *
	 * Use this method to modify the value(s) of the specified attribute.
	 * <p class="alert">This method replaces values currently present on the attribute.
	 * <br>
	 * If you supply an instance of the {@link Attribute} class as the $value, the instance
	 * will be replaced.</p>
	 *
	 * @param		string						The name of the attribute to be modified
	 * @param		string|array|Attribute		The value(s) to be set on the attribute
	 * @return		self
	 */
	public function set( $attribute, $value )
	{
		if ( $attribute instanceof Attribute )
		{
			$this->data["$attribute"] = $attribute;
			$attribute->belongs_to( $this );
			$this->_register_change( $attribute );
		}
		else $this->$attribute->set( $value );

		return $this;
	}

	/**
	 * Remove the specific attribute(s) from the object
	 *
	 * You can supply either a name of the attribute, an instance of the Attribute class
	 * or an array with either the names or instances to be removed.
	 *
	 * @param		string|array|Attribute		The attribute(s) to be removed
	 * @return		self
	 */
	public function remove( $attributes = array() )
	{
		// Make sure this param is an array
		$attributes = (array)$attributes;

		foreach ( $attributes as $attribute )
		{
			// Typecast to string in case Attribute object has been supplied (this will convert it to attribute's name)
			$attribute = (string)$attribute;

			// Remove the attribute by clearing it's value
			$this->$attribute->clear();
		}

		return $this;
	}

	/**
	 * Resolve the attribute into individual instances of the {@link self} class
	 *
	 * If the attribute holds distinguished name(s) as its values, it is
	 * considered to be resolvable. Resolving such attribute means
	 * that the distinguished names the attribute holds will be read from the
	 * directory server ( using {@link self::read()} ) and turned into editable instances
	 * of the Object class. You can supply an array of attributes that these objets
	 * should have when loaded from server.
	 * <br>
	 * <br>
	 * This method is very handy for retrieving information ( email address, name etc.)
	 * about e.g. members of a group.
	 *
	 * <p class="alert">You must load the property from the server first in order
	 * to resolve it.</p>
	 *
	 * <h2>Example:</h2>
	 * <code>
	 * use ADX\Core\Object;	// Import the class into the current namespace
	 * // $link is a fully configured and bound {@link Link} object
	 *
	 * // Read the list of members of a group
	 * $group = Object::read(
	 * 	'CN=My Security Group,OU=Groups,DC=corp,DC=company,DC=com',
	 * 	['member'],
	 * 	$link );
	 *
	 * // Resolve the group's members into individual
	 * // objects ( each object will have the 'mail' attribute loaded )
	 * $members = $group->resolve( 'member', ['mail'] );
	 *
	 * // You have two options now.
	 * // No. 1: The $members is an instance of the {@link Result} class -
	 * // you can utilise it's capabilities to traverse through the objects
	 * // in the resultset
	 * $members->each( function( $object )
	 * {
	 * 	print_r( $object->mail->value() );
	 * });
	 *
	 * // No. 2: Access those individual objects from the $group,
	 * // as if it were just another attribute
	 * print_r( $group->member->value(0)->mail->value() ); // Prints the email address of the first object in the 'member' attribute
	 * print_r( $group->member(0)->mail() );		// Use a shorter syntax to print the same information
	 * </code>
	 *
	 * @param		string				The ldap name of the attribute to be resolved
	 * @param		string|array		The attribute(s) the resolved objects should have
	 * @return		Result				The objects, contained within the Result class
	 */
	public function resolve( $attribute, $attributes = null )
	{
		$attribute	= $this->get( $attribute );
		$data		= $attribute->value();
		$objects	= array();

		if ( ! $attribute->isResolvable ) throw new InvalidOperationException( "Attribute '$attribute' is not resolvable" );

		foreach ( $data as $dn ) $objects[] = static::read( $dn, $attributes, $this->adxLink );

		$this->data["$attribute"] = new Attribute( "$attribute", $objects, $this );

		return new Result( $objects );
	}

	/**
	 * Get the distinguished name of the parent container of the object
	 *
	 * The object must be either loaded from a server or already saved to the
	 * server if created locally. Otherwise, the parent cannot be known.
	 *
	 * @return		string		The parent's distinguished name
	 */
	public function parent()
	{
		if ( ! $this->dn ) return null;	// This object has not yet been saved to the directory server

		$data = ldap_explode_dn( $this->dn, false );	// Explode the DN if this object into individual components
		unset( $data['count'] );						// Remove the count
		unset( $data[0] );								// Remove the RDN of this object

		return implode( ',', $data );					// Glue them back together
	}

	/**
	 * Get the number of defined Attributes in this object
	 *
	 * @return		integer
	 */
	public function count()
	{
		return count( $this->data );
	}

	/**
	 * Get an array of all attributes that are currently present on the object
	 *
	 * @return		array		An array containing the names of all attributes defined on the object
	 */
	public function all_attributes()
	{
		return array_keys( $this->data );
	}

	/**
	 * @internal
	 */
	public function _register_change( Attribute $attribute )
	{
		if ( ! in_array( $attribute, $this->changedAttributes ) ) $this->changedAttributes[] = "$attribute";
	}


	/**
	 * Get the changed data since retrieval from ldap or since object's creation
	 *
	 * This function returns data that is ready to be stored in ldap database, in
	 * ldap-compatible format.
	 *
	 * @return		array		The hash of changed data ( keys are attribute names and values are their values )
	 */
	protected function _get_changed_data()
	{
		$data = array();

		foreach ( $this->changedAttributes as $attribute ) $data[$attribute] = $this->get( $attribute )->ldap_data();

		return $data;
	}

	/**
	 * This method handles the last ldap error ( by throwing proper exception, for example )
	 *
	 * @return		void
	 */
	protected function _handle_last_error()
	{
		$link_id	= $this->adxLink->get_link(); 	// The link resource
		$error		= ldap_errno( $link_id );		// The error code

		switch( $error )
		{
			case Enums\ServerResponse::InsufficientAccess:
				throw new InsufficientAccessException( $link_id );
				break;
			case Enums\ServerResponse::AlreadyExists:
				throw new LdapNativeException( $link_id );
				break;
			case Enums\ServerResponse::UndefinedType:
				throw new UndefinedTypeException( $link_id );
			default:
				throw new LdapNativeException( $link_id );
				break;
		}
	}

	protected function _filter_ldap_result( $data, $performConversion )
	{
		$result			= array();
		$cleanResult	= array();

		unset( $data['count'] );

		foreach ( $data as $property => $value )
		{
			// There might be numeric keys, indicating the name of the attribute - just skip them...
			if ( is_numeric( $property ) )	continue;
			if ( is_array( $value ) ) 		unset( $value['count'] );

			// Some ldap servers ( i.e. AD ) may split too large attributes into smaller
			// attributes with a special notation in the attribute's name ( i.e. "member;0-4999" ).
			// Let's join those together.

			// Search for ';' in $property, returning string BEFORE needle
			$realProperty = strstr( $property, ';', true );
			if ( $realProperty )
			{
				if ( array_key_exists( $realProperty, $cleanResult ) )
				{
					// If that attribute already exists, join the two together
					$cleanResult[$realProperty] = array_merge( $cleanResult[$realProperty], $value );
				}
				else $cleanResult[$realProperty] = $value;
			}
			// It is a normal ( non-split ) attribute
			else $cleanResult[$property] = $value;
		}

		// Create the Attribute objects
		foreach ( $cleanResult as $attribute => $value )
		{
			$result[$attribute] = new Attribute( $attribute, $value, $this, $performConversion );
		}

		// Return the clean data
		return $result;
	}


	// ArrayAccess interface implementation

	/**
	 * @internal
	 */
	public function offsetSet( $offset, $value )
	{
		// Ony Attribute objects can be added to these types of objects
		if ( ! $value instanceof Attribute )
		{
			throw new IncorrectParameterException( 'You can only add instances of Attribute as an attribute of Object' );
		}

		// Typecast to string to get the attribute's name
		if ( is_null( $offset ) OR is_int( $offset ) ) $offset = (string)$value;
		$this->set( $offset, $value );	// Set the value!
	}

	/**
	 * @internal
	 */
	public function offsetExists( $offset )
	{
		return isset( $this->data[$offset] );
	}

	/**
	 * @internal
	 */
	public function offsetUnset( $offset )
	{
		$this->remove( $offset );
	}

	/**
	 * @internal
	 */
	public function offsetGet( $offset )
	{
		return $this->get( $offset );
	}


	// Iterator interface implementation

	/**
	 * @internal
	 */
	public function rewind()
	{
		$this->iteratorPosition = 0;
		$this->iteratorKeys = array_keys( $this->data );
	}

	/**
	 * @internal
	 */
	public function current()
	{
		$key = $this->iteratorKeys[$this->iteratorPosition];
		return $this->data[$key];
	}

	/**
	 * @internal
	 */
	public function key()
	{
		return $this->iteratorKeys[$this->iteratorPosition];
	}

	/**
	 * @internal
	 */
	public function next()
	{
		$this->iteratorPosition++;
	}

	/**
	 * @internal
	 */
	public function valid()
	{
		return isset( $this->iteratorKeys[$this->iteratorPosition] );
	}


	// JsonSerializable interface implementation

	/**
	 * @internal
	 */
	public function jsonSerialize()
	{
		$data = $this->data;
		$data['dn'] = $this->dn;

		return $data;
	}


	// Magic methods for magical functionality!

	/**
	 * Get the textual identifier of the object
	 *
	 * If the distinguished name is known, it will be returned.
	 * Otherwise, the value of the RDN ( relative distinguished name )
	 * is returned if present on the object. If not, the name of the class
	 * is returned.
	 *
	 * @return		string		Either a DN of the object, the value of the RDN attribute ( if present ) or class name, respectively
	 */
	public function __toString()
	{
		if ( isset( $this->dn ) ) return $this->dn;
		if ( isset( $this->data[$this->rdnAttId] ) ) return $this->data[$this->rdnAttId];

		return get_called_class();
	}

	/**
	 * @internal
	 */
	public function __get( $attribute )
	{
		return $this->get( $attribute );
	}

	/**
	 * @internal
	 */
	public function __isset( $attribute )
	{
		return isset( $this->data[$attribute] );
	}

	/**
	 * @internal
	 */
	public function __call( $attribute, $args )
	{
		if ( ! isset( $args[0] ) ) $args[0] = null;

		return $this->$attribute->value( $args[0] );
	}
}