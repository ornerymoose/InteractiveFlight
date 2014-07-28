var map, SWA_airports_wfs, vectors, o1 = "", o2 = "", d1 = "", d2 = "",  point = "", airplane = "", tail_path = "", projected_path = "", vectors_projected_path = "", vectors_flown_path = "", vectors_origin = "", vectors_destination = "", json;


function jump(lat,lon) {
        var zoom=map.getZoom();
        var lonLat = new OpenLayers.LonLat( lon, lat ).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject());
        map.setCenter (lonLat, zoom);
}
function jump_to_max_zoom(lat,lon) {
        var lonLat = new OpenLayers.LonLat( lon, lat ).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject());
        map.setCenter (lonLat, 15);
}
function toggleCheckmark() {
        $('.followPlaneState').toggleClass('checkmark');
        planeMovement();
}
function setCenterOnAircraft() {
        $.ajax({
                url: "flight_information.php",
                data: "version="+version,
                type: "POST",
                success: function(msg){
                        json = eval('('+msg+')');
                        var global_zoom = map.getZoom();
                        var lonLat = new OpenLayers.LonLat(json.plane_data.longitude, json.plane_data.latitude).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject());
                        map.setCenter (lonLat, global_zoom);
                }
        });
}

function getOrigAndDest() {
	$.ajax({
		url: "flight_information.php",
		data: "version="+version,
		type: "POST",
		//error: function(msg) { alert('wrong server'); },
		success: function(msg){
			
			json = eval('('+msg+')');
			var orig1lat = json.orig1.latitude;
			var orig1long = json.orig1.longitude;
			var orig2lat = json.orig2.latitude;
			var orig2long = json.orig2.longitude;

			var dest2lat = json.dest1.latitude;
			var dest2long = json.dest1.longitude;
			var dest1lat = json.dest2.latitude;
			var dest1long = json.dest2.longitude;

	
			var dest1_changing = json.dest1_iata_code.iata;
			var orig1_changing = json.orig1_iata_code.iata;
			var orig2_changing = json.orig2_iata_code.iata;
			var dest1_temp = "something else";
			var orig1_temp = "something else";
			if (dest1_changing != dest1_temp || orig1_changing != orig1_temp)
			{
			vectors_origin.removeAllFeatures([o1, o2]);
			vectors_destination.removeAllFeatures([d1, d2]);
			}

			var v1 = new OpenLayers.Geometry.Point(orig1lat, orig1long).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject());
			var v2 = new OpenLayers.Geometry.Point(orig2lat, orig2long).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject());
			var v3 = new OpenLayers.Geometry.Point(dest2lat, dest2long).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject());
			var v4 = new OpenLayers.Geometry.Point(dest1lat, dest1long).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject());

			var graphic = {externalGraphic: "./images/sw_red_pin.png", graphicHeight: 20, graphicWidth: 20};


			o1 = new OpenLayers.Feature.Vector(v1, null, graphic);
			vectors_origin.addFeatures(o1);
			o2 = new OpenLayers.Feature.Vector(v2, null, graphic);
			vectors_origin.addFeatures(o2);
			d1 = new OpenLayers.Feature.Vector(v3, null, graphic);
			vectors_destination.addFeatures(d1);
			d2 = new OpenLayers.Feature.Vector(v4, null, graphic);
			vectors_destination.addFeatures(d2);

                }
        });
}


function getSwaPoints() {

	SWA_airports_wfs.events.on({
		featureselected: function(event) {
                var feature = event.feature;
                feature.popup = new OpenLayers.Popup.FramedCloud("box",
                        feature.geometry.getBounds().getCenterLonLat(),
                        null,
                        '<div><a href="http://lf.airborne.aero:8164/citymap_dp2/citymap01.php?this_city_lat='+feature.attributes.lat+'&this_city_long='+feature.attributes.long+'&this_city_name='+feature.attributes.name+'">'+feature.attributes.name+'</a></div>',
                        null,
                        true, selectControl.unselect(feature)
                );

                while( map.popups.length ) {
                        map.removePopup( map.popups[0] );
                }
                map.addPopup(feature.popup);
                }
        } );

        selectControl = new OpenLayers.Control.SelectFeature([SWA_airports_wfs, vectors_origin, vectors_destination],
                { clickout: true }
        );

        map.addControl(selectControl);
        selectControl.activate();

}


