<?php
/**
 * Portal – Student Dashboard
 * Variables: $profile, $total_pts, $warnings (array), $cohort
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

$status_labels = [
    'pending_docs' => 'في انتظار المستندات',
    'active'       => 'نشط',
    'suspended'    => 'موقوف',
    'expelled'     => 'مفصول',
];
$status_labels_en = [
    'pending_docs' => 'Pending Documents',
    'active'       => 'Active',
    'suspended'    => 'Suspended',
    'expelled'     => 'Expelled',
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

$status_color = [ 'pending_docs' => '#f39c12', 'active' => '#27ae60', 'suspended' => '#e67e22', 'expelled' => '#e74c3c' ];
$sc = $status_color[ $profile->status ] ?? '#999';
$pts_color = $total_pts >= 30 ? '#e74c3c' : ( $total_pts >= 20 ? '#e67e22' : '#27ae60' );

$is_boss_man = ! empty( $profile->is_boss_man );
$institute_name = get_option( 'rsyi_institute_name', 'معهد البحر الأحمر' );
?>
<div class="rsyi-portal" dir="rtl" style="font-family:'Segoe UI', Tahoma, sans-serif; max-width:900px; margin:0 auto; color:#2c3e50;">

    <!-- ══ HEADER ══════════════════════════════════════════════════════════ -->
    <div style="background:linear-gradient(135deg,#1a3a5c,#0073aa); color:#fff; border-radius:14px; padding:28px 32px; margin-bottom:22px; display:flex; align-items:center; gap:20px; position:relative; overflow:hidden;">
        <div style="position:absolute; top:-30px; left:-30px; width:140px; height:140px; border-radius:50%; background:rgba(255,255,255,.06);"></div>
        <div style="position:absolute; bottom:-20px; left:60px; width:100px; height:100px; border-radius:50%; background:rgba(255,255,255,.04);"></div>
        <?php echo get_avatar( $profile->user_id, 72, '', '', [ 'style' => 'border-radius:50%; border:3px solid rgba(255,255,255,.5); flex-shrink:0; position:relative; z-index:1;' ] ); ?>
        <div style="position:relative; z-index:1;">
            <p style="margin:0 0 2px; opacity:.75; font-size:12px; letter-spacing:1px; text-transform:uppercase;"><?php echo esc_html( $institute_name ); ?> — بوابة الطالب</p>
            <h2 style="margin:0 0 6px; font-size:22px; font-weight:700;"><?php echo esc_html( $profile->arabic_full_name ); ?></h2>
            <p style="margin:0; opacity:.85; font-size:13px;"><?php echo esc_html( $profile->english_full_name ); ?></p>
            <?php if ( $is_boss_man ) : ?>
            <span style="display:inline-block; margin-top:8px; background:rgba(230,126,34,.85); color:#fff; padding:3px 14px; border-radius:20px; font-size:12px; font-weight:700;">
                👮 حكمدار الدفعة
            </span>
            <?php endif; ?>
        </div>
        <a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>"
           style="position:relative; z-index:1; margin-right:auto; background:rgba(255,255,255,.15); color:#fff; padding:8px 18px; border-radius:8px; text-decoration:none; font-size:13px; border:1px solid rgba(255,255,255,.25); white-space:nowrap;">
            تسجيل الخروج ↩
        </a>
    </div>

    <!-- ══ WARNINGS ════════════════════════════════════════════════════════ -->
    <?php if ( ! empty( $warnings ) ) : ?>
    <div style="background:#fff3cd; border:1px solid #ffc107; border-right:5px solid #ff9800; border-radius:10px; padding:16px 20px; margin-bottom:20px;">
        <strong style="color:#856404; font-size:14px;">⚠ مطلوب منك اتخاذ إجراء — تحذيرات سلوكية معلقة</strong>
        <?php foreach ( $warnings as $w ) : ?>
        <div style="margin-top:12px; padding-top:12px; border-top:1px solid rgba(0,0,0,.08);" class="rsyi-warning-item">
            <p style="margin:0 0 10px; color:#555; font-size:13px;">
                وصلت إلى <?php echo (int) $w->threshold; ?> نقطة سلوكية. يجب الاطلاع والموافقة على هذا التحذير للاستمرار.
            </p>
            <button class="button button-primary rsyi-ack-btn" data-warning-id="<?php echo esc_attr( $w->id ); ?>" style="background:#e67e22; border-color:#d35400;">
                ✍ موافق — لقد اطلعت
            </button>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ══ STATUS CARDS ═════════════════════════════════════════════════════ -->
    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:22px;">

        <!-- Cohort -->
        <div style="background:#fff; border:1px solid #e8ecef; border-radius:10px; padding:18px 16px; text-align:center; border-top:4px solid #0073aa; box-shadow:0 2px 8px rgba(0,0,0,.04);">
            <div style="font-size:28px; margin-bottom:6px;">🎓</div>
            <div style="font-size:11px; color:#888; text-transform:uppercase; letter-spacing:.8px; margin-bottom:4px;">الدفعة</div>
            <div style="font-size:16px; font-weight:700; color:#0073aa;"><?php echo esc_html( $cohort->name ?? '—' ); ?></div>
        </div>

        <!-- Status -->
        <div style="background:#fff; border:1px solid #e8ecef; border-radius:10px; padding:18px 16px; text-align:center; border-top:4px solid <?php echo esc_attr( $sc ); ?>; box-shadow:0 2px 8px rgba(0,0,0,.04);">
            <div style="font-size:28px; margin-bottom:6px;"><?php echo $profile->status === 'active' ? '✅' : ( $profile->status === 'pending_docs' ? '📋' : '⛔' ); ?></div>
            <div style="font-size:11px; color:#888; text-transform:uppercase; letter-spacing:.8px; margin-bottom:4px;">حالة الحساب</div>
            <div style="font-size:15px; font-weight:700; color:<?php echo esc_attr( $sc ); ?>;">
                <?php echo esc_html( $status_labels[ $profile->status ] ?? $profile->status ); ?>
            </div>
        </div>

        <!-- Behavior Points -->
        <div style="background:#fff; border:1px solid #e8ecef; border-radius:10px; padding:18px 16px; text-align:center; border-top:4px solid <?php echo esc_attr( $pts_color ); ?>; box-shadow:0 2px 8px rgba(0,0,0,.04);">
            <div style="font-size:28px; margin-bottom:6px;">📊</div>
            <div style="font-size:11px; color:#888; text-transform:uppercase; letter-spacing:.8px; margin-bottom:4px;">نقاط السلوك</div>
            <div style="font-size:22px; font-weight:700; color:<?php echo esc_attr( $pts_color ); ?>;">
                <?php echo esc_html( $total_pts ); ?> <span style="font-size:13px; color:#aaa;">/ 40</span>
            </div>
        </div>
    </div>

    <!-- ══ PENDING DOCS BANNER ═══════════════════════════════════════════════ -->
    <?php if ( $profile->status === 'pending_docs' && $page_links['documents'] ) : ?>
    <div style="background:linear-gradient(135deg,#e3f2fd,#bbdefb); border:1px solid #90caf9; border-right:5px solid #0073aa; border-radius:10px; padding:18px 22px; margin-bottom:22px; display:flex; align-items:center; gap:16px;">
        <span style="font-size:36px;">📋</span>
        <div style="flex:1;">
            <strong style="font-size:15px; color:#0d47a1;">مطلوب رفع المستندات</strong>
            <p style="margin:4px 0 0; color:#1565c0; font-size:13px;">سيتم تفعيل حسابك بعد رفع جميع الوثائق الـ 8 المطلوبة والموافقة عليها.</p>
        </div>
        <a href="<?php echo esc_url( $page_links['documents'] ); ?>" class="button button-primary"
           style="background:#0073aa; border-color:#005177; color:#fff; white-space:nowrap;">
            رفع الوثائق الآن ←
        </a>
    </div>
    <?php endif; ?>

    <!-- ══ BOSS MAN SECTION ══════════════════════════════════════════════════ -->
    <?php if ( $is_boss_man && $page_links['boss_man'] ) : ?>
    <a href="<?php echo esc_url( $page_links['boss_man'] ); ?>"
       style="display:flex; align-items:center; gap:16px; background:linear-gradient(135deg,#e67e22,#d35400); color:#fff; border-radius:10px; padding:18px 22px; margin-bottom:22px; text-decoration:none; transition:opacity .2s;"
       onmouseover="this.style.opacity='.92'" onmouseout="this.style.opacity='1'">
        <span style="font-size:40px;">👮</span>
        <div>
            <strong style="font-size:16px; display:block; margin-bottom:3px;">تقرير المتابعة الدراسية اليومي</strong>
            <span style="font-size:13px; opacity:.9;">بما أنك حكمدار الدفعة — سجّل الكورسات التي التحق بها الزملاء اليوم</span>
        </div>
        <span style="margin-right:auto; font-size:24px; opacity:.7;">←</span>
    </a>
    <?php endif; ?>

    <!-- ══ QUICK LINKS GRID ══════════════════════════════════════════════════ -->
    <h3 style="font-size:15px; color:#555; margin-bottom:14px; font-weight:600; border-bottom:1px solid #eee; padding-bottom:8px;">
        ⚡ الوصول السريع
    </h3>
    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(170px, 1fr)); gap:14px; margin-bottom:28px;">
    <?php
    $nav_items = [
        [
            'icon'  => '📄',
            'label' => 'وثائقي',
            'url'   => $page_links['documents'],
            'desc'  => 'رفع ومتابعة الوثائق المطلوبة',
            'color' => '#0073aa',
            'bg'    => '#e3f2fd',
        ],
        [
            'icon'  => '📝',
            'label' => 'التصاريح والطلبات',
            'url'   => $page_links['requests'],
            'desc'  => 'تصاريح الخروج والمبيت',
            'color' => '#8e44ad',
            'bg'    => '#f3e5f5',
        ],
        [
            'icon'  => '📊',
            'label' => 'سجل السلوك',
            'url'   => $page_links['behavior'],
            'desc'  => 'عرض نقاط ومخالفات السلوك',
            'color' => '#e74c3c',
            'bg'    => '#fdecea',
        ],
        [
            'icon'  => '⭐',
            'label' => 'التقييم المتبادل',
            'url'   => $page_links['evaluation'],
            'desc'  => 'تقييم زملاء الدفعة',
            'color' => '#f39c12',
            'bg'    => '#fff8e1',
        ],
        [
            'icon'  => '📚',
            'label' => 'المواد الدراسية',
            'url'   => $page_links['materials'],
            'desc'  => 'تحميل مواد الكورسات',
            'color' => '#27ae60',
            'bg'    => '#e8f5e9',
        ],
        [
            'icon'  => '🏅',
            'label' => 'درجاتي',
            'url'   => $page_links['grades'],
            'desc'  => 'نتائج الاختبارات والتقديرات',
            'color' => '#16a085',
            'bg'    => '#e0f2f1',
        ],
        [
            'icon'  => '📅',
            'label' => 'سجل الحضور',
            'url'   => $page_links['attendance'],
            'desc'  => 'سجل حضوري وغيابي',
            'color' => '#2980b9',
            'bg'    => '#e3f2fd',
        ],
    ];
    foreach ( $nav_items as $item ) :
        if ( ! $item['url'] ) continue;
    ?>
    <a href="<?php echo esc_url( $item['url'] ); ?>"
       style="background:#fff; border:1px solid #e8ecef; border-radius:10px; padding:20px 16px; text-align:center; text-decoration:none; color:#2c3e50; transition:all .2s; display:block; box-shadow:0 2px 6px rgba(0,0,0,.04);"
       onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 6px 18px rgba(0,0,0,.1)'; this.style.borderColor='<?php echo $item['color']; ?>';"
       onmouseout="this.style.transform=''; this.style.boxShadow='0 2px 6px rgba(0,0,0,.04)'; this.style.borderColor='#e8ecef';">
        <div style="width:52px; height:52px; border-radius:12px; background:<?php echo esc_attr( $item['bg'] ); ?>; display:flex; align-items:center; justify-content:center; font-size:26px; margin:0 auto 12px;">
            <?php echo $item['icon']; ?>
        </div>
        <div style="font-weight:700; font-size:13px; color:<?php echo esc_attr( $item['color'] ); ?>; margin-bottom:4px;">
            <?php echo esc_html( $item['label'] ); ?>
        </div>
        <div style="font-size:11px; color:#888; line-height:1.4;">
            <?php echo esc_html( $item['desc'] ); ?>
        </div>
    </a>
    <?php endforeach; ?>
    </div>

    <!-- ══ FOOTER ════════════════════════════════════════════════════════════ -->
    <div style="text-align:center; padding-top:16px; border-top:1px solid #eee; color:#aaa; font-size:12px;">
        <?php echo esc_html( $institute_name ); ?> — بوابة الطلاب
    </div>
</div>

<script>
jQuery(function($){
    $('.rsyi-ack-btn').on('click', function(){
        var btn = $(this);
        var id  = btn.data('warning-id');
        if(!confirm('هل تؤكد اطلاعك وموافقتك على هذا التحذير؟')) return;
        btn.prop('disabled', true);
        $.post(rsyiPortal.ajaxUrl, {
            action:     'rsyi_acknowledge_warning',
            _nonce:     rsyiPortal.nonce,
            warning_id: id
        }, function(res){
            if(res.success){
                btn.closest('.rsyi-warning-item').html('<p style="color:#27ae60; font-weight:700;">✅ ' + res.data.message + '</p>');
            } else {
                btn.prop('disabled', false);
                alert(res.data.message);
            }
        });
    });
});
</script>
