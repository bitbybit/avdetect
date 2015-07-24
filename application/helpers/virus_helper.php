<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| auto_version
|--------------------------------------------------------------------------
*/
if(!function_exists('_auto_version'))
{
  function _auto_version($file) {
    $CI =& get_instance();
    $file = $CI->config->item('rel_base_url').$file;
    if(strpos($file, '/') !== 0 || !file_exists($_SERVER['DOCUMENT_ROOT'] . $file))
      return $file;
    $mtime = filemtime($_SERVER['DOCUMENT_ROOT'] . $file);
    unset($CI);
    return preg_replace('{\\.([^./]+)$}', ".$mtime.\$1", $file);
  }
}

/*
|--------------------------------------------------------------------------
| no_cache
|--------------------------------------------------------------------------
*/
if(!function_exists('_no_cache'))
{
  function _no_cache() {
    $CI =& get_instance();
    $CI->output->set_header('Last-Modified: '.gmdate( 'D, j M Y H:i:s' ).' GMT');
    $CI->output->set_header('Expires: '.gmdate( 'D, j M Y H:i:s', time() ).' GMT');
    $CI->output->set_header('Cache-Control: no-store, no-cache, must-revalidate');
    $CI->output->set_header('Cache-Control: post-check=0, pre-check=0');
    $CI->output->set_header('Pragma: no-cache');
    unset($CI);
  }
}

/*
|--------------------------------------------------------------------------
| strpos_arr
|--------------------------------------------------------------------------
*/
if(!function_exists('_strpos_arr'))
{
  function _strpos_arr($haystack,$needle) {
    if(!is_array($needle)) {
      $needle = array($needle);
    }
    foreach($needle as $what) {
      if(($pos = strpos($haystack,$what)) !== false) {
        return $pos;
      }
    }
    return false;
  }
}

/*
|--------------------------------------------------------------------------
| avdetect
|--------------------------------------------------------------------------
*/
if(!function_exists('_avdetect'))
{
  function _avdetect($url,$type='url') {
    if(!isset($type) || ($type !== 'url' && $type !== 'domain')) {
      $type = 'url';
    }
    if(!$url) {
      return FALSE;
    }
    if($type === 'url') {
      $url = _is_url_exist($url);
      if(!$url || filter_var($url,FILTER_VALIDATE_URL) === FALSE) {
        return FALSE;
      }
    }
    $CI =& get_instance();
    $postfields = array(
      'api_key' => $CI->config->item('avdetect_key'),
      'data' => $url,
    );
    if($type === 'domain') {
      $postfields['check_type'] = $type;
    }
    else {
      $postfields['check_type'] = 'file_by_url';
      $file_name = split_url($url);
      if(isset($file_name['path'])) {
        $postfields['file_name'] = end(explode('/',$file_name['path']));
      }
      unset($file_name);
    }
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,'https://avdetect.com/api/');
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5');
    curl_setopt($ch,CURLOPT_TIMEOUT,180);
    curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,false);
    curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,false);
    curl_setopt($ch,CURLOPT_POST,1);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$postfields);
    $r = curl_exec($ch);
    curl_close($ch);
    unset($CI,$ch,$postfields);
    $r = json_decode($r);
    if($r) {
      if(is_array($r) && isset($r[0]) && is_object($r[0])) {
        return $r[0];
      }
      /*elseif(is_object($r) && isset($r->status) && $r->status === 'ERROR') {
        return FALSE;
      }*/
      else {
        return FALSE;
      }
    }
    else {
      return FALSE;
    }
  }
}

/*
|--------------------------------------------------------------------------
| get_headers_curl
|--------------------------------------------------------------------------
*/
if(!function_exists('_get_headers_curl'))
{
  function _get_headers_curl($url) {
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_HEADER,true);
    curl_setopt($ch,CURLOPT_NOBODY,true);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5');
    curl_setopt($ch,CURLOPT_TIMEOUT,2);
    $r = curl_exec($ch);
    $r = explode("\n",$r);
    curl_close($ch);
    unset($ch);
    return $r;
  }
}

/*
|--------------------------------------------------------------------------
| request_timeout
|--------------------------------------------------------------------------
*/
if(!function_exists('_request_timeout'))
{
  function _request_timeout($timeout) {
    stream_context_set_default(array(
      'http' => array(
        'timeout' => (int)$timeout,
      ),
    ));
  }
}

