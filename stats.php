<?php
	
/*
		SELECT * 
		FROM stats
		WHERE `Datetime` between '2011-08-09' and '2011-08-10'


*/
	include "config.php";								

	//	Connect to database
	mysql_connect(localhost,$mySQL_username,$mySQL_password);
	@mysql_select_db($mySQL_database) or die( "Unable to select database");
	
	// UA to search for
	$user_agents = array("iPhone",	"iPad",	"iPod",	"Android",	"Nokia",		"BlackBerry",	"Opera",		"Windows Phone OS 7",	"MSIE 6",	"Bada");

	$path = null;
	
	if ($_GET["path"])
	{
		$path = mysql_real_escape_string($_GET["path"]);
		$path_query = "AND `Path` LIKE '{$path}'";
	}

	//	The initial query
	$query = "	SELECT COUNT(UA)
					FROM `stats`
					WHERE `UA` LIKE ";

	//	Get the totals
	if ($path)
	{
		$built_query = $query . "'%' " . $path_query;
	}
	else
	{
		$built_query = $query . "'%'";
	}

	$Total_result = mysql_query($built_query);
	$Total_row = @mysql_fetch_array($Total_result);
	$Total_count = $Total_row['COUNT(UA)'];

	// A running subtotal
	$subtotal = 0;

	$pie_rows = "";

	//	Itterate through the array and perform the query
	foreach ($user_agents as $ua) 
	{
		if ($path)
		{
			$built_query = $query . "'%" . $ua . "%' " . $path_query;
		}
		else
		{
			$built_query = $query . "'%" . $ua . "%'";
		}
		$result = mysql_query($built_query);
		$row = @mysql_fetch_array($result);
		$count = $row['COUNT(UA)'];

		$percent = round((($count / $Total_count) * 100), 1);
		$pie_rows .= "['" . $ua . "', " . $percent . "],";
		$subtotal += $count;
	}

	//	All the other UAs not covered in the $user_agents array
	$Others_count = $Total_count - $subtotal;
	
	if ($Others_count < 0)	//	Something screwy is going on...
	{
		$Others_count = 0;
	}

	$Others_percent = round((($Others_count / $Total_count) * 100),1);

	$pie_rows .= "['Others', " . $Others_percent . "]";
	
	
	//	Get Top 10 QRpedia code destinations
	// Exclude qrpedia
	$query = "	SELECT Path, COUNT( * )
					FROM stats
					WHERE `Path` NOT LIKE 'qrpedia'
					GROUP BY Path
					ORDER BY COUNT( * ) DESC
					LIMIT 10"; 

	$result = mysql_query($query);

	
	//	Populate the graph request
	while($row = mysql_fetch_array($result))
	{
		$bar_rows .= "['" . urlencode($row['Path']) . "', " . $row['COUNT( * )'] . "],";
	}	
	
	//	Format the data correctly
	$bar_rows = trim($bar_rows,",");
	
	if ($_GET['path'])
	{
		$path = mysql_real_escape_string($_GET['path']);
	
		$query = "	SELECT Destination, COUNT( Destination )
						FROM stats
						WHERE `Path` LIKE '" . $path ."'
						GROUP BY Destination
						ORDER BY COUNT( * ) DESC";

		$result = mysql_query($query);
		
		$daily_query = "	SELECT COUNT( * ), DATE(`Datetime`) as scan_day
									FROM stats
									WHERE `Path` LIKE '" . $path . "'
									GROUP BY scan_day";
									
		$daily_result = mysql_query($daily_query);
	

		$table = 	"<table border=\"1\">
							<thead>
								<tr>
									<th>Language</th>
									<th>Visits</th>
								</tr>
							</thead>
							<tbody>"	;
		//	Populate the graph request
		while($row = mysql_fetch_array($result))
		{
			$dest = htmlspecialchars($row['Destination']);
			$lang_code = substr($row['Destination'], 7,2);
			if ($dest == "NA")
			{
				$dest = "";
				$lang_code = "N/A";
			}
		
			$table .= 		"<tr><td><a href=\"$dest\">$lang_code</a></td><td>" . $row['COUNT( Destination )'] . "</td></tr>";
		}	
		$table .= 		"</tbody>
						</table>";
	

		$daily_table = "	<table border=\"1\">
									<thead>
										<tr>
											<th>Date</th>
											<th>Visits</th>
										</tr>
									</thead>
									<tbody>"	;

		//	Populate the daily graph request
		$daily_js = "	daily_data.addColumn('string', 'Day');\n
							daily_data.addColumn('number', 'Visits');\n";
	
		$daily_table = "	var daily_table = new google.visualization.DataTable();\n
								daily_table.addColumn('string', 'Date');\n
								daily_table.addColumn('number', 'Visits');\n";

	
		while($row = mysql_fetch_array($daily_result))
		{
			$daily_js .= "daily_data.addRow([\"" 
							. $row['scan_day'] 
							. "\"," 
							. $row['COUNT( * )'] 
							. "]);\n";
							
			//$daily_table .= "		<tr><td>" . $row['scan_day'] . "</td><td>" . $row['COUNT( * )'] . "</td></tr>";
			$daily_table .= "daily_table.addRow([\"" 
							. $row['scan_day'] 
							. "\"," 
							. $row['COUNT( * )'] 
							. "]);\n";
		}		
	}
	else
	{
		$daily_query = "SELECT COUNT( * ), DATE(`Datetime`) as scan_day
									FROM stats
									GROUP BY scan_day";
									
		$daily_result = mysql_query($daily_query);
		
		//	Populate the daily graph request
		$daily_js = "	daily_data.addColumn('string', 'Day');\n
							daily_data.addColumn('number', 'Visits');\n";
	
		$daily_table = "	var daily_table = new google.visualization.DataTable();\n
								daily_table.addColumn('string', 'Date');\n
								daily_table.addColumn('number', 'Visits');\n";

	
		while($row = mysql_fetch_array($daily_result))
		{
			$daily_js .= "daily_data.addRow([\"" 
							. $row['scan_day'] 
							. "\"," 
							. $row['COUNT( * )'] 
							. "]);\n";
							
			//$daily_table .= "		<tr><td>" . $row['scan_day'] . "</td><td>" . $row['COUNT( * )'] . "</td></tr>";
			$daily_table .= "daily_table.addRow([\"" 
							. $row['scan_day'] 
							. "\"," 
							. $row['COUNT( * )'] 
							. "]);\n";
		}	
	
	}
