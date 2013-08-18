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


namespace ADX\Util;

use ADX\Enums;
use ADX\Core\Link;
use ADX\Core\Result;
use ADX\Core\Task;
use ADX\Core\Query as q;

/**
 * Abstract class to provide unified behaviour for classes that list ( select ) specific items from Active Directory
 *
 * Subclass the Selector to create a simple, easy to use search task for your code.
 * While subclassing, you only need to provide the operation to be performed
 * ( scope subtree by default ) and the ldap filter to be executed. Then,
 * in your code, you only need to instantiate your Selector and call {@link self::all()} on it
 * to get all the objects that match the search query. You can also perform additional processing
 * by implementing the {@link self::_process_result()} method.
 */
abstract class Selector
{
	// The following static properties must have a value in order for the subclass to work
	protected static $operation		= Enums\Operation::OpSearch;		// Perform search operation by default
	protected static $filter		= null;								// A filter that selects the objects to be returned

	// The following static properties are optional
	protected static $base			= null;								// A search base to be used
	protected static $attributes	= null;								// These attributes will be present on the returned objects
	protected static $paged_search	= false;							// Whether to do a paged search or not
	protected static $sizelimit		= 1000;								// How many objects should one page contain when doing paged search?

	protected $link;													// An instance of Link to operate on
	protected $query;													// The final search filter to be executed ( includes dynamic segment if provided )
	protected $result;													// The Result object will be stored here after successful retrieval from directory


	/**
	 * Create a new instance of the Selector class
	 *
	 * @param		ADX\Core\Link		The Link to a directory server to operate on
	 */
	public function __construct( Link $link )
	{
		$this->link = $link;
	}

	/**
	 * Get all objects that match the Selector
	 *
	 * @return		ADX\Core\Result|mixed		The Result containing all the matched objects or
	 * 											whatever the {@link self::_process_result()} returns
	 */
	public function all()
	{
		// Set the query to default value
		if ( $this->query !== static::$filter )
		{
			$this->query = static::$filter;
			$this->result = null;
		}

		return $this->_lookup();
	}

	/**
	 * Further customise the Selector by providing additional filter
	 *
	 * This is very useful if you want to write a generic Selector for all users,
	 * but sometimes you only need to get disabled users. So, instead of writing
	 * a new Selector for that purpose, you can use the `where()` method to refine
	 * the search results.
	 *
	 * @param		string						A valid ldap filter. This will be added to the main filter using '&' logic
	 *
	 * @return		ADX\Core\Result|mixed		The Result containing all the matched objects or
	 * 											whatever the {@link self::_process_result()} returns
	 */
	public function where( $filter )
	{
		// Build the search filter
		$query = q::a( static::$filter, $filter );

		// If we already have data for this query, no need to load it from server again
		if ( $this->query === $query ) return $this->_lookup();

		// Remove the previous resultset and execute the query again
		$this->result	= null;
		$this->query	= $query;

		return $this->_lookup();
	}


	/**
	 * Process the resultset in any way necessary
	 *
	 * Implement this method to provide additional processing logic after the result
	 * has been retrieved from the server.
	 *
	 * @param		Result		The Result, as retrieved from the directory server
	 *
	 * @return		mixed		The processed data
	 */
	protected function _process_result( Result $result )
	{
		$this->result = $result;

		return $this->result;
	}

	protected function _lookup()
	{
		// Do we already have the result?
		if ( $this->result instanceof Result ) return $this->result;

		// Get the data from the directory server
		$task = new Task( static::$operation, $this->link );
		$task->filter( $this->query );

		static::$attributes		&& $task->attributes( static::$attributes );
		static::$base			&& $task->base( static::$base );

		$result = static::$paged_search ? $task->run_paged( static::$sizelimit ) : $task->run();

		// Process the result and return the data!
		return $this->_process_result( $result );
	}
}