/*
|--------------------------------------------------------------------------
| make_clean_url
|--------------------------------------------------------------------------
*/
if(!function_exists('_make_clean_url'))
{
  function _make_clean_url($url) {
    $url = split_url($url);
    if(!isset($url['host'])) {
      return join_url($url);
    }
    else {
      $CI =& get_instance();
      $url['host'] = $CI->punycode->encodeHostname($url['host']);
      /*return rtrim(preg_replace('#^www\.(.+\.)#i','$1',join_url($url)),'/');*/
      unset($CI);
      return rtrim(join_url($url),'/');
    }
  }
}

/*
|--------------------------------------------------------------------------
| is_url_exist
|--------------------------------------------------------------------------
*/
if(!function_exists('_is_url_exist'))
{
  function _is_url_exist($url) {
    $url = _make_clean_url($url);
    $CI =& get_instance();
    $headers_array = $CI->config->item('headers_ok');
    unset($CI);
    if(filter_var($url,FILTER_VALIDATE_URL) === FALSE || !is_array($headers_array)) {
      return FALSE;
    }
    else {
      _request_timeout(2);
      $headers = @get_headers($url);
      if(!isset($headers[0]) || _strpos_arr($headers[0],$headers_array) === FALSE) {
        unset($headers);
        return FALSE;
      }
      else {
        $get_headers = _dirtyHeaderParser(implode("\n",$headers));
        unset($headers);
        /* 800 kilobytes */
        $content_length = 819200;
        if(isset($get_headers['location'])) {
          if(is_array($get_headers['location'])) {
            if(substr(end($get_headers['location']),0,4) === 'http') {
              $get_headers_location = end($get_headers['location']);
            }
            else {
              foreach(array_reverse($get_headers['location']) as $value) {
                if(substr($value,0,4) === 'http') {
                  $get_headers_location = $value;
                  break;
                }
              }
            }
          }
          else {
            $get_headers_location = $get_headers['location'];
          }
          unset($get_headers);
          if($get_headers_location) {
            $get_headers_location = _make_clean_url($get_headers_location);
            if(filter_var($get_headers_location,FILTER_VALIDATE_URL) === FALSE) {
              unset($get_headers_location);
              return FALSE;
            }
            else {
              $headers = @get_headers($get_headers_location);
              $get_headers = _dirtyHeaderParser(implode("\n",$headers));
              if(isset($get_headers['content-length'])) {
                if(is_array($get_headers['content-length'])) {
                  $content_length = end($get_headers['content-length']);
                }
                else {
                  $content_length = $get_headers['content-length'];
                }
              }
              if(!isset($headers[0]) || _strpos_arr($headers[0],$headers_array) === FALSE || (int)$content_length > 819200) {
                unset($headers,$get_headers,$get_headers_location,$content_length);
                return FALSE;
              }
              else {
                unset($headers,$get_headers,$content_length);
                return $get_headers_location;
              }
            }
          }
          else {
            unset($get_headers_location);
            return FALSE;
          }
        }
        else {
          if(isset($get_headers['content-length'])) {
            if(is_array($get_headers['content-length'])) {
              $content_length = end($get_headers['content-length']);
            }
            else {
              $content_length = $get_headers['content-length'];
            }
          }
          unset($get_headers);
          if((int)$content_length > 819200) {
            unset($content_length);
            return FALSE;
          }
          else {
            unset($content_length);
            return $url;
          }
        }
      }
    }
  }
}

