<?php
	include("SQLiteDB.php");
	$version = $_REQUEST["version"];
	if($version == "legacy")
	{
        //header('Access-Control-Allow-Origin: *');

        $flown = file("/tmp/navhome/data/path_tail.data");

        $latest_data = array();
        foreach ($flown as $entry) {
                $coords = preg_split("/ +/", $entry);
                $latest_data[] = array('latitude' => $coords[0], 'longitude' => $coords[1]);
        }

        $json = array();
        $json['path_data'] = $latest_data;

        $projected = file("/tmp/navhome/data/path_head.data");

        $projected_data = array();
        foreach ($projected as $entry) {
                $coords = preg_split("/ +/", $entry);
                $projected_data[] = array('latitude' => $coords[0], 'longitude' => $coords[1]);
        }

        $json['projected_data'] = $projected_data;

        $orig1 = file("/tmp/navhome/data/origpoint.data");

        foreach ($orig1 as $entry) {
                $coords = preg_split("/ +/", $entry);
                $orig1_data = array('latitude' => $coords[0], 'longitude' => $coords[1]);
        }

        $json['orig1'] = $orig1_data;

        $orig2 = file("/tmp/navhome/data/origpoint2.data");

        foreach ($orig2 as $entry) {
                $coords = preg_split("/ +/", $entry);
                $orig2_data = array('latitude' => $coords[0], 'longitude' => $coords[1]);
        }

        $json['orig2'] = $orig2_data;

        $dest1 = file("/tmp/navhome/data/destpoint.data");

        foreach ($dest1 as $entry) {
                $coords = preg_split("/ +/", $entry);
                $dest1_data = array('latitude' => $coords[0], 'longitude' => $coords[1]);
        }

        $json['dest1'] = $dest1_data;

        $dest2 = file("/tmp/navhome/data/destpoint2.data");

        foreach ($dest2 as $entry) {
                $coords = preg_split("/ +/", $entry);
                $dest2_data = array('latitude' => $coords[0], 'longitude' => $coords[1]);
        }

        $json['dest2'] = $dest2_data;

        $orig1_iata = file("/tmp/navhome/data/origIATA.data");

        foreach ($orig1_iata as $entry)
        {
                $data = str_replace('TEXT', "", $orig1_iata);
                $replacements = array("'", "\n", " ");
                $revised = str_replace($replacements, '', $data);
                $orig1_iata_data = array('iata' => $revised[0]);
        }

        $json['orig1_iata_code'] = $orig1_iata_data;


        $dest1_iata = file("/tmp/navhome/data/destIATA.data");

        foreach ($dest1_iata as $entry)
        {
                $data = str_replace('TEXT', "", $dest1_iata);
                $replacements = array("'", "\n", " ");
                $revised = str_replace($replacements, '', $data);
                $dest1_iata_data = array('iata' => $revised[0]);
        }

        $json['dest1_iata_code'] = $dest1_iata_data;

        $orig2_iata = file("/tmp/navhome/data/origIATA2.data");

        foreach ($orig2_iata as $entry)
        {
                $data = str_replace('TEXT', "", $orig2_iata);
                $replacements = array("'", "\n", " ");
                $revised = str_replace($replacements, '', $data);
                $orig2_iata_data = array('iata' => $revised[0]);
        }

        $json['orig2_iata_code'] = $orig2_iata_data;

        $flight_db = new SQLiteDB("/tmp/navhome/navigator.db");

        $flight_query = "SELECT latitude,longitude,heading from flightdata LIMIT 1";

        $flight_result = $flight_db->queryDB($flight_query);

        foreach($flight_result as $flight_row)
        {
        $json['plane_data'] = array('latitude' => $flight_row["latitude"], 'longitude' => $flight_row["longitude"], 'heading' => $flight_row["heading"]);
        }

        echo json_encode($json);

	}
	else 	
	{
	$json = array();

	//CURRENT AIRCRAFT LOCATION
	$flight_db = new SQLiteDB("/tmp/navhome/flightdata.db");

	$flight_query = "SELECT latitude,longitude,heading from FlightData LIMIT 1";

	$flight_result = $flight_db->queryDB($flight_query);

	foreach($flight_result as $flight_row)
	{
	$json['plane_data'] = array('latitude' => $flight_row["latitude"], 'longitude' => $flight_row["longitude"], 'heading' => $flight_row["heading"]);
	}

	//ORIGINS
	$orig_db = new SQLiteDB("/tmp/navhome/origin_point.db");
	$orig_query = "SELECT WKT_GEOMETRY from point";
	$orig_result = $orig_db->queryDB($orig_query);

	$needles = array("POINT(", ")");
	$i = 1;
        foreach ($orig_result as $entry) {
		
		list($long1, $lat1) = preg_split("/ /", $entry["WKT_GEOMETRY"]);
		$lat1 = str_replace($needles, "", $lat1);
		$long1 = str_replace($needles, "", $long1);
		
		$json['orig'.$i] = array('longitude' => $lat1, 'latitude' => $long1);
		$i++;
	}

	//DESTINATIONS
        $dest_db = new SQLiteDB("/tmp/navhome/destination_point.db");
        $dest_query = "SELECT WKT_GEOMETRY from point";
        $dest_result = $dest_db->queryDB($dest_query);

        $needles = array("POINT(", ")");
        $i = 1;
        foreach ($dest_result as $entry) {
                
                list($long1, $lat1) = preg_split("/ /", $entry["WKT_GEOMETRY"]);
                $lat1 = str_replace($needles, "", $lat1);
                $long1 = str_replace($needles, "", $long1);
                
                $json['dest'.$i] = array('longitude' => $lat1, 'latitude' => $long1);
                $i++;
        }

	//ORIGIN IATA
	$orig_iata = new SQLiteDB("/tmp/navhome/origin_point.db");
        $orig_iata_query = "SELECT IATA from point";
        $orig_iata_result = $orig_iata->queryDB($orig_iata_query);
	foreach ($orig_iata_result as $entry) {
		$orig_iata_code = array('iata' => $entry[0]);		
	}
	$json['orig1_iata_code'] = $orig_iata_code;
	$json['orig2_iata_code'] = $orig_iata_code;

	//DESTINATION IATA
        $dest_iata = new SQLiteDB("/tmp/navhome/destination_point.db");
        $dest_iata_query = "SELECT IATA from point";
        $dest_iata_result = $dest_iata->queryDB($dest_iata_query);
        foreach ($dest_iata_result as $entry) {
                $dest_iata_code = array('iata' => $entry[0]);
        }
        $json['dest1_iata_code'] = $dest_iata_code;
        //$json['dest2_iata_code'] = $dest_iata_code;

	//WHERE AIRCRAFT HAS TRAVELLED
	$flown = new SQLiteDB("/tmp/navhome/flightpath2.db");
        $flown_query = "select longitude, latitude from History";
        $flown_result = $flown->queryDB($flown_query);
        foreach ($flown_result as $entry) {
		$lat = $entry["latitude"];
		$long = $entry["longitude"];
		$combined[] = array('longitude' => $lat, 'latitude' => $long);
        }

	$json['path_data'] = $combined;
	
	//WHERE AIRCRAFT WILL TRAVEL
	$projected = new SQLiteDB("/tmp/navhome/flightpath.db");
        $projected_query = "select wkt_geometry from PathheadActualLine";
        $projected_result = $projected->queryDB($projected_query);
	$replacements = array("LINESTRING(", ")");
        foreach ($projected_result as $entry) {
		$wkt_geo = $entry["WKT_GEOMETRY"];
		$wkt_geo_replaced = str_replace($replacements, "", $wkt_geo);
		$latlong_array = preg_split("/, /", $wkt_geo_replaced);
        }

	foreach($latlong_array as $latlong)
	{
		$latlong_vals = preg_split("/ /", $latlong);
                $projected_path[] = array('longitude' => $latlong_vals[1], 'latitude' => $latlong_vals[0]);
	}

	$json['projected_data'] = $projected_path;

	echo json_encode($json);
	}
?>
