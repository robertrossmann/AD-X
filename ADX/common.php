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


namespace ADX\Enums;

// Define path constants ( these constants are defined under global namespace )
! defined( 'ADX_DS' )			&& define( 'ADX_DS', DIRECTORY_SEPARATOR );					// A little shortcut...
! defined( 'ADX_ROOT_PATH' )	&& define( 'ADX_ROOT_PATH', dirname( __DIR__ ) . ADX_DS );	// Path to the main folder


/**
 * Base class for all enumerations
 */
class TypedefEnum
{
	final private function __construct() {}	// No instances allowed
}

/**
 * Server controls enumerator
 *
 * @see		<a href="http://msdn.microsoft.com/en-us/library/cc223320.aspx">LDAP Extended Controls</a>
 */
class ServerControl extends TypedefEnum
{
	const PagedResults			= '1.2.840.113556.1.4.319';		// Pagination control
	const ShowDeleted			= '1.2.840.113556.1.4.417';		// Deleted objects are visible in the search query
	const LazyCommit			= '1.2.840.113556.1.4.619';		// Report success as soon as data is in DC's memory (do not wait for disk)
	const PermissiveModify		= '1.2.840.113556.1.4.1413';	// Allow changing attribute to the same value without errors
	const TreeDelete			= '1.2.840.113556.1.4.805';		// ( AD only ) Allows deleting non-leaf objects ( use with caution )
	const CrossDomMoveTarget	= '1.2.840.113556.1.4.521';		// ( AD only ) Move object across domains
	const ShowRecycled			= '1.2.840.113556.1.4.2064';	// ( AD only ) Include recycled objects in search results ( more verbose than ShowDeleted )
}

/**
 * Defines three standard lookup operations to be used with {@link Task}
 */
class Operation extends TypedefEnum
{
	const OpSearch	= 'ldap_search';
	const OpList	= 'ldap_list';
	const OpRead	= 'ldap_read';
}

/**
 * Binary search operators to search for flags in enumerations
 *
 * This can be used to perform ldap search queries against binary attributes like userAccountControl.
 * <br>
 *
 * <p class="alert">This seems to be MS-only feature. In case of future plans to add compatibility with OpenLDAP,
 * it should be noted that this functionality is not available.</p>
 */
class BitwiseFilter extends TypedefEnum
{
	const B_And	= '1.2.840.113556.1.4.803';
	const B_Or	= '1.2.840.113556.1.4.804';
}

/**
 * Defines known attribute syntaxes in Active Directory schema ( but only those worth defining )
 *
 * @see		<a href="http://msdn.microsoft.com/en-us/library/windows/desktop/ms684419%28v=vs.85%29.aspx">Syntaxes</a>
 * @see		<a href="http://msdn.microsoft.com/en-us/library/cc223177.aspx">LDAP Representations</a>
 */
class Syntax extends TypedefEnum
{
	const Binary			= '2.5.5.10';	// Binary data as usual - just raw ones and zeros
	const Sid				= '2.5.5.17';	// A binary-encoded SID string
	const Boolean			= '2.5.5.8';	// Boolean as we know it, but represented as a string ( "TRUE" / "FALSE" )
	const Integer			= '2.5.5.9';	// 32-bit integer
	const LargeInt			= '2.5.5.16';	// 64-bit integer
	const DnString			= '2.5.5.1';	// Distinguished name of another object in the Directory
	const UnicodeString		= '2.5.5.12';	// Unicode string
	const TeletexString		= '2.5.5.4';	// Case-insensitive string with teletex character set ( dunno, don't ask )
	const PrintableString	= '2.5.5.5';	// Basically just another string
	const NumericString		= '2.5.5.6';	// String that contains digits ( yet another string )
	const Time				= '2.5.5.11';	// Timestamp. There can be two forms, however! That's where OMSyntax will become handy
	// const CaseString		= '2.5.5.3';	// Case-sensitive string ( not used in AD by default )

}

/**
 * Enumerator for the infamous userAccountControl attribute
 *
 * @see		<a href="http://support.microsoft.com/kb/305144">
 *       	MSDN - How to use the UserAccountControl flags to manipulate user account properties</a>
 */
class UAC extends TypedefEnum
{
	const Script								= 1;
	const AccountDisable						= 2;
	const HomedirRequired						= 8;
	const Lockout								= 16;
	const PasswdNotReqd							= 32;
	const PasswdCantChange						= 64;
	const EncryptedTextPasswordAllowed			= 128;
	const TempDuplicateAccount					= 256;
	const NormalAccount							= 512;
	const InterdomainTrustAccount				= 2048;
	const WorkstationTrustAccount				= 4096;
	const ServerTrustAccount					= 8192;
	const DontExpirePasswd						= 65536;
	const MnsLogonAccount						= 131072;
	const SmartcardRequired						= 262144;
	const TrustedForDelegation					= 524288;
	const NotDelegated							= 1048576;
	const UseDesKeyOnly							= 2097152;
	const DontRequirePreauth					= 4194304;
	const PasswordExpired						= 8388608;
	const TrustedToAuthenticateForDelegation	= 16777216;
}

/**
 * Enumerator for the GroupType attribute
 */
class GroupType extends TypedefEnum
{
	const GlobalGroup		= 2;
	const DomainLocalGroup	= 4;
	const LocalGroup		= 4;
	const UniversalGroup	= 8;
	const SecurityEnabled	= 2147483648;
}

/**
 * Enumerator for the systemFlags attribute
 */
