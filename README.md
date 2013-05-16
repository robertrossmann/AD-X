# AD-X :: Active Directory library for php

The **AD-X** library aims to provide an easy-to-use, object-oriented and extremely powerful access to an Active Directory database.

## Features

### The important ones
----

#### Object-oriented API
Use a nice, understandable, object-oriented API to work with Active Directory.

#### Support for paged results
Need to fetch more objects than the maximum configured limit for a single request? No problem - just enable paged searching and off you go!

#### Support for referrals
If you use more domains in your Active Directory infrastructure, modifying objects across all domains could be a real pain. **AD-X** supports referrals, so if you hit one, the library will try to connect to the other domain and perform the operation there.

#### TLS or SSL encryption
You can use either TLS or SSL encryptions with the library. Just make sure you have the proper certificates installed and you are ready to go.

#### Automatic data conversion

Ever tried to find out when a user logged in to the system last time, only to discover that the `lastlogontimestamp` attribute is not a standard unix timestamp you know from *php*? Well, those days are over - when accessing any such attribute via **AD-X**, you get a unix timestamp. Free of charge.  

And it does not end with just timestamps. Need to reset someone's password? Just set the attribute's value and you are done. The library will handle proper attribute formatting for you.  
You can even convert attributes like `member` or `manager` into real objects with just one method call!

#### No assumptions: use the RootDSE and the directory schema
The library makes extensive use of the *rootDSE* entry and the directory schema to determine the nature of the current environment.


### The nice to have ones
----

#### *ldap v3* protocol support
Using the ldap v3 protocol, you can get the most out of a directory server by using the newest available technologies.

#### Namespaces all over the place
The library is fully namespaced to ensure 100% compatibility with any other libraries / frameworks you might need to use.

#### Exceptions-based error handling
Handle your exceptions flexibly by utilising exceptions. No more checking if a method returns `FALSE` or `NULL`!

#### Autoloading support
No need to include the library files - just include one file and the autoloader will take care of the rest.

#### Local directory schema cache
By caching the directory schema, the library can take advantage of the information present on the schema without the need to query the server for it, saving the execution time and speeding your application up.

#### Object to json string conversion
Using a javascript front-end and need to send the ldap data to the browser using json? No problem - just one method call and you have your json string ready.

#### Optional ldap query builder
If you like, you can use the provided ldap query builder that makes building ldap filters simple. You can even combine the query builder with hand-written filters.


## Stability

The library is to be considered of a **release candidate** stability level. The library has been tested extensively in a real-world environment, but some non-thought-of edge cases and outstanding non-critical known issues might need fixing.

Please submit your bugs to the Github's [issue tracker](https://github.com/Alaneor/AD-X/issues).

## Installation

### Requirements

 - PHP >= 5.4.0
 - PHP with LDAP support ( [setup instructions](http://www.php.net/manual/en/ldap.installation.php) )
 - PHP with OpenSSL for SSL / TLS encrypted connections ( [setup instructions](http://cz1.php.net/manual/en/openssl.installation.php) )

### Install:

#### Via Composer:

`composer require alaneor/ad-x`

#### Manually:

1. Download the repository to your drive
1. Include the [adx.php](adx.php) file into your project

#### Post-installation:

1. Generate the schema cache:  
 ```php
    $link = new ADX\Core\Link( 'company.com', 389 ); // Connect to server on default port  
    $link->bind( 'username@company.com', 'password' ); // Authenticate to the server  
    ADX\Core\Schema::build( $link ); // Build the local schema cache ( takes some time, go get a coffee )
```

1. Optionally include the namespaced objects into current namespace for easy usage:
 ```php
    use ADX\Core\Link; // To include the Link class
    // … Include more as needed
 ```

1. Start using the library!

Depending on the stability of your Active Directory environment, you might need to re-build the schema cache occasionally.

## Documentation

Documentation is currently available for the API only, but it includes a lot of examples and explanations.

### Read online: [API documentation](http://alaneor.github.io/AD-X/api)
### Generate the documentation locally:

1. Download the library
1. Install the dependencies using [Composer](http://getcomposer.org/):  
```
composer install --dev
```
1. Generate the API documentation using [ApiGen](http://apigen.org/) ( installed by Composer ):  
```
php ./vendor/apigen/apigen/apigen.php
```
1. Generated documentation will be available at *./docs/www/index.html*


## Known limitations

 - The *ldap v2* protocol is not supported and never will be. Pull requests to provide backwards-compatibility will be refused.
 - The library has been built **specifically for Active Directory**. Compatibility with standards-based ldap servers like [OpenLDAP](http://openldap.org) is likely broken. I have no plans to address this at the moment but a future release might make such thing possible.
 - Unit tests are missing completely. I realise this might be a serious issue for future development and plan to address this with a future update. Any help with writing tests is much appreciated.

## Future to-dos

 - Improve the API documentation
 - Write unit tests
 - Write tutorials
 - Provide standards-ldap compatibility?

## License

This software is licensed under the **MIT license**.
See the [LICENSE](LICENSE) file for more information.

## Appreciate

If you like this project and enjoy using it, feel free to spread the word about it wherever you wish.

If this library helped you earn some money, please consider donating ( any amount is greatly appreciated! ).
If you work for a company and the library saved you a lot of time / effort in your project, please consider donating a larger amount. :)

### Via Flattr:

<a href="http://flattr.com/thing/1301881/" target="_blank"><img src="http://api.flattr.com/button/flattr-badge-large.png" alt="Flattr this" title="Flattr this" border="0" /></a>
