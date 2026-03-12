<?php
/**
 * Portal: Exam Already Submitted
 * Variables: $exam, $submitted, $profile
 *
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

$list_url = remove_query_arg( 'exam_id', get_permalink() );
?>
<div class="rsyi-portal-section rsyi-exam-submitted" dir="rtl">

    <div class="rsyi-notice rsyi-notice-info">
        <strong><?php esc_html_e( 'لقد سلّمت هذا الامتحان مسبقاً.', 'rsyi-sa' ); ?></strong>
    </div>

    <div class="rsyi-exam-submitted-card">
        <h2><?php echo esc_html( $exam->title ); ?></h2>

        <?php if ( $exam->subject ) : ?>
            <p><strong><?php esc_html_e( 'المادة:', 'rsyi-sa' ); ?></strong> <?php echo esc_html( $exam->subject ); ?></p>
        <?php endif; ?>

        <?php if ( (int) $exam->show_results && $submitted->score !== null ) : ?>
            <div class="rsyi-result-box">
                <div class="rsyi-result-box__score">
                    <span class="rsyi-result-score-label"><?php esc_html_e( 'درجتك', 'rsyi-sa' ); ?></span>
                    <span class="rsyi-result-score-value">
                        <?php echo esc_html( $submitted->score ); ?>
                        <span class="rsyi-result-max">/ <?php echo esc_html( $exam->max_score ); ?></span>
                    </span>
                </div>

                <?php if ( $submitted->grade ) : ?>
                <div class="rsyi-result-box__grade">
                    <span class="rsyi-result-grade-label"><?php esc_html_e( 'التقدير', 'rsyi-sa' ); ?></span>
                    <span class="rsyi-result-grade-value"><?php echo esc_html( $submitted->grade ); ?></span>
                </div>
                <?php endif; ?>

                <div class="rsyi-result-box__status <?php echo (int) $submitted->is_passing ? 'passing' : 'failing'; ?>">
                    <?php if ( (int) $submitted->is_passing ) : ?>
                        <span class="rsyi-badge rsyi-badge-success rsyi-badge-large"><?php esc_html_e( 'ناجح ✓', 'rsyi-sa' ); ?></span>
                    <?php else : ?>
                        <span class="rsyi-badge rsyi-badge-error rsyi-badge-large"><?php esc_html_e( 'راسب ✗', 'rsyi-sa' ); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        <?php elseif ( ! (int) $exam->show_results ) : ?>
            <div class="rsyi-notice rsyi-notice-info">
                <?php esc_html_e( 'النتائج غير متاحة للعرض حالياً.', 'rsyi-sa' ); ?>
            </div>
        <?php else : ?>
            <div class="rsyi-notice rsyi-notice-warning">
                <?php esc_html_e( 'إجابتك قيد التصحيح. ستظهر نتيجتك بعد الانتهاء.', 'rsyi-sa' ); ?>
            </div>
        <?php endif; ?>
    </div>

    <p style="margin-top:20px;">
        <a href="<?php echo esc_url( $list_url ); ?>" class="rsyi-btn rsyi-btn-secondary">
            <?php esc_html_e( '← العودة لقائمة الامتحانات', 'rsyi-sa' ); ?>
        </a>
    </p>
</div>
