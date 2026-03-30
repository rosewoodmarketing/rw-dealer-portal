/* jshint esversion: 8 */
/* global google, rwdpMap, jQuery */

var rwdpInitMap; // exposed globally for Google Maps callback

(function ($) {
  'use strict';

  var map;
  var allMarkers    = [];
  var allDealers    = [];
  var infoWindow    = null;
  var selectedId    = null;

  // -----------------------------------------------------------------------
  // Haversine distance (miles)
  // -----------------------------------------------------------------------
  function haversine(lat1, lng1, lat2, lng2) {
    var R    = 3958.8; // Earth radius in miles
    var dLat = (lat2 - lat1) * Math.PI / 180;
    var dLng = (lng2 - lng1) * Math.PI / 180;
    var a    = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
               Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
               Math.sin(dLng / 2) * Math.sin(dLng / 2);
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  }

  // -----------------------------------------------------------------------
  // Build info window HTML for a dealer
  // -----------------------------------------------------------------------
  function buildInfoWindowContent(dealer) {
    var html = '<div class="rwdp-infowindow">';

    if (dealer.logo_url) {
      html += '<img src="' + dealer.logo_url + '" alt="' + escHtml(dealer.title) + ' logo" class="rwdp-infowindow__logo" />';
    } else if (dealer.feat_img) {
      html += '<img src="' + dealer.feat_img + '" alt="' + escHtml(dealer.title) + '" class="rwdp-infowindow__img" />';
    }

    html += '<strong class="rwdp-infowindow__name">' + escHtml(dealer.title) + '</strong>';

    if (dealer.address) {
      html += '<p class="rwdp-infowindow__address">' + escHtml(dealer.address) + ', ' + escHtml(dealer.city) + ', ' + escHtml(dealer.state) + ' ' + escHtml(dealer.zip) + '</p>';
    }
    if (dealer.phone) {
      html += '<p><a href="tel:' + escHtml(dealer.phone) + '">' + escHtml(dealer.phone) + '</a></p>';
    }
    if (dealer.website) {
      html += '<p><a href="' + escHtml(dealer.website) + '" target="_blank" rel="noopener noreferrer">' + escHtml(dealer.website) + '</a></p>';
    }
    if (dealer.hours) {
      html += '<p class="rwdp-infowindow__hours">' + escHtml(dealer.hours).replace(/\n/g, '<br>') + '</p>';
    }

    html += '<div class="rwdp-infowindow__actions">';
    if (dealer.address) {
      var mapsUrl = 'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(dealer.address + ', ' + dealer.city + ', ' + dealer.state + ' ' + dealer.zip);
      html += '<a href="' + mapsUrl + '" class="rwdp-btn rwdp-btn--outline rwdp-btn--sm" target="_blank" rel="noopener noreferrer">' + rwdpMap.directionsText + '</a>';
    }
    html += '<button type="button" class="rwdp-btn rwdp-btn--primary rwdp-btn--sm rwdp-contact-trigger" data-dealer-id="' + dealer.id + '" data-dealer-name="' + escHtml(dealer.title) + '">' + rwdpMap.contactText + '</button>';
    html += '</div></div>';

    return html;
  }

  function escHtml(str) {
    return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  // -----------------------------------------------------------------------
  // Place markers on map
  // -----------------------------------------------------------------------
  function placeMarkers(dealers) {
    clearMarkers();
    if (!dealers || !dealers.length) {
      showResultsList([]);
      return;
    }

    dealers.forEach(function (dealer) {
      var marker = new google.maps.Marker({
        position : { lat: dealer.lat, lng: dealer.lng },
        map      : map,
        title    : dealer.title,
      });

      marker.dealerData = dealer;

      marker.addListener('click', function () {
        openInfoWindow(marker, dealer);
      });

      allMarkers.push(marker);
    });

    fitBoundsToMarkers();
    showResultsList(dealers);
  }

  function clearMarkers() {
    allMarkers.forEach(function (m) { m.setMap(null); });
    allMarkers = [];
    if (infoWindow) infoWindow.close();
  }

  function fitBoundsToMarkers() {
    if (!allMarkers.length) return;
    if (allMarkers.length === 1) {
      map.panTo(allMarkers[0].getPosition());
      map.setZoom(12);
      return;
    }
    var bounds = new google.maps.LatLngBounds();
    allMarkers.forEach(function (m) { bounds.extend(m.getPosition()); });
    map.fitBounds(bounds);
  }

  function openInfoWindow(marker, dealer) {
    if (!infoWindow) infoWindow = new google.maps.InfoWindow();
    infoWindow.setContent(buildInfoWindowContent(dealer));
    infoWindow.open(map, marker);
    selectedId = dealer.id;

    // Emit custom event for fluent-forms-helper.js
    $(document).trigger('rwdp:dealer-selected', [dealer]);
  }

  // -----------------------------------------------------------------------
  // Results sidebar list
  // -----------------------------------------------------------------------
  function showResultsList(dealers) {
    var $list = $('#rwdp-results-list');
    $list.empty();

    if (!dealers || !dealers.length) {
      $list.html('<p class="rwdp-finder__no-results">' + rwdpMap.noResults + '</p>');
      return;
    }

    dealers.forEach(function (dealer, idx) {
      var $item = $('<div class="rwdp-result-item" role="button" tabindex="0">');
      $item.attr('data-dealer-id', dealer.id);
      $item.attr('aria-label', dealer.title);

      var inner = '';
      if (dealer.logo_url) {
        inner += '<img src="' + dealer.logo_url + '" alt="" class="rwdp-result-item__logo" aria-hidden="true" />';
      }
      inner += '<div class="rwdp-result-item__info">';
      inner += '<strong>' + escHtml(dealer.title) + '</strong>';
      if (dealer.city || dealer.state) {
        inner += '<span>' + escHtml([dealer.city, dealer.state].filter(Boolean).join(', ')) + '</span>';
      }
      if (dealer.dist !== undefined) {
        inner += '<span class="rwdp-result-item__dist">' + dealer.dist.toFixed(1) + ' mi</span>';
      }
      inner += '</div>';
      $item.html(inner);

      $item.on('click keypress', function (e) {
        if (e.type === 'keypress' && e.which !== 13) return;
        var matchMarker = allMarkers.find(function (m) { return m.dealerData && m.dealerData.id === dealer.id; });
        if (matchMarker) {
          map.panTo(matchMarker.getPosition());
          openInfoWindow(matchMarker, dealer);
        }
      });

      $list.append($item);
    });
  }

  // -----------------------------------------------------------------------
  // Fetch dealers from server
  // -----------------------------------------------------------------------
  function fetchDealers(callback) {
    var lockedType = $('#rwdp-dealer-finder').data('locked-type') || '';
    var typeId     = lockedType ? '' : ($('#rwdp-type-filter').val() || '');

    $.ajax({
      url    : rwdpMap.ajaxUrl,
      method : 'POST',
      data   : { action: 'rwdp_get_dealers', nonce: rwdpMap.nonce, type_id: typeId, locked_type: lockedType },
      success: function (res) {
        if (res.success && res.data.dealers) {
          allDealers = res.data.dealers;
          if (typeof callback === 'function') callback(allDealers);
        }
      }
    });
  }

  // -----------------------------------------------------------------------
  // Search / filter
  // -----------------------------------------------------------------------
  function performSearch() {
    var query  = $('#rwdp-location-search').val().trim();
    var radius = parseInt($('#rwdp-radius-select').val(), 10);

    if (!query) {
      placeMarkers(allDealers);
      return;
    }

    var geocoder = new google.maps.Geocoder();
    geocoder.geocode({ address: query }, function (results, status) {
      if (status !== 'OK' || !results.length) {
        $('#rwdp-results-list').html('<p class="rwdp-finder__no-results">' + rwdpMap.noResults + '</p>');
        return;
      }

      var loc     = results[0].geometry.location;
      var userLat = loc.lat();
      var userLng = loc.lng();

      var filtered = allDealers.filter(function (d) {
        if (!d.lat || !d.lng) return false;
        if (radius === 0) return true;
        var dist = haversine(userLat, userLng, d.lat, d.lng);
        d.dist   = dist;
        return dist <= radius;
      });

      filtered.sort(function (a, b) { return (a.dist || 0) - (b.dist || 0); });
      placeMarkers(filtered);
    });
  }

  // -----------------------------------------------------------------------
  // Contact modal
  // -----------------------------------------------------------------------
  $(document).on('click', '.rwdp-contact-trigger', function () {
    var dealerId   = $(this).data('dealer-id');
    var dealerName = $(this).data('dealer-name');
    openContactModal(dealerId, dealerName);
  });

  $(document).on('click', '#rwdp-modal-close, #rwdp-modal-overlay', function () {
    closeContactModal();
  });

  $(document).on('keydown', function (e) {
    if (e.key === 'Escape') closeContactModal();
  });

  function openContactModal(dealerId, dealerName) {
    var $modal = $('#rwdp-contact-modal');
    if (!$modal.length) return;

    $('#rwdp-modal-dealer-name').text(dealerName || '');
    $modal.attr('aria-hidden', 'false').addClass('is-open');

    // Emit event so fluent-forms-helper.js can populate hidden fields
    $(document).trigger('rwdp:dealer-selected', [{ id: dealerId, title: dealerName }]);
  }

  function closeContactModal() {
    var $modal = $('#rwdp-contact-modal');
    $modal.attr('aria-hidden', 'true').removeClass('is-open');
    selectedId = null;
  }

  // -----------------------------------------------------------------------
  // Type filter change
  // -----------------------------------------------------------------------
  $(document).on('change', '#rwdp-type-filter', function () {
    fetchDealers(function (dealers) {
      placeMarkers(dealers);
    });
  });

  // -----------------------------------------------------------------------
  // Map initialisation (called by Google Maps callback)
  // -----------------------------------------------------------------------
  rwdpInitMap = function () {
    var $mapEl = document.getElementById('rwdp-map');
    if (!$mapEl) return;

    map = new google.maps.Map($mapEl, {
      zoom   : 5,
      center : { lat: 39.5, lng: -98.35 }, // centre of USA
    });

    infoWindow = new google.maps.InfoWindow();

    fetchDealers(function (dealers) {
      placeMarkers(dealers);
    });

    // Search on Enter
    $('#rwdp-location-search').on('keypress', function (e) {
      if (e.which === 13) { e.preventDefault(); performSearch(); }
    });

    $('#rwdp-search-btn').on('click', performSearch);
  };

  // If Google Maps key not configured, still fetch and list dealers in text
  if (!rwdpMap.hasMapsKey) {
    $(function () {
      fetchDealers(function (dealers) {
        showResultsList(dealers);
      });
    });
  }

}(jQuery));
