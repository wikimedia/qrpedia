<?php
	// User Agent for API Requests
	$user_agent = "";

	// Server Name
	$server_name = "";

	// Default Language
	$default_language = "en";
	//	Add logging to MySQL database
	$mySQL_username="";
	$mySQL_password="";
	$mySQL_database="";	

	function writeLog($Redirected_URL)
	{
		//	Add logging to MySQL database
		$mySQL_username="";
		$mySQL_password="";
		$mySQL_database="";

		//	Connect to database
		$mysqli = new mysqli('localhost', $mySQL_username, $mySQL_password, $mySQL_database);
	
		/* check connection */
		if (!mysqli_connect_errno()) 
		{
			/* create a prepared statement */
			if ($stmt = $mysqli->prepare(							 
					"INSERT INTO `stats`	(`Datetime`,	`UA`,	`IP`,	`Languages`,	`Domain`,	`Path`,	`Destination`) 
									VALUES 	(?,				?,		?,		?,				 	?,				?,			?)"
												)
				)
			{		
				$stmt->bind_param('sssssss', 
												$datetime, 		$ua, 	$ip, 	$languages, 	$domain, 	$path, 	$destination);

				$datetime = date("Y-m-d H:i:s");
				$ua = $_SERVER['HTTP_USER_AGENT'];
				$ip = $_SERVER["REMOTE_ADDR"];
				$languages = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
				$domain = $_SERVER['SERVER_NAME'];
				$path = stripslashes($_GET['title']);
				$destination = $Redirected_URL;
		
				/* execute prepared statement */
				$stmt->execute();

				/* close statement and connection */
				$stmt->close();
				
				// Write a log file entry for each visitor
				$myFile = "log.txt";
				$fh = fopen($myFile, 'a+');
				// Tab separated. Date/Time	User Agent	IP Address	Language	Server requested	Page requested
				$stringData = $datetime . "\t" . $ua . "\t" . $ip . "\t" . $languages . "\t" . $domain . "\t" .  $path . "\t" . $destination . "\n";
				fwrite($fh, $stringData);
				fclose($fh);
			}
		}
	}
?>
