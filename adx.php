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


namespace ADX;
// Enable class autoloading
// As my folder structure follows my namespace structure, this is all I
// have to write in order to have my classes autoloaded.
function autoload( $class )
{
	$class = str_replace('\\', '/', $class );				// Convert the namespace path to file path
	$class = str_ireplace( __NAMESPACE__."/", '', $class );	// Remove the current namespace from the path

	$file = __DIR__.'/'.$class.'.php';						// Build the full file string by including current directory and file suffix

	if ( file_exists( $file ) ) include_once $file;			// Include the file if it exists
}

spl_autoload_register( 'ADX\autoload' );					// Register the autoloader

// This little bugger is the only exception.:)
require_once( __DIR__.'/common.php' );