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


namespace ADX\Core;
use ADX\Enums;
use ADX\Core\Query as q;

/**
 * This class is responsible for managing the directory schema
 *
 * @see			<a href="http://msdn.microsoft.com/en-us/library/windows/desktop/ms674984%28v=vs.85%29.aspx">MSDN - Active Directory Schema</a>
 */
class Schema
{
	/**
	 * Contains schema objects loaded from files for fast runtime access
	 *
	 * This ensures that if multiple objects request the same attribute schema,
	 * the Schema class will have to load that file only once and then it will
	 * serve that data from memory.
	 *
	 * @var array
	 */
	protected static $runtime_cache = array();

	/**
	 * Folder where the schema is going to be stored.
	 *
	 * This folder will <b>always</b> be relative to the Schema.php file.
	 *
	 * @var			string
	 */
	protected static $schema_dir = '../Schema';

	/**
	 * These attributes will be loaded about the schema object
	 *
	 * @var			array
	 */
	protected static $attribute_properties = [
		'ldapdisplayname',
		'attributesyntax',
		'omsyntax',
		'issinglevalued',
		'rangelower',
		'rangeupper',
		'systemflags',
	];

	protected static $class_properties = [
		'ldapdisplayname',
		'rdnattid',
		'subclassof',
		'allowedattributes',
		'systemonly',
	];

	final private function __construct() {}

	/**
	 * Build the local schema from server, using provided {@link Link}
	 *
	 * This method creates the locally cached schema from all schema objects located in
	 * CN=Schema,CN=Configuration ( or similar, depending on domain configuration ).
	 * The location of the Schema is taken from the RootDSE's "schemanamingcontext" entry.
	 *
	 * @param		Link		The Link object to be used to connect to directory server
	 *
	 * @todo		Also cache class schema objects
	 */
	public static function build( Link $adxLink )
	{
		// Define where to store the schema definition
		$schemaDir = __DIR__.'/'.static::$schema_dir;

		// Prepare the schema folder either by cleaning it's contents or by creating it
		file_exists( $schemaDir ) ? static::flush() : mkdir( $schemaDir, 0644 );

		$schema_base = $adxLink->rootDSE->schemaNamingContext(0); // schemanamingcontext is loaded by default

		// Create the tasks...
		// I have to create them separately because I have two different
		// sets of attributes that I need to have loaded
		$tasks[0] = new Task( Enums\Operation::OpList, $adxLink );
		$tasks[0]	->use_pages( 500 )
					->set_base( $schema_base )
					->set_filter( q::a( ['objectclass' => 'attributeschema'] ) )		// Attribute definitions
					->get_attributes( static::$attribute_properties );

		$tasks[1] = new Task( Enums\Operation::OpList, $adxLink );
		$tasks[1]	->use_pages( 500 )
					->set_base( $schema_base )
					->set_filter( q::a( ['objectclass' => 'classschema'] ) )			// Class definitions
					->get_attributes( static::$class_properties );

		// And retrieve the schema objects!
		foreach ( $tasks as $task )
		{
			// Do not use the Task::run_paged() method as it will very likely hit the memory execution limit.
			// Instead, mimick that method's functionality and handle the data for each page separately
			do
			{
				$objects = $task->run();

				if ( $objects )
				{
					// Loop through the schema objects and save them to a local file, named
					// after the attribute they represent
					foreach ( $objects as $object )
					{
						$filename = $object->ldapDisplayName(0).".json";
						$data = $object->json();

						file_put_contents( static::$schema_dir."/$filename", $data );
					}
				}
				else throw new Exception( 'Maximum number of referrals reached' );
			}
			while ( ! $task->complete );
		}
	}

	/**
	 * Clear the whole Schema cache folder
	 *
	 * This method clears all data from the Schema cache folder.
	 */
	public static function flush()
	{
		array_map( 'unlink', glob( static::$schema_dir.'/*.json' ) );
	}

	/**
	 * Get the cached data about a schema object
	 *
	 * Use this function to get the data that is cached in the Schema cache
	 * for a specified attribute or object.
	 *
	 * @param		string		ldap name of the attribute / object you want the Schema data for
	 * @return		array|null	The Schema data for the specified attribute / object or null if that object is not present in the schema cache
	 */
	public static function get( $schema_object )
	{
		// Convert to lowercase...
		$schema_object = strtolower( $schema_object );

		// Check if this schema object is already loaded in the runtime cache and return it if so
		if ( array_key_exists( $schema_object, static::$runtime_cache ) ) return static::$runtime_cache[$schema_object];

		// No, it is not - load it from the file and store it in runtime cache for future re-use

		$schema_file = __DIR__.'/'.static::$schema_dir."/$schema_object.json";

		// Check if this file has been cached and load it if so
		if ( file_exists( $schema_file ) )
		{
			// Got the schema file, so let's load it and read the properties
			$json	= file_get_contents( $schema_file );
			$data	= json_decode( $json, true, 512, JSON_BIGINT_AS_STRING );

			// Store the data in runtime cache
			static::$runtime_cache[$schema_object] = $data;

			return $data;
		}
		else return null;	// This schema object is not present in local schema cache - nothing to return...
	}
}