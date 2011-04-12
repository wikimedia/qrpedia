<?php
	include "config.php";
	include "googleAnalytics.php";

	// An .htaccess file changes example.com/Foo to example.com/?title=foo
	$request = $_GET['title'];

	// Gets the phone user's primary language - based on the headers of the phone's browser
	// Code modified from http://www.php.net/manual/en/reserved.variables.server.php#94237
	// RFC 2616 compatible Accept Language Parser
	// http://www.ietf.org/rfc/rfc2616.txt, 14.4 Accept-Language, Page 104
	// Hypertext Transfer Protocol -- HTTP/1.1

	foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $lang) 
	{
		$pattern = 	'/^(?P<primarytag>[a-zA-Z]{2,8})'.
						'(?:-(?P<subtag>[a-zA-Z]{2,8}))?(?:(?:;q=)'.
						'(?P<quantifier>\d\.\d))?$/';

		$splits = array();
		
		if (preg_match($pattern, $lang, $splits)) 
		{
			$phone_language = $splits[primarytag];
			// Once the language has been found - no need to continue the loop.
			break;
		} 
	}
	
	// Get the language requested. For example fr.qrwp.org/foo assumes that /foo is French
	$requested_server = $_SERVER['SERVER_NAME'];

	if ($requested_server != $server_name) // If this has a subdomain
	{
		$pieces = explode(".", $requested_server);
		$requested_language = $pieces[0]; // Assume that only one sub domain has been chosen. "fr.en.de.qrwp.org" will return "fr"
	}
	else
	{
		$requested_language = $default_language;
	}

	// If the phone hasn't sent through a language header - set it to the requested language
	if ($phone_language == null)
	{
		$phone_language = $requested_language;
	}

	// Find the correct URL for redirection
	/*
	Wikipedia API Documentation at http://en.wikipedia.org/w/api.php
	http://en.wikipedia.org/w/api.php?action=query&
			prop=info|langlinks&  	//Get page info and alternate languages
			lllimit=200&				//Max number of languages to return
			llurl&						//Get the URLs of alternate languages
			titles=Rossetta_Stone&	//Title of the page
			redirects=&					//Page may redirect - so get the final page
			format=json					//Other formats are available. Leave off for human readable XML
	*/


	// Construct the API call - this is to the $default_language Wikipedia
	$api_call = "http://$requested_language.wikipedia.org/w/api.php?action=query&prop=info|langlinks&lllimit=200&llurl&titles=$request&redirects=&format=json";

	// Use CURL to retrieve the information
	$curl_handle=curl_init();
	curl_setopt($curl_handle,CURLOPT_RETURNTRANSFER,1);
	// Set a user agent with contact information so Wikipedia Admins can see who is using the service
	curl_setopt($curl_handle, CURLOPT_USERAGENT, $user_agent);
	curl_setopt($curl_handle,CURLOPT_URL,$api_call);
	$response = curl_exec($curl_handle);
	$response_info=curl_getinfo($curl_handle);
	curl_close($curl_handle);
	
	// Decode the JSON into an array
	$results = json_decode($response,true);

	// We need to find the ID of the page
	$page_id_array = $results['query']['pages'];
	$page_id = key($page_id_array);
	
	//If there is no $page_id it means a 404
	if ($page_id)
	{
		// Find out how many links were returned
		$links_array = $results['query']['pages'][$page_id]['langlinks'];
	
		// Itterate through the array
		for ($i = 0; $i <	count($links_array); $i++)
		{
			// Get the language
			$article_language = $results['query']['pages'][$page_id]['langlinks'][$i]['lang'];

			// If the language matches - perform the redirection		
			if ($article_language == $phone_language )
			{
				// Get the Wikipedia URL for the language
				$article_url = $results['query']['pages'][$page_id]['langlinks'][$i]['url'];
				// Get the title of the article in the foreign language
				$article_title = $results['query']['pages'][$page_id]['langlinks'][$i]['*'];

				// Quick and dirty search and replace to convert the URL into a mobile version
				$mobile_url = str_replace('.wikipedia.org', '.m.wikipedia.org', $article_url);
				writeLog($mobile_url);
				header("Location: $mobile_url");
				exit;
			}
		}
		// The article wasn't found in the array - this may be because Wikipedia doesn't return the foo URL if the request is to foo.wikipedia
		if ($default_language == $phone_language)
		{	// If the phone's language is the default language, perform a simple redirection
			$mobile_url = "http://$default_language.m.wikipedia.org/wiki/$request";
			writeLog($mobile_url);
			header("Location: $mobile_url");
			exit;
		}

		// If we can't find the phone's language - or a translation of the article - perform a search on the native language wikipedia
		$mobile_url = "http://$phone_language.m.wikipedia.org/wiki?search=$request";
		writeLog($mobile_url);
		header("Location: $mobile_url");
		exit;	
}
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width; initial-scale=1.0;"/>
		<meta name="HandheldFriendly" content="true"/>
		<link rel="stylesheet" href="style.css" type="text/css" />
		<title>QR -&gt; Wikipedia - <?php echo $response; ?></title>
	</head>
	<body>
		<h1>QR -&gt; Wikipedia! Alpha</h1>
		<?php
			if ($phone_language)
			{
				echo "Wikipedia doesn't have that article in your language ($phone_language). Try one of these...";
			}
			else
			{
				echo "We were unable to determine your language. Try one of these...";
			}
		?>
		<div class="red">
		<?php
			//If there is no $page_id it means a 404
			if ($page_id != -1)
			{
				// Because we requested an English page, English isn't listed as a translation. Adding it in for completeness
				echo 	"[$default_language] <a href='http://$default_language.m.wikipedia.org/wiki/$request'>$request</a>$page_id<br />";
				// Itterate through the array
				for ($i = 0; $i <	count($links_array); $i++)
				{
					// Get the language
					$article_language = $results['query']['pages'][$page_id]['langlinks'][$i]['lang'];
					// Get the Wikipedia URL for the language
					$article_url = $results['query']['pages'][$page_id]['langlinks'][$i]['url'];
					// Get the title of the article in the foreign language
					$article_title = $results['query']['pages'][$page_id]['langlinks'][$i]['*'];
					// Quick and dirty search and replace to convert the URL into a mobile version
					$mobile_url = str_replace('.wikipedia.org', '.m.wikipedia.org', $article_url);
				
					// Print out the languages, and link the title
					echo 	"[$article_language] <a href='$mobile_url'>$article_title</a><br />";
				}
			}
			else 
			{

			}
		?>
		</div>
		<footer>
			Site created by <a href="http://twitter.com/edent">Terence Eden</a>.
		</footer>
		<?php
			$googleAnalyticsImageUrl = googleAnalyticsGetImageUrl();
			echo '<img src="' . $googleAnalyticsImageUrl . '" />';
		?>
		</body>
</html>
