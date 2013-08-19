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
use ADX\Core\Object;
use ADX\Core\Query as q;

/**
 * Find all Exchange address lists in the current environment
 */
class AddressListSelector extends Selector
{
	protected static $operation		= Enums\Operation::OpSearch;
	protected static $filter		= '(objectCategory=addressBookContainer)';

	protected static $base			= null;
	protected static $attributes	= ['cn', 'purportedSearch'];
	protected static $paged_search	= true;
	protected static $sizelimit		= 1000;

	protected $gal;			// The default global address list will be stored here once found

	public function __construct( Link $link )
	{
		parent::__construct( $link );

		// Make sure we are searching for the Exchange Servers in the Configuration Naming Context, otherwise
		// we might not find anything...
		static::$base = $this->link->rootDSE->configurationNamingContext(0);
	}

	/**
	 * Get the default address list for the domain forest
	 *
	 * @return		Object		The default global address list
	 */
	public function defaultGAL()
	{
		if ( $this->gal instanceof Object ) return $this->gal;

		$task = new \ADX\Core\Task( Enums\Operation::OpSearch, $this->link );
		$gal = $task
				->base( static::$base )
				->filter( q::a( ['objectCategory' => 'msExchConfigurationContainer'] ) )
				->attributes( 'globalAddressList' )
				->run()
				->first();

		$this->gal = Object::read( $gal->globalAddressList(0), static::$attributes, $this->link );

		return $this->gal;
	}
}
