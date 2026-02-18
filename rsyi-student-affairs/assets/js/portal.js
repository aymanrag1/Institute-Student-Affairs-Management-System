/**
 * RSYI Student Affairs – Portal JavaScript
 */
/* global rsyiPortal, jQuery */

(function ($) {
    'use strict';

    // ── File upload drag & drop enhancement ───────────────────────────────

    $('.rsyi-file-input').each(function () {
        var input    = $(this);
        var card     = input.closest('.rsyi-doc-card');

        card.on('dragover', function (e) {
            e.preventDefault();
            card.addClass('rsyi-drag-over');
        }).on('dragleave drop', function () {
            card.removeClass('rsyi-drag-over');
        }).on('drop', function (e) {
            e.preventDefault();
            var files = e.originalEvent.dataTransfer.files;
            if (files.length) {
                input[0].files = files;
                input.trigger('change');
            }
        });
    });

    // ── Show file name on selection ────────────────────────────────────────

    $(document).on('change', '.rsyi-file-input', function () {
        var name = this.files[0] ? this.files[0].name : '';
        var status = $(this).siblings('.rsyi-upload-status');
        if (name) status.text('📎 ' + name);
    });

    // ── Smooth scroll to first warning ────────────────────────────────────

    if ($('.rsyi-alert-danger').length) {
        $('html, body').animate({
            scrollTop: $('.rsyi-alert-danger').offset().top - 60
        }, 500);
    }

    // ── Form validation feedback ───────────────────────────────────────────

    $(document).on('submit', 'form[id^="rsyi_"]', function () {
        var form = $(this);
        var valid = true;
        form.find('[required]').each(function () {
            if (!$(this).val().trim()) {
                $(this).addClass('rsyi-invalid');
                valid = false;
            } else {
                $(this).removeClass('rsyi-invalid');
            }
        });
        return valid;
    });

}(jQuery));
