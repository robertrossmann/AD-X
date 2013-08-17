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


namespace ADX\Util\Exchange;

use ADX\Util\Selector;
use ADX\Enums;
use ADX\Core\Link;
use ADX\Core\Query as q;

/**
 * Find all message transfer agents defined in the current environment, optionally limited to a specific server ( defined per its DN )
 */
class TransferAgentSelector extends Selector
{
	protected static $operation		= Enums\Operation::OpSearch;
	protected static $filter		= '(objectClass=mTA)';

	protected static $base			= null;
	protected static $attributes	= ['cn'];
	protected static $paged_search	= true;
	protected static $sizelimit		= 1000;


	/**
	 * Create a new instance of the TransferAgentSelector class
	 *
	 * @param		Link				The Link to a directory server to operate on
	 */
	public function __construct( Link $link )
	{
		parent::__construct( $link );

		// Make sure we are searching for the mailbox databases in the Configuration Naming Context, otherwise
		// we might not find anything...
		static::$base = $this->link->rootDSE->configurationNamingContext(0);
	}

	/**
	 * Get all mailbox stores for a particular Exchange server
	 *
	 * @param		Object|string		The Exchange server for which to return mailbox stores
	 *
	 * @return		Result|mixed		The Result containing all the matched objects or whatever the {@link self::_process_result()} returns
	 */
	public function for_server( $exchangeServer = null )
	{
		$serverFilter = $exchangeServer !== null ? q::a( ['msExchResponsibleMTAServerBL' => "$exchangeServer"] ) : null;

		return $this->where( $serverFilter );
	}
}
