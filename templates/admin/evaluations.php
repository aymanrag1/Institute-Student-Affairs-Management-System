<?php
/**
 * Admin Template: Evaluations
 *
 * Displays evaluation periods, aggregation table, and evaluation entry forms.
 *
 * @package RSYI_StudentAffairs
 * @var string $tab  Current active tab (aggregation | periods | enter)
 */

defined( 'ABSPATH' ) || exit;

use RSYI_SA\Modules\Evaluations;

$active_tab = $tab ?? 'aggregation';
$cohorts    = Evaluations::get_cohorts();
$periods    = Evaluations::get_periods();

$peer_criteria  = Evaluations::get_peer_criteria();
$admin_criteria = Evaluations::get_admin_criteria();

// Selected period for aggregation / entry
$selected_period_id = absint( $_GET['period_id'] ?? 0 );
$aggregation        = $selected_period_id ? Evaluations::get_aggregation( $selected_period_id ) : [];

// Load selected period info
$selected_period = null;
if ( $selected_period_id && ! empty( $periods ) ) {
    foreach ( $periods as $p ) {
        if ( (int) $p->id === $selected_period_id ) {
            $selected_period = $p;
            break;
        }
    }
}

// Evaluatee list for evaluation entry form
$evaluatee_list = [];
if ( $selected_period ) {
    global $wpdb;
    $sp = $wpdb->prefix . 'rsyi_student_profiles';
    $evaluatee_list = $wpdb->get_results( $wpdb->prepare(
        "SELECT sp.user_id, sp.english_full_name FROM {$sp} sp
         WHERE sp.cohort_id = %d AND sp.status = 'active'
         ORDER BY sp.english_full_name ASC",
        (int) $selected_period->cohort_id
    ) );
}
?>
<h1 class="wp-heading-inline"><?php esc_html_e( 'Evaluations', 'rsyi-sa' ); ?></h1>
<hr class="wp-header-end">

<!-- ── Tabs ── -->
<nav class="nav-tab-wrapper rsyi-tabs" style="margin-bottom:20px;">
    <a href="<?php echo esc_url( add_query_arg( [ 'page' => 'rsyi-evaluations', 'tab' => 'aggregation' ], admin_url( 'admin.php' ) ) ); ?>"
       class="nav-tab <?php echo $active_tab === 'aggregation' ? 'nav-tab-active' : ''; ?>">
        <?php esc_html_e( 'Aggregation Table', 'rsyi-sa' ); ?>
    </a>
    <?php if ( current_user_can( 'rsyi_submit_admin_evaluation' ) ) : ?>
    <a href="<?php echo esc_url( add_query_arg( [ 'page' => 'rsyi-evaluations', 'tab' => 'enter' ], admin_url( 'admin.php' ) ) ); ?>"
       class="nav-tab <?php echo $active_tab === 'enter' ? 'nav-tab-active' : ''; ?>">
        <?php esc_html_e( 'Enter Evaluation', 'rsyi-sa' ); ?>
    </a>
    <?php endif; ?>
    <?php if ( current_user_can( 'rsyi_manage_evaluation_periods' ) ) : ?>
    <a href="<?php echo esc_url( add_query_arg( [ 'page' => 'rsyi-evaluations', 'tab' => 'periods' ], admin_url( 'admin.php' ) ) ); ?>"
       class="nav-tab <?php echo $active_tab === 'periods' ? 'nav-tab-active' : ''; ?>">
        <?php esc_html_e( 'Manage Periods', 'rsyi-sa' ); ?>
    </a>
    <?php endif; ?>
</nav>

<!-- ── Aggregation Table tab ── -->
<?php if ( $active_tab === 'aggregation' ) : ?>

