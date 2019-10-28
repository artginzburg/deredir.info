<?php
require_once dirname($_SERVER['DOCUMENT_ROOT']).'/const.php';
emptyErr();

function allIp() {
	return $_SERVER['HTTP_CLIENT_IP'] // shared client
		?: $_SERVER['HTTP_X_FORWARDED_FOR'] // proxy
		?: $_SERVER['REMOTE_ADDR'];
}

$ip = allIp();
$geo = geoip_record_by_name($ip);

function get_headers_with_stream_context($url, $context, $assoc = 1) {
    $fp = fopen($url, 'r', null, $context);
    $metaData = stream_get_meta_data($fp);
    fclose($fp);

    $headerLines = $metaData['wrapper_data'];

    if (!$assoc)
    	return $headerLines;

    $headers = array();

    $lastStat = 0;
   
    foreach ($headerLines as $line) {
        if (strpos($line, 'HTTP') === 0) {
            $headers[$lastStat] = $line;
            $lastStat++;
            continue;
        }

        list($key, $value) = explode(': ', $line);
        $headers[$key][] = $value;
    }

    return $headers;
}

$userReq = trim(htmlspecialchars(strip_tags($_GET['url'] ?? $_SERVER['QUERY_STRING'])));
$req = $userReq ?: 'http://facebook.com';

$startTime = microtime(1);
$keys = array_keys(
	$header = parse_url($req, PHP_URL_SCHEME)
		? get_headers($req, 1)
		: (get_headers('https://'.$req, 1)
			? get_headers($req = 'https://'.$req, 1)
			: get_headers($req = 'http://'.$req, 1))
);
$fullTime = microtime(1) - $startTime;

// suxk.com (463)
// facebook.com (unsupported browser)
if (isset($_GET['context'])) {
	$context = stream_context_create( array(
		'http' => array(
			'method' => 'GET',
			'header' =>
				"Accept-language: en\r\n" .
				"Cookie: fqtr=iam\r\n" .
				($_GET['context'] === 'my') ? "User-agent: ".$_SERVER['HTTP_USER_AGENT'] : "User-agent: Safari Google fqtr\r\n"
		)
	));
	$header = get_headers_with_stream_context($req, $context);
	$keys = array_keys($header);
}

foreach (array_filter($keys, 'is_int') as $int)
	$status[] = trim(substr($header[$int], strpos($header[$int], ' ') + 1));

$redir = array_values($header)[array_search('location', array_map('strtolower', $keys)) ?: null];
if (!is_array($redir) && $redir)
	$redir = [$redir];

foreach ($redir as $key => &$dir) {
	$last = $key > 0
		? $redir[$key-1]
		: $req;

	$parse = parse_url($dir, PHP_URL_HOST);

	$folder = (substr($last, -1) === '/');

	if (!contains('/', $dir) && $folder)
		$dir = $last.$dir;
	elseif (!contains('/', $dir))
		$dir = dirname($last).'/'.$dir;
	elseif (!$parse)
		$dir = dirname($last).$dir;
}

$dest = $redir[sizeof($redir) - 1];
$destStartTime = microtime(1);
$destConnection = get_headers($dest);
$destTime = microtime(1) - $destStartTime;

$timesFaster = round($fullTime / $destTime);

foreach ($status as $key => $stat)
{
	$shortStat = explode(' ', $stat)[0];

	if ($shortStat === $stat)
	{
		switch ($shortStat)
		{
			case 463:
					$stat .= ' (Restricted Client)';
				break;
			case 200:
					$stat .= ' (OK)';
				break;
		}
	}

	$viewout .=	"|<br>\n".
				'<span class="status" id="s'.$shortStat.'">'."$stat</span><br>\n";
if ($key !== array_key_last($status) || (count($redir) === 1 && $redir[$key]) || (count($redir) > count($status)))
	$viewout .=	"&darr;<br>\n".
				'<a href="'.$redir[$key].'" target="_blank"><span class="'.($key !== array_key_last($status)-1 ? 'redir' : 'dest').'">'.$redir[$key]."</span></a><br>\n";
}

if (!$header)
	$error = 'invalid URL';
elseif (!$redir)
	$error = 'no redirects found';
	
if (APIDOMAIN || trim(file_get_contents('php://input')))

	apiOut(autoArr(

		'req',
		'error',
		'dest',
		'redir',
		'status',
		'shortStat'

	));

