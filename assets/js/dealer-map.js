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
  function getPopupToggles() {
    var $map = $('#rwdp-map');
    return {
      logo    : $map.data('show-logo')    !== 0 && $map.data('show-logo')    !== '0',
      phone   : $map.data('show-phone')   !== 0 && $map.data('show-phone')   !== '0',
      website : $map.data('show-website') !== 0 && $map.data('show-website') !== '0',
      hours   : $map.data('show-hours')   !== 0 && $map.data('show-hours')   !== '0',
      contact         : $map.data('show-contact') !== 0 && $map.data('show-contact') !== '0',
      contact_text    : $map.data('contact-text')    || rwdpMap.contactText    || 'Contact This Dealer',
      directions_text : $map.data('directions-text') || rwdpMap.directionsText || 'Get Directions',
    };
  }

  function buildInfoWindowContent(dealer) {
    var show = getPopupToggles();
    var html = '<div class="rwdp-infowindow">';

    if ( show.logo && dealer.logo_url ) {
        html += '<img src="' + dealer.logo_url + '" alt="' + escHtml(dealer.title) + ' logo" class="rwdp-infowindow__logo" />';
      }

    html += '<h3 class="rwdp-infowindow__name">' + escHtml(dealer.title) + '</h3>';

    html += '<div class="rwdp-infowindow__details">';

    
    if ( show.phone && dealer.phone) {
      html += '<p><a href="tel:' + escHtml(dealer.phone) + '">' + escHtml(dealer.phone) + '</a></p>';
    }
    if ( show.website && dealer.website) {
      html += '<p><a href="' + escHtml(dealer.website) + '" target="_blank" rel="noopener noreferrer">' + escHtml(dealer.website) + '</a></p>';
    }
    if ( show.hours && dealer.hours) {
      html += '<p class="rwdp-infowindow__hours">' + escHtml(dealer.hours).replace(/\n/g, '<br>') + '</p>';
    }

    if (dealer.address) {
      html += '<p class="rwdp-infowindow__address">' + escHtml(dealer.address) + ', ' + escHtml(dealer.city) + ', ' + escHtml(dealer.state) + ' ' + escHtml(dealer.zip) + '</p>';
      var mapsUrl     = 'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(dealer.address + ', ' + dealer.city + ', ' + dealer.state + ' ' + dealer.zip);
      var dirIconHtml = $('#rwdp-map').attr('data-directions-icon') || '';
      var dirIconPos  = $('#rwdp-map').attr('data-directions-icon-position') || 'before';
      var dirInner    = dirIconPos === 'before'
          ? dirIconHtml + show.directions_text
          : show.directions_text + dirIconHtml;
      html += '<a href="' + mapsUrl + '" class="rwdp-btn rwdp-btn--outline rwdp-btn--sm rwdp-popup-dir-btn" target="_blank" rel="noopener noreferrer">' + dirInner + '</a>';
    }

    html += '</div>';

    if ( show.contact ) {
        html += '<button type="button" class="rwdp-btn rwdp-btn--primary rwdp-btn--sm rwdp-contact-trigger" data-dealer-id="' + dealer.id + '" data-dealer-name="' + escHtml(dealer.title) + '">' + show.contact_text + '</button>';
      }

    html += '</div>';
    return html;
  }

  function escHtml(str) {
    return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
  }

  function escAttr(str) {
    return String(str || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#039;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
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
  // Results grid/list
  // -----------------------------------------------------------------------
  function getListToggles() {
    var $el = $('#rwdp-results-list');
    return {
      thumbnail  : $el.data('show-thumbnail')   !== 0 && $el.data('show-thumbnail')   !== '0',
      logo       : $el.data('show-logo')        !== 0 && $el.data('show-logo')        !== '0',
      title      : $el.data('show-title')       !== 0 && $el.data('show-title')       !== '0',
      address    : $el.data('show-address')     !== 0 && $el.data('show-address')     !== '0',
      phone      : $el.data('show-phone')       !== 0 && $el.data('show-phone')       !== '0',
      hours      : $el.data('show-hours')       !== 0 && $el.data('show-hours')       !== '0',
      directions : $el.data('show-directions')  !== 0 && $el.data('show-directions')  !== '0',
      contact    : $el.data('show-contact')     !== 0 && $el.data('show-contact')     !== '0',
      more_info  : $el.data('show-more-info')   !== 0 && $el.data('show-more-info')   !== '0',
      view_on_map      : $el.data('show-view-on-map') !== 0 && $el.data('show-view-on-map') !== '0',
      contact_text     : $el.data('contact-text')     || rwdpMap.contactText    || 'Contact This Dealer',
      more_info_text   : $el.data('more-info-text')   || rwdpMap.moreInfoText   || 'More Info',
      directions_text  : $el.data('directions-text')  || rwdpMap.directionsText || 'Get Directions',
      view_on_map_text : $el.data('view-on-map-text') || rwdpMap.viewOnMapText  || 'View on Map',
    };
  }

  function showResultsList(dealers) {
    var $list = $('#rwdp-results-list');
    $list.empty();

    if (!dealers || !dealers.length) {
      $list.html('<p class="rwdp-finder__no-results">' + rwdpMap.noResults + '</p>');
      return;
    }

    var t = getListToggles();
    var dirIconHtml = $('#rwdp-results-list').attr('data-directions-icon') || '';
    var dirIconPos  = $('#rwdp-results-list').attr('data-directions-icon-position') || 'after';
    var $grid = $('<div class="rwdp-results-grid">');

    dealers.forEach(function (dealer) {
      var card = '<div class="rwdp-result-card" data-dealer-id="' + dealer.id + '">';

      // View on Map button (absolute, top-right of card)
      if (t.view_on_map) {
        card += '<button type="button" class="rwdp-result-card__view-on-map rwdp-vom-btn"'
             +  ' data-dealer-id="' + dealer.id + '"'
             +  ' aria-label="' + escAttr(t.view_on_map_text) + '">'
             +  escHtml(t.view_on_map_text)
             + '</button>';
      }

      // Thumbnail
      if (t.thumbnail && dealer.feat_img) {
        card += '<img src="' + escAttr(dealer.feat_img) + '" alt="" class="rwdp-result-card__thumbnail" aria-hidden="true" />';
      }

      card += '<div class="rwdp-result-card__body">';

      // Logo
      if (t.logo && dealer.logo_url) {
        card += '<div class="rwdp-result-card__logo-wrap">';
        card += '<img src="' + escAttr(dealer.logo_url) + '" alt="" class="rwdp-result-card__logo" aria-hidden="true" />';
        card += '</div>';
      }

      // Title
      if (t.title) {
        card += '<div class="rwdp-result-card__title">' + escHtml(dealer.title) + '</div>';
      }

      // Address row: address text + inline Get Directions button
      if (t.address) {
        var addrParts = [];
        if (dealer.address) addrParts.push(dealer.address);
        var cityLine = [dealer.city, dealer.state].filter(Boolean).join(', ');
        if (dealer.zip) cityLine += (cityLine ? ' ' : '') + dealer.zip;
        if (cityLine)   addrParts.push(cityLine);
        if (addrParts.length) {
          card += '<div class="rwdp-result-card__address-row">';
          card += '<div class="rwdp-result-card__address">' + addrParts.map(escHtml).join('<br>') + '</div>';
          if (t.directions) {
            var dirQuery   = [dealer.address, dealer.city, dealer.state, dealer.zip].filter(Boolean).join(', ');
            var dirContent = dirIconPos === 'before'
                ? dirIconHtml + escHtml(t.directions_text)
                : escHtml(t.directions_text) + dirIconHtml;
            card += '<a href="https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(dirQuery) + '"'
                 +   ' target="_blank" rel="noopener noreferrer"'
                 +   ' class="rwdp-result-card__directions">'
                 +   dirContent
                 + '</a>';
          }
          card += '</div>';
        }
      }

      // Phone
      if (t.phone && dealer.phone) {
        var tel = dealer.phone.replace(/[^\d+]/g, '');
        card += '<div class="rwdp-result-card__phone"><a href="tel:' + escAttr(tel) + '">' + escHtml(dealer.phone) + '</a></div>';
      }

      // Hours
      if (t.hours && dealer.hours) {
        card += '<div class="rwdp-result-card__hours">' + escHtml(dealer.hours) + '</div>';
      }

      // Actions: Contact This Dealer | More Info
      var hasContact  = t.contact;
      var hasMoreInfo = t.more_info && dealer.permalink;

      if (hasContact || hasMoreInfo) {
        card += '<div class="rwdp-result-card__actions">';
        if (hasContact) {
          card += '<button type="button" class="rwdp-result-card__contact rwdp-contact-btn"'
               +  ' data-dealer-id="' + dealer.id + '"'
               +  ' data-dealer-name="' + escAttr(dealer.title) + '">'
               +  escHtml(t.contact_text)
               + '</button>';
        }
        if (hasMoreInfo) {
          card += '<a href="' + escAttr(dealer.permalink) + '"'
               +  ' class="rwdp-result-card__more-info">'
               +  escHtml(t.more_info_text)
               + '</a>';
        }
        card += '</div>';
      }

      card += '</div></div>'; // .body / .card
      $grid.append($(card));
    });

    $list.append($grid);
  }

  // -----------------------------------------------------------------------
  // Fetch dealers from server
  // -----------------------------------------------------------------------
  function fetchDealers(callback) {
    var lockedType = $('#rwdp-dealer-finder').data('locked-type') || '';
    var typeId     = lockedType ? '' : ($('#rwdp-related-filter').val() || $('#rwdp-type-filter').val() || '');
    var taxTypeId  = lockedType ? '' : ($('#rwdp-tax-filter').val() || '');

    $.ajax({
      url    : rwdpMap.ajaxUrl,
      method : 'POST',
      data   : {
        action: 'rwdp_get_dealers',
        nonce: rwdpMap.nonce,
        type_id: typeId,
        tax_type_id: taxTypeId,
        locked_type: lockedType
      },
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
  $(document).on('click', '.rwdp-contact-trigger, .rwdp-contact-btn', function () {
    var dealerId   = $(this).data('dealer-id');
    var dealerName = $(this).data('dealer-name');
    openContactModal(dealerId, dealerName);
  });

  $(document).on('click', '.rwdp-vom-btn', function () {
    var dealerId    = $(this).data('dealer-id');
    var dealer      = allDealers.find(function (d) { return d.id === dealerId; });
    var matchMarker = allMarkers.find(function (m) { return m.dealerData && m.dealerData.id === dealerId; });
    if (matchMarker) {
      map.panTo(matchMarker.getPosition());
      if (dealer) openInfoWindow(matchMarker, dealer);
    }
    var $map = $('#rwdp-map');
    if ($map.length) {
      $('html, body').animate({ scrollTop: $map.offset().top }, 400);
    }
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
  $(document).on('change', '#rwdp-related-filter, #rwdp-type-filter, #rwdp-tax-filter', function () {
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
