<?php
	
	include "config.php";								

	//	Connect to database
	$mysqli = new mysqli(localhost,$mySQL_username,$mySQL_password,$mySQL_database);


	mysql_connect(localhost,$mySQL_username,$mySQL_password);
	@mysql_select_db($mySQL_database) or die( "Unable to select database");

	$path = null;
	
	
	//	The initial query
	$query = "	SELECT *
					FROM `stats`";

	
	if ($_GET["path"])
	{
		$path = mysql_real_escape_string($_GET["path"]);
		$query .= " WHERE `Path` LIKE '{$path}'";
	}


	$result = $mysqli->query($query); //mysql_query($query);

	$num_fields = $mysqli->field_count; //_num_fields($result);	

	$headers = array();

	for ($i = 0; $i < $num_fields; $i++) {
		$headers[] = mysqli_fetch_field($result)->name;//$mysqli->fetch_field();//mysql_field_name($result , $i);
	}

	$fp = fopen('php://output', 'w');
	if ($fp && $result) {
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="'.$path.' QRpedia Statistics.csv"');
		header('Pragma: no-cache');
		header('Expires: 0');
		fputcsv($fp, $headers);
		while ($row = $result->fetch_array(MYSQLI_NUM)) {
			fputcsv($fp, array_values($row));
		}
	die;
}

