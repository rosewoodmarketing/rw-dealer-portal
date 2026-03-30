/* jshint esversion: 8 */
/* global JSZip, rwdpGallery, jQuery */

(function ($) {
  'use strict';

  $(document).on('click', '.rwdp-zip-download', function () {
    var $btn         = $(this);
    var assetId      = $btn.data('gallery-id');
    var sectionIndex = $btn.data('section-index') !== undefined ? $btn.data('section-index') : 0;
    var nonce        = $btn.data('nonce');
    var origText     = $btn.text();

    $btn.prop('disabled', true).text(rwdpGallery.downloadingText);

    // Fetch image proxy URLs from server
    $.ajax({
      url    : rwdpGallery.ajaxUrl,
      method : 'POST',
      data   : {
        action        : 'rwdp_get_gallery_images',
        asset_id      : assetId,
        section_index : sectionIndex,
        nonce         : nonce
      },
      success: function (res) {
        if (!res.success || !res.data.images || !res.data.images.length) {
          $btn.prop('disabled', false).text(origText);
          return;
        }
        buildAndDownloadZip(res.data.images, assetId, $btn, origText);
      },
      error: function () {
        $btn.prop('disabled', false).text(origText);
      }
    });
  });

  function buildAndDownloadZip(images, assetId, $btn, origText) {
    var zip      = new JSZip();
    var folder   = zip.folder('gallery-' + assetId);
    var promises = [];

    images.forEach(function (img) {
      var promise = fetch(img.url)
        .then(function (response) { return response.arrayBuffer(); })
        .then(function (buffer) {
          folder.file(img.name, buffer);
        });
      promises.push(promise);
    });

    Promise.all(promises)
      .then(function () {
        return zip.generateAsync({ type: 'blob', compression: 'DEFLATE', compressionOptions: { level: 6 } });
      })
      .then(function (blob) {
        var url  = URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.href     = url;
        link.download = 'gallery-' + assetId + '.zip';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
        $btn.prop('disabled', false).text(origText);
      })
      .catch(function () {
        $btn.prop('disabled', false).text(origText);
      });
  }

}(jQuery));