<div class="rsyi-card" style="margin-bottom:20px; padding:20px; background:#fff; border:1px solid #ccd0d4; border-radius:4px;">
    <h2 style="margin-top:0;"><?php esc_html_e( 'Select Evaluation Period', 'rsyi-sa' ); ?></h2>
    <form method="get" action="">
        <input type="hidden" name="page" value="rsyi-evaluations">
        <input type="hidden" name="tab" value="aggregation">
        <select name="period_id" id="period_id" style="min-width:280px; margin-right:10px;">
            <option value=""><?php esc_html_e( '— Select a Period —', 'rsyi-sa' ); ?></option>
            <?php foreach ( $periods as $p ) : ?>
                <option value="<?php echo esc_attr( $p->id ); ?>"
                    <?php selected( $selected_period_id, (int) $p->id ); ?>>
                    <?php echo esc_html( $p->name . ' (' . $p->cohort_name . ')' ); ?>
                    <?php echo $p->is_active ? '' : ' [inactive]'; ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="button button-primary"><?php esc_html_e( 'Load', 'rsyi-sa' ); ?></button>
    </form>
</div>

<?php if ( $selected_period && ! empty( $aggregation ) ) :
    // Determine which admin roles actually submitted evaluations for this period
    global $wpdb;
    $ae_table    = $wpdb->prefix . 'rsyi_admin_evaluations';
    $roles_found = $wpdb->get_col( $wpdb->prepare(
        "SELECT DISTINCT evaluator_role FROM {$ae_table} WHERE period_id = %d",
        $selected_period_id
    ) );
    $has_sa_mgr  = in_array( 'student_affairs_mgr', $roles_found, true );
    $has_dorm    = in_array( 'dorm_supervisor', $roles_found, true );
    $has_dean    = in_array( 'dean', $roles_found, true );
?>
<div style="overflow-x:auto;">
<table class="wp-list-table widefat fixed striped" id="rsyi-aggregation-table"
       style="font-size:13px; white-space:nowrap;">
