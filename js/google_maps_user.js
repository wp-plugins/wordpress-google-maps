/**
 * Global handler object variable.
 * Initiated as global, will be bound as object on document.load.
 */
var AgmMapHandler;

(function($){
$(function() {
	
/**
 * Public side map handler.
 * Responsible for rendering maps on public facing pages.
 * 
 * @param selector Container element selector string.
 * @param data Map data object.
 */
AgmMapHandler = function (selector, data) {
	var map;
	var directionsDisplay;
	var directionsService;
	var panoramioImages;
	var travelType;
	var mapId = 'map_' + Math.floor(Math.random()* new Date().getTime()) + '_preview';
	var $container = $(selector);
	var _markers = [];
	
	var closeDirections = function () {
		$(selector + ' .agm_mh_directions_container').remove();
		return false;
	};
	
	var createDirectionsMarkup = function () {
		var html = '<div class="agm_mh_directions_container agm_mh_container">';
		html += '<div style="width:300px">' +
					'<span style="float:right"><input type="button" class="agm_mh_close_directions" value="' + l10nStrings.close + '" /> </span>' +
					'<div>' +
						'<a href="#" class="agm_mh_travel_type"><img src="' + _agm_root_url + '/img/system/car_on.png"></a>' +
						'<a href="#" class="agm_mh_travel_type"><img src="' + _agm_root_url + '/img/system/bike_off.png"></a>' + 
						'<a href="#" class="agm_mh_travel_type"><img src="' + _agm_root_url + '/img/system/walk_off.png"></a>' +
					'</div>' +
				'</div>' +
			'<div>' +
				'<img src="' + _agm_root_url + '/img/system/a.png">' +
				'&nbsp;' +
				'<input size="32" type="text" class="agm_waypoint_a" />' +
			'</div>' +
			'<div><a href="#" class="agm_mh_swap_direction_waypoints"><img src="' + _agm_root_url + '/img/system/swap.png"></a></div>' +
			'<div>' +
				'<img src="' + _agm_root_url + '/img/system/b.png">' +
				'&nbsp;' +
				'<input size="32" type="text"  class="agm_waypoint_b" />' +
			'</div>' +
			'<div>' +
				'<input type="button" class="agm_mh_get_directions" value="' + l10nStrings.get_directions + '" />' +
			'</div>' +
			'<div class="agm_mh_directions_panel agm_mh_container">' +
			'</div>' 
		;
		html += '</div>';
		$container.append(html);
	};
	
	var switchTravelType = function () {
		var $me = $(this);
		var $meImg = $me.find('img');
		var $allImg = $(selector + ' .agm_mh_travel_type img');
		$allImg.each(function () {
			$(this).attr('src', $(this).attr('src').replace(/_on\./, '_off.'));
		});
		if ($meImg.attr('src').match(/car_off\.png/)) {
			travelType = google.maps.DirectionsTravelMode.DRIVING;
		} else if ($meImg.attr('src').match(/bike_off\.png/)) {
			travelType = google.maps.DirectionsTravelMode.BICYCLING;
		} else if ($meImg.attr('src').match(/walk_off\.png/)) {
			travelType = google.maps.DirectionsTravelMode.WALKING;
		}
		$meImg.attr('src', $meImg.attr('src').replace(/_off\./, '_on.'));
		return false;
	};
	
	var setDirectionWaypoint = function () {
		if (!$(selector + ' .agm_mh_directions_container').is(':visible')) createDirectionsMarkup();
		var id = extractMarkerId($(this).attr('href'));
		var marker = _markers[id];
		var geocoder = new google.maps.Geocoder();
		geocoder.geocode({'latLng': marker.getPosition()}, function (results, status) {
			if (status == google.maps.GeocoderStatus.OK) {
				$(selector + ' .agm_waypoint_a').val(results[0].formatted_address);
			}
			else alert(l10nStrings.geocoding_error);
		});
		return false;
	};
	
	var swapWaypoints = function () {
		var tmpA = $(selector + ' .agm_waypoint_a').val();
		$(selector + ' .agm_waypoint_a').val($(selector + ' .agm_waypoint_b').val());
		$(selector + ' .agm_waypoint_b').val(tmpA);
		return false;
	};
	
	var getDirections = function () {
		var locA = $(selector + ' .agm_waypoint_a').val();
		var locB = $(selector + ' .agm_waypoint_b').val();
		if (!locA || !locB) {
			alert(l10nStrings.missing_waypoint);
			return false;
		}
		var request = {
			"origin": locA, 
    		"destination": locB,
    		"travelMode": travelType
		};
		directionsDisplay.setPanel($(selector + ' .agm_mh_directions_panel').get(0));
		directionsService.route(request, function(result, status) {
			if (status == google.maps.DirectionsStatus.OK) directionsDisplay.setDirections(result);
			else alert(l10nStrings.oops_no_directions); 
		});
		return false;
	};
	
	var addNewMarker = function (title, pos, body, icon) {
		var idx = _markers.length;
		map.setCenter(pos);
		var marker = new google.maps.Marker({
			title: title,
            map: map, 
            icon: _agm_root_url + '/img/' + icon,
            draggable: false,
            clickable: true,
            position: pos
        });
		var infoContent = '<div class="agm_mh_info_content">' +
			'<div class="agm_mh_info_title">' + title + '</div>' + 
    		'<img class="agm_mh_info_icon" src="' + _agm_root_url + '/img/' + icon + '" />' +
    		'<div class="agm_mh_info_body">' + body + '</div>' +
    		createDirectionsLink(idx) +
    	'</div>';
		var info = new google.maps.InfoWindow({
		    content: infoContent 
		});
		google.maps.event.addListener(marker, 'click', function() {
			info.open(map, marker);
		});
		marker._agmBody = body;
		_markers[idx] = marker;
		updateMarkersListDisplay();
	};
	
	var addMarkers = function () {
		if (!data.markers) return;
		$.each(data.markers, function (idx, marker) {
			addNewMarker(marker.title, new google.maps.LatLng(marker.position[0], marker.position[1]), marker.body, marker.icon);
		});
	};
	
	var extractMarkerId = function (href) {
		var id = href.replace(/[^0-9]+/, '');
		return parseInt(id);
	};
	
	var centerToMarker = function () {
		var $me = $(this);
		var id = extractMarkerId($me.attr('href'));
		var m = _markers[id];
		map.setCenter(m.getPosition());
		return false;
	};
	
	var createDirectionsLink = function (idx) {
		return '<a href="#agm_mh_marker-' + idx + '" class="agm_mh_marker_item_directions">' + l10nStrings.directions + '</a>';
	};
	
	var updateMarkersListDisplay = function () {
		if (!data.show_markers || !parseInt(data.show_markers)) return false;
		var html = '<ul class="agm_mh_marker_list">';
		$.each(_markers, function (idx, mark) {
			html += '<li style="clear:both">' +
				'<a href="#agm_mh_marker-' + idx + '" class="agm_mh_marker_item">' + 
					'<img src="' + mark.getIcon() + '" />' +
					'<div class="agm_mh_marker_item_content">' +
						'<div><b>'+ mark.getTitle() + '</b></div>' +
						'<div>' + mark._agmBody + '</div>' +
					'</div>' +
				'</a>' +
				createDirectionsLink(idx) +
				'<div style="clear:both"></div>' +
			'</li>';
		});
		html += '</ul>';
		$('#agm_mh_markers_' + mapId).html(html);
	};
	
	var init = function () {
		try {
			var width = (parseInt(data.width) > 0) ? data.width : data.defaults.width;
			var height = (parseInt(data.height) > 0) ? data.height : data.defaults.height;
		} catch (e) {
			var width = (parseInt(data.width) > 0) ? data.width : 200;
			var height = (parseInt(data.height) > 0) ? data.height : 200;
		}
		data.zoom = (data.zoom) ? data.zoom : 4;
		data.map_type = (data.map_type) ? data.map_type : 'ROADMAP';
		data.image_size = data.image_size || data.defaults.image_size;
		data.image_limit = data.image_limit || data.defaults.image_limit;
		$container.html('<div id="' + mapId + '"></div>');
		$('#' + mapId)
			.width(parseInt(width))
			.height(parseInt(height))
		;
		$container
			.width(parseInt(width))
		;
		if (!data.show_map || !parseInt(data.show_map)) $('#' + mapId).css({
			"position": "absolute",
			"left": "-120000px"
		});
		map = new google.maps.Map($('#' + mapId).get(0), {
			"zoom": parseInt(data.zoom),
			"center": new google.maps.LatLng(40.7171, -74.0039), // New York
			"mapTypeId": google.maps.MapTypeId[data.map_type]
		});
		directionsDisplay = new google.maps.DirectionsRenderer({
			"draggable": true
		});
		directionsService = new google.maps.DirectionsService();
		directionsDisplay.setMap(map);
		travelType = google.maps.DirectionsTravelMode.DRIVING;
		$container.append(
			'<div id="agm_mh_footer">' +
				'<div class="agm_mh_container" id="agm_mh_markers_' + mapId + '">' +
				'</div>' +
			'</div>'
		);
		if ("show_links" in data && parseInt(data.show_links)) {
			$container.append('<div class="agm_upgrade_link"><small><a href="http://premium.wpmudev.org/project/wordpress-google-maps-plugin">Created by the WordPress Google Maps plugin</a></small></div>');
		}
		addMarkers();

		if(data.show_images && parseInt(data.show_images)) {
			panoramioImages = new AgmPanoramioHandler(map, $container, data.image_limit, data.image_size);
			$container.append(panoramioImages.createMarkup());
		}
		
		$(selector + ' .agm_mh_travel_type').live('click', switchTravelType);
		$(selector + ' .agm_mh_swap_direction_waypoints').live('click', swapWaypoints);
		$(selector + ' .agm_mh_close_directions').live('click', closeDirections);
		$(selector + ' .agm_mh_get_directions').live('click', getDirections);
		$(selector + ' .agm_mh_marker_item').live('click', centerToMarker);
		$(selector + ' .agm_mh_marker_item_directions').live('click', setDirectionWaypoint);
	};
	
	init();
	
};

/**
 * Local Panoramio handler.
 * If not enabled per map, Panoramio images won't be loaded and this
 * won't be executed.
 * Since it's optional, the handler is not global.
 */
var AgmPanoramioHandler = function (map, $container, limit, size) {
	var containerId = 'agm_panoramio_' + Math.floor(Math.random() * new Date().getTime()) + '_container';
	var images = [];
	var height = 200;
	
	var loadPanoramioScript = function () {
		var bounds = map.getBounds();
		if (!bounds) return setTimeout(loadPanoramioScript, 100);
		var callback = 'func_' + containerId + '_image_handler';
		window[callback] = function (data) {
			images = data.photos;
		};
		var script = document.createElement("script");
		var src = 'http://www.panoramio.com/map/get_panoramas.php?set=full&from=0&to=' + limit +
			'&miny=' + bounds.getSouthWest().lat() + '&minx=' + bounds.getSouthWest().lng() + 
			'&maxy=' + bounds.getNorthEast().lat() + '&maxx=' + bounds.getNorthEast().lng() +
			'&size=' + size +
		'&callback=' + callback;
		script.type = "text/javascript";
		script.src = src;
		document.body.appendChild(script);
	};
	
	var loadGalleriaScript = function () {
		var script = document.createElement("script");
		var src = _agm_root_url + '/js/external/galleria/galleria-1.2.2.min.js';
		script.type = "text/javascript";
		script.src = src;
		document.body.appendChild(script);
	};
	
	var loadGalleriaTheme = function () {
		var script = document.createElement("script");
		var src = _agm_root_url + '/js/external/galleria/themes/classic/galleria.classic.min.js';
		script.type = "text/javascript";
		script.src = src;
		document.body.appendChild(script);
		var gheight = height ? height : 200;
		$('#'+containerId + ' div.agm_panoramio_image_list_container').galleria({
	        width: $container.width(),
	        height: gheight
	    });
	};
	
	var waitForImages = function () {
		if (!images.length) setTimeout(waitForImages, 1000);
		else {
			populateContainer();
			loadGalleriaTheme();
		}
	};
	
	var createMarkup = function () {
		return '<div class="agm_panoramio_container" id="' + containerId + '">' +
		'</div>';
	};
	
	var populateContainer = function () {
		if (!$('#' + containerId).length) return false;
		var html = '<div class="agm_panoramio_image_list_container"><ul class="agm_panoramio_image_list">';
		var totalImageWidth = 0;
		$.each(images, function(idx, img) {
			var imgh = parseInt(img.height);
			height = (imgh > height) ? imgh : height;
			html += '<li>' +
				'<img src="' + img.photo_file_url + '" title="' + img.photo_title + '" />' +
			'</li>';
			totalImageWidth += img.width;
		});
		html += '</ul></div>';
		$('#'+containerId).html(html);
	};
	
	var init = function () {
		loadPanoramioScript();
		loadGalleriaScript();
		waitForImages();
	};
	
	init();
	
	return {
		"createMarkup": createMarkup
	};
};
	
/**
 * Uses global _agmMaps array to create the needed map objects.
 * Deferres AgmMapHandler creation until Google Maps API is available.
 */
function createMaps () {
	if (!_agmMapIsLoaded) {
		setTimeout(createMaps, 100);
	} else {
		$.each(_agmMaps, function (idx, map) {
			new AgmMapHandler(map.selector, map.data);
		});
	}
}

// Create map objects on document.load,
// or as soon as we're able to
createMaps();


});
})(jQuery);