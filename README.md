# AD-X :: Active Directory library for php

The **AD-X** library aims to provide an easy-to-use, object-oriented and powerful tool to work with Active Directory.

## Features

### The important ones
----
 - A nice, understandable, object-oriented API to work with Active Directory
 - **Automatic data conversion** - with this library, you won't encounter a value that has to be converted to something else before you can work with it in php ( like `unicodePwd` or `lastLogonTimestamp` ) - you simply use the values and the library will take care of the conversion
 - Support for paged results to get more objects than the server-enforced maximum objects per query
 - Support for referrals - useful if you operate on multiple domains in a forest
 - TLS or SSL encryption support
 - Directory Schema caching allows to perform checks, validations and provide guidance before you even attempt to modify anything on the server
 - Simple change tracking on your directory server

### The nice to have ones
----
 - *ldap v3* protocol support
 - Namespaced classes to prevent collisions
 - Exceptions-based error handling
 - Autoloading support via Composer
 - Object-to-json conversion for easy integration with your front-end
 - Optional simple ldap query builder

## Found an issue / have idea?

Please submit issues and ideas to the Github's [issue tracker](https://github.com/Alaneor/AD-X/issues).

Contributing rules will be provided in a future update - for now, please try to match the programming style currently used in the code when submitting pull requests.

## Installation

### Requirements

 - PHP 5.4.0 and newer with LDAP support ( [setup instructions](http://www.php.net/manual/en/ldap.installation.php) )
 - OpenSSL module for SSL / TLS connections ( [setup instructions](http://cz1.php.net/manual/en/openssl.installation.php) )

### Install:

#### Via Composer:

This will install the latest stable release:<br>
`composer require alaneor/ad-x:dev-master`
( visit [Packagist](https://packagist.org/packages/alaneor/ad-x) for list of all available versions )

### Post-installation ( optional, but **strongly recommended** ) :

#### Generate the schema cache:

Create a new php script with the following contents, providing relevant information like domain and your domain credentials:
```php
    include './vendor/autoload.php'; // Include Composer's autoloader

    $link = new ADX\Core\Link( 'company.com', 389 ); // Connect to server on default port
    $link->bind( 'username', 'password' ); // Authenticate to the server
    ADX\Core\Schema::build( $link ); // Build the local schema cache ( takes some time, go get a coffee )
```
A future release will include a wizard-like script to guide you through the process right after installation.

Depending on the stability of your Active Directory environment, you might need to re-build the schema cache occasionally, especially after upgrading to a newer Active Directory functional level or after installing new directory-related components, like MS Exchange, OCS Services or similar.

## Documentation

Documentation is currently available for the API only, but it includes a lot of examples and explanations.
I recommend that you start with the [Link](http://alaneor.github.io/AD-X/api/class-ADX.Core.Link.html) class, follow with [Task](http://alaneor.github.io/AD-X/api/class-ADX.Core.Task.html) and [Object](http://alaneor.github.io/AD-X/api/class-ADX.Core.Object.html) and finish with [Attribute](http://alaneor.github.io/AD-X/api/class-ADX.Core.Attribute.html) class - this will give you a good idea about basic usage.

### Read online: [API documentation](http://alaneor.github.io/AD-X/api)
### Generate the documentation locally:

1. Download the library
1. Install the dependencies using [Composer](http://getcomposer.org/):
```
composer install --dev
```
1. Generate the API documentation using [ApiGen](http://apigen.org/) ( installed by Composer ):
```
php ./vendor/bin/apigen.php
```
1. Generated documentation will be available at *./docs/www/index.html*


## Known limitations

 - The *ldap v2* protocol is not supported and never will be. Pull requests to provide backwards-compatibility will be refused.
 - The library has been built **specifically for Active Directory**. Compatibility with standards-based ldap servers like [OpenLDAP](http://openldap.org) is likely broken. I have no plans to address this at the moment but a future release might make such thing possible.
 - Unit tests are missing completely. I realise this might be a serious issue for future development and plan to address this with a future update. Any help with writing tests is much appreciated.

## Future to-dos

 - Improve the API documentation
 - Provide "helper" classes to simplify working with computers, users, groups, contacts, Exchange mailboxes etc.
 - Write unit tests
 - Write tutorials

## License

This software is licensed under the **BSD (3-Clause) License**.
See the [LICENSE](LICENSE) file for more information.

## Appreciate

If you like this project and enjoy using it, feel free to spread the word about it wherever you wish.

You may also consider donating.

### Via Flattr:

<a href="http://flattr.com/thing/1301881/" target="_blank"><img src="http://api.flattr.com/button/flattr-badge-large.png" alt="Flattr this" title="Flattr this" border="0" /></a>
