/* jshint esversion: 8 */
(function ($) {
  'use strict';

  // -----------------------------------------------------------------------
  // Tab switching (login / register)
  // -----------------------------------------------------------------------
  $(document).on('click', '.rwdp-tabs__btn', function () {
    var target = $(this).data('tab');
    var $wrap  = $(this).closest('.rwdp-tabs');

    $wrap.find('.rwdp-tabs__btn').attr('aria-selected', 'false');
    $(this).attr('aria-selected', 'true');

    $wrap.find('.rwdp-tab-panel').hide().attr('aria-hidden', 'true');
    $('#' + target).show().attr('aria-hidden', 'false');
  });

  // Activate first tab on load
  $('.rwdp-tabs').each(function () {
    var $first = $(this).find('.rwdp-tabs__btn').first();
    if ($first.length && !$(this).find('.rwdp-tabs__btn[aria-selected="true"]').length) {
      $first.trigger('click');
    }
  });

  // -----------------------------------------------------------------------
  // Asset category tab-filter
  // -----------------------------------------------------------------------
  $(document).on('click', '.rwdp-assets__tab', function (e) {
    e.preventDefault();
    var target = $(this).data('target');

    $(this).closest('.rwdp-assets').find('.rwdp-assets__tab').removeClass('rwdp-assets__tab--active');
    $(this).addClass('rwdp-assets__tab--active');

    var $cards = $(this).closest('.rwdp-assets').find('.rwdp-asset-card');
    $cards.each(function () {
      var cats = $(this).data('categories') || '';
      if (target === 'all' || cats.split(' ').indexOf(String(target)) !== -1) {
        $(this).show();
      } else {
        $(this).hide();
      }
    });
  });

  // -----------------------------------------------------------------------
  // Registration AJAX
  // -----------------------------------------------------------------------
  $(document).on('submit', '#rwdp-register-form', function (e) {
    e.preventDefault();
    var $form = $(this);
    var $btn  = $form.find('[type="submit"]');
    var $msg  = $form.find('.rwdp-form-message');

    $btn.prop('disabled', true);
    $msg.hide().text('');

    $.ajax({
      url: rwdpPortal.ajaxUrl,
      method: 'POST',
      data: $form.serialize() + '&action=rwdp_register_request&nonce=' + rwdpPortal.nonce,
      success: function (res) {
        if (res.success) {
          $form[0].reset();
          $msg.addClass('rwdp-notice--success').removeClass('rwdp-notice--error')
              .text(res.data.message).show();
        } else {
          $msg.addClass('rwdp-notice--error').removeClass('rwdp-notice--success')
              .text(res.data.message || 'An error occurred.').show();
          $btn.prop('disabled', false);
        }
      },
      error: function () {
        $msg.addClass('rwdp-notice--error').text('A network error occurred.').show();
        $btn.prop('disabled', false);
      }
    });
  });

  // -----------------------------------------------------------------------
  // Set Password AJAX (dealer approval email link)
  // -----------------------------------------------------------------------
  $(document).on('submit', '#rwdp-set-password-form', function (e) {
    e.preventDefault();
    var $form = $(this);
    var $btn  = $form.find('[type="submit"]');
    var $msg  = $form.find('.rwdp-form-message');

    $btn.prop('disabled', true);
    $msg.hide().text('');

    $.ajax({
      url: rwdpPortal.ajaxUrl,
      method: 'POST',
      data: $form.serialize() + '&action=rwdp_set_password&nonce=' + rwdpPortal.setPasswordNonce,
      success: function (res) {
        if (res.success) {
          window.location.href = res.data.redirect;
        } else {
          $msg.addClass('rwdp-notice--error').removeClass('rwdp-notice--success')
              .text(res.data.message || 'An error occurred.').show();
          $btn.prop('disabled', false);
        }
      },
      error: function () {
        $msg.addClass('rwdp-notice--error').text('A network error occurred.').show();
        $btn.prop('disabled', false);
      }
    });
  });

  // -----------------------------------------------------------------------
  // Account update AJAX
  // -----------------------------------------------------------------------
  $(document).on('submit', '#rwdp-account-form', function (e) {
    e.preventDefault();
    var $form = $(this);
    var $btn  = $form.find('[type="submit"]');
    var $msg  = $('#rwdp-account-message');

    $btn.prop('disabled', true);
    $msg.hide().removeClass('rwdp-notice--success rwdp-notice--error');

    $.ajax({
      url: rwdpPortal.ajaxUrl,
      method: 'POST',
      data: $form.serialize() + '&action=rwdp_update_account&nonce=' + rwdpPortal.nonce,
      success: function (res) {
        if (res.success) {
          $msg.addClass('rwdp-notice--success').text(res.data.message).show();
        } else {
          $msg.addClass('rwdp-notice--error').text(res.data.message || 'Update failed.').show();
        }
        $btn.prop('disabled', false);
      },
      error: function () {
        $msg.addClass('rwdp-notice--error').text('A network error occurred.').show();
        $btn.prop('disabled', false);
      }
    });
  });

  // -----------------------------------------------------------------------
  // Dealer profile edit AJAX
  // -----------------------------------------------------------------------
  $(document).on('submit', '.rwdp-dealer-edit-form', function (e) {
    e.preventDefault();
    var $form     = $(this);
    var dealerId  = $form.data('dealer-id');
    var $btn      = $form.find('[type="submit"]');
    var $msg      = $('#rwdp-edit-dealer-msg-' + dealerId);

    $btn.prop('disabled', true);
    $msg.hide().removeClass('rwdp-notice--success rwdp-notice--error');

    $.ajax({
      url: rwdpPortal.ajaxUrl,
      method: 'POST',
      data: $form.serialize() + '&action=rwdp_save_dealer_profile&nonce=' + rwdpPortal.nonce + '&dealer_id=' + dealerId,
      success: function (res) {
        if (res.success) {
          $msg.addClass('rwdp-notice--success').text(res.data.message).show();
        } else {
          $msg.addClass('rwdp-notice--error').text(res.data.message || 'Save failed.').show();
        }
        $btn.prop('disabled', false);
      },
      error: function () {
        $msg.addClass('rwdp-notice--error').text('A network error occurred.').show();
        $btn.prop('disabled', false);
      }
    });
  });

}(jQuery));
