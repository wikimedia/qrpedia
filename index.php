<?php
	include "config.php";
	
	function getLanguageNameFromCode($code)
	{
		global $mySQL_username;
		global $mySQL_password;
		global $mySQL_database;
	
		//	Connect to database
		$mysqli = new mysqli('localhost', $mySQL_username, $mySQL_password, $mySQL_database);

		/* check connection */
		if (!mysqli_connect_errno()) 
		{
			/* change character set to utf8 */
			$mysqli->set_charset("utf8"); 
			
			/* create a prepared statement */
			if ($stmt = $mysqli->prepare("	SELECT LanguageName
															FROM lang
															WHERE `Code`=?"
												))
			{
				/* bind parameters for markers */
				$stmt->bind_param("s", $code);

				/* execute query */
				$stmt->execute();

				/* bind result variables */
				$stmt->bind_result($language_name);

				/* fetch values */
				$stmt->fetch();

				/* close statement */
				$stmt->close();
			}
			return $language_name;
		}
		else	//	If the DB is not available, display the language code
		{
			return $code;
		}
	}	

	// An .htaccess file changes example.com/Foo to example.com/?title=foo
	// Remove any escaped characters. Eg \'
	$request = stripslashes($_GET['title']);

	// If a request has been sent - redirect the user	
	if ($request != null)
	{
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
		else	//	If there is no sub domain, use the default language set in config.php
		{
			$requested_language = $default_language;
		}

		// If the phone hasn't sent through a language header - set it to the requested language
		if ($phone_language == null)
		{
			$phone_language = $requested_language;
		}
		
		//	If the phone's language is the same as the requested language (eg en-gb & en.qrwp) do the redirection without a call to Wikipedia 
		if ($phone_language == $requested_language)
		{
			$mobile_url = "http://$requested_language.m.wikipedia.org/wiki/$request";
			$mobile_url = utf8_decode($mobile_url);
			writeLog($mobile_url);
			header("Location: $mobile_url");
			exit;
		}

		// Find the correct URL for redirection
		/*
		Wikipedia API Documentation at http://en.wikipedia.org/w/api.php
		http://en.wikipedia.org/w/api.php?action=query&
				prop=info|langlinks&		//	Get page info and alternate languages
				lllimit=200&				//	Max number of languages to return
				llurl&						//	Get the URLs of alternate languages
				titles=Rossetta_Stone&	//	Title of the page
				redirects=&					//	Page may redirect - so get the final page
				format=json					//	Other formats are available. Leave off for human readable XML
		*/

		// Construct the API call - this is to the $requested_language Wikipedia
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
		if ($page_id != -1)
		{
			// Find out how many links were returned
			$links_array = $results['query']['pages'][$page_id]['langlinks'];
	
			// Itterate through the array
			for ($i = 0; $i <	count($links_array); $i++)
			{
				// Get the language
				$article_language = $results['query']['pages'][$page_id]['langlinks'][$i]['lang'];

				// If the language matches - perform the redirection
								//	Catalan Fix
				// Catalan isn't well supported on the phone.  Many phones don't have it as an option.
				//	If the URL is ca.qrwp then we do the following
				// 	If the phone is set to CA - send the article
				//		Else, show the language select screen
		
				if ( ( ($article_language == $phone_language) && ($requested_language != "ca") ) )
				{
					// Get the Wikipedia URL for the language
					$article_url = $results['query']['pages'][$page_id]['langlinks'][$i]['url'];
				
					// Quick and dirty search and replace to convert the URL into a mobile version
					$mobile_url = str_replace('.wikipedia.org', '.m.wikipedia.org', $article_url);
					$mobile_url = utf8_decode($mobile_url);
					writeLog($mobile_url);
					header("Location: $mobile_url");
					exit;
				}
			}
		}
		else	//	404
		{
			//	Something has gone wrong
			//	The article was not found in the requested Wikipedia.
			// For example, en.qrwp.org/Llibre_de_Domesday  (English request to a Catalan page)
			// This means either
			// 1) The page has been removed
			// 2) Whoever made the QRpedia code made a mistake
			//	This is a pretty rare edge case
			// Write 404 into the log
			//	Send them to their native Wikipedia so they can search for themselves
			
			//writeLog("404");
			//header("Location: http://$phone_language.m.wikipedia.org/");
			//exit;
		}
		
		
		//Minority Language / Missing Language

		//	An html list of articles - for use if a translation can't be found
		// The first in the list will be the article in the requested languge
		$article_list .= "<li>" . getLanguageNameFromCode($requested_language) . " - <a href=\"http://$requested_language.m.wikipedia.org/wiki/$request\">$request</a></li>\n";
		
		// Find out how many links were returned
		$links_array = $results['query']['pages'][$page_id]['langlinks'];

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
			
			//	Get the Language name
			$language_name = getLanguageNameFromCode($article_language);
		
			//	Add to the HTML list
			$article_list .= "<li>$language_name - <a href=\"$mobile_url\">$article_title</a></li>\n";
		}
		
		//	Print a list of the available articles and let the user choose
		header('Content-Type: text/html;charset=utf-8'); //	We're returning lots of unicode, let's make sure the browser knows that
			
		//	HTML5 FTW!
		echo "<!DOCTYPE html>
				<html>
					<head>
						<meta charset=utf-8 />
						<meta name=\"viewport\" content=\"width=device-width; initial-scale=1.0;\" />
						<title>QRpedia Language Selection for $request</title>
					</head>
					<body>";
		
		//	Catalan Fix
		// Catalan isn't well supported on the phone.  Many phones don't have it as an option.
		//	If the URL is ca.qrwp then we do the following
		//		Show the language select screen
		
		if ($requested_language == "ca" && $phone_language != "ca")
		{
			echo "CA: En quin idioma vols llegir aquest article?<br />
					ES: ¿En qué idioma quieres leer este artículo?<br />
					EN: Which language would you like to read this article in?<br />";
		}
		else
		{
			echo	"Sorry, but Wikipedia does not have the page \"$request\" in your language ($phone_language).<br />\n";
			echo	"Please try reading the <a href=\"http://translate.google.com/translate?tl=$phone_language&u=http%3A%2F%2F$requested_language.m.wikipedia.org%2Fwiki%2F$request\">Google auto-translated version</a>.
					<br />
					<br />
					Or read in one of the following languages:
					<br />\n";
		}
			
		echo	"<ul>
					$article_list
				</ul>";
		echo "</body>
			</html>";
			
		writeLog("NA");
			
		exit;
	}

	// No request was sent - send them to the main page
	header("Location: http://qrpedia.org/");
	exit;
