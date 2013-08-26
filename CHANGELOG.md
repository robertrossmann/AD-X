# Changelog

### Version 0.3.1 ( 2013-08-27 )

#### New features / changes

 - The `Link::rootDSE` is now only loaded on first property access or by calling `Link::rootDSE()`
 - Stop checking for *ldap v3* compatibility during Link instantiation - all AD implementations support *ldap v3*
 - The `Link::rootDSE()` no longer performs anonymous bind and behaviour has also been slightly changed - make sure to check API documentation (backwards-compatibility is not compromised)
 - When resetting ldap password, the password is expected to be encoded in character set defined in your php.ini - `ini_get( 'default_charset' );`

#### Fixes

 - Correct capitalisation of `ADX\Enums\UAC::PasswdNotReqd` in code after 0ac9135
 - Fix php notice when unsetting serialised `Link` instances
 - `Link` unserialisation was broken

### Version 0.3 ( 2013-08-25 )

The 0.3 is a major release aimed primarily at User management / Exchange support and at adding long-missing functionality. A lot of internal work has been done on the code and a fair amount of new features have been introduced.

Take a look at the changelog to see what's new.

#### New features / changes
 - AD-X is now licensed under [BSD-3 license](http://choosealicense.com/licenses/bsd-3-clause)
 - [User management](http://alaneor.github.io/AD-X/api/class-ADX.Classes.User.html) / Exchange management support
 - Improved API documentation
 - A new `ADX\Util\Selector` class to simplify lookup operations
 - `Attribute::reset()` : Allows you to reset the attribute to its original state at instantiation
 - `Object::move()` : Move objects across containers in a domain
 - The **CN** and **OU** attributes are now returned for all lookup operations by default ( when present )
 - `Object::bit_state()` : Easily check & manipulate bitfield attributes like **userAccountControl**
 - `Task::sizelimit()` : Set your own sizelimit per search query
 - Do not require **ext-openssl** since using SSL / TLS is optional
 - Ditch the internal autoloader in favour of **Composer**
 - Query builder now performs basic escaping on values provided in a search filter

#### Fixes

 - **msExchMailboxGuid** is now displayed properly
 - Capitalise `UAC::PasswdNotReqd` correctly ( was `PasswdNotreqd` )
 - Fixed `Object::create()` to actually work
 - Do not throw on non-erroneous, non-zero ldap responses ( like *Sizelimit exceeded* etc. )
 - Calling `Task::run_paged()` no longer permanently enables pagination on Task
 - Suppress php errors when doing lookup operations - error situations are handled via exceptions
 - Conversion of Object or its subclass into a string might have returned incorrect class name when both DN and rdnAttId were not present
 - Setting **pwdLastSet** to -1 ( "do not require password change" ) now behaves correctly

### Version 0.2.3 ( 2013-08-20 )

#### Fixes

 - Correct conversion of ldap timestamps, again... ( pwdLastSet, accountExpires etc. )
 - Correct logic introduced in 0585fcd ( adding non-DN values to an attribute got broken )
 - The schema cache files should be stored in lowercase filenames to ensure full compatibility with case-sensitive filesystems

### Version 0.2.2 ( 2013-08-02 )

#### Fixes

 - Fix accessing values from the end in Attribute::value() using negative indexes
 - The Attribute::remove() method was not removing items passed as values
 - Do not add the same distinguished name to a DN-syntax-based attribute more than once

### Version 0.2.1 ( 2013-07-31 )

#### New features / changes

 - Schema::isCached() returns the current state of the Schema cache

#### Fixes

 - Throw if the Schema cache is present and trying to work with Attribute not defined in Schema
 - Fix conversion of timestamp-like attributes ( pwdLastSet, accountExpires etc. )
 - Throw if Object::read does not receive a string as the first parameter

### Version 0.2 ( 2013-07-15 )

#### New features / changes

 - Implemented method for tracking changes in Active Directory ( Task::changes() )
 - *objectGuid* is now returned for all retrieved objects automatically
 - Include *highestCommitedUSN* in Link::rootDSE entry by default
 - Allow the **Converter** to accept attribute names as strings instead of requiring an instance of **Attribute** class
 - *ObjectGUID* is now displayed exactly as AD tools show it
 - Tweak the Task class' API to allow reading current Task configuration
 - Add **autoload** section to composer.json to allow automatic Composer-based class loading
 - Enable pagination automatically on Task::run_paged()
 - If you call a function on an **Object** and it does not exist explicitly, you will get an instance of **Attribute**
 - The **Attribute** class now has basic API documentation
 - **Attribute** can now be treated as function

#### Fixes

 - Fix issue with undefined variable in Schema::flush()
 - Validate presence of *isSingleValued* in the schema cache before using it
 - Fallback to *CN* as the **RdnAttId** if schema-based value is not present
 - Resolve problems with **Schema** caching / flushing / loading cached items
 - Schema: Fix permissions on newly created Schema folders ( was 644, now 755 )
 - Fix some **E_NOTICE** errors
 - Schema::build() now throws Exception if maximum referrals limit was hit
 - Object::remove() now does what it is supposed to do
