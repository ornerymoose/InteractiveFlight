<?php 
include("SQLiteDB.php");
$version = (isset($_REQUEST ["version"])) ? $_REQUEST ["version"] : "default"; 

//CREATE DATABASE OBJECTS
if($version == "legacy") {
	$flight_db = new SQLiteDB("/tmp/navhome/navigator.db");
}
else {
	$flight_db = new SQLiteDB("/tmp/navhome/flightdata.db");
}

//DATABASE QUERY TO RETURN AIRCRAFT LOCATION
$flight_query = "SELECT latitude,longitude,heading from FlightData LIMIT 1";
$flight_result = $flight_db->queryDB($flight_query);
$json = array("plane_data");
if(is_object($flight_result))
{
	foreach($flight_result as $flight_row)
	{
		$json['plane_data'] = array('latitude' => $flight_row["latitude"], 'longitude' => $flight_row["longitude"], 'heading' => $flight_row["heading"]);
	}
}
?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<script src="/jquery-1.6.2.js"></script>
<script src="http://maps.google.com/maps/api/js?v=3.2&sensor=false"></script>
<script src="/OpenLayers-2.13.1/OpenLayers.js"></script>
<script type="text/javascript">
    var version = "<?php echo $version; ?>";
    var initial_json_plane_data_longitude = <?php echo $json['plane_data']['longitude']; ?>;
    var initial_json_plane_data_latitude = <?php echo $json['plane_data']['latitude']; ?>;
</script>
<script src="interactive_swa01.js"></script>

<link rel="stylesheet" type="text/css" href="interactive_swa01.css">
</head>
<body onload="init(), getSwaPoints(), getOrigAndDest();">
<div id="map"></div></div>

<input type="button" class="center-aircraft-button" title="Center on Aircraft" name="airplane" value="Center on Aircraft" onClick="setCenterOnAircraft()">

<div class="followPlaneState"></div>

</body>
</html>

