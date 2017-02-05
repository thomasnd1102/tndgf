"use strict";

function renderMap( _center, markersData, zoom, mapType, mapTypeControl ) {
	var
	mapObject,
	markers = [];

	var mapOptions = {
		zoom: zoom,
		center: new google.maps.LatLng(_center[0], _center[1]),
		mapTypeId: mapType,

		mapTypeControl: mapTypeControl,
		mapTypeControlOptions: {
			// style: google.maps.MapTypeControlStyle.DROPDOWN_MENU,
			position: google.maps.ControlPosition.TOP_LEFT
		},
		panControl: false,
		panControlOptions: {
			position: google.maps.ControlPosition.TOP_RIGHT
		},
		zoomControl: true,
		zoomControlOptions: {
			style: google.maps.ZoomControlStyle.LARGE,
			position: google.maps.ControlPosition.RIGHT_BOTTOM
		},
		scrollwheel: false,
		scaleControl: true,
		scaleControlOptions: {
			position: google.maps.ControlPosition.TOP_LEFT
		},
		streetViewControl: true,
		streetViewControlOptions: {
			position: google.maps.ControlPosition.RIGHT_BOTTOM
		},
		styles: [/*map styles*/]
	};
	var marker;
	mapObject = new google.maps.Map(document.getElementById('map'), mapOptions);
	var icon_url = '';
	for (var key in markersData) {
		markersData[key].forEach(function (item) {
			
			icon_url = theme_url + '/img/pins/' + item.type + '.png';

			if ( item.type == 'Tours' && typeof tour_icon != 'undefined' ) { 
				icon_url = tour_icon;
			} else if ( item.type == 'Hotels' && typeof hotel_icon != 'undefined' ) { 
				icon_url = hotel_icon;
			}

			marker = new google.maps.Marker({
				position: new google.maps.LatLng(item.location_latitude, item.location_longitude),
				map: mapObject,
				icon: icon_url,
				title: item.name,
			});

			if ('undefined' === typeof markers[key])
				markers[key] = [];
			markers[key].push(marker);
			google.maps.event.addListener(marker, 'click', (function () {
				closeInfoBox();
				getInfoBox(item).open(mapObject, this);
				mapObject.setCenter(new google.maps.LatLng(item.location_latitude, item.location_longitude));
			}));
		});
	}

	function hideAllMarkers () {
		for (var key in markers) {
			markers[key].forEach(function (marker) {
				marker.setMap(null);
			});
		}
	};
		
	function toggleMarkers (category) {
		hideAllMarkers();m
		closeInfoBox();

		if ('undefined' === typeof markers[category])
			return false;
		markers[category].forEach(function (marker) {
			marker.setMap(mapObject);
			marker.setAnimation(google.maps.Animation.DROP);

		});
	};

	function closeInfoBox() {
		$('div.infoBox').remove();
	};

	function getInfoBox(item) {
		return new InfoBox({
			content:
			'<div class="marker_info" id="marker_info">' +
			'<img width="280" height="140" src="' + item.map_image_url + '" alt=""/>' +
			'<h3>'+ item.name_point +'</h3>' +
			'<span>'+ item.description_point +'</span>' +
			'<a href="'+ item.url_point + '" class="btn_1">Details</a>' +
			'</div>',
			disableAutoPan: true,
			maxWidth: 0,
			pixelOffset: new google.maps.Size(40, -190),
			closeBoxMargin: '5px -20px 2px 2px',
			closeBoxURL: "http://www.google.com/intl/en_us/mapfiles/close.gif",
			isHidden: false,
			pane: 'floatPane',
			enableEventPropagation: true
		});
	};
}