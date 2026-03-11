<?php
/**
 * Portal – My Grades
 * Variables: $profile, $results (array of exam results joined with exams)
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

// Calculate summary statistics
$total_exams  = count( $results );
$passed       = 0;
$failed       = 0;
$score_sum    = 0;
$score_count  = 0;

foreach ( $results as $r ) {
    $max     = (int) $r->max_score ?: 100;
    $passing = isset( $r->passing_score ) && $r->passing_score !== null ? (int) $r->passing_score : (int) round( $max * 0.5 );
    if ( (int) $r->score >= $passing ) {
        $passed++;
    } else {
        $failed++;
    }
    $score_sum += (int) $r->score / $max * 100;
    $score_count++;
}
$avg_pct = $score_count > 0 ? round( $score_sum / $score_count, 1 ) : 0;

// Group results by subject
$by_subject = [];
foreach ( $results as $r ) {
    $key = $r->subject ?: __( 'General', 'rsyi-sa' );
    $by_subject[ $key ][] = $r;
}

function rsyi_grade_badge( int $score, int $max, ?int $passing_override ): array {
    $pct     = $max > 0 ? $score / $max * 100 : 0;
    $passing = $passing_override !== null ? ( $passing_override / $max * 100 ) : 50;
    if ( $pct >= 90 ) { $letter = 'A+'; $label = 'ممتاز';    $color = '#155724'; $bg = '#d4edda'; }
    elseif ( $pct >= 80 ) { $letter = 'A';  $label = 'جيد جداً'; $color = '#1e7e34'; $bg = '#d4edda'; }
    elseif ( $pct >= 70 ) { $letter = 'B';  $label = 'جيد';      $color = '#0c5460'; $bg = '#d1ecf1'; }
    elseif ( $pct >= 60 ) { $letter = 'C';  $label = 'مقبول';    $color = '#856404'; $bg = '#fff3cd'; }
    elseif ( $pct >= 50 ) { $letter = 'D';  $label = 'ضعيف';     $color = '#856404'; $bg = '#ffeeba'; }
    else                   { $letter = 'F';  $label = 'راسب';     $color = '#721c24'; $bg = '#f8d7da'; }
    $is_passing = $pct >= $passing;
    return compact( 'letter', 'label', 'color', 'bg', 'is_passing', 'pct' );
}
?>
<div class="rsyi-portal" dir="ltr" style="font-family:sans-serif; max-width:860px; margin:0 auto;">

    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
        <h2 style="margin:0; color:#0073aa;">
            📝 <?php esc_html_e( 'My Grades', 'rsyi-sa' ); ?>
        </h2>
        <?php $dashboard_id = get_option( 'rsyi_page_dashboard' ); if ( $dashboard_id ) : ?>
        <a href="<?php echo esc_url( get_permalink( $dashboard_id ) ); ?>" style="color:#888; text-decoration:none; font-size:13px;">
            ← <?php esc_html_e( 'Back to Dashboard', 'rsyi-sa' ); ?>
        </a>
        <?php endif; ?>
    </div>

    <?php if ( $total_exams > 0 ) : ?>
    <!-- Summary cards -->
    <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:24px;">
        <div style="background:#fff; border:1px solid #dee2e6; border-radius:8px; padding:16px; text-align:center;">
            <div style="font-size:28px; font-weight:700; color:#0073aa;"><?php echo esc_html( $total_exams ); ?></div>
            <div style="font-size:12px; color:#888; margin-top:4px;"><?php esc_html_e( 'Total Exams', 'rsyi-sa' ); ?></div>
        </div>
        <div style="background:#d4edda; border:1px solid #c3e6cb; border-radius:8px; padding:16px; text-align:center;">
            <div style="font-size:28px; font-weight:700; color:#155724;"><?php echo esc_html( $passed ); ?></div>
            <div style="font-size:12px; color:#155724; margin-top:4px;"><?php esc_html_e( 'Passed', 'rsyi-sa' ); ?></div>
        </div>
        <div style="background:#f8d7da; border:1px solid #f5c6cb; border-radius:8px; padding:16px; text-align:center;">
            <div style="font-size:28px; font-weight:700; color:#721c24;"><?php echo esc_html( $failed ); ?></div>
            <div style="font-size:12px; color:#721c24; margin-top:4px;"><?php esc_html_e( 'Failed', 'rsyi-sa' ); ?></div>
        </div>
        <div style="background:#fff; border:1px solid #dee2e6; border-top:3px solid <?php echo $avg_pct >= 60 ? '#27ae60' : '#e74c3c'; ?>; border-radius:8px; padding:16px; text-align:center;">
            <div style="font-size:28px; font-weight:700; color:<?php echo $avg_pct >= 60 ? '#27ae60' : '#e74c3c'; ?>;"><?php echo esc_html( $avg_pct ); ?>%</div>
            <div style="font-size:12px; color:#888; margin-top:4px;"><?php esc_html_e( 'Average', 'rsyi-sa' ); ?></div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ( empty( $results ) ) : ?>
    <div style="background:#fff; border:1px solid #dee2e6; border-radius:8px; padding:40px; text-align:center; color:#888;">
        <div style="font-size:48px; margin-bottom:12px;">📋</div>
        <p><?php esc_html_e( 'No exam results available yet.', 'rsyi-sa' ); ?></p>
    </div>
    <?php else : ?>
    <?php foreach ( $by_subject as $subject => $items ) : ?>
    <div style="background:#fff; border:1px solid #dee2e6; border-radius:8px; margin-bottom:20px; overflow:hidden;">
        <div style="background:#f8f9fa; padding:12px 20px; border-bottom:1px solid #dee2e6;">
            <h3 style="margin:0; font-size:16px; color:#333;">📖 <?php echo esc_html( $subject ); ?></h3>
        </div>
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background:#fafafa; font-size:12px; color:#888; text-transform:uppercase; letter-spacing:.5px;">
                    <th style="padding:10px 16px; text-align:left; font-weight:600;"><?php esc_html_e( 'Exam', 'rsyi-sa' ); ?></th>
                    <th style="padding:10px 8px; text-align:center;"><?php esc_html_e( 'Date', 'rsyi-sa' ); ?></th>
                    <th style="padding:10px 8px; text-align:center;"><?php esc_html_e( 'Score', 'rsyi-sa' ); ?></th>
                    <th style="padding:10px 8px; text-align:center;"><?php esc_html_e( 'Grade', 'rsyi-sa' ); ?></th>
                    <th style="padding:10px 16px; text-align:center;"><?php esc_html_e( 'Result', 'rsyi-sa' ); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $items as $r ) :
                $max     = (int) $r->max_score ?: 100;
                $passing = isset( $r->passing_score ) && $r->passing_score !== null ? (int) $r->passing_score : null;
                $badge   = rsyi_grade_badge( (int) $r->score, $max, $passing );
                $pct     = round( (int) $r->score / $max * 100, 1 );
            ?>
            <tr style="border-top:1px solid #f0f0f0;">
                <td style="padding:12px 16px;">
                    <div style="font-weight:600; color:#333;"><?php echo esc_html( $r->exam_title ); ?></div>
                    <?php if ( $r->exam_type && $r->exam_type !== 'written' ) : ?>
                    <div style="font-size:11px; color:#888; margin-top:2px;">
                        <?php
                        $type_labels = [ 'practical' => 'عملي', 'project' => 'مشروع', 'oral' => 'شفهي', 'written' => 'نظري' ];
                        echo esc_html( $type_labels[ $r->exam_type ] ?? $r->exam_type );
                        ?>
                    </div>
                    <?php endif; ?>
                </td>
                <td style="padding:12px 8px; text-align:center; font-size:13px; color:#666;">
                    <?php echo $r->exam_date ? esc_html( date_i18n( 'j M Y', strtotime( $r->exam_date ) ) ) : '—'; ?>
                </td>
                <td style="padding:12px 8px; text-align:center;">
                    <strong><?php echo esc_html( $r->score ); ?></strong>
                    <span style="color:#888; font-size:12px;">/ <?php echo esc_html( $max ); ?></span>
                    <div style="font-size:11px; color:#888;"><?php echo esc_html( $pct ); ?>%</div>
                </td>
                <td style="padding:12px 8px; text-align:center;">
                    <span style="display:inline-block; padding:4px 10px; border-radius:12px; font-weight:700; font-size:14px;
                                 background:<?php echo esc_attr( $badge['bg'] ); ?>; color:<?php echo esc_attr( $badge['color'] ); ?>;">
                        <?php echo esc_html( $r->grade ?: $badge['letter'] ); ?>
                    </span>
                    <div style="font-size:11px; color:#888; margin-top:2px;"><?php echo esc_html( $badge['label'] ); ?></div>
                </td>
                <td style="padding:12px 16px; text-align:center;">
                    <?php if ( $badge['is_passing'] ) : ?>
                    <span style="background:#d4edda; color:#155724; padding:4px 12px; border-radius:12px; font-size:13px; font-weight:600;">
                        ✓ <?php esc_html_e( 'Pass', 'rsyi-sa' ); ?>
                    </span>
                    <?php else : ?>
                    <span style="background:#f8d7da; color:#721c24; padding:4px 12px; border-radius:12px; font-size:13px; font-weight:600;">
                        ✗ <?php esc_html_e( 'Fail', 'rsyi-sa' ); ?>
                    </span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

</div>