/*
|--------------------------------------------------------------------------
| dirtyHeaderParser
|--------------------------------------------------------------------------
| http://stackoverflow.com/a/22798163
|--------------------------------------------------------------------------
*/
if(!function_exists('_dirtyHeaderParser'))
{
  function _dirtyHeaderParser($headers, $strict = false){
    $arr = array();
    $s = strtok($headers, ':');
    while ($s){
      if ( ($s[0] === ' ') || ($s[0] === "\t") ){
        if (count($arr) != 0){
          $tail = strtok('');
          $tail = "{$s}:{$tail}";
          $v = strtok($tail, "\n");
          if (is_array($arr[$key])){
            end($arr[$key]);
            $last = key($arr[$key]);
            $arr[$key][$last] = "{$arr[$key][$last]}\n{$v}";
            reset($arr[$key]);
          } else {
            $arr[$key] = "{$arr[$key]}\n{$v}";
          }
        }
      } else {
        $v = strtok("\n");
        if ($v){
          $key = strtolower($s);
          if (((strpos($key, "\n") !== false) || (strpos($key, "\t") !== false) || (strpos($key, " ") !== false)) && $strict) {
            return false;
          }
          if (array_key_exists($key, $arr)){
            if (!is_array($arr[$key])){
              $arr[$key] = array($arr[$key]);
            }
            $arr[$key][] = trim($v);
          } else {
            $arr[$key] = trim($v);
          }
        } else {
          break;
        }
      }
      $s = strtok(':');
    }
    return (count($arr) == 0) ? false : $arr;
  }
}

/*
|--------------------------------------------------------------------------
| split_url
|--------------------------------------------------------------------------
| http://nadeausoftware.com/articles/2008/05/php_tip_how_parse_and_build_urls
|--------------------------------------------------------------------------
*/
if(!function_exists('split_url'))
{
  function split_url( $url, $decode=FALSE )
  {
    $xunressub   = 'a-zA-Z\d\-._~\!$&\'()*+,;=';
    $xpchar    = $xunressub . ':@%';

    $xscheme     = '([a-zA-Z][a-zA-Z\d+-.]*)';

    $xuserinfo   = '((['  . $xunressub . '%]*)' .
             '(:([' . $xunressub . ':%]*))?)';

    $xipv4     = '(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})';

    $xipv6     = '(\[([a-fA-F\d.:]+)\])';

    // IDN
    $xhost_name  = '([a-zA-Z\d-.%]+|'.str_replace('.','\.',parse_url($url,PHP_URL_HOST)).')';

    $xhost     = '(' . $xhost_name . '|' . $xipv4 . '|' . $xipv6 . ')';

    $xport     = '(\d*)';
    $xauthority  = '((' . $xuserinfo . '@)?' . $xhost .
             '?(:' . $xport . ')?)';

    $xslash_seg  = '(/[' . $xpchar . ']*)';
    $xpath_authabs = '((//' . $xauthority . ')((/[' . $xpchar . ']*)*))';
    $xpath_rel   = '([' . $xpchar . ']+' . $xslash_seg . '*)';
    $xpath_abs   = '(/(' . $xpath_rel . ')?)';
    $xapath    = '(' . $xpath_authabs . '|' . $xpath_abs .
             '|' . $xpath_rel . ')';

    $xqueryfrag  = '([' . $xpchar . '/?' . ']*)';

    $xurl      = '^(' . $xscheme . ':)?' .  $xapath . '?' .
             '(\?' . $xqueryfrag . ')?(#' . $xqueryfrag . ')?$';

    // Split the URL into components.
    if ( !preg_match( '!' . $xurl . '!', $url, $m ) )
      return FALSE;

    if ( !empty($m[2]) )    $parts['scheme']  = strtolower($m[2]);

    if ( !empty($m[7]) ) {
      if ( isset( $m[9] ) )   $parts['user']  = $m[9];
      else      $parts['user']  = '';
    }
    if ( !empty($m[10]) )     $parts['pass']  = $m[11];

    if ( !empty($m[13]) )     $h=$parts['host'] = $m[13];
    else if ( !empty($m[14]) )  $parts['host']  = $m[14];
    else if ( !empty($m[16]) )  $parts['host']  = $m[16];
    else if ( !empty( $m[5] ) ) $parts['host']  = '';

    if ( !empty($m[17]) )     $parts['port']  = $m[18];

    if ( !empty($m[19]) )     $parts['path']  = $m[19];
    else if ( !empty($m[21]) )  $parts['path']  = $m[21];
    else if ( !empty($m[25]) )  $parts['path']  = $m[25];

    if ( !empty($m[27]) )     $parts['query']   = $m[28];
    if ( !empty($m[29]) )     $parts['fragment']= $m[30];

    if ( !$decode )
      return $parts;
    if ( !empty($parts['user']) )
      $parts['user']   = rawurldecode( $parts['user'] );
    if ( !empty($parts['pass']) )
      $parts['pass']   = rawurldecode( $parts['pass'] );
    if ( !empty($parts['path']) )
      $parts['path']   = rawurldecode( $parts['path'] );
    if ( isset($h) )
      $parts['host']   = rawurldecode( $parts['host'] );
    if ( !empty($parts['query']) )
      $parts['query']  = rawurldecode( $parts['query'] );
    if ( !empty($parts['fragment']) )
      $parts['fragment'] = rawurldecode( $parts['fragment'] );
    return $parts;
  }
}