function init(){
	planeInterval();
	planeMovement();
	var options = {
		theme: null,
		maxResolution: 0.703125,
                maxExtent: new OpenLayers.Bounds(-180, -90, 180, 90),
                //restrictedExtent: new OpenLayers.Bounds(-360, -90, 360, 90),
                numZoomLevels: 8,
		controls: [
		new OpenLayers.Control.OverviewMap(),
		new OpenLayers.Control.PanPanel(),
		new OpenLayers.Control.Zoom(),
		new OpenLayers.Control.Navigation(),
		new OpenLayers.Control.KeyboardDefaults(),
		new OpenLayers.Control.ScaleLine( { maxWidth: 150 })
		],
	};
	map = new OpenLayers.Map('map', options);
	vectors_projected_path = new OpenLayers.Layer.Vector("Projected Flight Path");
	vectors_flown_path = new OpenLayers.Layer.Vector("Flown Flight Path");
	vectors_airplane = new OpenLayers.Layer.Vector("Airplane");
	vectors_origin = new OpenLayers.Layer.Vector("Origin", {
		eventListeners:{
			'featureselected':function(evt){
			var feature = evt.feature;
			var popup = new OpenLayers.Popup.FramedCloud("popup",
			OpenLayers.LonLat.fromString(feature.geometry.toShortString()),
			null,
			json.orig1_iata_code.iata+"<br>",
			null,
			true,
			null
		);
		popup.autoSize = true;
		popup.maxSize = new OpenLayers.Size(400,800);
		popup.fixedRelativePosition = true;
		feature.popup = popup;
		map.addPopup(popup);
		},
			'featureunselected':function(evt){
			var feature = evt.feature;
			map.removePopup(feature.popup);
			}
		}
	});

	vectors_destination = new OpenLayers.Layer.Vector("Destination", {
		eventListeners:{
			'featureselected':function(evt){
			var feature = evt.feature;
			var popup = new OpenLayers.Popup.FramedCloud("popup",
			OpenLayers.LonLat.fromString(feature.geometry.toShortString()),
			null,
			json.dest1_iata_code.iata+"<br>",
			null,
			true,
			null
		);
		popup.autoSize = true;
		popup.maxSize = new OpenLayers.Size(400,800);
		popup.fixedRelativePosition = true;
		feature.popup = popup;
		map.addPopup(popup);
	},
			'featureunselected':function(evt){
			var feature = evt.feature;
			map.removePopup(feature.popup);
			}
		}
	});

	SWA_airports_wfs = new OpenLayers.Layer.Vector(
                                                "SW Airports",
                                             {  styleMap: new OpenLayers.StyleMap({     externalGraphic: "./images/heart.png",
                                                graphicWidth: 15,
                                                graphicHeight: 15 }),
                                                wrapDateLine: true,
                                                strategies: [new OpenLayers.Strategy.Fixed()],
                                                protocol: new OpenLayers.Protocol.WFS({
                                                srsName: "EPSG:3857",
                                                version: "1.0.0",
                                                url: "http://lf.airborne.aero:8164/cgi-bin/mapserv.exe?map=/tmp/navhome/navcore/swa/interactive_viewer.map&SERVICE=WFS&VERSION=1.1.0&srsName=EPSG:3857",
                                                featureType: "Airports" })
        });


        var osm = new OpenLayers.Layer.OSM("OSM");

	map.addLayers([osm]);

	//map.setCenter ((-82, 28), 3);
	map.addLayers([SWA_airports_wfs, vectors_projected_path, vectors_flown_path, vectors_airplane, vectors_origin, vectors_destination]);

	var external_zoom_panel = new OpenLayers.Control.Panel({
		div: document.getElementById('external_zoom_panel')
	});

	var external_pan_panel = new OpenLayers.Control.PanPanel({
		div: document.getElementById('swa_pan_panel'),
		slideFactor: 200
	});

	var control_zoom_in = new OpenLayers.Control.ZoomIn();
	var control_zoom_out = new OpenLayers.Control.ZoomOut();

	var FollowPlaneToggle  = new OpenLayers.Control.Button({
                displayClass: 'first',
                title: 'Follow Plane Mode',
                trigger: toggleCheckmark
        });
        layer_control_panel = new OpenLayers.Control.Panel({
                displayClass: 'FollowPlaneToggle'
        });

        map.addControl(layer_control_panel);

        layer_control_panel.addControls([FollowPlaneToggle]);


	var lonLat = new OpenLayers.LonLat(initial_json_plane_data_longitude, initial_json_plane_data_latitude).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject());

	var ll = new OpenLayers.LonLat(-95, 40).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject());

	map.setCenter(ll, 4);


}
//end of init() function

