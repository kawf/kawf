<?php
/*******************************************************************************
	phpSniff: HTTP_USER_AGENT Client Sniffer for PHP
	Copyright (C) 2001 Roger Raymond ~ epsilon7@users.sourceforge.net

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*******************************************************************************/

if(!defined('_PHP_SNIFF_CORE_INCLUDED')) define('_PHP_SNIFF_CORE_INCLUDED',1);
/**
 *  phpSniff_core
 *  here sits the core functionality for the client sniffer
 *
 *  @author     Roger Raymond
 *  @created    2001.05.19
 *  @modified   2001.12.21
 **/

class phpSniff_core
{   // initialize some vars
	var $_browser_info = array(
    'ua'         => '',
    'browser'    => 'Unknown',
    'version'    => 0,
    'maj_ver'    => 0,
    'min_ver'    => 0,
    'letter_ver' => '',
    'javascript' => '0.0',
    'platform'   => 'Unknown',
    'os'         => 'Unknown',
    'ip'         => 'Unknown',
    'cookies'    => 0,
    'language'   => '',
	'long_name'  => '',
	'gecko'      => '');

	var $_get_languages_ran_once = false;
	var $_browser_search_regex = '([a-z]+)([0-9]*)([\.0-9]*)(up)?';
	var $_language_search_regex = '([a-z-]{2,})';
        var $_browser_regex;

    /**
     *  init
     *  this method starts the madness
     **/
    function init ()
    {   //  run the cookie check routine first
        //  [note: method only runs if allowed]
        $this->_test_cookies();
        //  rip the user agent to pieces
        $this->_get_browser_info();
        //  look for other languages
        $this->_get_languages();
        //  establish the operating platform
        $this->_get_os_info();
        //  determine javascript version
        $this->_get_javascript();
        //  collect the ip
        $this->_get_ip();
		//	gecko build
		$this->_get_gecko();
    }

    /**
     *  property
     *  @param $p property to return . optional (null returns entire array)
     *  @return array/string entire array or value of property
     **/
    function property ($p=null)
    {   if($p==null)
        {   return $this->_browser_info;
        }
        else
        {   return $this->_browser_info[strtolower($p)];
        }
    }
	
	/**
	 *	get_property
	 *	alias for property
	 **/
	function get_property ($p)
	{	return $this->property($p);
	}

    /**
     *  is
     *  @param $s string search phrase format = l:lang;b:browser
     *  @return bool true on success
     *  ex: $client->is('b:OP5Up');
     **/
    function is ($s)
    {   // perform language search
        if(preg_match('/l:'.$this->_language_search_regex.'/i',$s,$match))
        {   return $this->_perform_language_search($match);
        }
        // perform browser search
        elseif(preg_match('/b:'.$this->_browser_search_regex.'/i',$s,$match))
        {   return $this->_perform_browser_search($match);
        }
        return false;
    }
	
	/**
	 *	browser_is
	 *	@param $s string search phrase for browser
	 *  @return bool true on success
     *  ex: $client->browser_is('OP5Up');
	 **/
	function browser_is ($s)
	{	preg_match('/'.$this->_browser_search_regex.'/i',$s,$match);
		return $this->_perform_browser_search($match);
	}
	
	/**
	 *	language_is
	 *	@param $s string search phrase for language
	 *  @return bool true on success
     *  ex: $client->language_is('en-US');
	 **/
	function language_is ($s)
	{	preg_match('/'.$this->_language_search_regex.'/i',$s,$match);
		return $this->_perform_language_search($match);
	}

    /**
     *  _perform_browser_search
     *  @param $data string what we're searching for
     *  @return bool true on success
     *  @private
     **/
    function _perform_browser_search ($data)
    {   $search = array();
        $search['phrase']  = $data[0];
        $search['name']    = strtolower($data[1]);  // browser name
        $search['maj_ver'] = $data[2];              // browser maj_ver
        $search['min_ver'] = $data[3];              // browser min_ver
        $search['up']      = !empty($data[4]);      // searching for version higher?
        $looking_for = $search['maj_ver'].$search['min_ver'];
        if($search['name'] == 'aol' || $search['name'] == 'webtv')
        {   return stristr($this->_browser_info['ua'],$search['name']);
        }
        elseif($this->_browser_info['browser'] == $search['name'])
        {   $majv = $search['maj_ver'] ? $this->_browser_info['maj_ver'] : '';
            $minv = $search['min_ver'] ? $this->_browser_info['min_ver'] : '';
            $what_we_are = $majv.$minv;
            if($search['up'] && ($what_we_are >= $looking_for))
            {   return true;
            }
            elseif($what_we_are == $looking_for)
            {   return true;
            }
        }
        return false;
    }

    function _perform_language_search ($data)
    {   // if we've not grabbed the languages, then do so.
        $this->_get_languages();
        return stristr($this->_browser_info['language'],$data[1]);
    }

