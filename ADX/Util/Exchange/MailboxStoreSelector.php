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
use ADX\Core\Query as q;

/**
 * Find all mailbox stores defined in the current environment, optionally limited to a specific server ( defined per its DN )
 */
class MailboxStoreSelector extends Selector
{
	protected static $operation		= Enums\Operation::OpSearch;
	protected static $filter		= '(objectCategory=msExchPrivateMDB)';

	protected static $base			= null;
	protected static $attributes	= ['cn', 'msExchHomePublicMDB', 'msExchOwningServer', 'msExchUseOAB', 'msExchVersion'];
	protected static $paged_search	= true;
	protected static $sizelimit		= 1000;


	/**
	 * Create a new instance of the MailboxStoreSelector class
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
		$serverFilter = $exchangeServer !== null ? q::a( ['msExchOwningServer' => "$exchangeServer"] ) : null;

		return $this->where( $serverFilter );
	}

	/**
	 * Randomly pick one mailbox store from all available stores
	 *
	 * @return		Object		A single matched object from the original resultset
	 */
	public function pick()
	{
		$result = $this->result instanceof \ADX\Core\Result ? $this->result : $this->all();

		$item = array_rand( $result->to_array() );	// Randomly pick one item from the array

		return $result[$item];
	}
}
