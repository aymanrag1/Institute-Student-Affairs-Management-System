<?php
/**
 * Portal – Student Dashboard
 * Variables: $profile, $total_pts, $warnings (array), $cohort, $is_boss_man
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

$status_labels = [
    'pending_docs' => 'في انتظار المستندات',
    'active'       => 'نشط',
    'suspended'    => 'موقوف',
    'expelled'     => 'مفصول',
];

$page_links = [
    'documents'   => get_option( 'rsyi_page_documents' )         ? get_permalink( get_option( 'rsyi_page_documents' ) )         : '',
    'requests'    => get_option( 'rsyi_page_requests' )          ? get_permalink( get_option( 'rsyi_page_requests' ) )          : '',
    'behavior'    => get_option( 'rsyi_page_behavior' )          ? get_permalink( get_option( 'rsyi_page_behavior' ) )          : '',
    'evaluation'  => get_option( 'rsyi_page_evaluation' )        ? get_permalink( get_option( 'rsyi_page_evaluation' ) )        : '',
    'materials'   => get_option( 'rsyi_page_materials' )         ? get_permalink( get_option( 'rsyi_page_materials' ) )         : '',
    'grades'      => get_option( 'rsyi_page_grades' )            ? get_permalink( get_option( 'rsyi_page_grades' ) )            : '',
    'attendance'  => get_option( 'rsyi_page_attendance_record' ) ? get_permalink( get_option( 'rsyi_page_attendance_record' ) ) : '',
    'boss_man'    => get_option( 'rsyi_page_boss_man' )          ? get_permalink( get_option( 'rsyi_page_boss_man' ) )          : '',
];

$status_color = [
    'pending_docs' => '#f39c12',
    'active'       => '#27ae60',
    'suspended'    => '#e67e22',
    'expelled'     => '#e74c3c',
];
$sc        = $status_color[ $profile->status ] ?? '#999';
$pts_color = $total_pts >= 30 ? '#e74c3c' : ( $total_pts >= 20 ? '#e67e22' : '#27ae60' );

// $is_boss_man is passed from render_dashboard() (weekly schedule check)
$is_boss_man    = ! empty( $is_boss_man );
$institute_name = get_option( 'rsyi_institute_name', 'معهد البحر الأحمر' );

// AJAX config — output in PHP, not relying on rsyiPortal (which is in wp_footer)
$ajax_url = admin_url( 'admin-ajax.php' );
$ajax_nonce = wp_create_nonce( 'rsyi_sa_portal' );
?>
<style>
.rsyi-dash { font-family:'Segoe UI', Tahoma, 'Arial', sans-serif; }
.rsyi-dash a { text-decoration:none; }
.rsyi-dash-card { background:#fff; border:1px solid #e8ecef; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.05); }
.rsyi-dash-nav-item {
    display:block; background:#fff; border:1px solid #e8ecef; border-radius:12px;
    padding:20px 14px; text-align:center; text-decoration:none; color:#2c3e50;
    box-shadow:0 2px 6px rgba(0,0,0,.04); transition:transform .15s, box-shadow .15s, border-color .15s;
}
.rsyi-dash-nav-item:hover {
    transform:translateY(-3px);
    box-shadow:0 8px 20px rgba(0,0,0,.1);
}
.rsyi-warn-item { padding-top:12px; margin-top:12px; border-top:1px solid rgba(0,0,0,.08); }
.rsyi-ack-btn {
    display:inline-block; background:#e67e22; color:#fff; border:none; border-radius:6px;
    padding:9px 20px; font-size:13px; font-weight:600; cursor:pointer; margin-top:8px;
}
.rsyi-ack-btn:hover { background:#d35400; }
.rsyi-ack-btn:disabled { opacity:.6; cursor:not-allowed; }
</style>

<div class="rsyi-portal rsyi-dash" dir="rtl" style="max-width:900px; margin:0 auto; color:#2c3e50;">

    <!-- ══ HEADER ══════════════════════════════════════════════════════════ -->
    <div style="background:linear-gradient(135deg,#1a3a5c,#0073aa); color:#fff; border-radius:14px; padding:28px 32px; margin-bottom:22px; display:flex; align-items:center; gap:20px; flex-wrap:wrap; position:relative; overflow:hidden;">
        <!-- decorative circles -->
        <div style="position:absolute; top:-30px; left:-30px; width:140px; height:140px; border-radius:50%; background:rgba(255,255,255,.05); pointer-events:none;"></div>
        <div style="position:absolute; bottom:-20px; left:60px; width:100px; height:100px; border-radius:50%; background:rgba(255,255,255,.04); pointer-events:none;"></div>

        <?php echo get_avatar( $profile->user_id, 72, '', '', [
            'style' => 'border-radius:50%;border:3px solid rgba(255,255,255,.4);flex-shrink:0;position:relative;z-index:1;',
        ] ); ?>

        <div style="flex:1; min-width:160px; position:relative; z-index:1;">
            <p style="margin:0 0 2px; opacity:.7; font-size:11px; letter-spacing:1px; text-transform:uppercase;"><?php echo esc_html( $institute_name ); ?> — بوابة الطالب</p>
            <h2 style="margin:0 0 4px; font-size:22px; font-weight:700;"><?php echo esc_html( $profile->arabic_full_name ); ?></h2>
            <?php if ( $profile->english_full_name ) : ?>
            <p style="margin:0; opacity:.8; font-size:13px;"><?php echo esc_html( $profile->english_full_name ); ?></p>
            <?php endif; ?>
            <?php if ( $is_boss_man ) : ?>
            <span style="display:inline-block; margin-top:8px; background:rgba(230,126,34,.9); color:#fff; padding:3px 14px; border-radius:20px; font-size:12px; font-weight:700;">
                👮 حكمدار الدفعة هذا الأسبوع
            </span>
            <?php endif; ?>
        </div>

        <a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>"
           style="position:relative; z-index:1; background:rgba(255,255,255,.15); color:#fff; padding:8px 18px; border-radius:8px; text-decoration:none; font-size:13px; border:1px solid rgba(255,255,255,.25); white-space:nowrap; flex-shrink:0;">
            تسجيل الخروج ↩
        </a>
    </div>

    <!-- ══ WARNINGS ════════════════════════════════════════════════════════ -->
    <?php if ( ! empty( $warnings ) ) : ?>
    <div style="background:#fff8e1; border:1px solid #ffe082; border-right:5px solid #ff9800; border-radius:10px; padding:18px 22px; margin-bottom:20px;">
        <strong style="color:#e65100; font-size:15px; display:block; margin-bottom:4px;">⚠ مطلوب اتخاذ إجراء — تحذيرات سلوكية معلقة</strong>
        <p style="margin:0 0 4px; color:#6d4c41; font-size:13px;">يجب قراءة والموافقة على التحذير للمتابعة</p>
        <?php foreach ( $warnings as $w ) : ?>
        <div class="rsyi-warn-item">
            <p style="margin:0 0 8px; color:#5d4037; font-size:13px;">
                وصلت إلى <strong><?php echo (int) $w->threshold; ?></strong> نقطة سلوكية — يجب الاطلاع والموافقة لمواصلة استخدام البوابة.
            </p>
            <button type="button"
                    class="rsyi-ack-btn"
                    data-warning-id="<?php echo esc_attr( $w->id ); ?>"
                    data-ajax-url="<?php echo esc_attr( $ajax_url ); ?>"
                    data-nonce="<?php echo esc_attr( $ajax_nonce ); ?>">
                ✍ موافق — لقد اطلعت
            </button>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ══ STATUS CARDS ═════════════════════════════════════════════════════ -->
    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:22px;">

        <div class="rsyi-dash-card" style="padding:18px 16px; text-align:center; border-top:4px solid #0073aa;">
            <div style="font-size:30px; margin-bottom:6px;">🎓</div>
            <div style="font-size:11px; color:#999; text-transform:uppercase; letter-spacing:.8px; margin-bottom:4px;">الدفعة / Cohort</div>
            <div style="font-size:16px; font-weight:700; color:#0073aa;"><?php echo esc_html( $cohort->name ?? '—' ); ?></div>
        </div>

        <div class="rsyi-dash-card" style="padding:18px 16px; text-align:center; border-top:4px solid <?php echo esc_attr( $sc ); ?>;">
            <div style="font-size:30px; margin-bottom:6px;">
                <?php echo $profile->status === 'active' ? '✅' : ( $profile->status === 'pending_docs' ? '📋' : '⛔' ); ?>
            </div>
            <div style="font-size:11px; color:#999; text-transform:uppercase; letter-spacing:.8px; margin-bottom:4px;">حالة الحساب</div>
            <div style="font-size:15px; font-weight:700; color:<?php echo esc_attr( $sc ); ?>;">
                <?php echo esc_html( $status_labels[ $profile->status ] ?? $profile->status ); ?>
            </div>
        </div>

        <div class="rsyi-dash-card" style="padding:18px 16px; text-align:center; border-top:4px solid <?php echo esc_attr( $pts_color ); ?>;">
            <div style="font-size:30px; margin-bottom:6px;">📊</div>
            <div style="font-size:11px; color:#999; text-transform:uppercase; letter-spacing:.8px; margin-bottom:4px;">نقاط السلوك</div>
            <div style="font-size:24px; font-weight:700; color:<?php echo esc_attr( $pts_color ); ?>;">
                <?php echo esc_html( $total_pts ); ?>
                <span style="font-size:13px; color:#bbb;">/ 40</span>
            </div>
        </div>

    </div>

    <!-- ══ PENDING DOCS BANNER ═══════════════════════════════════════════════ -->
    <?php if ( $profile->status === 'pending_docs' && $page_links['documents'] ) : ?>
    <div style="background:linear-gradient(135deg,#e3f2fd,#bbdefb); border:1px solid #90caf9; border-right:5px solid #0073aa; border-radius:10px; padding:18px 22px; margin-bottom:22px; display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
        <span style="font-size:38px; flex-shrink:0;">📋</span>
        <div style="flex:1; min-width:180px;">
            <strong style="font-size:15px; color:#0d47a1; display:block; margin-bottom:4px;">مطلوب رفع المستندات</strong>
            <p style="margin:0; color:#1565c0; font-size:13px;">سيتم تفعيل حسابك بعد رفع جميع الوثائق الـ 8 المطلوبة والموافقة عليها.</p>
        </div>
        <a href="<?php echo esc_url( $page_links['documents'] ); ?>"
           style="display:inline-block; background:#0073aa; color:#fff; border-radius:7px; padding:9px 20px; font-size:13px; font-weight:600; text-decoration:none; white-space:nowrap; flex-shrink:0;">
            رفع الوثائق الآن ←
        </a>
    </div>
    <?php endif; ?>

    <!-- ══ BOSS MAN SECTION ══════════════════════════════════════════════════ -->
    <?php if ( $is_boss_man && $page_links['boss_man'] ) : ?>
    <a href="<?php echo esc_url( $page_links['boss_man'] ); ?>"
       style="display:flex; align-items:center; gap:16px; background:linear-gradient(135deg,#e67e22,#d35400); color:#fff; border-radius:10px; padding:18px 22px; margin-bottom:22px; text-decoration:none; flex-wrap:wrap;"
       onmouseover="this.style.opacity='.9'" onmouseout="this.style.opacity='1'">
        <span style="font-size:42px; flex-shrink:0;">👮</span>
        <div style="flex:1; min-width:180px;">
            <strong style="font-size:16px; display:block; margin-bottom:4px;">تقرير المتابعة الدراسية اليومي</strong>
            <span style="font-size:13px; opacity:.9;">بما أنك حكمدار الدفعة هذا الأسبوع — سجّل الكورسات التي التحق بها الزملاء اليوم</span>
        </div>
        <span style="margin-right:auto; font-size:22px; opacity:.7; flex-shrink:0;">←</span>
    </a>
    <?php endif; ?>

    <!-- ══ QUICK LINKS GRID ══════════════════════════════════════════════════ -->
    <h3 style="font-size:14px; color:#666; margin:0 0 14px; font-weight:600; border-bottom:1px solid #eee; padding-bottom:8px; letter-spacing:.5px;">
        ⚡ الوصول السريع
    </h3>
    <div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:12px; margin-bottom:28px;">
    <?php
    $nav_items = [
        [ 'icon'=>'📄', 'label'=>'وثائقي',              'sub'=>'Documents',          'url'=>$page_links['documents'],  'color'=>'#0073aa', 'bg'=>'#e3f2fd' ],
        [ 'icon'=>'📝', 'label'=>'التصاريح والطلبات',  'sub'=>'Permits & Requests',  'url'=>$page_links['requests'],   'color'=>'#8e44ad', 'bg'=>'#f3e5f5' ],
        [ 'icon'=>'📊', 'label'=>'سجل السلوك',          'sub'=>'Behavior Record',     'url'=>$page_links['behavior'],   'color'=>'#e74c3c', 'bg'=>'#fdecea' ],
        [ 'icon'=>'⭐', 'label'=>'التقييم المتبادل',   'sub'=>'Peer Evaluation',      'url'=>$page_links['evaluation'], 'color'=>'#f39c12', 'bg'=>'#fff8e1' ],
        [ 'icon'=>'📚', 'label'=>'المواد الدراسية',    'sub'=>'Study Materials',      'url'=>$page_links['materials'],  'color'=>'#27ae60', 'bg'=>'#e8f5e9' ],
        [ 'icon'=>'🏅', 'label'=>'درجاتي',              'sub'=>'My Grades',           'url'=>$page_links['grades'],     'color'=>'#16a085', 'bg'=>'#e0f2f1' ],
        [ 'icon'=>'📅', 'label'=>'سجل الحضور',         'sub'=>'Attendance Record',    'url'=>$page_links['attendance'], 'color'=>'#2980b9', 'bg'=>'#e3f2fd' ],
    ];
    foreach ( $nav_items as $item ) :
        if ( ! $item['url'] ) continue;
    ?>
    <a href="<?php echo esc_url( $item['url'] ); ?>" class="rsyi-dash-nav-item"
       style="border-top:3px solid <?php echo esc_attr( $item['color'] ); ?>;"
       onmouseover="this.style.borderColor='<?php echo esc_attr( $item['color'] ); ?>'"
       onmouseout="this.style.borderColor='<?php echo esc_attr( $item['color'] ); ?>'">
        <div style="width:50px; height:50px; border-radius:12px; background:<?php echo esc_attr( $item['bg'] ); ?>; display:flex; align-items:center; justify-content:center; font-size:26px; margin:0 auto 10px;">
            <?php echo $item['icon']; ?>
        </div>
        <div style="font-weight:700; font-size:13px; color:<?php echo esc_attr( $item['color'] ); ?>; margin-bottom:3px;">
            <?php echo esc_html( $item['label'] ); ?>
        </div>
        <div style="font-size:11px; color:#aaa;">
            <?php echo esc_html( $item['sub'] ); ?>
        </div>
    </a>
    <?php endforeach; ?>
    </div>

    <!-- ══ FOOTER ════════════════════════════════════════════════════════════ -->
    <div style="text-align:center; padding:16px 0 8px; border-top:1px solid #eee; color:#bbb; font-size:12px;">
        <?php echo esc_html( $institute_name ); ?> — Student Portal
    </div>

</div><!-- /rsyi-dash -->

<?php /* Warning-ack JavaScript is added via wp_add_inline_script in render_dashboard() */ ?>
