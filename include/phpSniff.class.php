<?php
/*******************************************************************************
	phpSniff: HTTP_USER_AGENT Client Sniffer for PHP
	Copyright (C) 2001 Roger Raymond ~ epsilon7@users.sourceforge.net

	This library is free software; you can redistribute it and/or
    modify it under the terms of the GNU Lesser General Public
    License as published by the Free Software Foundation; either
    version 2.1 of the License, or (at your option) any later version.

    This library is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
    Lesser General Public License for more details.

    You should have received a copy of the GNU Lesser General Public
    License along with this library; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*******************************************************************************/

if(!defined('_PHP_SNIFF_CORE_INCLUDED')) include('phpSniff.core.php');
/**
 *  phpSniff
 *  this file is used to set up the
 *  default values for the class
 *
 *  @author     Roger Raymond
 *  @created    2001.06.12
 *  @modified   2001.12.21
 **/

class phpSniff extends phpSniff_core
{   var $_version = '2.0.6';
	/**
     *  Configuration
     *
     *  $_check_cookies
     *      default : null
     *      desc    : Allow for the script to redirect the browser in order
     *              : to check for cookies.   In order for this to work, this
     *              : class must be instantiated before any headers are sent.
     *
     *  $_default_language
     *      default : en-us
     *      desc    : language to report as if no languages are found
     *
     *  $_allow_masquerading
     *      default : null
     *      desc    : Allow for browser to Masquerade as another.
     *              : (ie: Opera identifies as MSIE 5.0)
     *
     *  $_browsers
     *      desc    : 2D Array of browsers we wish to search for
     *              : in key => value pairs.
     *              : key   = browser to search for [as in HTTP_USER_AGENT]
     *              : value = value to return as 'browser' property
     *
     *  $_javascript_versions
     *      desc    : 2D Array of javascript version supported by which browser
     *              : in key => value pairs.
     *              : key   = javascript version
     *              : value = search parameter for browsers that support the
     *              :         javascript version listed in the key (comma delimited)
     *              :         note: the search parameters rely on the values
     *              :               set in the $_browsers array
     **/

    var $_check_cookies         = NULL;
    var $_default_language      = 'en-us';
    var $_allow_masquerading    = NULL;

    var $_browsers = array(
        'microsoft internet explorer' => 'ie',
        'msie'                        => 'ie',
        'netscape6'                   => 'ns',
        'mozilla'                     => 'ns',
        'opera'                       => 'op',
        'konqueror'                   => 'kq',
        'icab'                        => 'ic',
        'lynx'                        => 'lx',
        'ncsa mosaic'                 => 'mo',
        'amaya'                       => 'ay',
        'omniweb'                     => 'ow');

    var $_javascript_versions = array(
        '1.5'   =>  'NS5UP,IE6UP',
        '1.4'   =>  '',
        '1.3'   =>  'NS4.05UP,IE5UP,OP5UP',
        '1.2'   =>  'NS4UP,IE4UP',
        '1.1'   =>  'NS3UP,OP',
        '1.0'   =>  'NS2UP,IE3UP'
        );

    function phpSniff($UA='',$run = true)
    {   // populate the HTTP_USER_AGENT string
        if(empty($UA)) $UA = getenv('HTTP_USER_AGENT');
        $this->_insert('ua',$UA);
        if($run) $this->init();
    }
}
?>