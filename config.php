<?php
	// User Agent for API Requests
	$user_agent = "";

	// Server Name
	$server_name = "";

	// Default Language
	$default_language = "en";
	
	function writeLog($Redirected_URL)
	{
		// Write a log file entry for each visitor
		$myFile = "log.txt";
		$fh = fopen($myFile, 'a+');
		// Tab separated. Date/Time	User Agent	IP Address	Language	Server requested	Page requested
		$stringData = date("d/m/y H:i:s") . "\t" . $_SERVER['HTTP_USER_AGENT'] . "\t" . $_SERVER["REMOTE_ADDR"] . "\t" . $_SERVER['HTTP_ACCEPT_LANGUAGE'] . "\t" . $_SERVER['SERVER_NAME']. "\t" .  $_GET['title'] . "\t" . $Redirected_URL . "\n";
		fwrite($fh, $stringData);
		fclose($fh);
		
		//	Add logging to MySQL database
		$mySQL_username="";
		$mySQL_password="";
		$mySQL_database="";

		mysql_connect(localhost,$mySQL_username,$mySQL_password);
		@mysql_select_db($mySQL_database) or die( "Unable to select database");

		$query = "INSERT INTO `".$mySQL_database."`.`stats` (`Datetime`, `UA`, `IP`, `Languages`, `Domain`, `Path`, `Destination`) VALUES ("
					. "'" . date("y-m-d H:i:s") 
					. "', '" . $_SERVER['HTTP_USER_AGENT'] 
					. "', '" . $_SERVER["REMOTE_ADDR"]
					. "', '" . $_SERVER['HTTP_ACCEPT_LANGUAGE']
					. "', '" . $_SERVER['SERVER_NAME']
					. "', '" . $_GET['title']
					. "', '" . $Redirected_URL
					. "');";

		$result = mysql_query($query);
		
	}
?>
