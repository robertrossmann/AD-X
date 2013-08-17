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


namespace ADX\Enums;

// Base class for all typedef enum imitations
class TypedefEnum
{
	final private function __construct() {}	// No instances allowed
}

// Define known ldap v3 & Active Directory errors/responses
class ServerResponse extends TypedefEnum
{
	const Success						= 0;
	const OperationsError				= 1;
	const ProtocolError					= 2;
	const TimelimitExceeded				= 3;
	const SizelimitExceeded				= 4;
	const CompareFalse					= 5;
	const CompareTrue					= 6;
	const AuthMethodNotSupported		= 7;
	const StrongAuthRequired			= 8;
	const Referral						= 10;
	const AdminlimitExceeded			= 11;
	const UnavailableCriticalExtension	= 12;
	const ConfidentialityRequired		= 13;
	const SaslBindInProgress			= 14;
	const NoSuchAttribute				= 16;
	const UndefinedType					= 17;
	const InappropriateMatching			= 18;
	const ConstraintViolation			= 19;
	const TypeOrValueExists				= 20;
	const InvalidSyntax					= 21;
	const NoSuchObject					= 32;
	const AliasProblem					= 33;
	const InvalidDnSyntax				= 34;
	const InappropriateAuth				= 48;
	const InvalidCredentials			= 49;
	const ErrorTooManyContextIds		= 49;
	const InsufficientAccess			= 50;
	const Busy							= 51;
	const Unavailable					= 52;
	const UnwillingToPerform			= 53;
	const LoopDetect					= 54;
	const NamingViolation				= 64;
	const ObjectClassViolation			= 65;
	const NotAllowedOnNonleaf			= 66;
	const NotAllowedOnRdn				= 67;
	const AlreadyExists					= 68;
	const NoObjectClassMods				= 69;
	const ResultsTooLarge				= 70;
	const AffectsMultipleDsas			= 71;
	const Other							= 80;
	// Active Directory-specific responses
	const UserNotFound					= 525;
	const NotPermittedToLogonAtThisTime	= 530;
	const RestrictedToSpecificMachines	= 531;
	const PasswordExpired				= 532;
	const AccountDisabled				= 533;
	const AccountExpired				= 701;
	const UserMustResetPassword			= 773;
}

// Server controls enumerator. For more info, see
// http://msdn.microsoft.com/en-us/library/cc223320.aspx
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

// Library-specific. This is used when configuring Task object to perform a specific lookup operation
class Operation extends TypedefEnum
{
	const OpSearch	= 'ldap_search';
	const OpList	= 'ldap_list';
	const OpRead	= 'ldap_read';
}

// This can be used to perform ldap search queries against binary attributes like useraccountcontrol
// NOTE - this seems to be MS-only feature. In case of future plans to add compatibility with OpenLDAP,
// it should be noted that this functionality is not available.
class BitwiseFilter extends TypedefEnum
{
	const B_And	= '1.2.840.113556.1.4.803';
	const B_Or	= '1.2.840.113556.1.4.804';
}

// Defines known attribute syntaxes in Active Directory schema ( but only those worth defining )
// See http://msdn.microsoft.com/en-us/library/windows/desktop/ms684419%28v=vs.85%29.aspx
// or http://msdn.microsoft.com/en-us/library/cc223177.aspx
class Syntax extends TypedefEnum
{
	const Binary			= '2.5.5.10';	// Binary data as usual - just raw ones and zeros
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

// Enumerator for the useraccountcontrol attribute
class UAC extends TypedefEnum
{
	const Script								= 1;
	const AccountDisable						= 2;
	const HomedirRequired						= 8;
	const Lockout								= 16;
	const PasswdNotreqd							= 32;
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

class GroupType extends TypedefEnum
{
	const GlobalGroup		= 2;
	const DomainLocalGroup	= 4;
	const LocalGroup		= 4;
	const UniversalGroup	= 8;
	const SecurityEnabled	= 2147483648;
}

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

// http://blogs.technet.com/b/benw/archive/2007/04/05/exchange-2007-and-recipient-type-details.aspx
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


namespace ADX\Core;

trait Jsonizer
{
	public function json()
	{
		// Used options ( in order of appearance ):
		// Do not escape unicode characters ( š, ,č ž, ú etc. )
		// Encode numeric strings as numbers
		return json_encode( $this, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK );
	}
}


// Base exception class for AD-X library
class Exception extends \Exception {}

// This exception is thrown when the php's ldap library emits
// error messages ( where taken care of in the code )
class LdapNativeException extends Exception
{
	public function __construct( $link_id )
	{
		parent::__construct();

		ldap_get_option( $link_id, LDAP_OPT_HOST_NAME, $domain );

		$domain			= explode( ':', $domain )[0]; // Get the server name and discard the port
		$this->code		= ldap_errno( $link_id );
		$this->message	= "$domain: ".ldap_error( $link_id );
	}
}

// This exception is thrown when trying to connect to server that does not support ldap v3 protocol
class UnsupportedPlatformException extends Exception
{
	protected $code		= 1001;
	protected $message	= 'This domain does not support ldap v3 protocol';
}

class IncorrectParameterException extends Exception
{
	protected $code		= 1002;
	protected $message	= 'The supplied value for the parameter is incorrect';
}

class OutOfRangeException extends Exception
{
	protected $code		= 1003;
	protected $message	= 'The supplied value is out of the range for the target attribute'; // TODO - maybe a better description?
}

// thrown when trying to modify a constructed attribute
class InvalidOperationException extends Exception
{
	protected $code		= 1004;
	protected $message	= 'This operation is not allowed or available on the current object';
}

// Thrown if user's credentials are incorrect or if the account is not usable
// for any reason ( locked, disabled, expired etc. )
class InvalidCredentialsException extends LdapNativeException {}

// Thrown when trying to set or modify an attribute that is not defined in ldap schema
class UndefinedTypeException extends LdapNativeException {}

class InsufficientAccessException extends LdapNativeException {}

// Thrown when the ldap server is not reachable due to network issues
class ServerUnreachableException extends LdapNativeException {}