<thead>
    <tr style="background:#0073aa; color:#fff;">
        <th rowspan="3" style="vertical-align:middle; min-width:160px; background:#0073aa; color:#fff; border-right:2px solid #fff;">
            <?php echo esc_html( $selected_period->name ); ?><br>
            <small style="font-weight:400;"><?php echo esc_html( $selected_period->cohort_name ); ?></small>
        </th>
        <!-- Peer evaluations header -->
        <th colspan="6" style="text-align:center; background:#1d5b8a; color:#fff; border-right:2px solid #fff;">
            <?php esc_html_e( 'Peer Evaluation (Cohort)', 'rsyi-sa' ); ?>
        </th>
        <?php if ( $has_sa_mgr ) : ?>
        <th colspan="7" style="text-align:center; background:#2e7d32; color:#fff; border-right:2px solid #fff;">
            <?php esc_html_e( 'Student Affairs Manager', 'rsyi-sa' ); ?>
        </th>
        <?php endif; ?>
        <?php if ( $has_dorm ) : ?>
        <th colspan="7" style="text-align:center; background:#6a1aab; color:#fff; border-right:2px solid #fff;">
            <?php esc_html_e( 'Dorm Supervisor', 'rsyi-sa' ); ?>
        </th>
        <?php endif; ?>
        <?php if ( $has_dean ) : ?>
        <th colspan="7" style="text-align:center; background:#bf360c; color:#fff; border-right:2px solid #fff;">
            <?php esc_html_e( 'Dean', 'rsyi-sa' ); ?>
        </th>
        <?php endif; ?>
        <th colspan="7" style="text-align:center; background:#37474f; color:#fff;">
            <?php esc_html_e( 'Grand Total', 'rsyi-sa' ); ?>
        </th>
    </tr>
    <tr>
        <!-- Peer sub-headers: C1–C5 + Total -->
        <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
        <th style="text-align:center; background:#1d5b8a; color:#fff;" title="<?php echo esc_attr( $peer_criteria[ $i ] ); ?>">C<?php echo $i; ?></th>
        <?php endfor; ?>
        <th style="text-align:center; background:#154a72; color:#fff; border-right:2px solid #fff;"><?php esc_html_e( 'Total', 'rsyi-sa' ); ?></th>

        <?php if ( $has_sa_mgr ) : ?>
        <?php for ( $i = 1; $i <= 6; $i++ ) : ?>
        <th style="text-align:center; background:#2e7d32; color:#fff;" title="<?php echo esc_attr( $admin_criteria[ $i ] ); ?>">C<?php echo $i; ?></th>
        <?php endfor; ?>
        <th style="text-align:center; background:#1b5e20; color:#fff; border-right:2px solid #fff;"><?php esc_html_e( 'Total', 'rsyi-sa' ); ?></th>
        <?php endif; ?>

        <?php if ( $has_dorm ) : ?>
        <?php for ( $i = 1; $i <= 6; $i++ ) : ?>
        <th style="text-align:center; background:#6a1aab; color:#fff;" title="<?php echo esc_attr( $admin_criteria[ $i ] ); ?>">C<?php echo $i; ?></th>
        <?php endfor; ?>
        <th style="text-align:center; background:#4a1080; color:#fff; border-right:2px solid #fff;"><?php esc_html_e( 'Total', 'rsyi-sa' ); ?></th>
        <?php endif; ?>

        <?php if ( $has_dean ) : ?>
        <?php for ( $i = 1; $i <= 6; $i++ ) : ?>
        <th style="text-align:center; background:#bf360c; color:#fff;" title="<?php echo esc_attr( $admin_criteria[ $i ] ); ?>">C<?php echo $i; ?></th>
        <?php endfor; ?>
        <th style="text-align:center; background:#8c2a00; color:#fff; border-right:2px solid #fff;"><?php esc_html_e( 'Total', 'rsyi-sa' ); ?></th>
        <?php endif; ?>

        <!-- Grand total sub-headers: C1–C6 + Overall -->
        <?php for ( $i = 1; $i <= 6; $i++ ) : ?>
        <th style="text-align:center; background:#37474f; color:#fff;">C<?php echo $i; ?></th>
        <?php endfor; ?>
        <th style="text-align:center; background:#263238; color:#fff;"><?php esc_html_e( 'Total', 'rsyi-sa' ); ?></th>
    </tr>
    <tr style="font-size:11px; background:#f0f6fb;">
        <!-- Peer criteria labels -->
        <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
        <td style="text-align:center; max-width:70px; overflow:hidden; text-overflow:ellipsis;" title="<?php echo esc_attr( $peer_criteria[ $i ] ); ?>">/10</td>
        <?php endfor; ?>
        <td style="text-align:center; font-weight:700;">/50 × n</td>

        <?php if ( $has_sa_mgr ) : ?>
        <?php for ( $i = 1; $i <= 6; $i++ ) : ?>
        <td style="text-align:center;">/10</td>
        <?php endfor; ?>
        <td style="text-align:center; font-weight:700;">/60</td>
        <?php endif; ?>

        <?php if ( $has_dorm ) : ?>
        <?php for ( $i = 1; $i <= 6; $i++ ) : ?>
        <td style="text-align:center;">/10</td>
        <?php endfor; ?>
        <td style="text-align:center; font-weight:700;">/60</td>
        <?php endif; ?>

        <?php if ( $has_dean ) : ?>
        <?php for ( $i = 1; $i <= 6; $i++ ) : ?>
        <td style="text-align:center;">/10</td>
        <?php endfor; ?>
        <td style="text-align:center; font-weight:700;">/60</td>
        <?php endif; ?>

        <!-- Grand totals -->
        <?php for ( $i = 1; $i <= 6; $i++ ) : ?>
        <td style="text-align:center;"></td>
        <?php endfor; ?>
        <td style="text-align:center; font-weight:700;"></td>
    </tr>
</thead>
<tbody>
<?php foreach ( $aggregation as $row ) :
    $student = $row['student'];
    $peer    = $row['peer'];
    $admin   = $row['admin'];
    $grand   = $row['grand'];
    $overall = $row['overall'];

    $sa_mgr  = $admin['student_affairs_mgr'];
    $dorm    = $admin['dorm_supervisor'];
    $dean    = $admin['dean'];
