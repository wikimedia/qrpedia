<?php
	// Write a log file entry for each visitor
	$myFile = "log.txt";
	$fh = fopen($myFile, 'a+');
	// Tab separated. Date/Time	User Agent	IP Address	Language	Page requested
	$stringData = date("d/m/y H:i:s") . "\t" . $_SERVER['HTTP_USER_AGENT'] . "\t" . $_SERVER["REMOTE_ADDR"] . "\t" . $_SERVER['HTTP_ACCEPT_LANGUAGE'] . "\t" .  $request . "\n";
	fwrite($fh, $stringData);
	fclose($fh);
?>
