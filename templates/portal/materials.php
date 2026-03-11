<?php
/**
 * Portal – Study Materials
 * Variables: $profile, $materials (array)
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

// Group materials by subject
$by_subject = [];
foreach ( $materials as $m ) {
    $key = $m->subject ?: __( 'General / عام', 'rsyi-sa' );
    $by_subject[ $key ][] = $m;
}
ksort( $by_subject );

$download_base = add_query_arg( [ 'rsyi_dl' => '1' ], home_url( '/' ) );
?>
<div class="rsyi-portal" dir="ltr" style="font-family:sans-serif; max-width:860px; margin:0 auto;">

    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
        <h2 style="margin:0; color:#0073aa;">
            📚 <?php esc_html_e( 'Study Materials', 'rsyi-sa' ); ?>
        </h2>
        <?php $dashboard_id = get_option( 'rsyi_page_dashboard' ); if ( $dashboard_id ) : ?>
        <a href="<?php echo esc_url( get_permalink( $dashboard_id ) ); ?>" style="color:#888; text-decoration:none; font-size:13px;">
            ← <?php esc_html_e( 'Back to Dashboard', 'rsyi-sa' ); ?>
        </a>
        <?php endif; ?>
    </div>

    <?php if ( empty( $materials ) ) : ?>
    <div style="background:#fff; border:1px solid #dee2e6; border-radius:8px; padding:40px; text-align:center; color:#888;">
        <div style="font-size:48px; margin-bottom:12px;">📂</div>
        <p><?php esc_html_e( 'No study materials available yet.', 'rsyi-sa' ); ?></p>
    </div>
    <?php else : ?>
    <?php foreach ( $by_subject as $subject => $items ) : ?>
    <div style="background:#fff; border:1px solid #dee2e6; border-radius:8px; margin-bottom:20px; overflow:hidden;">
        <div style="background:#f8f9fa; padding:12px 20px; border-bottom:1px solid #dee2e6;">
            <h3 style="margin:0; font-size:16px; color:#333;">📖 <?php echo esc_html( $subject ); ?></h3>
        </div>
        <table style="width:100%; border-collapse:collapse;">
        <?php foreach ( $items as $m ) :
            $ext = strtolower( pathinfo( $m->file_name_orig, PATHINFO_EXTENSION ) );
            $icon_map = [ 'pdf' => '📄', 'doc' => '📝', 'docx' => '📝', 'ppt' => '📊', 'pptx' => '📊', 'xls' => '📋', 'xlsx' => '📋', 'zip' => '🗜' ];
            $icon = $icon_map[ $ext ] ?? '📎';
            $dl_url = add_query_arg( [ 'rsyi_dl' => '1', 'file' => rawurlencode( $m->file_path ), '_wpnonce' => wp_create_nonce( 'rsyi_dl_' . $m->file_path ) ], home_url( '/' ) );
        ?>
        <tr style="border-bottom:1px solid #f0f0f0;">
            <td style="padding:12px 20px; width:40px; font-size:22px; text-align:center;"><?php echo $icon; ?></td>
            <td style="padding:12px 8px;">
                <div style="font-weight:600; color:#333;"><?php echo esc_html( $m->title ); ?></div>
                <?php if ( $m->description ) : ?>
                <div style="font-size:12px; color:#888; margin-top:2px;"><?php echo esc_html( $m->description ); ?></div>
                <?php endif; ?>
                <div style="font-size:11px; color:#aaa; margin-top:4px;">
                    <?php echo esc_html( $m->file_name_orig ); ?>
                    &bull; <?php echo esc_html( size_format( $m->file_size ) ); ?>
                    &bull; <?php echo esc_html( date_i18n( 'j M Y', strtotime( $m->created_at ) ) ); ?>
                </div>
            </td>
            <td style="padding:12px 20px; text-align:right; white-space:nowrap;">
                <a href="<?php echo esc_url( $dl_url ); ?>"
                   class="button"
                   style="background:#0073aa; color:#fff; border-color:#0073aa; text-decoration:none; padding:6px 14px; border-radius:4px; font-size:13px; display:inline-block;">
                    ⬇ <?php esc_html_e( 'Download', 'rsyi-sa' ); ?>
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
        </table>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

</div>