?>
<tr>
    <td style="font-weight:600; border-right:2px solid #ddd;">
        <?php echo esc_html( $student->english_full_name ?: $student->display_name ); ?>
        <br><small style="color:#999;">Peers: <?php echo (int) $peer['count']; ?></small>
    </td>
    <!-- Peer: C1–C5 -->
    <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
    <td style="text-align:center;"><?php echo $peer[ $i ] > 0 ? esc_html( $peer[ $i ] ) : '<span style="color:#ccc;">—</span>'; ?></td>
    <?php endfor; ?>
    <td style="text-align:center; font-weight:700; border-right:2px solid #ddd;"><?php echo $peer['total'] > 0 ? esc_html( $peer['total'] ) : '<span style="color:#ccc;">—</span>'; ?></td>

    <?php if ( $has_sa_mgr ) : ?>
    <?php for ( $i = 1; $i <= 6; $i++ ) : ?>
    <td style="text-align:center;"><?php echo $sa_mgr ? esc_html( $sa_mgr->{"criterion_{$i}"} ) : '<span style="color:#ccc;">—</span>'; ?></td>
    <?php endfor; ?>
    <td style="text-align:center; font-weight:700; border-right:2px solid #ddd;"><?php echo $sa_mgr ? esc_html( $sa_mgr->total ) : '<span style="color:#ccc;">—</span>'; ?></td>
    <?php endif; ?>

    <?php if ( $has_dorm ) : ?>
    <?php for ( $i = 1; $i <= 6; $i++ ) : ?>
    <td style="text-align:center;"><?php echo $dorm ? esc_html( $dorm->{"criterion_{$i}"} ) : '<span style="color:#ccc;">—</span>'; ?></td>
    <?php endfor; ?>
    <td style="text-align:center; font-weight:700; border-right:2px solid #ddd;"><?php echo $dorm ? esc_html( $dorm->total ) : '<span style="color:#ccc;">—</span>'; ?></td>
    <?php endif; ?>

    <?php if ( $has_dean ) : ?>
    <?php for ( $i = 1; $i <= 6; $i++ ) : ?>
    <td style="text-align:center;"><?php echo $dean ? esc_html( $dean->{"criterion_{$i}"} ) : '<span style="color:#ccc;">—</span>'; ?></td>
    <?php endfor; ?>
    <td style="text-align:center; font-weight:700; border-right:2px solid #ddd;"><?php echo $dean ? esc_html( $dean->total ) : '<span style="color:#ccc;">—</span>'; ?></td>
    <?php endif; ?>

    <!-- Grand totals per criterion -->
    <?php for ( $i = 1; $i <= 6; $i++ ) : ?>
    <td style="text-align:center; font-weight:600;"><?php echo $grand[ $i ] > 0 ? esc_html( $grand[ $i ] ) : '<span style="color:#ccc;">—</span>'; ?></td>
    <?php endfor; ?>
    <td style="text-align:center; font-weight:800; font-size:14px; color:#0073aa;"><?php echo esc_html( $overall ); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
