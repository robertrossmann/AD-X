# Changelog

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
