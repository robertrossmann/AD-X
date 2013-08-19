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
 * A helper class returned upon retrieving changes from the directory server
 *
 * Instances of this class represent a current state of a search query for
 * which you would later like to retrieve changes. You should always
 * serialize these instances and store them for later use in order for the
 * change tracking to work.
 *
 * @property-read		Result		$result			The data returned by the current search ( not set on unserialised instances )
 * @property-read		string		$filter			The filter used for the search
 * @property-read		array		$attributes		Attributes that you requested to be loaded from server for the returned objects
 * @property-read		string		$server			The server name that this search query was run on
 *
 * @see		{@link Task::changes()}		Pull changes from Active Directory for a given query
 */
class Delta
{
	protected $result		= null;	// The Result object containing the changed objects in an instance of the Result class
	protected $filter		= '';	// The filter used for the diff
	protected $attributes	= [];	// The attributes that the objects in the diff should have
	protected $server		= '';	// The directory server this delta was pulled from
	protected $cookie		= '';	// The highestCommittedUsn value of the server


	/**
	 * Create a new instance of the Delta class
	 *
	 * @param		Result		The data returned by the current search
	 * @param		string		The filter used for the search
	 * @param		array		Attributes that are loaded from the server for the returned objects
	 * @param		string		The server name that this search query was run on
	 * @param		mixed		A piece of data that keeps track of the state of changes
	 *
	 * @internal
	 */
	public function __construct( Result $result, $filter, $attributes, $server, $cookie )
	{
		$this->result		= $result;
		$this->filter		= $filter;
		$this->attributes	= $attributes;
		$this->server		= $server;
		$this->cookie		= $cookie;
	}

	/**
	 * Mapper to provide read-only access to protected properties
	 *
	 * @internal
	 */
	public function __get( $property )
	{
		if ( ! isset( $this->$property ) || $property == 'cookie' )
		{
			$trace = debug_backtrace();
			trigger_error(
				'Undefined read-only property: ' . $property .
				' in ' . $trace[0]['file'] .
				' on line ' . $trace[0]['line'],
				E_USER_NOTICE );

			return null;
		}

		return $this->$property;
	}

	/**
	 * Clean up the object a bit before serialisation
	 *
	 * Just get rid of any resultsets present on the instance -
	 * there's no reason to have all that data serialised
	 *
	 * @internal
	 */
	public function __sleep()
	{
		return [
			'filter',
			'attributes',
			'server',
			'cookie',
		];
	}
}
