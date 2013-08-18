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
 * Contains logic for storing and manipulating the value(s) of a particular property of an {@link Object}.
 *
 * @property-read	bool	$isResolvable	If the attribute contains distinguished name(s),
 * 											it is considered to be resolvable ( the DNs can be converted to Objects )
 */
class Attribute implements \Iterator, \ArrayAccess, \Countable, \JsonSerializable
{
	use	Jsonizer;

	protected $adxObject;					// The object this attribute belongs to ( Object )

	protected $attribute;					// The name of the ldap attribute
	protected $value			= array();	// The value or values of the ldap attribute, converted to php-compatible format where applicable
	protected $needsReindex		= false;	// Determines whether the values should be reindexed upon retrieval to keep the array keys continuous
	// Schema-loaded properties of the attribute
	protected $attributeSyntax;				// Determines the syntax of the current attribute
	protected $omSyntax;					// Determines the subsyntax of the current syntax
	protected $isSingleValued	= false;	// Tells if the attribute can have only a single value ( no by default )
	protected $isConstructed	= false;	// Tells if the attribute is constructed ( it cannot be modified directly )
	protected $isResolvable		= false;	// If the attribute contains a DN or an array of DNs, the attribute is considered to be resolvable

	//Iterator interface properties
	protected $iteratorPosition	= 0;


	/**
	 * Create a new instance of an attribute
	 *
	 * You should explicitly create new objects of this class only rarely - usually
	 * all you need to do is to set the property on the object, a new instance of the
	 * Attribute class will be created for you automatically.
	 *
	 * @param		string			The ldap display name of the attribute to be created
	 * @param		mixed|array		The initial value(s) this attribute should have
	 * @param		Object			An instance of {@link Object} this attribute belongs to
	 */
	public function __construct( $attribute, $values = array(), Object $object = null )
	{
		$args = func_get_args();
		$shouldConvert = isset( $args[3] ) ? $args[3] : false;	// This parameter is hidden

		$this->attribute = strtolower( $attribute );

		// Check for presence of the schema definition for the current attribute
		$schema = Schema::get( $attribute );

		if ( $schema )
		{
			$this->attributeSyntax	= $schema['attributesyntax'][0];
			$this->omSyntax			= $schema['omsyntax'][0];
			$this->isSingleValued	= isset( $schema['issinglevalued'] )	? ( $schema['issinglevalued'][0] == 'TRUE' ) : false;	// I have to do manual conversion here
			$this->isConstructed	= isset( $schema['systemflags'] )		? (bool)( $schema['systemflags'][0] & Enums\SystemFlags::AttrIsConstructed ) : false;
			$this->isResolvable		= $this->attributeSyntax === Enums\Syntax::DnString;
		}

		// Ensure we have the values in an array
		$values = (array)$values;

		// Convert the value to a valid php value ( bool, timestamp conversions etc. )
		if ( $shouldConvert ) $values = Converter::from_ldap( $this, $values );

		// Add new value or values to the attribute
		foreach ( $values as $value ) $this->_set_value( $value, null, true );

		// Set the attribute's owner
		if ( $object ) $this->belongs_to( $object );
	}

	/**
	 * Get the ldap display name of the attribute
	 *
	 * @return		string		The display name of this attribute
	 */
	public function attribute()
	{
		return $this->attribute;
	}

	/**
	 * Get the value of the attribute, optionally at a specified index
	 *
	 * @param		integer			The optional index of the value
	 *
	 * @return		array|mixed		The array with all values ( if no index was provided ) or the value at the specified index
	 */
	public function value( $index = null )
	{
		// Fix numeric indexes if some values have been removed in a multi-valued attribute
		$this->_reindex();

		// Special case - if negative index is specified, it returns data from the end of the Object
		if ( $index < abs( $index ) ) return $this->value( $this->count() - abs( $index ) );

		if ( isset( $this->value[$index] ) ) return $this->value[$index];	// The value at specified index exists - return it
		if ( $index !== null && $this->count() === 0 ) return null;			// The value at specified index does NOT exist - return NULL
		return $this->value;												// No index specified - return everything
	}

	/**
	 * Returns the attribute's data in an ldap-compatible format
	 *
	 * This method is used internally to get the attribute's data
	 * in a format that is suitable for storage in an ldap database.
	 *
	 * @return		array	The array containing all the values
	 */
	public function ldap_data()
	{
		return Converter::to_ldap( $this, $this->value() );
	}

	/**
	 * Add a value to the attribute, preserving already existing values
	 *
	 * @param		mixed|array		The value(s) to be added to the attribute
	 *
	 * @return		self
	 */
	public function add( $values )
	{
		$args = func_get_args();
		$ignoreChanges = isset( $args[1] ) ? $args[1] : false;	// This parameter is hidden

		// Add new value or values to the attribute
		$values = (array)$values;
		$lowercase_values = array_map( 'strtolower', $this->value );

		foreach ( $values as $value )
		{
			// First, check if this object is a DnString and then check if we are trying to add a DN that
			// is already present in the attribute
			if ( $this->attributeSyntax == Enums\Syntax::DnString &&
				in_array( strtolower( $value ), $lowercase_values ) )
			{
				continue;	// Nothing to be done here - this DN is already there
			}

			$this->_set_value( $value, null, $ignoreChanges );
		}

		return $this;
	}

