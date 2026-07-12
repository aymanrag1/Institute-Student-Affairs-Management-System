<?php
/**
 * Portal – Boss Man Daily Study Report / لوحة تحكم الحكمدار
 * Variables: $profile, $cohort, $students, $courses, $week_start
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

$today         = current_time( 'Y-m-d' );
$dashboard_url = get_option( 'rsyi_page_dashboard' ) ? get_permalink( get_option( 'rsyi_page_dashboard' ) ) : '';

// Build courses JSON for JS — done in PHP so JS has no dependency on external variables
$courses_json = wp_json_encode( array_map( fn( $c ) => [
    'id'      => (int) $c->id,
    'name_ar' => $c->name_ar,
    'name_en' => $c->name_en ?: '',
], $courses ) ) ?: '[]';

// Shared button styles (avoids WP admin classes that may not load on frontend)
$btn_primary = 'display:inline-block;background:#0073aa;color:#fff;border:none;border-radius:6px;padding:8px 18px;font-size:14px;font-weight:600;cursor:pointer;';
$btn_orange  = 'display:inline-block;background:#e67e22;color:#fff;border:none;border-radius:6px;padding:10px 28px;font-size:15px;font-weight:700;cursor:pointer;';
$btn_default = 'display:inline-block;background:#f5f5f5;color:#333;border:1px solid #ccc;border-radius:6px;padding:10px 18px;font-size:14px;cursor:pointer;';
?>
<style>
.rsyi-bm-portal { font-family: 'Segoe UI', Tahoma, sans-serif; }
.rsyi-bm-portal .bm-course-row { display:flex; gap:8px; align-items:center; margin-bottom:6px; }
.rsyi-bm-portal .bm-course-row select { flex:2; border:1px solid #ccd0d4; border-radius:6px; padding:6px 10px; font-size:13px; min-width:0; }
.rsyi-bm-portal .bm-course-row input  { flex:2; border:1px solid #ccd0d4; border-radius:6px; padding:6px 10px; font-size:13px; min-width:0; box-sizing:border-box; }
.rsyi-bm-portal .bm-remove-btn { background:none; border:1px solid #e74c3c; color:#e74c3c; border-radius:4px; padding:4px 8px; cursor:pointer; font-size:14px; flex-shrink:0; line-height:1; }
.rsyi-bm-portal .bm-remove-btn:hover { background:#e74c3c; color:#fff; }
.rsyi-bm-portal .bm-add-course-btn { background:none; border:1px dashed #0073aa; color:#0073aa; border-radius:6px; padding:5px 14px; cursor:pointer; font-size:12px; font-weight:600; width:100%; margin-top:4px; box-sizing:border-box; }
.rsyi-bm-portal .bm-add-course-btn:hover { background:#e8f4fb; }
.rsyi-bm-portal .rsyi-bm-tab { padding:10px 24px; border:none; background:none; cursor:pointer; font-size:14px; color:#666; border-bottom:3px solid transparent; margin-bottom:-2px; }
.rsyi-bm-portal .rsyi-bm-tab.active { color:#e67e22; border-bottom-color:#e67e22; font-weight:700; }
.rsyi-bm-portal .bm-notice-box { display:none; margin-bottom:12px; padding:10px 16px; border-radius:6px; font-size:13px; }
.rsyi-bm-portal .bm-notice-box.success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
.rsyi-bm-portal .bm-notice-box.error   { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
</style>

<div class="rsyi-portal rsyi-bm-portal" dir="rtl" style="max-width:980px; margin:0 auto;">

    <!-- Header -->
    <div style="background:linear-gradient(135deg,#e67e22,#d35400); color:#fff; border-radius:12px; padding:24px 28px; margin-bottom:24px; display:flex; align-items:center; gap:18px; flex-wrap:wrap;">
        <div style="font-size:48px; flex-shrink:0;">👮</div>
        <div style="flex:1; min-width:200px;">
            <h2 style="margin:0 0 4px; font-size:20px;">لوحة تحكم الحكمدار / Boss Man Dashboard</h2>
            <p style="margin:0; opacity:.9; font-size:14px;">
                <?php echo esc_html( $profile->arabic_full_name ); ?> —
                دفعة / Cohort: <strong><?php echo esc_html( isset($cohort->name) ? $cohort->name : '—' ); ?></strong>
            </p>
            <p style="margin:4px 0 0; opacity:.75; font-size:12px;">
                الأسبوع / Week: <?php echo esc_html( $week_start ); ?>
            </p>
        </div>
        <?php if ( $dashboard_url ) : ?>
        <a href="<?php echo esc_url( $dashboard_url ); ?>"
           style="background:rgba(255,255,255,.2); color:#fff; padding:8px 16px; border-radius:6px; text-decoration:none; font-size:13px; border:1px solid rgba(255,255,255,.3); white-space:nowrap;">
            ← الرئيسية / Home
        </a>
        <?php endif; ?>
    </div>

    <!-- Tabs -->
    <div style="display:flex; gap:0; margin-bottom:24px; border-bottom:2px solid #dee2e6;">
        <button type="button" class="rsyi-bm-tab active" data-tab="daily">
            📋 تقرير اليوم / Today's Report
        </button>
        <button type="button" class="rsyi-bm-tab" data-tab="history">
            📅 السجل التاريخي / History
        </button>
    </div>

    <!-- ═══ Daily Report Tab ═════════════════════════════════════════════════ -->
    <div id="rsyi-bm-tab-daily">
        <div style="background:#fff; border:1px solid #dee2e6; border-radius:10px; padding:20px; margin-bottom:20px;">

            <div style="display:flex; align-items:center; gap:14px; margin-bottom:20px; flex-wrap:wrap;">
                <label style="font-weight:700; font-size:14px;">📅 التاريخ / Date:</label>
                <input type="date" id="bm-report-date" value="<?php echo esc_attr( $today ); ?>"
                       max="<?php echo esc_attr( $today ); ?>"
                       style="border:1px solid #ccd0d4; border-radius:6px; padding:7px 12px; font-size:14px;">
                <button type="button" id="bm-load-btn" style="<?php echo esc_attr( $btn_primary ); ?>">
                    🔄 تحميل البيانات / Load Data
                </button>
            </div>

            <?php if ( empty( $students ) ) : ?>
            <div style="text-align:center; padding:30px; color:#999;">
                <div style="font-size:40px; margin-bottom:10px;">👥</div>
                <p>لا يوجد طلاب نشطون في دفعتك. / No active students in your cohort.</p>
            </div>
            <?php else : ?>

            <div id="bm-notice" class="bm-notice-box"></div>

            <table style="width:100%; border-collapse:collapse;" id="bm-students-table">
                <thead>
                    <tr style="background:#f8f9fa; border-bottom:2px solid #dee2e6;">
                        <th style="padding:10px 14px; text-align:right; font-size:13px; width:36px;">#</th>
                        <th style="padding:10px 14px; text-align:right; font-size:13px; width:180px;">اسم الطالب / Student</th>
                        <th style="padding:10px 14px; text-align:right; font-size:13px;">الكورسات والأنشطة / Courses & Activities</th>
                    </tr>
                </thead>
                <tbody>
                <?php $i = 1; foreach ( $students as $st ) : ?>
                <tr style="border-bottom:1px solid #f0f0f0; vertical-align:top;" class="bm-student-row"
                    data-id="<?php echo (int) $st->id; ?>">
                    <td style="padding:12px 14px; color:#888; font-size:13px;"><?php echo $i++; ?></td>
                    <td style="padding:12px 14px;">
                        <strong style="font-size:14px; display:block;"><?php echo esc_html( $st->arabic_full_name ); ?></strong>
                        <?php if ( $st->english_full_name ) : ?>
                        <span style="font-size:11px; color:#888;"><?php echo esc_html( $st->english_full_name ); ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="padding:10px 14px;">
                        <div class="bm-courses-container">
                            <div class="bm-course-row">
                                <select class="bm-course-select">
                                    <option value="">— اختر الكورس / Select Course —</option>
                                    <?php foreach ( $courses as $cr ) : ?>
                                    <option value="<?php echo (int) $cr->id; ?>">
                                        <?php echo esc_html( $cr->name_ar ); ?><?php if ( $cr->name_en ) : ?> (<?php echo esc_html( $cr->name_en ); ?>)<?php endif; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="text" class="bm-notes-input" placeholder="ملاحظة / Note…">
                                <button type="button" class="bm-remove-btn" title="حذف / Remove" style="display:none;">✕</button>
                            </div>
                        </div>
                        <button type="button" class="bm-add-course-btn">+ إضافة كورس / Add Course</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <div style="margin-top:20px; display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                <button type="button" id="bm-save-btn" style="<?php echo esc_attr( $btn_orange ); ?>">
                    💾 حفظ التقرير / Save Report
                </button>
                <button type="button" id="bm-clear-btn" style="<?php echo esc_attr( $btn_default ); ?>">
                    🗑 مسح الكل / Clear All
                </button>
                <span id="bm-save-status" style="font-size:13px; color:#27ae60; display:none;"></span>
            </div>
            <?php endif; ?>

        </div>
    </div><!-- /daily tab -->

    <!-- ═══ History Tab ══════════════════════════════════════════════════════ -->
    <div id="rsyi-bm-tab-history" style="display:none;">
        <div style="background:#fff; border:1px solid #dee2e6; border-radius:10px; padding:20px;">
            <div style="display:flex; gap:14px; align-items:flex-end; margin-bottom:20px; flex-wrap:wrap;">
                <div>
                    <label style="display:block; font-weight:700; font-size:13px; margin-bottom:4px;">التاريخ / Date</label>
                    <input type="date" id="hist-date" value="<?php echo esc_attr( $today ); ?>"
                           style="border:1px solid #ccd0d4; border-radius:6px; padding:7px 12px; font-size:14px;">
                </div>
                <button type="button" id="hist-load-btn" style="<?php echo esc_attr( $btn_primary ); ?>">
                    🔍 عرض / View
                </button>
            </div>
            <div id="hist-content" style="text-align:center; color:#999; padding:30px;">
                اختر تاريخاً واضغط عرض / Choose a date and click View
            </div>
        </div>
    </div><!-- /history tab -->

</div><!-- /rsyi-bm-portal -->

<?php /* JavaScript is enqueued via wp_enqueue_script + wp_localize_script in render_boss_man() */ ?>