class SystemFlags extends TypedefEnum
{
	const DisallowDelete			= 2147483648;
	const ConfigAllowRename			= 1073741824;
	const ConfigAllowMove			= 536870912;
	const ConfigAllowLimitedMove	= 268435456;
	const DomainDisallowRename		= 134217728;
	const DomainDisallowMove		= 67108864;
	const CrNtdsNc					= 1;
	const CrNtdsDomain				= 2;
	const AttrNotReplicated			= 1;
	const AttrIsConstructed			= 4;

	// This one is not part of Active Directory documentation, but is mentioned in the Remarks section here:
	// http://msdn.microsoft.com/en-us/library/windows/desktop/aa772297%28v=vs.85%29.aspx
	const PartOfBaseSchema			= 16;
}

/**
 * Defines known MS Exchange version numbers as seen in msExchVersion attribute
 */
class ExchangeVersion extends TypedefEnum
{
	const v2007	= 4535486012416;
	const v2010	= 44220983382016;
	const v2013	= 88218628259840;
}

/**
 * Defines known recpient types as per the RecipientTypeDetails attribute
 *
 * @see		http://blogs.technet.com/b/benw/archive/2007/04/05/exchange-2007-and-recipient-type-details.aspx
 */
class RecipientTypeDetails extends TypedefEnum
{
	const UserMailbox						= 1;
	const LinkedMailbox						= 2;
	const SharedMailbox						= 4;
	const LegacyMailbox						= 8;
	const RoomMailbox						= 16;
	const EquipmentMailbox					= 32;
	const MailContact						= 64;
	const MailUser							= 128;
	const MailUniversalDistributionGroup	= 256;
	const MailNonUniversalGroup				= 512;
	const MailUniversalSecurityGroup		= 1024;
	const DynamicDistributionGroup			= 2048;
	const PublicFolder						= 4096;
	const SystemAttendantMailbox			= 8192;
	const SystemMailbox						= 16384;
	const MailForestContact					= 32768;
	const User								= 65536;
	const Contact							= 131072;
	const UniversalDistributionGroup		= 262144;
	const UniversalSecurityGroup			= 524288;
	const NonUniversalGroup					= 1048576;
	const DisabledUser						= 2097152;
	const MicrosoftExchange					= 4194304;
}

/**
 * Defines known recpient display types as per the RecipientDisplayType attribute
 */
class RecipientDisplayType extends TypedefEnum
{
	const DistributionGroup			= 1;
	const RemoteMailUser			= 2;
	const DynamicDistributionGroup	= 3;
	const Organization				= 4;
	const PrivateDistributionList	= 5;
	const MailUser					= 6;
	const ConferenceRoomMailbox		= 7;
	const EquipmentMailbox			= 8;
	const ACLableMailboxUser		= 1073741824;
	const SecurityDistributionGroup	= 1073741833;
}

/**
 * Enumerator for some values that are statically hardcoded in MS Exchange
 */
class ExchangeCommon extends TypedefEnum
{
	const AdminGroupLDN		= '/o=TietoEnator/ou=Exchange Administrative Group (FYDIBOHF23SPDLT)';
	const DefaultPolicyGUID	= '{26491cfc-9e50-4857-861b-0cb8df22b5d7}';
}


namespace ADX\Core;

trait Jsonizer
{
	/**
	 * Convert the object's data into json string
	 *
	 * @param		bool		If true, the json string will also contain whitespace to improve readability
	 *
	 * @return		string		The json string
	 */
	public function json( $pretty = false )
	{

		// Used options ( in order of appearance ):
		// Do not escape unicode characters ( š, ,č ž, ú etc. )
		// Encode numeric strings as numbers
		$options = JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK;
		$pretty && $options = $options | JSON_PRETTY_PRINT;

		return json_encode( $this, $options );
	}
}


/**
 * Base exception class for AD-X library
 */
class Exception extends \Exception {}

/**
 * This exception ( or its more specific subclass ) is thrown when any of php's ldap functions triggers errors
 */
class LdapNativeException extends Exception
{
	public function __construct( \Ldap\Response $response )
	{
		parent::__construct();

		$this->code		= $response->code;
		$this->message	= $response->message;
	}
}

/**
 * Thrown when you provide an unexpected input to a method call
 */
class IncorrectParameterException extends Exception
{
	protected $code		= 1002;
	protected $message	= 'The supplied value for the parameter is incorrect';
}

/**
 * Thrown when the ldap attribute you are trying to modify cannot have multiple
 * values or the maximum amount of values has been already reached
 */
class OutOfRangeException extends Exception
{
	protected $code		= 1003;
	protected $message	= 'The supplied value is out of the range for the target attribute'; // TODO - maybe a better description?
}

/**
 * Thrown when trying to modify a constructed attribute
 */
class InvalidOperationException extends Exception
{
	protected $code		= 1004;
	protected $message	= 'This operation is not allowed or available on the current object';
}

/**
 * Thrown if user's credentials are incorrect or if the account is not usable
 * for any reason ( locked, disabled, expired etc. )
 */
class InvalidCredentialsException extends LdapNativeException {}

/**
 * Thrown when trying to set or modify an attribute that is not defined in ldap schema
 */
class UndefinedTypeException extends LdapNativeException {}

/**
 * Thrown when the account does not have enough privileges to perform the requested operation
 */
class InsufficientAccessException extends LdapNativeException {}

/**
 * Thrown when the ldap server is not reachable due to network issues
 */
class ServerUnreachableException extends LdapNativeException {}
