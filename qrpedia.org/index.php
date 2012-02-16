<?php
if (isset($_REQUEST['url']))
{
	include "article.php";
	
	exit;
}
else if (isset($_REQUEST['download_qr']))
{
	include "download_qr.php";
	
	exit;
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">

<html>

<head>
	<title>QRpedia - Language-detecting &amp; mobile-friendly Wikipedia QR codes</title>
	<style type="text/css">
		@import 'themes/world/style.css';
	</style>
	<script type="text/javascript" src="js/prototype.js"></script>
	<script type="text/javascript" src="js/core.js"></script>
	<meta name="viewport" content="width=420px; initial-scale=0.75">
	<meta name="google-site-verification" content="7f_u6a3kKrqMc3YgClqAplBfQ4Cd0-Edab7oKzNrVQ0" />
</head>

<body>
<div id="wrapper">
	<div id="inner_wrapper">
		<div id="title">
			<a href="http://en.wikipedia.org/wiki/QRpedia" target="_blank" class="title">What Is QRpedia?</a>
		</div>
		<div id="panel">
			<div id="panel_content">
				<div id="qr_area">
					<div id="qr_image">
						<div id="image">
							Paste a Wikipedia URL into the box below to create a language-detecting, mobile-friendly QR code
						</div>
					</div>
				</div>
				<div id="url_area">
					<input id="url" type="text" value="" autofocus>
					<div id="url_overlay">
						<div class="cover left"></div>
						<div class="cover right"></div>
					</div>
				</div>
			</div>
		</div>
		<div id="title"><a href="http://qrpedia.org/blog/" target="_blank">Blog</a> - <a href="http://qrpedia.org/blog/privacy/" target="_blank">Privacy</a> - <a href="http://qrpedia.org/blog/credits/" target="_blank">Credits</a></div>
	</div>
</div>
<script type="text/javascript">
var _gaq = _gaq || [];
_gaq.push(['_setAccount', 'UA-22457304-1']);
_gaq.push(['_trackPageview']);

(function() {
 var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
 ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
 var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
})();
</script>
</body>

</html>