/*
|--------------------------------------------------------------------------
| join_url
|--------------------------------------------------------------------------
| http://nadeausoftware.com/articles/2008/05/php_tip_how_parse_and_build_urls
|--------------------------------------------------------------------------
*/
if(!function_exists('join_url'))
{
  function join_url( $parts, $encode=FALSE )
  {
    if ( $encode )
    {
      if ( isset( $parts['user'] ) )
        $parts['user']   = rawurlencode( $parts['user'] );
      if ( isset( $parts['pass'] ) )
        $parts['pass']   = rawurlencode( $parts['pass'] );
      if ( isset( $parts['host'] ) &&
        !preg_match( '!^(\[[\da-f.:]+\]])|([\da-f.:]+)$!ui', $parts['host'] ) )
        $parts['host']   = rawurlencode( $parts['host'] );
      if ( !empty( $parts['path'] ) )
        $parts['path']   = preg_replace( '!%2F!ui', '/',
          rawurlencode( $parts['path'] ) );
      if ( isset( $parts['query'] ) )
        $parts['query']  = rawurlencode( $parts['query'] );
      if ( isset( $parts['fragment'] ) )
        $parts['fragment'] = rawurlencode( $parts['fragment'] );
    }

    $url = '';
    if ( !empty( $parts['scheme'] ) )
      $url .= $parts['scheme'] . ':';
    if ( isset( $parts['host'] ) )
    {
      $url .= '//';
      if ( isset( $parts['user'] ) )
      {
        $url .= $parts['user'];
        if ( isset( $parts['pass'] ) )
          $url .= ':' . $parts['pass'];
        $url .= '@';
      }
      if ( preg_match( '!^[\da-f]*:[\da-f.:]+$!ui', $parts['host'] ) )
        $url .= '[' . $parts['host'] . ']'; // IPv6
      else
        $url .= $parts['host'];       // IPv4 or name
      if ( isset( $parts['port'] ) )
        $url .= ':' . $parts['port'];
      if ( !empty( $parts['path'] ) && $parts['path'][0] != '/' )
        $url .= '/';
    }
    if ( !empty( $parts['path'] ) )
      $url .= $parts['path'];
    if ( isset( $parts['query'] ) )
      $url .= '?' . $parts['query'];
    if ( isset( $parts['fragment'] ) )
      $url .= '#' . $parts['fragment'];
    return $url;
  }
}

/*
|--------------------------------------------------------------------------
| url_remove_dot_segments
|--------------------------------------------------------------------------
| http://nadeausoftware.com/node/79
|--------------------------------------------------------------------------
*/
if(!function_exists('url_remove_dot_segments'))
{
  function url_remove_dot_segments( $path )
  {
    // multi-byte character explode
    $inSegs  = preg_split( '!/!u', $path );
    $outSegs = array( );
    foreach ( $inSegs as $seg )
    {
      if ( $seg == '' || $seg == '.')
        continue;
      if ( $seg == '..' )
        array_pop( $outSegs );
      else
        array_push( $outSegs, $seg );
    }
    $outPath = implode( '/', $outSegs );
    if ( $path[0] == '/' )
      $outPath = '/' . $outPath;
    // compare last multi-byte character against '/'
    if ( $outPath != '/' &&
      (mb_strlen($path)-1) == mb_strrpos( $path, '/', 'UTF-8' ) )
      $outPath .= '/';
    return $outPath;
  }
}