function planeInterval() 
{
	setInterval(function() {planeMovement(), getOrigAndDest()}, 5000);
}

function planeMovement() {
	$.ajax({
		url: "flight_information.php",
		data: "version="+version,
		type: "POST",
		//error: function(msg) { alert('wrong server'); },
		success: function(msg){
			json = eval('('+msg+')');
			if($('.followPlaneState').hasClass("checkmark")) {
				var global_zoom = map.getZoom();
                                var lonLat = new OpenLayers.LonLat(json.plane_data.longitude, json.plane_data.latitude).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject());
                                map.setCenter (lonLat, global_zoom);
                        }

			if (vectors_airplane != "" || tail_path != "" || projected_path != "" || vectors_projected_path != "" || vectors_flown_path != "")
			{
				vectors_projected_path.removeAllFeatures([projected_path]);
				vectors_flown_path.removeAllFeatures([tail_path]);
				vectors_airplane.removeAllFeatures([airplane]);
			}

			var path_travelled = new Array();
			var path = json.path_data;

			for (i in path)
			{
				var lat = path[i].latitude;
				var long = path[i].longitude;
				path_travelled.push(new OpenLayers.Geometry.Point(lat, long).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()));
			}

			var path_projected = new Array();
			var proj = json.projected_data;

			for (i in proj)
			{
				var lat = proj[i].latitude;
				var long = proj[i].longitude;
				path_projected.push(new OpenLayers.Geometry.Point(lat, long).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject()));
			}

			var orig1lat = json.orig1.latitude;
			var orig1long = json.orig1.longitude;
			var orig2lat = json.orig2.latitude;
			var orig2long = json.orig2.longitude;
			var dest2lat = json.dest1.latitude;
			var dest2long = json.dest1.longitude;
			var dest1lat = json.dest2.latitude;
			var dest1long = json.dest2.longitude;

			var style_flown = {
				strokeColor: '#FFFF26',
				strokeOpacity: 0.5,
				strokeWidth: 3
			};
			var style_projected = {
				strokeColor: '#19F816',
				strokeOpacity: 0.5,
				strokeWidth: 3
			};

			tail_path = new OpenLayers.Feature.Vector(new OpenLayers.Geometry.LineString(path_travelled), null, style_flown);
			projected_path = new OpenLayers.Feature.Vector(new OpenLayers.Geometry.LineString(path_projected), null, style_projected);

			point = new OpenLayers.Geometry.Point(json.plane_data.longitude, json.plane_data.latitude).transform(new OpenLayers.Projection("EPSG:4326"), map.getProjectionObject());
                        airplane = new OpenLayers.Feature.Vector(point, null, {
                                externalGraphic: "images/aircraft-sw.png",
                                graphicWidth: 50,
                                graphicHeight: 50,
                                rotation: json.plane_data.heading
                        });
                        vectors_flown_path.addFeatures([tail_path]);
                        vectors_projected_path.addFeatures([projected_path]);
                        vectors_airplane.addFeatures([airplane]);
		 }
	});
}