<tfoot>
    <tr style="background:#f9f9f9; font-weight:700;">
        <td><?php esc_html_e( 'Column Totals', 'rsyi-sa' ); ?></td>
        <?php
        // Peer column totals
        for ( $i = 1; $i <= 5; $i++ ) {
            $col_sum = array_sum( array_column( array_column( $aggregation, 'peer' ), $i ) );
            echo '<td style="text-align:center;">' . esc_html( $col_sum ) . '</td>';
        }
        $peer_total_sum = array_sum( array_column( array_column( $aggregation, 'peer' ), 'total' ) );
        echo '<td style="text-align:center; border-right:2px solid #ddd;">' . esc_html( $peer_total_sum ) . '</td>';

        if ( $has_sa_mgr ) {
            for ( $i = 1; $i <= 6; $i++ ) {
                $col_sum = 0;
                foreach ( $aggregation as $r ) {
                    if ( $r['admin']['student_affairs_mgr'] ) {
                        $col_sum += (int) $r['admin']['student_affairs_mgr']->{"criterion_{$i}"};
                    }
                }
                echo '<td style="text-align:center;">' . esc_html( $col_sum ) . '</td>';
            }
            $sa_total = 0;
            foreach ( $aggregation as $r ) {
                if ( $r['admin']['student_affairs_mgr'] ) {
                    $sa_total += (int) $r['admin']['student_affairs_mgr']->total;
                }
            }
            echo '<td style="text-align:center; border-right:2px solid #ddd;">' . esc_html( $sa_total ) . '</td>';
        }

        if ( $has_dorm ) {
            for ( $i = 1; $i <= 6; $i++ ) {
                $col_sum = 0;
                foreach ( $aggregation as $r ) {
                    if ( $r['admin']['dorm_supervisor'] ) {
                        $col_sum += (int) $r['admin']['dorm_supervisor']->{"criterion_{$i}"};
                    }
                }
                echo '<td style="text-align:center;">' . esc_html( $col_sum ) . '</td>';
            }
            $dorm_total = 0;
            foreach ( $aggregation as $r ) {
                if ( $r['admin']['dorm_supervisor'] ) {
                    $dorm_total += (int) $r['admin']['dorm_supervisor']->total;
                }
            }
            echo '<td style="text-align:center; border-right:2px solid #ddd;">' . esc_html( $dorm_total ) . '</td>';
        }

        if ( $has_dean ) {
            for ( $i = 1; $i <= 6; $i++ ) {
                $col_sum = 0;
                foreach ( $aggregation as $r ) {
                    if ( $r['admin']['dean'] ) {
                        $col_sum += (int) $r['admin']['dean']->{"criterion_{$i}"};
                    }
                }
                echo '<td style="text-align:center;">' . esc_html( $col_sum ) . '</td>';
            }
            $dean_total = 0;
            foreach ( $aggregation as $r ) {
                if ( $r['admin']['dean'] ) {
                    $dean_total += (int) $r['admin']['dean']->total;
                }
            }
            echo '<td style="text-align:center; border-right:2px solid #ddd;">' . esc_html( $dean_total ) . '</td>';
        }

        // Grand totals per criterion
        for ( $i = 1; $i <= 6; $i++ ) {
            $col_sum = array_sum( array_column( array_column( $aggregation, 'grand' ), $i ) );
            echo '<td style="text-align:center;">' . esc_html( $col_sum ) . '</td>';
        }
        $grand_sum = array_sum( array_column( $aggregation, 'overall' ) );
        echo '<td style="text-align:center; color:#0073aa;">' . esc_html( $grand_sum ) . '</td>';
        ?>
    </tr>
</tfoot>
</table>
</div>

<!-- Legend -->
<div style="margin-top:14px; font-size:12px; color:#555;">
    <strong><?php esc_html_e( 'Criteria Legend:', 'rsyi-sa' ); ?></strong><br>
    <em><?php esc_html_e( 'Peer (5 criteria, /10 each):', 'rsyi-sa' ); ?></em>
    <?php foreach ( $peer_criteria as $k => $label ) : ?>
        <span style="margin-right:12px;"><strong>C<?php echo $k; ?>:</strong> <?php echo esc_html( $label ); ?></span>
    <?php endforeach; ?>
    <br>
    <em><?php esc_html_e( 'Admin / Supervisor (6 criteria, /10 each):', 'rsyi-sa' ); ?></em>
    <?php foreach ( $admin_criteria as $k => $label ) : ?>
        <span style="margin-right:12px;"><strong>C<?php echo $k; ?>:</strong> <?php echo esc_html( $label ); ?></span>
    <?php endforeach; ?>
</div>

<?php elseif ( $selected_period && empty( $aggregation ) ) : ?>
<div class="notice notice-warning"><p><?php esc_html_e( 'No active students found in this cohort for the selected period.', 'rsyi-sa' ); ?></p></div>
<?php elseif ( ! $selected_period_id ) : ?>
<div class="notice notice-info"><p><?php esc_html_e( 'Please select an evaluation period to view the aggregation table.', 'rsyi-sa' ); ?></p></div>
<?php endif; ?>

<?php endif; // end aggregation tab ?>


<!-- ── Enter Evaluation tab ── -->
<?php if ( $active_tab === 'enter' && current_user_can( 'rsyi_submit_admin_evaluation' ) ) : ?>