/*
|--------------------------------------------------------------------------
| url_to_absolute
|--------------------------------------------------------------------------
| http://nadeausoftware.com/node/79
| http://ca.php.net/manual/en/function.parse-url.php#76979
| http://ca.php.net/manual/en/function.parse-url.php#76682
|--------------------------------------------------------------------------
*/
if(!function_exists('url_to_absolute'))
{
  function url_to_absolute( $baseUrl, $relativeUrl )
  {
    // If relative URL has a scheme, clean path and return.
    $r = split_url( $relativeUrl );
    if ( $r === FALSE )
      return FALSE;
    if ( !empty( $r['scheme'] ) )
    {
      if ( !empty( $r['path'] ) && $r['path'][0] == '/' )
        $r['path'] = url_remove_dot_segments( $r['path'] );
      return join_url( $r );
    }

    // Make sure the base URL is absolute.
    $b = split_url( $baseUrl );
    if ( $b === FALSE || empty( $b['scheme'] ) || empty( $b['host'] ) )
      return FALSE;
    $r['scheme'] = $b['scheme'];

    // If relative URL has an authority, clean path and return.
    if ( isset( $r['host'] ) )
    {
      if ( !empty( $r['path'] ) )
        $r['path'] = url_remove_dot_segments( $r['path'] );
      return join_url( $r );
    }
    unset( $r['port'] );
    unset( $r['user'] );
    unset( $r['pass'] );

    // Copy base authority.
    $r['host'] = $b['host'];
    if ( isset( $b['port'] ) ) $r['port'] = $b['port'];
    if ( isset( $b['user'] ) ) $r['user'] = $b['user'];
    if ( isset( $b['pass'] ) ) $r['pass'] = $b['pass'];

    // If relative URL has no path, use base path
    if ( empty( $r['path'] ) )
    {
      if ( !empty( $b['path'] ) )
        $r['path'] = $b['path'];
      if ( !isset( $r['query'] ) && isset( $b['query'] ) )
        $r['query'] = $b['query'];
      return join_url( $r );
    }

    // If relative URL path doesn't start with /, merge with base path
    if ( $r['path'][0] != '/' )
    {
      $base = mb_strrchr( $b['path'], '/', TRUE, 'UTF-8' );
      if ( $base === FALSE ) $base = '';
      $r['path'] = $base . '/' . $r['path'];
    }
    $r['path'] = url_remove_dot_segments( $r['path'] );
    return join_url( $r );
  }
}

/*
|--------------------------------------------------------------------------
| parse_external_js
|--------------------------------------------------------------------------
*/
if(!function_exists('_parse_external_js'))
{
  function _parse_external_js($url) {
    $url = _make_clean_url($url);
    if(filter_var($url,FILTER_VALIDATE_URL) === FALSE) {
      return FALSE;
    }
    else {
      $url_header = _is_url_exist($url);
      if($url_header) {
        /*_request_timeout(3);
        $html = @file_get_contents($url);*/
        $html = @file_get_html($url_header,false,stream_context_create(array(
          'http' => array(
            'timeout' => 3,
          ),
        )));
        if($html) {
          $external_js = array();
          $external_js_count = 0;
          $external_js_count_max = 20;
          foreach($html->find('script') as $script) {
            $src = $script->src;
            if($src != null) {
              if(strpos($src,'//') === FALSE) {
                /*if($src[0] === '/') {
                  $src = $url.$src;
                }
                elseif($src[0] != '.') {
                  $src = $url.'/'.$src;
                }
                else {
                  $src = null;
                }*/
                if(isset($url_header[strlen($url_header)-1]) && $url_header[strlen($url_header)-1] !== '/') {
                  $url_header .= '/';
                }
                $src = url_to_absolute($url_header,$src);
              }
              if(_is_url_exist($src)) {
                $external_js[] = $src;
              }
            }
            $external_js_count++;
            if($external_js_count === $external_js_count_max) {
              unset($external_js_count,$external_js_count_max);
              break;
            }
          }
          $html = NULL;
          unset($url_header);
          if(!empty($external_js)) {
            return $external_js;
          }
          else {
            unset($external_js);
            return FALSE;
          }
        }
        else {
          $html = NULL;
          unset($url_header);
          return FALSE;
        }
      }
      else {
        unset($url_header);
        return FALSE;
      }
    }
  }
}

/*
|--------------------------------------------------------------------------
| trylock
|--------------------------------------------------------------------------
| http://php.net/manual/en/function.getmypid.php#112782
| http://www.electrictoolbox.com/check-php-script-already-running/
|--------------------------------------------------------------------------
*/
if(!function_exists('_trylock'))
{
  function _trylock() {
    if(!file_exists(LOCK_FILE) && @symlink("/proc/".getmypid(),LOCK_FILE) !== FALSE) {
      return TRUE;
    }
    if(is_link(LOCK_FILE) && !is_dir(LOCK_FILE)) {
      unlink(LOCK_FILE);
      return _trylock();
    }
    return FALSE;
  }
}

