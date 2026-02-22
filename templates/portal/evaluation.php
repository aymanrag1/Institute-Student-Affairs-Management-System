<?php
/**
 * Portal – Peer Evaluation Form
 *
 * Variables:
 *   $profile       – current student profile
 *   $periods       – active evaluation periods for this cohort (array)
 *   $evaluatees    – other active students in the cohort (array)
 *   $submitted     – [period_id][evaluatee_id] => total  (already submitted)
 *   $peer_criteria – array of 5 criterion labels (from Evaluations::get_peer_criteria())
 *
 * Shortcode: [rsyi_portal_evaluation]
 *
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="rsyi-portal rsyi-evaluation-portal" dir="ltr" style="font-family:sans-serif; max-width:860px; margin:0 auto;">

    <h2 style="border-bottom:3px solid #0073aa; padding-bottom:10px; color:#0073aa;">
        <?php esc_html_e( 'Cohort Peer Evaluation', 'rsyi-sa' ); ?>
    </h2>

    <p style="color:#555; margin-bottom:20px;">
        <?php esc_html_e( 'Rate each student in your cohort on the 5 criteria below. Each criterion is scored from 0 to 10.', 'rsyi-sa' ); ?>
    </p>

    <?php if ( empty( $periods ) ) : ?>
        <div class="rsyi-notice rsyi-notice-info"
             style="background:#e8f4fd; border-left:4px solid #0073aa; padding:14px 18px; border-radius:4px;">
            <strong><?php esc_html_e( 'No Active Evaluation Periods', 'rsyi-sa' ); ?></strong><br>
            <?php esc_html_e( 'There are no open evaluation periods for your cohort at this time. Please check back later.', 'rsyi-sa' ); ?>
        </div>

    <?php elseif ( empty( $evaluatees ) ) : ?>
        <div class="rsyi-notice rsyi-notice-warning"
             style="background:#fff8e1; border-left:4px solid #f9a825; padding:14px 18px; border-radius:4px;">
            <?php esc_html_e( 'No other active students found in your cohort to evaluate.', 'rsyi-sa' ); ?>
        </div>

    <?php else : ?>

        <!-- Period selector -->
        <div style="background:#f8f9fa; border:1px solid #dee2e6; border-radius:6px; padding:18px; margin-bottom:28px;">
            <label for="rsyi_eval_period_select" style="font-weight:700; display:block; margin-bottom:8px;">
                <?php esc_html_e( 'Select Evaluation Period:', 'rsyi-sa' ); ?>
            </label>
            <select id="rsyi_eval_period_select"
                    style="min-width:320px; padding:8px 12px; border:1px solid #ccc; border-radius:4px; font-size:15px;">
                <?php foreach ( $periods as $period ) : ?>
                    <option value="<?php echo esc_attr( $period->id ); ?>">
                        <?php echo esc_html( $period->name ); ?>
                        <?php if ( $period->start_date && $period->end_date ) : ?>
                            (<?php echo esc_html( $period->start_date . ' → ' . $period->end_date ); ?>)
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Criteria legend -->
        <div style="background:#e8f5e9; border:1px solid #a5d6a7; border-radius:6px; padding:14px 18px; margin-bottom:24px;">
            <strong style="color:#2e7d32;"><?php esc_html_e( 'Evaluation Criteria (each /10):', 'rsyi-sa' ); ?></strong>
            <ol style="margin:8px 0 0 18px; padding:0; color:#333; line-height:1.8;">
                <?php foreach ( $peer_criteria as $num => $label ) : ?>
                    <li><strong>C<?php echo esc_html( $num ); ?>:</strong> <?php echo esc_html( $label ); ?></li>
                <?php endforeach; ?>
            </ol>
        </div>

        <!-- One evaluation card per evaluatee -->
        <?php foreach ( $evaluatees as $ev ) :
            $ev_uid = (int) $ev->user_id;
            $ev_name = esc_html( $ev->english_full_name ?: $ev->display_name ?? '' );
        ?>
        <div class="rsyi-eval-card"
             data-evaluatee="<?php echo esc_attr( $ev_uid ); ?>"
             style="background:#fff; border:1px solid #dee2e6; border-radius:8px;
                    margin-bottom:24px; box-shadow:0 1px 4px rgba(0,0,0,.06); overflow:hidden;">

            <!-- Card header -->
            <div style="background:#0073aa; color:#fff; padding:12px 20px; display:flex; justify-content:space-between; align-items:center;">
                <span style="font-size:16px; font-weight:700;"><?php echo $ev_name; ?></span>
                <span class="rsyi-submitted-badge-<?php echo esc_attr( $ev_uid ); ?>"
                      style="display:none; background:#4caf50; color:#fff; border-radius:20px;
                             padding:3px 12px; font-size:13px; font-weight:600;">
                    <?php esc_html_e( 'Submitted', 'rsyi-sa' ); ?>
                </span>
            </div>

            <!-- Card body: scoring table -->
            <div style="padding:20px;">
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f5f5f5;">
                            <th style="padding:8px 12px; text-align:left; border:1px solid #e0e0e0; width:50px;">#</th>
                            <th style="padding:8px 12px; text-align:left; border:1px solid #e0e0e0;"><?php esc_html_e( 'Criterion', 'rsyi-sa' ); ?></th>
                            <th style="padding:8px 12px; text-align:center; border:1px solid #e0e0e0; width:120px;"><?php esc_html_e( 'Score (0–10)', 'rsyi-sa' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $peer_criteria as $num => $label ) : ?>
                        <tr>
                            <td style="padding:8px 12px; border:1px solid #e0e0e0; text-align:center; font-weight:700; color:#0073aa;">C<?php echo esc_html( $num ); ?></td>
                            <td style="padding:8px 12px; border:1px solid #e0e0e0;"><?php echo esc_html( $label ); ?></td>
                            <td style="padding:8px 12px; border:1px solid #e0e0e0; text-align:center;">
                                <input type="number"
                                       class="rsyi-criterion-input"
                                       data-evaluatee="<?php echo esc_attr( $ev_uid ); ?>"
                                       data-criterion="<?php echo esc_attr( $num ); ?>"
                                       name="criterion_<?php echo esc_attr( $num ); ?>"
                                       min="0" max="10" value="0"
                                       style="width:70px; padding:6px; text-align:center;
                                              border:1px solid #ccc; border-radius:4px; font-size:15px;">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background:#f9f9f9; font-weight:700;">
                            <td colspan="2" style="padding:10px 12px; border:1px solid #e0e0e0; text-align:right;">
                                <?php esc_html_e( 'Total', 'rsyi-sa' ); ?>
                            </td>
                            <td style="padding:10px 12px; border:1px solid #e0e0e0; text-align:center; font-size:16px; color:#0073aa;">
                                <span class="rsyi-live-total-<?php echo esc_attr( $ev_uid ); ?>">0</span> / 50
                            </td>
                        </tr>
                    </tfoot>
                </table>

                <!-- Notes -->
                <div style="margin-top:14px;">
                    <label style="font-weight:600; display:block; margin-bottom:5px;">
                        <?php esc_html_e( 'Notes (optional):', 'rsyi-sa' ); ?>
                    </label>
                    <textarea class="rsyi-eval-notes-<?php echo esc_attr( $ev_uid ); ?>"
                              rows="2" style="width:100%; padding:8px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box;"></textarea>
                </div>

                <!-- Submit row -->
                <div style="margin-top:14px; display:flex; align-items:center; gap:14px;">
                    <button type="button"
                            class="rsyi-submit-peer-eval button button-primary"
                            data-evaluatee="<?php echo esc_attr( $ev_uid ); ?>"
                            style="padding:8px 22px; font-size:14px;">
                        <?php esc_html_e( 'Submit Evaluation', 'rsyi-sa' ); ?>
                    </button>
                    <span class="rsyi-eval-msg-<?php echo esc_attr( $ev_uid ); ?>"
                          style="display:none; font-size:14px;"></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

    <?php endif; // end evaluatees check ?>

</div><!-- .rsyi-evaluation-portal -->

<!-- Prefill already-submitted evaluations -->
<?php
$submitted_json = wp_json_encode( $submitted );
$nonce_val      = wp_create_nonce( 'rsyi_sa_portal' );
$ajax_url       = admin_url( 'admin-ajax.php' );
?>
<script>
jQuery(function($){
    var submitted    = <?php echo $submitted_json; // phpcs:ignore – already JSON-encoded ?>;
    var ajaxUrl      = <?php echo wp_json_encode( $ajax_url ); ?>;
    var portalNonce  = <?php echo wp_json_encode( $nonce_val ); ?>;

    function getSelectedPeriod(){
        return $('#rsyi_eval_period_select').val();
    }

    // Live total calculation
    function recalcTotal(uid){
        var total = 0;
        $('.rsyi-criterion-input[data-evaluatee="' + uid + '"]').each(function(){
            var v = parseInt($(this).val(), 10) || 0;
            total += Math.min(10, Math.max(0, v));
        });
        $('.rsyi-live-total-' + uid).text(total);
    }

    $(document).on('input change', '.rsyi-criterion-input', function(){
        recalcTotal($(this).data('evaluatee'));
    });

    // When period changes, update submitted badges
    function refreshBadges(){
        var pid = parseInt(getSelectedPeriod(), 10);
        $('.rsyi-eval-card').each(function(){
            var uid = parseInt($(this).data('evaluatee'), 10);
            if( submitted[pid] && typeof submitted[pid][uid] !== 'undefined' ){
                $('.rsyi-submitted-badge-' + uid).show();
            } else {
                $('.rsyi-submitted-badge-' + uid).hide();
            }
        });
    }
    $('#rsyi_eval_period_select').on('change', refreshBadges);
    refreshBadges();

    // Submit peer evaluation
    $(document).on('click', '.rsyi-submit-peer-eval', function(){
        var uid    = $(this).data('evaluatee');
        var pid    = getSelectedPeriod();
        var $btn   = $(this);
        var $msg   = $('.rsyi-eval-msg-' + uid);

        if( ! pid ){
            $msg.show().css('color','red').text('<?php echo esc_js( __( 'Please select an evaluation period.', 'rsyi-sa' ) ); ?>');
            return;
        }

        var data = {
            action:        'rsyi_save_peer_evaluation',
            _nonce:        portalNonce,
            period_id:     pid,
            evaluatee_id:  uid,
            notes:         $('.rsyi-eval-notes-' + uid).val()
        };

        // Collect criterion scores
        var hasScore = false;
        $('.rsyi-criterion-input[data-evaluatee="' + uid + '"]').each(function(){
            data[ 'criterion_' + $(this).data('criterion') ] = $(this).val();
            if( parseInt($(this).val(),10) > 0 ) hasScore = true;
        });

        if( ! hasScore ){
            $msg.show().css('color','#e65100').text('<?php echo esc_js( __( 'Please enter at least one score before submitting.', 'rsyi-sa' ) ); ?>');
            return;
        }

        $btn.prop('disabled', true).text('<?php echo esc_js( __( 'Saving…', 'rsyi-sa' ) ); ?>');
        $msg.hide();

        $.post(ajaxUrl, data, function(res){
            $btn.prop('disabled', false).text('<?php echo esc_js( __( 'Submit Evaluation', 'rsyi-sa' ) ); ?>');
            if( res.success ){
                $msg.show().css('color','green').text('✓ ' + res.data.message);
                // Mark as submitted
                if( ! submitted[pid] ) submitted[pid] = {};
                submitted[pid][uid] = res.data.total;
                $('.rsyi-submitted-badge-' + uid).show();
            } else {
                $msg.show().css('color','red').text('✗ ' + res.data.message);
            }
        }).fail(function(){
            $btn.prop('disabled', false).text('<?php echo esc_js( __( 'Submit Evaluation', 'rsyi-sa' ) ); ?>');
            $msg.show().css('color','red').text('<?php echo esc_js( __( 'Connection error. Please try again.', 'rsyi-sa' ) ); ?>');
        });
    });
});
</script>
