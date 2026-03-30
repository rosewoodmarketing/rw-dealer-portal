/* jshint esversion: 8 */
/* global jQuery */

/**
 * fluent-forms-helper.js
 *
 * Listens for the `rwdp:dealer-selected` custom event fired by dealer-map.js
 * and populates hidden Fluent Forms fields:
 *   - rwdp_dealer_id
 *   - rwdp_dealer_name
 */
(function ($) {
  'use strict';

  $(document).on('rwdp:dealer-selected', function (e, dealer) {
    if (!dealer) return;

    // Fluent Forms renders hidden fields as <input type="hidden" name="...">
    // but also as visible fields that user shouldn't see (class ff-el-input).
    // We target by name attribute to cover both cases.
    var $idFields   = $('input[name="rwdp_dealer_id"]');
    var $nameFields = $('input[name="rwdp_dealer_name"]');

    if ($idFields.length) {
      $idFields.val(dealer.id).trigger('change');
    }

    if ($nameFields.length) {
      $nameFields.val(dealer.title || '').trigger('change');
    }
  });

}(jQuery));