/*
|--------------------------------------------------------------------------
| beautify_av_title
|--------------------------------------------------------------------------
*/
if(!function_exists('_beautify_av_title'))
{
  function _beautify_av_title($codename) {
    switch ($codename) {
      case 'aware':
        return array(
          'title' => 'Ad-Aware',
          'domain' => 'lavasoft.com',
        );
      break;
      case 'ahnlab':
        return array(
          'title' => 'AhnLab V3 Internet Security',
          'domain' => 'global.ahnlab.com',
        );
      break;
      case 'arcavir':
        return array(
          'title' => 'ArcaVir',
          'domain' => 'arcavir.asia',
        );
      break;
      case 'avast':
        return array(
          'title' => 'Avast',
          'domain' => 'avast.com',
        );
      break;
      case 'avg':
        return array(
          'title' => 'AVG',
          'domain' => 'avg.com',
        );
      break;
      case 'avira':
        return array(
          'title' => 'Avira',
          'domain' => 'avira.com',
        );
      break;
      case 'bitdef':
        return array(
          'title' => 'BitDefender',
          'domain' => 'bitdefender.com',
        );
      break;
      case 'bullguard':
        return array(
          'title' => 'BullGuard Internet Security',
          'domain' => 'bullguard.com',
        );
      break;
      case 'clamav':
        return array(
          'title' => 'ClamAv',
          'domain' => 'clamav.net',
        );
      break;
      case 'comodo':
        return array(
          'title' => 'Comodo',
          'domain' => 'comodo.com',
        );
      break;
      case 'drwebfile':
        return array(
          'title' => 'Dr. Web',
          'domain' => 'drweb.com',
        );
      break;
      case 'a2':
        return array(
          'title' => 'Emisoft AntiMalware (A-Squared)',
          'domain' => 'emsisoft.com',
        );
      break;
      case 'escan':
        return array(
          'title' => 'eScan Internet Security Suite 14',
          'domain' => 'escan.com',
        );
      break;
      case 'fsecure':
        return array(
          'title' => 'F-Secure',
          'domain' => 'f-secure.com',
        );
      break;
      case 'fortinet':
        return array(
          'title' => 'Fortinet 5',
          'domain' => 'fortinet.com',
        );
      break;
      case 'fprot':
        return array(
          'title' => 'F-PROT',
          'domain' => 'f-prot.com',
        );
      break;
      case 'gdata':
        return array(
          'title' => 'G Data',
          'domain' => 'gdata-software.com',
        );
      break;
      case 'ikarus':
        return array(
          'title' => 'IKARUS',
          'domain' => 'ikarussecurity.com',
        );
      break;
      case 'immunet':
        return array(
          'title' => 'Immunet',
          'domain' => 'immunet.com',
        );
      break;
      case 'k7':
        return array(
          'title' => 'K7 Ultimate Security',
          'domain' => 'k7computing.com',
        );
      break;
      case 'kis2013':
        return array(
          'title' => 'Kaspersky Internet Security 2014',
          'domain' => 'kaspersky.com',
        );
      break;
      case 'mcafee':
        return array(
          'title' => 'McAfee Total Protection 2013',
          'domain' => 'mcafee.com',
        );
      break;
      case 'se':
        return array(
          'title' => 'Microsoft Security Essentials',
          'domain' => 'windows.microsoft.com',
        );
      break;
      case 'nano':
        return array(
          'title' => 'NANO',
          'domain' => 'nanoav.com',
        );
      break;
      case 'nod':
        return array(
          'title' => 'ESET NOD32',
          'domain' => 'eset.com',
        );
      break;
      case 'norman':
        return array(
          'title' => 'Norman',
          'domain' => 'norman.com',
        );
      break;
      case 'nis':
        return array(
          'title' => 'Norton Internet Security',
          'domain' => 'norton.com',
        );
      break;
      case 'outpost':
        return array(
          'title' => 'Outpost Security Suite Pro',
          'domain' => 'agnitum.com',
        );
      break;
      case 'panda':
        return array(
          'title' => 'Panda Antivirus',
          'domain' => 'pandasecurity.com',
        );
      break;
      case 'pandacl':
        return array(
          'title' => 'Panda Cloud',
          'domain' => 'pandacloudsecurity.com',
        );
      break;
      case 'quickheal':
        return array(
          'title' => 'Quick Heal',
          'domain' => 'quickheal.com',
        );
      break;
      case 'sophos':
        return array(
          'title' => 'Sophos',
          'domain' => 'sophos.com',
        );
      break;
      case 'sas':
        return array(
          'title' => 'SUPERAntiSpyware',
          'domain' => 'superantispyware.com',
        );
      break;
      case 'deftot':
        return array(
          'title' => 'Total Defense Internet Security',
          'domain' => 'totaldefense.com',
        );
      break;
      case 'trendmicro':
        return array(
          'title' => 'Trend Micro Titanium Security',
          'domain' => 'trendmicro.com',
        );
      break;
      case 'twister':
        return array(
          'title' => 'Twister Antivirus 8',
          'domain' => 'filseclab.com',
        );
      break;
      case 'vba':
        return array(
          'title' => 'VirusBlokAda',
          'domain' => 'anti-virus.by',
        );
      break;
      case 'vipre':
        return array(
          'title' => 'VIPRE Internet Security 2013',
          'domain' => 'vipreantivirus.com',
        );
      break;
      case 'virit':
        return array(
          'title' => 'VirIT eXplorer',
          'domain' => 'tgsoft.it',
        );
      break;
      case 'abuseat':
        return array(
          'title' => 'Abuseat Composite Blocking List',
          'domain' => 'cbl.abuseat.org',
        );
      break;
      case 'baracuda':
        return array(
          'title' => 'Barracuda Reputation Block List (BRBL)',
          'domain' => 'barracudacentral.org',
        );
      break;
      case 'drweb':
        return array(
          'title' => 'Dr. Web',
          'domain' => 'drweb.com',
        );
      break;
      case 'googlesb':
        return array(
          'title' => 'Google Safe Browsing',
          'domain' => 'google.com',
          'url' => 'google.com/safebrowsing/diagnostic?site=',
        );
      break;
      case 'honeypotproject':
        return array(
          'title' => 'Project Honey Pot',
          'domain' => 'projecthoneypot.org',
        );
      break;
      case 'hphosts':
        return array(
          'title' => 'hpHosts',
          'domain' => 'hosts-file.net',
        );
      break;
      case 'kisksn':
        return array(
          'title' => 'Kaspersky Security Network (KSN)',
          'domain' => 'kaspersky.com',
        );
      break;
      case 'malcode':
        return array(
          'title' => 'Malc0de Blacklist',
          'domain' => 'malc0de.com',
        );
      break;
      case 'malwaredomainlist':
        return array(
          'title' => 'MalwareDomainList.com',
          'domain' => 'malwaredomainlist.com',
        );
      break;
      case 'malwaredomains':
        return array(
          'title' => 'MalwareDomains.com',
          'domain' => 'malwaredomains.com',
        );
      break;
      case 'malwarepatrol':
        return array(
          'title' => 'Malware Patrol',
          'domain' => 'malwarepatrol.net',
        );
      break;
      case 'mywot':
        return array(
          'title' => 'Web of Trust',
          'domain' => 'mywot.com',
          'url' => 'mywot.com/en/scorecard/',
        );
      break;
      case 'phishtank':
        return array(
          'title' => 'PhishTank',
          'domain' => 'phishtank.com',
        );
      break;
      case 'sorbs':
        return array(
          'title' => 'Spam and Open Relay Blocking System (SORBS)',
          'domain' => 'sorbs.net',
        );
      break;
      case 'spamcop':
        return array(
          'title' => 'SpamCop',
          'domain' => 'spamcop.net',
        );
      break;
      case 'spamhaus':
        return array(
          'title' => 'Spamhaus',
          'domain' => 'spamhaus.org',
        );
      break;
      case 'spyeyebl':
        return array(
          'title' => 'Spyeye Tracker',
          'domain' => 'spyeyetracker.abuse.ch',
        );
      break;
      case 'yandexsb':
        return array(
          'title' => 'Yandex Safe Browsing',
          'domain' => 'yandex.ru',
          'url' => 'yandex.ru/infected?url=',
        );
      break;
      case 'zeusbl':
        return array(
          'title' => 'ZeuS Tracker',
          'domain' => 'zeustracker.abuse.ch',
        );
      break;
      default:
         return $codename;
      break;
    }
  }
}