	/**
	 * Remove the specified value or the value at specified index from the attribute
	 *
	 * If you provide an index, the value at that index will be removed. If you provide
	 * any other datatype, that datatype will be converted to a string and will be looked up
	 * among the values ( which will also be converted to strings ).
	 *
	 * @param		integer|mixed		The index or other data that should be removed
	 *
	 * @return		self
	 */
	public function remove( $valueOrIndex )
	{
		// Make sure this attribute is not constructed
		if ( $this->isConstructed ) throw new InvalidOperationException();

		$index = false;

		// If we have a numeric index, then it's easy...
		if ( is_numeric( $valueOrIndex ) )
		{
			$index = $valueOrIndex;
		}
		else
		{
			// Other datatypes, however, require some special care... We could have instances of Object class,
			// some strings and whatnot.

			// Search for the specified value by typecasting it into string
			$valueOrIndex = strtolower( (string)$valueOrIndex );

			foreach ( $this->value as $i => $item )
			{
				if ( strtolower( (string)$item ) == $valueOrIndex )
				{
					$index = $i;
					break;
				}
			}
		}

		if ( $index !== false )
		{
			unset( $this->value[$index] );
			$this->needsReindex = true;
			$this->adxObject->_register_change( $this );
		}

		return $this;
	}

	/**
	 * Set the value(s) of the attribute, replacing any existing values
	 *
	 * @param		mixed		The value(s) to be set
	 *
	 * @return		self
	 */
	public function set( $value )
	{
	// Replace the value in attribute with the supplied new value

		return $this->clear()->add( $value );
	}

	/**
	 * Remove all values from the attribute
	 *
	 * @return		self
	 */
	public function clear()
	{
		// Make sure this attribute is not constructed
		if ( $this->isConstructed ) throw new InvalidOperationException();

		$this->value		= array();
		$this->needsReindex	= false;

		$this->adxObject->_register_change( $this );

		return $this;
	}

	/**
	 * Get the {@link Object} this attribute belongs to or set a new parent
	 *
	 * If you do not provide a parameter to this method it will return
	 * the current owner of this attribute ( instance of {@link Object} ).
	 * You can also provide a new instance of the Object class as a parameter
	 * to inform the attribute that it should belong to that Object instead.
	 *
	 * @param		Object		The new owner of this Attribute
	 *
	 * @return		self
	 */
	public function belongs_to( Object $object = null )
	{	// Return or set the object this attribute belongs to

		if ( ! $object ) return $this->adxObject ?: null;

		$this->adxObject = $object;

		return $this;
	}

	/**
	 * Get the number of values this attribute has
	 *
	 * This is an implementation of the {@link \Countable} interface.
	 *
	 * @return    integer		The number of values this attribute currently has
	 */
	public function count()
	{
		// Returns the number of values in the property

		return count( $this->value );
	}


	protected function _reindex()
	{
		// Fix non-continuous indexes if values have been unset or otherwise manipulated

		if ( $this->needsReindex ) $this->value = array_values( $this->value );

		return $this;
	}

	protected function _set_value( $value, $offset = null, $ignoreChanges = false )
	{
		// Make sure this attribute is not constructed
		if ( ! $ignoreChanges && $this->isConstructed ) throw new InvalidOperationException();

		// Make sure this attribute can have multiple values
		if ( $this->isSingleValued && $this->count() > 0 ) throw new OutOfRangeException( 'This attribute cannot have multiple values' );

		if ( is_null( $offset ) )
		{
			$this->value[] = $value;
		}
		else
		{
			$this->value[$offset] = $value;
		}

		// Register the change on parent object
		if ( ! $ignoreChanges ) $this->adxObject->_register_change( $this );

		return $this;
	}


	// ArrayAccess interface implementation

	/**
	 * @internal
	 */
	public function offsetSet( $offset, $value )
	{
		$this->_set_value( $value, $offset );
	}

	/**
	 * @internal
	 */
	public function offsetExists( $offset )
	{
		return isset( $this->value[$offset] );
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
		return $this->value[$offset] ?: null;
	}


	// Iterator interface implementation

	/**
	 * @internal
	 */
	public function rewind()
	{
		$this->iteratorPosition = 0;
	}

	/**
	 * @internal
	 */
	public function current()
	{
		return $this->value[$this->iteratorPosition];
	}

	/**
	 * @internal
	 */
	public function key()
	{
		return $this->iteratorPosition;
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
		return isset( $this->value[$this->iteratorPosition] );
	}


	// JsonSerializable interface implementation

	/**
	 * @internal
	 */
	public function jsonSerialize()
	{
		return $this->value();
	}


	// Magic methods for magical functionality!

	public function __toString()
	{
		return $this->attribute;
	}

	public function __get( $attribute )
	{
		switch ( $attribute )
		{
			// These protected attributes are disclosed publicly as read-only
			case 'isResolvable':
				return $this->$attribute;

			// Throw standard php notice for "undefined variables" ( non-disclosed vars )
			default:
				$trace = debug_backtrace();
				trigger_error(
					'Undefined magic property: ' . $name .
					' in ' . $trace[0]['file'] .
					' on line ' . $trace[0]['line'],
					E_USER_NOTICE);
				return null;
		}
	}

	public function __invoke( $index = null )
	{
		return $this->value( $index );
	}

	public function __clone()
	{
		$this->_reindex(); // Reindex the value keys if necessary

		// Remove the reference to parent object; it makes no sense for an object to have
		// two identical properties, so the new copy will have to be used for another object
		unset( $this->belongs_to );
	}
}