header('Content-Type: text/html; charset=UTF-8');
// deredirect.net

function preload($uri) {
	header("Link: <{$uri}>; rel=preload; as=style", false);
}

preload('/css/theme.css');
preload('/css/stylus.css');
preload('/css/redir.css');
preload('/css/dark.css');
?>

<html lang="zxx"><head>	
	<meta charset="utf-8">
	<link rel="preload stylesheet" href="/css/theme.css" as="style">
	<link rel="preload stylesheet" href="/css/stylus.css" as="style">
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=no">
	<meta name="author" content="Arthur Factor">
	<meta name="format-detection" content="telephone=no">
	<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="/icons/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/icons/favicon-16x16.png">
	<link rel="manifest" href="/icons/site.webmanifest">
	<link rel="mask-icon" href="/icons/safari-pinned-tab.svg" color="#b91d47">
	<meta name="msapplication-TileColor" content="#b91d47">
	<meta name="theme-color" content="#1c1c1c">
	<link rel="shortcut icon" type="image/png" sizes="600x600" href="/icons/favicon-600.png">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
	<!--[if lt IE 9]>
	<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.js"></script>
	<![endif]-->

	<link rel="preload stylesheet" href="/css/redir.css" as="style">
	<link rel="preload stylesheet" href="/css/dark.css" as="style">
	
	<?php
		if ($shortStat == 200 && $userReq)
			echo("<title>Detect Redirect • $userReq | Trace URLs</title>");
		else
			echo('<title>Detect Redirect | Trace URLs</title>');
	?>
	<?= desc('Unshorten and trace URLs') ?>
	<?= canonical() ?>
	<meta name="yandex-verification" content="40cfcc567bd9dc88" />
</head>

<body>

<header>
	<h1><strong>Detect</strong> Redirect</h1>
	<h3>Unshorten and trace URLs</h3>
</header>

<article class="flex">
	<form action novalidate>
		<input type="url" required inputmode="url" <?php if (!$userReq || $error) { ?>autofocus <?php } ?>placeholder="somesite.com/posts" <?php if ($userReq) { ?>value="<?= $userReq ?>" <?php } ?>autocorrect="off" autocapitalize="off" autocomplete="off" spellcheck="false" id="url" ontouchstart name="url"><br>
		<!-- <label for="context"><input type="checkbox" name="context" id="context"<?php if (isset($_GET['context'])) { ?> checked<?php } ?>>Use custom stream context</label> -->
		<button ontouchstart>Trace</button>
	</form>

	<div id="result">
		<?php if ($error) { ?>
		<div class="initBuzzOut">
			<?= "$error\n" ?>
		</div>
		<?php } ?>
	<?= '<a href="'.$req.'" target="_blank" ontouchstart><span ',((!$redir && $header) ? 'class="dest"' : 'id="req"'),">$req</span></a><br>\n" ?>
	<?= $viewout ?>
	</div>
</article><br>

<section>
	<article>
		<?php if ($timesFaster < 100 && $timesFaster > 1) { ?>
		<p><?= 'You can connect &bull; <strong title="Full time (' , round($fullTime, 2) , ') / Direct connection time (' , round($destTime, 2) , ')">' , $timesFaster , ' times faster</strong> &bull; by going directly to the <a href="' , $dest , '" target="_blank"><span class="dest">destination</span></a>' ?></p>
		<a href="https://twitter.com/share?ref_src=twsrc%5Etfw" class="twitter-share-button" data-text="It turns out that it was possible to connect to this site <?= $timesFaster ?> times faster. I wonder how much time I lost." data-url="https://deredir.info/<?= $userReq ?>" data-show-count="false">Tweet</a><script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>
		<?php } ?>
		<?php if ($_SERVER['HTTP_REFERER']) { ?>
		<p>[Test] You came from <?= $_SERVER['HTTP_REFERER'] ?></p>
		<?php } ?>
		<?php if ($geo) { ?>
		<p>[Test] Your country <?= $geo ?></p>
		<?php } ?>
	</article>
</section>

<footer>
	<a href="<?= PROTO . 'api.' . SITENAME . $_SERVER['REQUEST_URI'] ?>" id="left" ontouchstart>API</a>
	<a href="//fqtr.ga" id="right" ontouchstart>&copy; <?= date('Y') ?> Arthur Factor</a>
</footer>

</body></html>