/*
|--------------------------------------------------------------------------
| relative_time
|--------------------------------------------------------------------------
| http://ellislab.com/forums/viewthread/106557/
|--------------------------------------------------------------------------
*/
if(!function_exists('_relative_time'))
{
  function _relative_time($datetime) {
    if(!$datetime) {
      return FALSE;
    }
    if(!is_numeric($datetime)) {
      $val = explode(" ",$datetime);
      $date = explode("-",$val[0]);
      $time = explode(":",$val[1]);
      $datetime = mktime($time[0],$time[1],$time[2],$date[1],$date[2],$date[0]);
    }
    $difference = time() - $datetime;
    $periods = array("сек.", "мин.", "час.", "дн.", "нед.", "мес.", "г.");
    $lengths = array("60","60","24","7","4.35","12");
    /*if($difference > 0) {
      $ending = 'назад';
    }
    else {
      $difference = -$difference;
      $ending = 'ещё';
    }*/
    for($j = 0; $difference >= $lengths[$j]; $j++) {
      $difference /= $lengths[$j];
    }
    $difference = round($difference);
    /*if($difference != 1) {
      $period = strtolower($periods[$j].'s');
    }
    else {*/
      $period = strtolower($periods[$j]);
    /*}*/
    return $difference.' '.$period;
  }
}

