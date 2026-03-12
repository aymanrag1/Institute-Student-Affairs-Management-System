<?php
/**
 * Portal: Student Exam List
 * Variables: $profile, $exams, $results_map, $now
 *
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;
?>
<div class="rsyi-portal-section rsyi-exams" dir="rtl">
    <h2><?php esc_html_e( 'الامتحانات', 'rsyi-sa' ); ?></h2>

    <?php if ( empty( $exams ) ) : ?>
        <div class="rsyi-notice rsyi-notice-info">
            <?php esc_html_e( 'لا توجد امتحانات متاحة حالياً.', 'rsyi-sa' ); ?>
        </div>
    <?php else : ?>
        <div class="rsyi-exams-list">
            <?php foreach ( $exams as $exam ) :
                $exam_id      = (int) $exam->id;
                $result       = $results_map[ $exam_id ] ?? null;
                $started      = $exam->starts_at && $now >= $exam->starts_at;
                $ended        = $exam->ends_at   && $now >  $exam->ends_at;
                $is_open      = $started && ! $ended;
                $submitted    = ! empty( $result );
            ?>
            <div class="rsyi-exam-card">
                <div class="rsyi-exam-card__header">
                    <h3 class="rsyi-exam-card__title"><?php echo esc_html( $exam->title ); ?></h3>
                    <?php if ( $submitted ) : ?>
                        <span class="rsyi-badge rsyi-badge-success"><?php esc_html_e( 'تم التسليم ✓', 'rsyi-sa' ); ?></span>
                    <?php elseif ( $ended ) : ?>
                        <span class="rsyi-badge rsyi-badge-error"><?php esc_html_e( 'انتهى الوقت', 'rsyi-sa' ); ?></span>
                    <?php elseif ( $is_open ) : ?>
                        <span class="rsyi-badge rsyi-badge-success"><?php esc_html_e( 'مفتوح الآن', 'rsyi-sa' ); ?></span>
                    <?php else : ?>
                        <span class="rsyi-badge rsyi-badge-warning"><?php esc_html_e( 'لم يبدأ بعد', 'rsyi-sa' ); ?></span>
                    <?php endif; ?>
                </div>

                <div class="rsyi-exam-card__meta">
                    <?php if ( $exam->subject ) : ?>
                        <span><strong><?php esc_html_e( 'المادة:', 'rsyi-sa' ); ?></strong> <?php echo esc_html( $exam->subject ); ?></span>
                    <?php endif; ?>

                    <?php if ( $exam->starts_at ) : ?>
                        <span><strong><?php esc_html_e( 'يبدأ:', 'rsyi-sa' ); ?></strong>
                            <?php echo esc_html( date_i18n( 'j M Y H:i', strtotime( $exam->starts_at ) ) ); ?>
                        </span>
                    <?php endif; ?>

                    <?php if ( $exam->ends_at ) : ?>
                        <span><strong><?php esc_html_e( 'ينتهي:', 'rsyi-sa' ); ?></strong>
                            <?php echo esc_html( date_i18n( 'j M Y H:i', strtotime( $exam->ends_at ) ) ); ?>
                        </span>
                    <?php endif; ?>

                    <?php if ( $exam->duration_min ) : ?>
                        <span><strong><?php esc_html_e( 'المدة:', 'rsyi-sa' ); ?></strong>
                            <?php printf( esc_html__( '%d دقيقة', 'rsyi-sa' ), (int) $exam->duration_min ); ?>
                        </span>
                    <?php endif; ?>

                    <span><strong><?php esc_html_e( 'الدرجة القصوى:', 'rsyi-sa' ); ?></strong>
                        <?php echo esc_html( $exam->max_score ); ?>
                    </span>
                </div>

                <?php if ( $exam->description ) : ?>
                    <p class="rsyi-exam-card__desc"><?php echo esc_html( $exam->description ); ?></p>
                <?php endif; ?>

                <div class="rsyi-exam-card__footer">
                    <?php if ( $submitted ) : ?>
                        <?php if ( $exam->show_results && $result->score !== null ) : ?>
                            <span class="rsyi-exam-score">
                                <?php esc_html_e( 'درجتك:', 'rsyi-sa' ); ?>
                                <strong><?php echo esc_html( $result->score ); ?> / <?php echo esc_html( $exam->max_score ); ?></strong>
                                <?php if ( $result->grade ) : ?>
                                    (<?php echo esc_html( $result->grade ); ?>)
                                <?php endif; ?>
                                <?php if ( (int) $result->is_passing === 1 ) : ?>
                                    <span class="rsyi-badge rsyi-badge-success"><?php esc_html_e( 'ناجح', 'rsyi-sa' ); ?></span>
                                <?php elseif ( (int) $result->is_passing === 0 && $result->score !== null ) : ?>
                                    <span class="rsyi-badge rsyi-badge-error"><?php esc_html_e( 'راسب', 'rsyi-sa' ); ?></span>
                                <?php endif; ?>
                            </span>
                        <?php elseif ( ! $exam->show_results ) : ?>
                            <span class="rsyi-notice-inline"><?php esc_html_e( 'تم التسليم بنجاح. ستظهر نتيجتك لاحقاً.', 'rsyi-sa' ); ?></span>
                        <?php else : ?>
                            <span class="rsyi-notice-inline"><?php esc_html_e( 'في انتظار التصحيح.', 'rsyi-sa' ); ?></span>
                        <?php endif; ?>
                    <?php elseif ( $is_open ) : ?>
                        <a href="<?php echo esc_url( add_query_arg( 'exam_id', $exam_id, get_permalink() ) ); ?>"
                           class="rsyi-btn rsyi-btn-primary">
                            <?php esc_html_e( 'ابدأ الامتحان ←', 'rsyi-sa' ); ?>
                        </a>
                    <?php elseif ( ! $started ) : ?>
                        <span class="rsyi-notice-inline">
                            <?php esc_html_e( 'يبدأ في:', 'rsyi-sa' ); ?>
                            <?php echo esc_html( date_i18n( 'j M Y H:i', strtotime( $exam->starts_at ) ) ); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
