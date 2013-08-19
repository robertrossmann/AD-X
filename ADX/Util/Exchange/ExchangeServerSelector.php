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
