# Changelog

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