    function _get_languages ()
    {   // capture available languages and insert into container
        if(!$this->_get_languages_ran_once)
        {   if($languages = getenv('HTTP_ACCEPT_LANGUAGE'))
            {   $languages = preg_replace('/(;q=[0-9]+.[0-9]+)/i','',$languages);
            }
            else
            {   $languages = $this->_default_language;
            }
            $this->_insert('language',$languages);
            $this->_get_languages_ran_once = true;
        }
    }

    function _get_os_info ()
    {
        if (empty($this->_browse_info['ua']))
          return;

        // regexes to use
        $regex_windows  = '/(win[dows]*)[\s]?([0-9a-z]*)[\w\s]?([a-z0-9.]*)/i';
        $regex_mac      = '/(68)[k0]{1,3}|[p\S]{1,5}(pc)/i';
        $regex_os2      = '/os\/2|ibm-webexplorer/i';
        $regex_sunos    = '/(sun|i86)[os\s]*([0-9]*)/i';
        $regex_irix     = '/(irix)[\s]*([0-9]*)/i';
        $regex_hpux     = '/(hp-ux)[\s]*([0-9]*)/i';
        $regex_aix      = '/aix([0-9]*)/i';
        $regex_dec      = '/dec|osfl|alphaserver|ultrix|alphastation/i';
        $regex_vms      = '/vax|openvms/i';
        $regex_sco      = '/sco|unix_sv/i';
        $regex_linux    = '/x11|inux/i';
        $regex_bsd      = '/(free)?(bsd)/i';

        // look for Windows Box
        if(preg_match_all($regex_windows,$this->_browser_info['ua'],$match))
        {   /** Windows has some of the most ridiculous HTTP_USER_AGENT strings */
			//$match[1][count($match[0])-1];
            $v  = $match[2][count($match[0])-1];
            $v2 = $match[3][count($match[0])-1];
            // Establish NT 5.1 as Windows XP
				if(stristr($v,'NT') && $v2 == 5.1) $v = 'xp';
			// Establish NT 5.0 and Windows 2000 as win2k
                elseif($v == '2000') $v = '2k';
                elseif(stristr($v,'NT') && $v2 == 5.0) $v = '2k';
			// Establish 9x 4.90 as Windows 98
				elseif(stristr($v,'9x') && $v2 == 4.9) $v = '98';
            // See if we're running windows 3.1
                elseif($v.$v2 == '16bit') $v = '31';
            // otherwise display as is (31,95,98,NT,ME,XP)
                else $v .= $v2;
            // update browser info container array
            if(empty($v)) $v = 'win';
            $this->_insert('os',strtolower($v));
            $this->_insert('platform','win');
        }
        // look for OS2
        elseif( preg_match($regex_os2,$this->_browser_info['ua']))
        {   $this->_insert('os','os2');
            $this->_insert('platform','os2');
        }
        // look for mac
        // sets: platform = mac ; os = 68k or ppc
        elseif( preg_match($regex_mac,$this->_browser_info['ua'],$match) )
        {   $this->_insert('platform','mac');
            $os = !empty($match[1]) ? '68k' : '';
            $os = !empty($match[2]) ? 'ppc' : $os;
            $this->_insert('os',$os);
        }
        //  look for *nix boxes
        //  sunos sets: platform = *nix ; os = sun|sun4|sun5|suni86
        elseif(preg_match($regex_sunos,$this->_browser_info['ua'],$match))
        {   $this->_insert('platform','*nix');
            if(!stristr('sun',$match[1])) $match[1] = 'sun'.$match[1];
            $this->_insert('os',$match[1].$match[2]);
        }
        //  irix sets: platform = *nix ; os = irix|irix5|irix6|...
        elseif(preg_match($regex_irix,$this->_browser_info['ua'],$match))
        {   $this->_insert('platform','*nix');
            $this->_insert('os',$match[1].$match[2]);
        }
        //  hp-ux sets: platform = *nix ; os = hpux9|hpux10|...
        elseif(preg_match($regex_hpux,$this->_browser_info['ua'],$match))
        {   $this->_insert('platform','*nix');
            $match[1] = str_replace('-','',$match[1]);
            $match[2] = (int) $match[2];
            $this->_insert('os',$match[1].$match[2]);
        }
        //  aix sets: platform = *nix ; os = aix|aix1|aix2|aix3|...
        elseif(preg_match($regex_aix,$this->_browser_info['ua'],$match))
        {   $this->_insert('platform','*nix');
            $this->_insert('os','aix'.$match[1]);
        }
        //  dec sets: platform = *nix ; os = dec
        elseif(preg_match($regex_dec,$this->_browser_info['ua'],$match))
        {   $this->_insert('platform','*nix');
            $this->_insert('os','dec');
        }
        //  vms sets: platform = *nix ; os = vms
        elseif(preg_match($regex_vms,$this->_browser_info['ua'],$match))
        {   $this->_insert('platform','*nix');
            $this->_insert('os','vms');
        }
        //  sco sets: platform = *nix ; os = sco
        elseif(preg_match($regex_sco,$this->_browser_info['ua'],$match))
        {   $this->_insert('platform','*nix');
            $this->_insert('os','sco');
        }
        //  unixware sets: platform = *nix ; os = unixware
        elseif(stristr('unix_system_v',$this->_browser_info['ua']))
        {   $this->_insert('platform','*nix');
            $this->_insert('os','unixware');
        }
        //  mpras sets: platform = *nix ; os = mpras
        elseif(stristr('ncr',$this->_browser_info['ua']))
        {   $this->_insert('platform','*nix');
            $this->_insert('os','mpras');
        }
        //  reliant sets: platform = *nix ; os = reliant
        elseif(stristr('reliantunix',$this->_browser_info['ua']))
        {   $this->_insert('platform','*nix');
            $this->_insert('os','reliant');
        }
        //  sinix sets: platform = *nix ; os = sinix
        elseif(stristr('sinix',$this->_browser_info['ua']))
        {   $this->_insert('platform','*nix');
            $this->_insert('os','sinix');
        }
        //  bsd sets: platform = *nix ; os = bsd|freebsd
        elseif(preg_match($regex_bsd,$this->_browser_info['ua'],$match))
        {   $this->_insert('platform','*nix');
            $this->_insert('os',$match[1].$match[2]);
        }
        //  last one to look for
        //  linux sets: platform = *nix ; os = linux
        elseif(preg_match($regex_linux,$this->_browser_info['ua'],$match))
        {   $this->_insert('platform','*nix');
            $this->_insert('os','linux');
        }
    }