?>
<!DOCTYPE HTML>
<html lang="en">
	<head>
		<meta charset=utf-8>
		<title>QRpedia Statistics</title>
		<!--Load the AJAX API-->
		<script type="text/javascript" src="https://www.google.com/jsapi"></script>
		<script type="text/javascript">

			// Load the Visualization API and the piechart package.
			google.load('visualization', '1.0', {'packages':['corechart','table']});

			// Set a callback to run when the Google Visualization API is loaded.
			google.setOnLoadCallback(drawChart);

			// Callback that creates and populates a data table, 
			// instantiates the pie chart, passes in the data and
			// draws it.
			function drawChart() 
			{
				// Create the data table.
				var pie_data = new google.visualization.DataTable();
				pie_data.addColumn('string', 'Topping');
				pie_data.addColumn('number', 'Slices');
				pie_data.addRows([
					<?php echo $pie_rows; ?>
				]);

				// Set chart options
				var pie_options = 	{	'title':<?php
																if ($_GET["path"])
																{
																	echo "'Breakdown of Devices Accessing " . htmlspecialchars(mysql_real_escape_string($_GET['path'])) . "'";
																}
																else
																{
																	echo "'Popular Devices on QRpedia'";
																}
														?>,
										'width':650,
										'height':400
									};

				// Instantiate and draw our chart, passing in some options.
				var pie_chart = new google.visualization.PieChart(document.getElementById('pie_chart_div'));
				pie_chart.draw(pie_data, pie_options);

				// Create the data table.
				var bar_data = new google.visualization.DataTable();
				bar_data.addColumn('string', 'Destination');
				bar_data.addColumn('number', 'Visits');
				bar_data.addRows([
					<?php echo $bar_rows; ?>
				]);

				// Set chart options
				var bar_options =	{	'title':'Top 10 Destinations on QRpedia',
										'width':650,
										'height':400
									};
				// Instantiate and draw our chart, passing in some options.
				var bar_chart = new google.visualization.BarChart(document.getElementById('bar_chart_div'));
				bar_chart.draw(bar_data, bar_options);	
			}
						
			function drawDailyChart()
			{
				// Daily Chart
				var daily_data = new google.visualization.DataTable();
				<?php
					echo $daily_js;
				?>
				var chart = new google.visualization.LineChart(document.getElementById('daily_div'));
				<?php
					echo "chart.draw(daily_data, {width: 600, height: 300, title: 'Daily Visits to " . htmlspecialchars(mysql_real_escape_string($_GET['path'])) . "'});";
				?>
			}

			function drawDailyTable()
			{
			
				<?php
					echo $daily_table;
				?>
				// Create and draw the visualization.
				dailyTable = new google.visualization.Table(document.getElementById('daily_table_div'));
				dailyTable.draw(daily_table, {width: 150, height: 2048, title: 'Daily Table'});

			}
			<?php
				echo "google.setOnLoadCallback(drawDailyChart);\n";
				echo "google.setOnLoadCallback(drawDailyTable);";
			?>
		</script>
	</head>
	<body>
		<?php
			$path = mysql_real_escape_string($_GET['path']);
			echo "<h2>Total Requests for qrwp.org/" . htmlspecialchars($path). "</h2>";
			echo "<div id=\"destination_table_div\">";
			echo $table;
			echo "</div>";

			echo "<h2>Daily Requests for qrwp.org/" . htmlspecialchars($path). "</h2>";
			echo "<div id=\"daily_table_div\" style=\"float:left\">";
			echo "</div>";
			echo "<div id=\"daily_div\" style=\"float:left\"></div>";

			echo "<div id=\"pie_chart_div\" style=\"float:right\"></div>";

			if (!$_GET['path']) 
			{
				echo "<h2>Total QRpedia Statistics</h2>";
				echo "<div id=\"bar_chart_div\"></div>";
			}
		?>
	</body>
</html>
