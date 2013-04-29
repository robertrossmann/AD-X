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
use ADX\Enums\Syntax;

/**
 * Provides conversion functionality between ldap and php datatypes
 *
 * All the conversion functionality is defined here. This class is used
 * internally to convert data automatically to php as it is received from
 * ldap server and also to convert data to ldap when data is being written
 * to ldap server.
 */
class Converter
{
	// No instances allowed
	final private function __construct() {}


	public static function to_ldap( Attribute $attribute, $values )
	{
		return static::_convert( 'u_to_l', $attribute, $values );
	}

	public static function from_ldap( Attribute $attribute, $values )
	{
		return static::_convert( 'l_to_u', $attribute, $values );
	}


	protected static function _convert( $direction, Attribute $attribute, $values )
	{
		// Load the schema information for the current attribute
		$schema		= Schema::get( "$attribute" );
		$ats		= $schema['attributesyntax'][0];
		$oms		= $schema['omsyntax'][0];

		$converted	= array();
		$method		= null;
		$class		= get_called_class();

		// Based on syntax, choose the corresponding syntax conversion method
		switch ( $ats )
		{
			case Syntax::Binary:
				$method = 'binary';
				break;

			case Syntax::Boolean:
				$method = 'bool';
				break;

			case Syntax::DnString:
				$method = 'object';
				break;

			case Syntax::Integer:
			case Syntax::LargeInt:
				// Conversion not needed
				break;

			case Syntax::UnicodeString:
			case Syntax::TeletexString:
			case Syntax::PrintableString:
			case Syntax::NumericString:
				// Conversion not needed
				break;

			case Syntax::Time:
				switch ( $oms )
				{
					case '23':	// UTC Time format
						$method = 'utctime';
						break;

					case '24':	// Generalised Time format
						$method = 'generalisedtime';
						break;
				}
				break;
		}

		// Define some syntax overrides based on attribute names ( a hit here will replace
		// the result from the previous switch )
		switch ( "$attribute" )
		{
			case 'currenttime':					// Not in schema, present on rootDSE
				$method = 'generalisedtime';
				break;

			case 'issynchronized':				// Not in schema, present on rootDSE
			case 'isglobalcatalogready':		// Not in schema, present on rootDSE
				$method = 'bool';
				break;

			case 'unicodepwd':					// A password always needs special treatment
				$method = 'unicodepwd';
				break;

			// These attributes have LargeInt as syntax, but their meaning is different ( they represent a time )
			case 'pwdlastset':
			case 'accountexpires':
			case 'lastlogon':
			case 'lockouttime':
				$method = 'timestamp';
				break;
		}

		// If a conversion method has been found, call it for all values in $value
		$method = '_'.$direction.'_'.$method;

		if ( is_callable( [$class, $method]) )
		{
			foreach ( $values as $value ) $converted[] = call_user_func( [ $class, $method], $value );
		}
		else $converted = $values;	// No conversion has been found - use the raw data

		// Aaand we are done!
		return $converted;
	}

	// Conversion functions are defined below

	protected static function _u_to_l_timestamp( $timestamp )
	{
		if ( $timestamp === 0 ) return 0;

		return ( floor( $timestamp / 10000000 ) - 11644473600 );
	}

	protected static function _l_to_u_timestamp( $timestamp )
	{
		// The second part of the conditiona takes care of some crazy behaviour
		// ( documented, though ) of accountExpires, which can be zero
		// or 0x7FFFFFFFFFFFFFFF when the account is set to never expire.
		// Hopefully, this will not have any side effects...
		if ( $timestamp === 0 || $timestamp === hexdec('0x7FFFFFFFFFFFFFFF') ) return 0;

		return ( ( $timestamp * 10000000 ) + 11644473600 );
	}

	protected static function _u_to_l_bool( $bool )
	{
		if ( $bool === true )	return 'TRUE';
		if ( $bool === false )	return 'FALSE';
	}

	protected static function _l_to_u_bool( $bool )
	{
		if ( strtolower( $bool ) === 'true' )	return true;
		if ( strtolower( $bool ) === 'false' )	return false;
	}

	protected static function _u_to_l_utctime( $time )
	{
		// Example: 20130327203157.0Z
		return date( "YmdHis", $time ).'.0Z';
	}

	protected static function _l_to_u_utctime( $time )
	{
		// Example imput: 20130327203157.0Z
		// Strip the timezone offset by exploding and pass the first part into DateTime for parsing
		// Timezone is hardcoded as for now, sorry...
		$time = new \DateTime( explode( '.', $time )[0], new \DateTimeZone( 'UTC' ) );

		return $time->getTimestamp();
	}

	protected static function _u_to_l_generalisedtime( $time )
	{
		// Example: 20130327203157.0Z
		return date( "YmdHis", $time ).'.0Z';
	}

	protected static function _l_to_u_generalisedtime( $time )
	{
		// Example imput: 20130327203157.0Z
		// Strip the timezone offset by exploding and pass the first part into DateTime for parsing
		// Timezone is hardcoded as for now, sorry...
		$time = new \DateTime( explode( '.', $time )[0], new \DateTimeZone( 'UTC' ) );

		return $time->getTimestamp();
	}

	protected static function _l_to_u_binary( $data )
	{
		return base64_encode( $data );
	}

	protected static function _u_to_l_object( $object )
	{
		if ( $object instanceof Object && ! $object->dn )	throw new InvalidOperationException( "The object $object is not stored on server - please save your object first, then retry the action" );

		return ( $object instanceof object ) ? $object->dn : (string)$object;
	}


	// Special cases

	protected static function _u_to_l_unicodepwd( $password )
	{
		$password	= "\"$password\"";	// Enclose the password in double quotes

		$length		= strlen( $password );
		$pwd		= '';

		for ( $i = 0; $i < $length; $i++ )
		{
			$pwd .= "{$password{$i}}\000";	// Pad every character with a NULL value ( null-padded string )
		}

		return $pwd;
	}
}