/*
|--------------------------------------------------------------------------
| send_mail_url
|--------------------------------------------------------------------------
*/
if(!function_exists('_send_mail_url'))
{
  function _send_mail_url($email,$hash,$url) {
    if(!isset($email) || !isset($hash) || !isset($url)) {
      return FALSE;
    }
    $CI =& get_instance();
    $CI->email->clear();
    $CI->email->from('no-reply@'.((isset($_SERVER['HTTP_HOST'])) ? str_replace('www.','',$_SERVER['HTTP_HOST']) : 'localhost'),'Virus Checker');
    $CI->email->to($email);
    $CI->email->subject('Результаты проверки на вредоносный код '.$url);
    $CI->email->message('{unwrap}'.base_url().'check/'.$hash.'{/unwrap}');
    $CI->email->send();
    unset($CI);
    return TRUE;
  }
}

/*
|--------------------------------------------------------------------------
| mask_email
|--------------------------------------------------------------------------
*/
if(!function_exists('_mask_email'))
{
  function _mask_email($email) {
    return preg_replace('/(?<=.).(?=.*@)/u','*',$email);
  }
}

/*
|--------------------------------------------------------------------------
| captcha_activate
|--------------------------------------------------------------------------
*/
if(!function_exists('_captcha_activate'))
{
  function _captcha_activate() {
    $CI =& get_instance();
    $captcha_value = random_string('numeric',4);
    $captcha = create_captcha(array(
      'word' => $captcha_value,
      'img_path' => BASEPATH.'../html/captcha/',
      'img_url' => base_url().'captcha/',
      'font_path' => BASEPATH.'fonts/texb.ttf',
      'img_width' => 88,
      'img_height' => 31,
      'expiration' => 7200,
    ));
    if($CI->session->userdata['captcha_image'] && file_exists(BASEPATH."../html/captcha/".$CI->session->userdata['captcha_image'])) {
      unlink(BASEPATH."../html/captcha/".$CI->session->userdata['captcha_image']);
    }
    $CI->session->set_userdata(array(
      'captcha' => $captcha_value,
      'captcha_image' => $captcha['time'].'.jpg',
    ));
    return $captcha['image'];
  }
}

/*
|--------------------------------------------------------------------------
| captcha_destroy
|--------------------------------------------------------------------------
*/
if(!function_exists('_captcha_destroy'))
{
  function _captcha_destroy() {
    $CI =& get_instance();
    if($CI->session->userdata['captcha_image'] && file_exists(BASEPATH."../html/captcha/".$CI->session->userdata['captcha_image'])) {
      unlink(BASEPATH."../html/captcha/".$CI->session->userdata['captcha_image']);
    }
    $CI->session->unset_userdata('captcha');
    $CI->session->unset_userdata('captcha_image');
  }
}
