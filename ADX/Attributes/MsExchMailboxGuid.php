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

namespace ADX\Attributes;

/**
 * Overrides the msExchMailboxGuid attribute behaviour
 *
 * This class provides special functionality when converting the raw data into ldap-compatible value.
 * The problem is that ldap refuses to accept a hex string as an input for writing into this attribute -
 * it has to be converted to a binary string.
 */
class MsExchMailboxGuid extends \ADX\Core\Attribute
{
	/**
	 * Convert the raw ldap data into binary string instead of keeping it as hexadecimal value
	 *
	 * @return		array		The data in binary format
	 */
	public function ldap_data()
	{
		$data = parent::ldap_data();
		$data = str_ireplace( '\\', '', $data[0] );

		return [hex2bin( $data )];
	}
}
