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

/**
 * Find all Exchange servers in the current environment
 */
class ExchangeServerSelector extends Selector
{
	protected static $operation		= Enums\Operation::OpSearch;
	protected static $filter		= '(objectCategory=msExchExchangeServer)';

	protected static $base			= null;
	protected static $attributes	= ['cn', 'msExchServerSite', 'msExchVersion', 'networkAddress'];
	protected static $paged_search	= true;
	protected static $sizelimit		= 1000;


	public function __construct( Link $link )
	{
		parent::__construct( $link );

		// Make sure we are searching for the Exchange Servers in the Configuration Naming Context, otherwise
		// we might not find anything...
		static::$base = $this->link->rootDSE->configurationNamingContext(0);
	}
}
