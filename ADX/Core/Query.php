<?php

/**
 * AD-X
 *
 * Licensed under the BSD (3-Clause) license
 * For full copyright and license information, please see the LICENSE file
 *
 * @copyright		2012-2013 Robert Rossmann
 * @author			Robert Rossmann <rr.rossmann@me.com>
 * @link			https://github.com/Alaneor/AD-X
 * @license			http://choosealicense.com/licenses/bsd-3-clause		BSD (3-Clause) License
 */


namespace ADX\Core;

/**
 * This query builder provides a means of creating ldap search filters in a simple way
 *
 * Use this class to build your ldap search filter. Current support is implemented
 * for the AND( & ), OR( | ) and NOT( ! ) operators.
 *
 * Usage of this class is completely optional - you can either use it to build
 * your filter or supply your own search filter as a regular string.
 *
 * <h2>Example:</h2>
 * <code>
 * use ADX\Core\Query;
 *
 * // The simplest filter - all objects with email address present:
 * $filter = Query::a(
 * 	[ 'mail' => '*' ]
 * );
 * // (&(mail=*))
 *
 * // Limit the objects to only user accounts with email address present:
 * $filter = Query::a([
 * 	'objectclass'	=> 'user',
 * 	'mail'		=> '*'
 * ]);
 * // (&(objectclass=user)(mail=*))
 *
 * // All users that do NOT have the email address set:
 * // ( notice that we pass multiple parameters to the
 * // Query::a() method - you can pass as many as you like -
 * // as long as they are keyed arrays or strings )
 * $filter = Query::a(
 * 	['objectclass' => 'user'],
 * 	Query::n([ 'mail' => '*' ])	// We pass multiple parameters here to the Query::a() method
 * );
 * // (&(objectclass=user)(!(mail=*)))
 * </code>
 */
class Query
{
	protected static $escapeMap = [
		'\\'	=> '\5c',
		'/'		=> '\2f',
		'('		=> '\28',
		')'		=> '\29',
	//	'*'		=> '\2a', // Not escaping the asterisk because its primary use is to denote a wildcard; escape it yourself if you have to
	];

	/**
	 * Build a logical AND filter - (&(attribute=value))
	 *
	 * @param	array|string	$data,...	Unlimited number of hash arrays or strings to be used
	 *
	 * @return	string						The generated ldap filter
	 */
	public static function a()
	{
		$args = func_get_args();	// Get all arguments passed to the method

		return static::_parse( $args, '&' );
	}

	/**
	 * Build a logical OR filter - (|(attribute=value))
	 *
	 * @param	array|string	$data,...	Unlimited number of hash arrays or strings to be used
	 *
	 * @return	string						The generated ldap filter
	 */
	public static function o()
	{
		$args = func_get_args();	// Get all arguments passed to the method

		return static::_parse( $args, '|' );
	}

	/**
	 * Build a logical NOT filter - (|(attribute=value))
	 *
	 * @param	array|string	$data,...	Unlimited number of hash arrays or strings to be used
	 *
	 * @return	string						The generated ldap filter
	 */
	public static function n()
	{
		$args = func_get_args();	// Get all arguments passed to the method

		return static::_parse( $args, '!' );
	}

	/**
	 * @todo	Implement the bitwise IS filter creation
	 * @internal
	 */
	public static function is()
	{
	}

	/**
	 * @todo	Implement the bitwise IS filter creation
	 * @internal
	 */
	public static function has()
	{
	}

	/**
	 * Loop through the arguments and generate a proper ldap filter using the provided operator
	 *
	 * @param	array	Array containing the attribute => value mappings, or a
	 * 					string with already generated ldap filter
	 *
	 * @return	string	The generated ldap filter
	 */
	protected static function _parse( $args, $operator )
	{
		$filter = '';

		foreach ( $args as $argument )
		{
			if ( is_array( $argument ) )
			{
				$result = array_map('static::_stringify', array_keys( $argument ), $argument );

				$filter .= implode( '', $result );
			}
			else $filter .= $argument;
		}

		return "($operator$filter)";
	}

	/**
	 * Encapsulate the key and value pair in a proper ldap filter string
	 *
	 * @param	string		The ldap property to be filtered for
	 * @param	string		The value to search for
	 * @param	string		Optional logic to be used ( Bitwise or Extended match filters )
	 *
	 * @return	string		The formatted ldap filter
	 */
	protected static function _stringify( $key, $value, $logic = null )
	{
		return "($key" . ( $logic ? ":$logic:" : "" ) . "=" . static::_escape( $value ) . ")";
	}

	/**
	 * Replace all characters that are not allowed in a ldap query's value with their escaped equivalents
	 *
	 * @param		string		The value to be escaped
	 *
	 * @return		string		The escaped value
	 */
	protected static function _escape( $value )
	{
		$keys	= array_keys( static::$escapeMap );
		$values	= array_values( static::$escapeMap );

		$value	= str_ireplace( $keys, $values, $value );

		return $value;
	}
}
