<?php
function contains($needle, $haystack) {
    return strpos($haystack, $needle) !== false;
}

if (contains('%20', $_SERVER['REQUEST_URI'])) {
	$_SERVER['REQUEST_URI'] = str_replace('%20', '', $_SERVER['REQUEST_URI']);
	header('Location: '.$_SERVER['REQUEST_URI']);
}

$reqUri = urldecode($_SERVER['REQUEST_URI']);
if (isset($_GET['decode']) && $reqUri !== $_SERVER['REQUEST_URI'])
	header("Location: $reqUri");

define ('BASE', explode(':', ini_get('open_basedir'))[0]);

define ('APIDOMAIN', contains('api.', $_SERVER['SERVER_NAME']));
define ('SITENAME', APIDOMAIN ? explode('api.', $_SERVER['SERVER_NAME'])[1] : $_SERVER['SERVER_NAME']);

define ('HTTPS', isset($_SERVER['HTTPS']) && filter_var($_SERVER['HTTPS'], FILTER_VALIDATE_BOOLEAN));
define ('PROTO', (HTTPS || $_SERVER['SERVER_PORT'] == 443 || $_SERVER['HTTP_X_FORWARDED_PORT'] == 443) ? 'https://' : 'http://');

function canonical($href = null) {
	$pathinfo = pathinfo($_SERVER['SCRIPT_NAME']);
	$href = isset($href)
		? '/'.$href
		: (($pathinfo['dirname'] == '/'
			? $pathinfo['dirname']
			: $pathinfo['dirname'].'/')
		. $pathinfo['filename']);
	if ($href == '/index')
		unset($href);
	elseif (contains('index', $href))
		$href = str_replace('index', '', $href);
	return '<link rel="canonical" href="'.PROTO.SITENAME.$href.'">'."\n";
}

function script($src = null, $defer = null, $async = 'async', $custom = null) {
	$src = $src ?? pathinfo($_SERVER['SCRIPT_NAME'])['filename'];
	if (contains('index', $src))
		$src = substr(pathinfo($_SERVER['SCRIPT_NAME'])['dirname'], 1);
	$path = "/scripts/$src.js";
	if (file_exists($_SERVER['DOCUMENT_ROOT'].$path)) {
		$defer = (isset($defer) && !filter_var($defer, FILTER_VALIDATE_BOOLEAN)) ? ' defer' : null;
		$async = (isset($async) && !filter_var($async, FILTER_VALIDATE_BOOLEAN)) ? 'async' : null;
		return '<script src="'.$path.'" '.$async.$defer.'>'.$custom.'</script>'."\n";
	}
}

function noScript($faster = 0) {
	$jstext = '<span class="jstext">JavaScript</span>';
	$section = 'This section of my site';
	$text = ($faster == 1)
		? "$section could work faster with $jstext. Seriously."
		: "$section is not working without $jstext at all";
	return
		"<noscript>\n".
		"	<h3>$text</h3><br>\n".
		"</noscript>\n";
}

function desc($str) {
	return '<meta name="description" content="'.$str.'">'."\n";
}

function auth() {
	require BASE.'/includes/auth.php';
}

function newCookie($cname, $cvalue, ?int $ctime = 30, $cpath = '/') {
	setcookie($cname, $cvalue, time() + ($ctime * 24*60*60), $cpath);
	$_COOKIE[$cname] = $cvalue;
}
function delCookie($cname, $cpath = '/') {
	setcookie($cname, '', time(), $cpath);
	unset($_COOKIE[$cname]);
}

function getJson($url, $array = 1) {
	return json_decode(trim(file_get_contents($url)), $array);
}

function api2ui() {
	if (APIDOMAIN)
		header('Location: '.PROTO.SITENAME.$_SERVER['REQUEST_URI']);
}
function apiOut($arr, $type = null, $filename = null, ?int $refresh = null, $refreshPath = null) {
	$type = $type ?? @pathinfo($arr)['extension'] ?? 'text/plain';
	
	if ($type == 'pdf')
		$type = 'application/pdf';
	elseif ($type == 'zip')
		$type = 'application/zip';
	elseif ($type == 'css')
		$type = 'text/css';

	$filename = $filename ?? @basename($arr) ?? null;

	if (@is_file($arr) && @file_exists($arr) && @is_readable($arr)) {
		if ($type == 'application/pdf')
			$readyFile = file_get_contents($arr);
	}

	if ($type == 'text/plain' && !empty($arr))
		$output = json_encode($arr, JSON_PRETTY_PRINT);
	elseif ($readyFile)
		$output = $readyFile;
	elseif (@file_get_contents($arr))
		$output = file_get_contents($arr);
	else {
		header('Content-Type: text/plain');
		die($filename.' is not uploaded');
	}
	
	if (isset($filename))
		$filename = "; filename=$filename";
	
	if (isset($refreshPath))
		$refreshPath = '; url='.$refreshPath;
	if (isset($refresh))
		$refresh = header('Refresh: '.$refresh.$refreshPath);
	
	if ($output) {
	header("Content-Type: $type; charset=UTF-8");
	header('Content-Length: ' . strlen($output));
	header("Content-Disposition: inline$filename");
	header('Cache-Control: private, max-age=0, must-revalidate');
	$refresh;
	header('Pragma: public');
	header('Access-Control-Allow-Origin: *');

	ini_set('zlib.output_compression', '0');
	exit($output);
	}
}

function varName(&$var) {
	$ret = '';
	$tmp = $var;
	$var = md5(uniqid(rand(), TRUE));

	$key = array_keys($GLOBALS);
	foreach ( $key as $k )
		if ( $GLOBALS[$k] === $var ) {
			$ret = $k;
			break;
		}

	$var = $tmp;
	return $ret;
}

function autoArr(...$params) {
	foreach ($params as $par) {
		global $$par; // on error: replace all $$par with ${"$par"}
		if (isset( $$par ))
			$arr[$par] = $$par;
	}
	return $arr;
}

function emptyErr() {
	function warnHandler($errno, $errstr) {}
	set_error_handler('warnHandler', E_WARNING);
}

if (!function_exists('array_key_last')) {
	function array_key_last(array $arr) {
		return array_keys($arr)[count($arr) - 1];
	}
}
function array_value_last(array $arr) {
	return $arr[array_key_last($arr)];
}