<div style="max-width:800px;">
<h2><?php esc_html_e( 'Enter Admin / Supervisor Evaluation', 'rsyi-sa' ); ?></h2>
<p style="color:#555;"><?php esc_html_e( 'Rate a student on the 6 criteria below (/10 each). The system will record your role automatically.', 'rsyi-sa' ); ?></p>

<form id="rsyi-admin-eval-form" style="background:#fff; padding:24px; border:1px solid #ccd0d4; border-radius:4px;">
    <?php wp_nonce_field( 'rsyi_sa_admin', '_nonce' ); ?>
    <input type="hidden" name="action" value="rsyi_save_admin_evaluation">

    <table class="form-table">
        <tr>
            <th scope="row"><label for="eval_period"><?php esc_html_e( 'Evaluation Period', 'rsyi-sa' ); ?></label></th>
            <td>
                <select name="period_id" id="eval_period" required style="min-width:300px;">
                    <option value=""><?php esc_html_e( '— Select a Period —', 'rsyi-sa' ); ?></option>
                    <?php foreach ( $periods as $p ) :
                        if ( ! $p->is_active ) continue; ?>
                        <option value="<?php echo esc_attr( $p->id ); ?>"
                            <?php selected( $selected_period_id, (int) $p->id ); ?>>
                            <?php echo esc_html( $p->name . ' — Cohort: ' . $p->cohort_name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="eval_evaluatee"><?php esc_html_e( 'Student Being Evaluated', 'rsyi-sa' ); ?></label></th>
            <td>
                <select name="evaluatee_id" id="eval_evaluatee" required style="min-width:300px;">
                    <option value=""><?php esc_html_e( '— Select a Period first —', 'rsyi-sa' ); ?></option>
                    <?php foreach ( $evaluatee_list as $ev ) : ?>
                        <option value="<?php echo esc_attr( $ev->user_id ); ?>">
                            <?php echo esc_html( $ev->english_full_name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span id="eval_evaluatee_loading" style="display:none; margin-right:8px; color:#888;">
                    <?php esc_html_e( 'Loading…', 'rsyi-sa' ); ?>
                </span>
            </td>
        </tr>
    </table>

    <h3 style="border-bottom:1px solid #eee; padding-bottom:8px;"><?php esc_html_e( 'Criteria Scores (0 – 10)', 'rsyi-sa' ); ?></h3>

    <table class="widefat" style="margin-bottom:20px;">
        <thead>
            <tr>
                <th style="width:40px;">#</th>
                <th><?php esc_html_e( 'Criterion', 'rsyi-sa' ); ?></th>
                <th style="width:80px; text-align:center;"><?php esc_html_e( 'Score /10', 'rsyi-sa' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $admin_criteria as $num => $label ) : ?>
            <tr>
                <td><?php echo esc_html( $num ); ?></td>
                <td><?php echo esc_html( $label ); ?></td>
                <td style="text-align:center;">
                    <input type="number" name="criterion_<?php echo esc_attr( $num ); ?>"
                           min="0" max="10" value="0" required
                           style="width:60px; text-align:center;"
                           class="rsyi-score-input" data-max="10">
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background:#f9f9f9; font-weight:700;">
                <td colspan="2" style="text-align:right;"><?php esc_html_e( 'Total', 'rsyi-sa' ); ?></td>
                <td style="text-align:center;"><span id="rsyi-admin-eval-total">0</span> / 60</td>
            </tr>
        </tfoot>
    </table>

    <div>
        <label for="eval_notes"><strong><?php esc_html_e( 'Notes (optional)', 'rsyi-sa' ); ?></strong></label><br>
        <textarea name="notes" id="eval_notes" rows="3" style="width:100%; margin-top:6px;"></textarea>
    </div>

    <p style="margin-top:16px;">
        <button type="submit" class="button button-primary button-large" id="rsyi-admin-eval-submit">
            <?php esc_html_e( 'Save Evaluation', 'rsyi-sa' ); ?>
        </button>
        <span id="rsyi-admin-eval-msg" style="margin-left:12px; display:none;"></span>
    </p>
</form>
</div>

<script>
jQuery(function($){
    // Live total calculation
    function recalcAdminTotal(){
        var total = 0;
        $('.rsyi-score-input').each(function(){
            var v = parseInt($(this).val(), 10) || 0;
            var max = parseInt($(this).data('max'), 10) || 10;
            total += Math.min(max, Math.max(0, v));
        });
        $('#rsyi-admin-eval-total').text(total);
    }
    $(document).on('input change', '.rsyi-score-input', recalcAdminTotal);

    // Dynamic evaluatee loading when period changes
    $('#eval_period').on('change', function(){
        var period_id = $(this).val();
        var $sel  = $('#eval_evaluatee');
        var $spin = $('#eval_evaluatee_loading');

        $sel.html('<option value=""><?php echo esc_js( esc_html__( '— Loading… —', 'rsyi-sa' ) ); ?></option>');

        if ( ! period_id ) {
            $sel.html('<option value=""><?php echo esc_js( esc_html__( '— Select a Period first —', 'rsyi-sa' ) ); ?></option>');
            return;
        }

        $spin.show();
        $.post(rsyiSA.ajaxUrl, {
            action:    'rsyi_get_period_students',
            _nonce:    rsyiSA.nonce,
            period_id: period_id
        }, function(res){
            $spin.hide();
            if ( res.success && res.data.students.length ) {
                var opts = '<option value=""><?php echo esc_js( esc_html__( '— Select a Student —', 'rsyi-sa' ) ); ?></option>';
                $.each(res.data.students, function(i, s){
                    opts += '<option value="' + s.user_id + '">' + s.english_full_name + '</option>';
                });
                $sel.html(opts);
            } else {
                $sel.html('<option value=""><?php echo esc_js( esc_html__( '— No active students —', 'rsyi-sa' ) ); ?></option>');
            }
        });
    });

    // AJAX submit
    var savingText  = '<?php echo esc_js( esc_html__( 'Saving…', 'rsyi-sa' ) ); ?>';
    var savedBtnTxt = '<?php echo esc_js( esc_html__( 'Save Evaluation', 'rsyi-sa' ) ); ?>';

    $('#rsyi-admin-eval-form').on('submit', function(e){
        e.preventDefault();
        var $btn = $('#rsyi-admin-eval-submit');
        var $msg = $('#rsyi-admin-eval-msg');
        $btn.prop('disabled', true).text(savingText);
        $.post(rsyiSA.ajaxUrl, $(this).serialize(), function(res){
            $btn.prop('disabled', false).text(savedBtnTxt);
            $msg.show().css('color', res.success ? 'green' : 'red')
                .text(res.data.message);
        });
    });
});
</script>

<?php endif; // end enter tab ?>


<!-- ── Manage Periods tab ── -->
<?php if ( $active_tab === 'periods' && current_user_can( 'rsyi_manage_evaluation_periods' ) ) : ?>

<div style="max-width:700px;">
<h2><?php esc_html_e( 'Create New Evaluation Period', 'rsyi-sa' ); ?></h2>
<form id="rsyi-create-period-form" style="background:#fff; padding:24px; border:1px solid #ccd0d4; border-radius:4px; margin-bottom:30px;">
    <?php wp_nonce_field( 'rsyi_sa_admin', '_nonce' ); ?>
    <input type="hidden" name="action" value="rsyi_create_evaluation_period">

    <table class="form-table">
        <tr>
            <th scope="row"><label for="period_name"><?php esc_html_e( 'Period Name', 'rsyi-sa' ); ?></label></th>
            <td><input type="text" name="name" id="period_name" class="regular-text" required
                       placeholder="<?php esc_attr_e( 'e.g. January 2026 Evaluation', 'rsyi-sa' ); ?>"></td>
        </tr>
        <tr>
            <th scope="row"><label for="period_cohort"><?php esc_html_e( 'Cohort', 'rsyi-sa' ); ?></label></th>
            <td>
                <select name="cohort_id" id="period_cohort" required style="min-width:280px;">
                    <option value=""><?php esc_html_e( '— Select a Cohort —', 'rsyi-sa' ); ?></option>
                    <?php foreach ( $cohorts as $c ) : ?>
                        <option value="<?php echo esc_attr( $c->id ); ?>"><?php echo esc_html( $c->name . ' (' . $c->code . ')' ); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php esc_html_e( 'Only active cohorts are shown.', 'rsyi-sa' ); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e( 'Date Range', 'rsyi-sa' ); ?></th>
            <td>
                <input type="date" name="start_date" style="margin-right:8px;">
                <?php esc_html_e( 'to', 'rsyi-sa' ); ?>
                <input type="date" name="end_date" style="margin-left:8px;">
            </td>
        </tr>
    </table>

    <p>
        <button type="submit" class="button button-primary"><?php esc_html_e( 'Create Period', 'rsyi-sa' ); ?></button>
        <span id="rsyi-period-msg" style="margin-left:12px; display:none;"></span>
    </p>
</form>

<h2><?php esc_html_e( 'Existing Periods', 'rsyi-sa' ); ?></h2>
<?php if ( empty( $periods ) ) : ?>
<p><?php esc_html_e( 'No evaluation periods have been created yet.', 'rsyi-sa' ); ?></p>
<?php else : ?>
<table class="wp-list-table widefat fixed striped">
    <thead>
        <tr>
            <th><?php esc_html_e( 'Period Name', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'Cohort', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'Start', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'End', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'Status', 'rsyi-sa' ); ?></th>
            <th><?php esc_html_e( 'Actions', 'rsyi-sa' ); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ( $periods as $p ) : ?>
    <tr id="period-row-<?php echo esc_attr( $p->id ); ?>">
        <td><?php echo esc_html( $p->name ); ?></td>
        <td><?php echo esc_html( $p->cohort_name ); ?></td>
        <td><?php echo $p->start_date ? esc_html( $p->start_date ) : '—'; ?></td>
        <td><?php echo $p->end_date   ? esc_html( $p->end_date )   : '—'; ?></td>
        <td>
            <span class="rsyi-period-status-<?php echo esc_attr( $p->id ); ?>"
                  style="color:<?php echo $p->is_active ? 'green' : '#999'; ?>; font-weight:600;">
                <?php echo $p->is_active ? esc_html__( 'Active', 'rsyi-sa' ) : esc_html__( 'Inactive', 'rsyi-sa' ); ?>
            </span>
        </td>
        <td>
            <a href="<?php echo esc_url( add_query_arg( [
                'page'      => 'rsyi-evaluations',
                'tab'       => 'aggregation',
                'period_id' => $p->id,
            ], admin_url( 'admin.php' ) ) ); ?>" class="button button-small">
                <?php esc_html_e( 'View', 'rsyi-sa' ); ?>
            </a>
            <button type="button" class="button button-small rsyi-toggle-period"
                    data-period="<?php echo esc_attr( $p->id ); ?>"
                    data-nonce="<?php echo esc_attr( wp_create_nonce( 'rsyi_sa_admin' ) ); ?>">
                <?php echo $p->is_active ? esc_html__( 'Deactivate', 'rsyi-sa' ) : esc_html__( 'Activate', 'rsyi-sa' ); ?>
            </button>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
</div>

<script>
jQuery(function($){
    // Create period
    $('#rsyi-create-period-form').on('submit', function(e){
        e.preventDefault();
        var $msg = $('#rsyi-period-msg');
        $.post(rsyiSA.ajaxUrl, $(this).serialize(), function(res){
            $msg.show().css('color', res.success ? 'green' : 'red').text(res.data.message);
            if(res.success){ location.reload(); }
        });
    });

    // Toggle period active state
    $(document).on('click', '.rsyi-toggle-period', function(){
        var $btn      = $(this);
        var period_id = $btn.data('period');
        $.post(rsyiSA.ajaxUrl, {
            action: 'rsyi_toggle_evaluation_period',
            _nonce: rsyiSA.nonce,
            period_id: period_id
        }, function(res){
            if(res.success){
                location.reload();
            }
        });
    });
});
</script>

<?php endif; // end periods tab ?>
