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


// AD-X library bootstrap script

namespace ADX;

// Define path constants
! defined( 'ADX_DS' )			&& define( 'ADX_DS', DIRECTORY_SEPARATOR );		// A little shortcut...
! defined( 'ADX_ROOT_PATH' )	&& define( 'ADX_ROOT_PATH', __DIR__ . ADX_DS );	// Path to folder where the adx.php file resides ( the root )

// Enable class autoloading
// As my folder structure follows my namespace structure, this is all I
// have to write in order to have my classes autoloaded.
function autoload( $class )
{
	$class = str_ireplace( '\\', ADX_DS, $class );					// Convert the namespace path to file path
	$class = str_ireplace( __NAMESPACE__ . ADX_DS, '', $class );	// Remove the current namespace from the path

	$file = ADX_ROOT_PATH . 'src' . ADX_DS . $class . '.php';		// Build the full file string by including current directory and file suffix

	file_exists( $file ) && include_once $file;						// Include the file if it exists
}

spl_autoload_register( 'ADX\autoload' );							// Register the autoloader

// This little bugger is the only exception.:)
require_once( ADX_ROOT_PATH . 'src' . ADX_DS . 'common.php' );
