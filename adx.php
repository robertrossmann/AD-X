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
	$class = str_ireplace('\\', ADX_DS, $class );					// Convert the namespace path to file path
	$class = str_ireplace( __NAMESPACE__ . ADX_DS, '', $class );	// Remove the current namespace from the path

	$file = ADX_ROOT_PATH . ADX_DS . $class . '.php';				// Build the full file string by including current directory and file suffix

	file_exists( $file ) && include_once $file;						// Include the file if it exists
}

spl_autoload_register( 'ADX\autoload' );							// Register the autoloader

// This little bugger is the only exception.:)
require_once( ADX_ROOT_PATH . 'common.php' );