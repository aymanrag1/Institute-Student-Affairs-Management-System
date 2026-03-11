<?php
/**
 * Portal – Attendance Record
 * Variables: $profile, $records (array)
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

// Calculate statistics
$total    = count( $records );
$present  = 0;
$absent   = 0;
$late     = 0;

foreach ( $records as $rec ) {
    if ( $rec->status === 'present' )    $present++;
    elseif ( $rec->status === 'absent' ) $absent++;
    elseif ( $rec->status === 'late' )   $late++;
}
$attend_pct = $total > 0 ? round( ( $present + $late ) / $total * 100, 1 ) : 0;

$status_map = [
    'present' => [ 'label' => __( 'Present', 'rsyi-sa' ),  'color' => '#155724', 'bg' => '#d4edda', 'icon' => '✓' ],
    'absent'  => [ 'label' => __( 'Absent', 'rsyi-sa' ),   'color' => '#721c24', 'bg' => '#f8d7da', 'icon' => '✗' ],
    'late'    => [ 'label' => __( 'Late', 'rsyi-sa' ),     'color' => '#856404', 'bg' => '#fff3cd', 'icon' => '⏰' ],
    'excused' => [ 'label' => __( 'Excused', 'rsyi-sa' ),  'color' => '#0c5460', 'bg' => '#d1ecf1', 'icon' => '📋' ],
];
?>
<div class="rsyi-portal" dir="ltr" style="font-family:sans-serif; max-width:860px; margin:0 auto;">

    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
        <h2 style="margin:0; color:#0073aa;">
            📅 <?php esc_html_e( 'Attendance Record', 'rsyi-sa' ); ?>
        </h2>
        <?php $dashboard_id = get_option( 'rsyi_page_dashboard' ); if ( $dashboard_id ) : ?>
        <a href="<?php echo esc_url( get_permalink( $dashboard_id ) ); ?>" style="color:#888; text-decoration:none; font-size:13px;">
            ← <?php esc_html_e( 'Back to Dashboard', 'rsyi-sa' ); ?>
        </a>
        <?php endif; ?>
    </div>

    <?php if ( $total > 0 ) : ?>
    <!-- Summary cards -->
    <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:24px;">
        <div style="background:#fff; border:1px solid #dee2e6; border-top:3px solid <?php echo $attend_pct >= 75 ? '#27ae60' : ( $attend_pct >= 60 ? '#f39c12' : '#e74c3c' ); ?>; border-radius:8px; padding:16px; text-align:center;">
            <div style="font-size:28px; font-weight:700; color:<?php echo $attend_pct >= 75 ? '#27ae60' : ( $attend_pct >= 60 ? '#f39c12' : '#e74c3c' ); ?>;"><?php echo esc_html( $attend_pct ); ?>%</div>
            <div style="font-size:12px; color:#888; margin-top:4px;"><?php esc_html_e( 'Attendance Rate', 'rsyi-sa' ); ?></div>
        </div>
        <div style="background:#d4edda; border:1px solid #c3e6cb; border-radius:8px; padding:16px; text-align:center;">
            <div style="font-size:28px; font-weight:700; color:#155724;"><?php echo esc_html( $present ); ?></div>
            <div style="font-size:12px; color:#155724; margin-top:4px;"><?php esc_html_e( 'Present', 'rsyi-sa' ); ?></div>
        </div>
        <div style="background:#f8d7da; border:1px solid #f5c6cb; border-radius:8px; padding:16px; text-align:center;">
            <div style="font-size:28px; font-weight:700; color:#721c24;"><?php echo esc_html( $absent ); ?></div>
            <div style="font-size:12px; color:#721c24; margin-top:4px;"><?php esc_html_e( 'Absent', 'rsyi-sa' ); ?></div>
        </div>
        <div style="background:#fff3cd; border:1px solid #ffeeba; border-radius:8px; padding:16px; text-align:center;">
            <div style="font-size:28px; font-weight:700; color:#856404;"><?php echo esc_html( $late ); ?></div>
            <div style="font-size:12px; color:#856404; margin-top:4px;"><?php esc_html_e( 'Late', 'rsyi-sa' ); ?></div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ( empty( $records ) ) : ?>
    <div style="background:#fff; border:1px solid #dee2e6; border-radius:8px; padding:40px; text-align:center; color:#888;">
        <div style="font-size:48px; margin-bottom:12px;">📅</div>
        <p><?php esc_html_e( 'No attendance records available yet.', 'rsyi-sa' ); ?></p>
    </div>
    <?php else : ?>
    <div style="background:#fff; border:1px solid #dee2e6; border-radius:8px; overflow:hidden;">
        <div style="background:#f8f9fa; padding:12px 20px; border-bottom:1px solid #dee2e6;">
            <h3 style="margin:0; font-size:16px; color:#333;"><?php esc_html_e( 'Session Details', 'rsyi-sa' ); ?></h3>
        </div>
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background:#fafafa; font-size:12px; color:#888; text-transform:uppercase; letter-spacing:.5px;">
                    <th style="padding:10px 16px; text-align:left; font-weight:600;"><?php esc_html_e( 'Date', 'rsyi-sa' ); ?></th>
                    <th style="padding:10px 8px; text-align:left; font-weight:600;"><?php esc_html_e( 'Session', 'rsyi-sa' ); ?></th>
                    <th style="padding:10px 8px; text-align:center; font-weight:600;"><?php esc_html_e( 'Status', 'rsyi-sa' ); ?></th>
                    <th style="padding:10px 16px; text-align:left; font-weight:600;"><?php esc_html_e( 'Notes', 'rsyi-sa' ); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $records as $rec ) :
                $s = $status_map[ $rec->status ] ?? [ 'label' => $rec->status, 'color' => '#333', 'bg' => '#eee', 'icon' => '?' ];
            ?>
            <tr style="border-top:1px solid #f0f0f0;">
                <td style="padding:11px 16px; font-weight:600; color:#333; white-space:nowrap;">
                    <?php echo esc_html( date_i18n( 'j M Y', strtotime( $rec->session_date ) ) ); ?>
                    <div style="font-size:11px; color:#aaa; font-weight:400;"><?php echo esc_html( date_i18n( 'l', strtotime( $rec->session_date ) ) ); ?></div>
                </td>
                <td style="padding:11px 8px; color:#555;">
                    <?php echo $rec->session_name ? esc_html( $rec->session_name ) : '<span style="color:#ccc;">—</span>'; ?>
                </td>
                <td style="padding:11px 8px; text-align:center;">
                    <span style="display:inline-block; padding:4px 12px; border-radius:12px; font-size:12px; font-weight:600;
                                 background:<?php echo esc_attr( $s['bg'] ); ?>; color:<?php echo esc_attr( $s['color'] ); ?>;">
                        <?php echo $s['icon']; ?> <?php echo esc_html( $s['label'] ); ?>
                    </span>
                </td>
                <td style="padding:11px 16px; color:#888; font-size:13px;">
                    <?php echo $rec->notes ? esc_html( $rec->notes ) : '<span style="color:#ccc;">—</span>'; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</div>