    function _get_browser_info ()
    {   $this->_build_regex();
        if(preg_match_all($this->_browser_regex,$this->_browser_info['ua'],$results))
        {   // get the position of the last browser found
            $count = count($results[0])-1;
            // if we're allowing masquerading, revert to the next to last browser found
            // if possible, otherwise stay put
            if($this->_allow_masquerading && $count > 0) $count--;
            // insert findings into the container
            $this->_insert('browser',$this->_get_short_name($results[1][$count]));
			$this->_insert('long_name',$results[1][$count]);
            $this->_insert('maj_ver',$results[2][$count]);
            // parse the minor version string and look for alpha chars
            preg_match('/([.\0-9]+)([\.a-z0-9]+)?/i',$results[3][$count],$match);
            $this->_insert('min_ver',$match[1]);
            if(isset($match[2])) $this->_insert('letter_ver',$match[2]);
            // insert findings into container
            $this->_insert('version',$this->_browser_info['maj_ver'].$this->property('min_ver'));
        }
    }

    function _get_ip ()
    {   if(getenv('HTTP_CLIENT_IP'))
        {   $ip = getenv('HTTP_CLIENT_IP');
        }
        else
        {   $ip = getenv('REMOTE_ADDR');
        }
        $this->_insert('ip',$ip);
    }

    function _build_regex ()
    {   $browsers = '';
        //while(list($k,) = each($this->_browsers))
        foreach(array_keys($this->_browsers) as $k)
        {   if(!empty($browsers)) $browsers .= "|";
            $browsers .= $k;
        }
        $version_string = "[\/\sa-z]*([0-9]+)([\.0-9a-z]+)";
        $this->_browser_regex = "/($browsers)$version_string/i";
    }

    function _get_short_name ($long_name)
    {   return $this->_browsers[strtolower($long_name)];
    }

    function _insert ($k,$v)
    {   $this->_browser_info[strtolower($k)] = strtolower($v);
    }

    function _test_cookies ()
    {   global $ctest,$phpSniff_testCookie;
        if($this->_check_cookies)
        {   if ($ctest != 1)
            {   SetCookie('phpSniff_testCookie','test',0,'/');
                // See if we were passed anything in the QueryString we might need
                $QS = getenv('QUERY_STRING');
                // fix compatability issues when PHP is
                // running as CGI ~ 6/28/2001 v2.0.2 ~ RR
                $script_path = getenv('PATH_INFO') ? getenv('PATH_INFO') : getenv('SCRIPT_NAME');
                $location = $script_path . ($QS=="" ? "?ctest=1" : "?" . $QS . "&ctest=1");
                header("Location: $location");
                exit;
            }
            // Check for the cookie on page reload
            elseif ($phpSniff_testCookie == "test")
            {   $this->_insert('cookies',true);
            }
            else
            {   $this->_insert('cookies',false);
            }
        }
        else $this->_insert('cookies',false);

    }

    function _get_javascript ()
    {   $set=false;
		// see if we have any matches
        //while(list($version,$browser) = each($this->_javascript_versions))
        foreach($this->_javascript_versions as $version => $browser)
        {   $browser = explode(',',$browser);
            //while(list(,$search) = each($browser))
            foreach($browser as $search)
            {   if($this->is('b:'.$search))
                {   $this->_insert('javascript',$version);
                    $set = true;
                    break;
                }
            }
        if($set) break;
        }
    }
	
	function _get_gecko ()
	{	if(preg_match('/gecko\/([0-9]+)/i',$this->property('ua'),$match))
		{	$this->_insert('gecko',$match[1]);
		}
	}
}
?>
