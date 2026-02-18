/**
 * RSYI Student Affairs – Admin JavaScript
 */
/* global rsyiSA, jQuery */

(function ($) {
    'use strict';

    // ── Generic approve/reject with notes modal ────────────────────────────

    /**
     * Bind click on a data-action button and post to AJAX.
     * Button attrs:  data-action, data-id, data-confirm-msg
     */
    function bindActionButton(selector, ajaxAction, idParam) {
        $(document).on('click', selector, function () {
            var btn    = $(this);
            var id     = btn.data('id');
            var notes  = '';
            var msg    = btn.data('confirm-msg') || rsyiSA.i18n.confirm_approve;

            if (!confirm(msg)) return;

            if (btn.data('needs-notes')) {
                notes = prompt(rsyiSA.i18n.confirm_reject) || '';
                if (!notes) return;
            }

            var payload = { action: ajaxAction, _nonce: rsyiSA.nonce, notes: notes };
            payload[idParam] = id;

            btn.prop('disabled', true);

            $.post(rsyiSA.ajaxUrl, payload, function (res) {
                if (res.success) {
                    btn.closest('tr').addClass('rsyi-row-muted');
                    btn.replaceWith('<span class="rsyi-done">✅</span>');
                    showNotice(res.data.message, 'success');
                } else {
                    btn.prop('disabled', false);
                    showNotice(res.data.message || 'Error', 'error');
                }
            }).fail(function () {
                btn.prop('disabled', false);
                showNotice('Connection failed', 'error');
            });
        });
    }

    function showNotice(msg, type) {
        var cls = type === 'success' ? 'notice-success' : 'notice-error';
        var notice = $('<div class="notice is-dismissible ' + cls + '"><p>' + $('<div>').text(msg).html() + '</p></div>');
        $('.wp-header-end').after(notice);
        setTimeout(function () { notice.fadeOut(400, function () { $(this).remove(); }); }, 4000);
    }

    // ── Approve / reject buttons for documents ─────────────────────────────

    bindActionButton('.rsyi-doc-approve-btn', 'rsyi_approve_document', 'doc_id');

    $(document).on('click', '.rsyi-doc-reject-btn', function () {
        var btn    = $(this);
        var doc_id = btn.data('id');
        var reason = prompt(rsyiSA.i18n.confirm_reject);
        if (!reason) return;
        btn.prop('disabled', true);
        $.post(rsyiSA.ajaxUrl, {
            action: 'rsyi_reject_document',
            _nonce: rsyiSA.nonce,
            doc_id: doc_id,
            rejection_reason: reason
        }, function (res) {
            btn.prop('disabled', false);
            if (res.success) {
                location.reload();
            } else {
                showNotice(res.data.message, 'error');
            }
        });
    });

    // ── Approve buttons for exit permits ───────────────────────────────────

    bindActionButton('.rsyi-exit-approve-btn', 'rsyi_approve_exit_permit', 'permit_id');
    bindActionButton('.rsyi-exit-execute-btn', 'rsyi_execute_exit_permit', 'permit_id');

    $(document).on('click', '.rsyi-exit-reject-btn', function () {
        var btn       = $(this);
        var permit_id = btn.data('id');
        var notes     = prompt(rsyiSA.i18n.confirm_reject);
        if (!notes) return;
        btn.prop('disabled', true);
        $.post(rsyiSA.ajaxUrl, {
            action: 'rsyi_reject_exit_permit',
            _nonce: rsyiSA.nonce,
            permit_id: permit_id,
            notes: notes
        }, function (res) {
            btn.prop('disabled', false);
            if (res.success) { location.reload(); }
            else { showNotice(res.data.message, 'error'); }
        });
    });

    // ── Approve buttons for overnight permits ──────────────────────────────

    bindActionButton('.rsyi-overnight-approve-btn', 'rsyi_approve_overnight_permit', 'permit_id');
    bindActionButton('.rsyi-overnight-execute-btn', 'rsyi_execute_overnight_permit', 'permit_id');

    $(document).on('click', '.rsyi-overnight-reject-btn', function () {
        var btn       = $(this);
        var permit_id = btn.data('id');
        var notes     = prompt(rsyiSA.i18n.confirm_reject);
        if (!notes) return;
        btn.prop('disabled', true);
        $.post(rsyiSA.ajaxUrl, {
            action: 'rsyi_reject_overnight_permit',
            _nonce: rsyiSA.nonce,
            permit_id: permit_id,
            notes: notes
        }, function (res) {
            btn.prop('disabled', false);
            if (res.success) { location.reload(); }
            else { showNotice(res.data.message, 'error'); }
        });
    });

    // ── Expulsion case ─────────────────────────────────────────────────────

    $(document).on('click', '.rsyi-expulsion-approve-btn', function () {
        var btn     = $(this);
        var case_id = btn.data('id');
        var notes   = prompt('ملاحظات العميد (اختياري):') || '';
        if (!confirm('هل تؤكد الموافقة على قرار الطرد؟')) return;
        btn.prop('disabled', true);
        $.post(rsyiSA.ajaxUrl, {
            action: 'rsyi_approve_expulsion',
            _nonce: rsyiSA.nonce,
            case_id: case_id,
            notes: notes
        }, function (res) {
            btn.prop('disabled', false);
            if (res.success) {
                showNotice(res.data.message, 'success');
                setTimeout(function () { location.reload(); }, 1500);
            } else {
                showNotice(res.data.message, 'error');
            }
        });
    });

    // ── Cohort transfer ────────────────────────────────────────────────────

    bindActionButton('.rsyi-transfer-approve-btn', 'rsyi_approve_cohort_transfer', 'transfer_id');

    $(document).on('click', '.rsyi-transfer-reject-btn', function () {
        var btn         = $(this);
        var transfer_id = btn.data('id');
        var notes       = prompt('سبب الرفض:');
        if (!notes) return;
        btn.prop('disabled', true);
        $.post(rsyiSA.ajaxUrl, {
            action: 'rsyi_reject_cohort_transfer',
            _nonce: rsyiSA.nonce,
            transfer_id: transfer_id,
            notes: notes
        }, function (res) {
            btn.prop('disabled', false);
            if (res.success) { location.reload(); }
            else { showNotice(res.data.message, 'error'); }
        });
    });

    // ── Tab navigation ─────────────────────────────────────────────────────

    $(document).on('click', '.rsyi-tab-btn', function () {
        var target = $(this).data('target');
        $('.rsyi-tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.rsyi-tab-panel').hide();
        $('#' + target).show();
    });
    $('.rsyi-tab-btn:first').trigger('click');

}(jQuery));
