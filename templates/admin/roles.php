<?php
/**
 * Admin Template: Roles & Permissions Management
 *
 * Full read/write permissions screen for all custom roles.
 * Requires capability: rsyi_manage_roles
 *
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

use RSYI_SA\Roles;

if ( ! current_user_can( 'rsyi_manage_roles' ) ) {
    echo '<div class="notice notice-error"><p>' . esc_html__( 'ليس لديك صلاحية الوصول لهذه الصفحة.', 'rsyi-sa' ) . '</p></div>';
    return;
}

$definitions = Roles::get_definitions();
$all_caps    = Roles::get_all_caps();

// HR-managed roles: SA capabilities are injected via rsyi_hr_extend_roles hook.
// These roles are created/owned by RSYI HR System; SA only manages their SA caps.
$hr_managed_roles = [];
foreach ( [ 'rsyi_dean', 'rsyi_hr_manager', 'rsyi_dept_head', 'rsyi_staff', 'rsyi_readonly' ] as $hr_slug ) {
    $hr_role = get_role( $hr_slug );
    if ( $hr_role ) {
        $hr_labels = [
            'rsyi_dean'       => 'Dean / عميد (HR)',
            'rsyi_hr_manager' => 'HR Manager / مدير الموارد البشرية (HR)',
            'rsyi_dept_head'  => 'Dept Head / رئيس قسم (HR)',
            'rsyi_staff'      => 'Staff / موظف (HR)',
            'rsyi_readonly'   => 'Read Only / مشاهد (HR)',
        ];
        $hr_managed_roles[ $hr_slug ] = [ 'label' => $hr_labels[ $hr_slug ] ?? $hr_slug, 'role' => $hr_role ];
    }
}

// Group capabilities by category for better display
$cap_groups = [
    'طلاب'         => [ 'rsyi_view_all_students', 'rsyi_create_student', 'rsyi_edit_student', 'rsyi_suspend_student', 'rsyi_delete_student' ],
    'الطالب - ذاتي' => [ 'rsyi_view_own_profile', 'rsyi_upload_own_documents', 'rsyi_submit_exit_permit', 'rsyi_submit_overnight_permit', 'rsyi_view_own_violations', 'rsyi_acknowledge_warning' ],
    'وثائق'        => [ 'rsyi_view_all_documents', 'rsyi_approve_document', 'rsyi_reject_document' ],
    'تصاريح'       => [ 'rsyi_view_all_requests', 'rsyi_approve_exit_permit', 'rsyi_reject_exit_permit', 'rsyi_approve_overnight_permit', 'rsyi_reject_overnight_permit' ],
    'مخالفات'      => [ 'rsyi_view_all_violations', 'rsyi_create_violation', 'rsyi_assign_violation_points', 'rsyi_overturn_violation', 'rsyi_manage_violation_types' ],
    'طرد'          => [ 'rsyi_manage_expulsion', 'rsyi_approve_expulsion' ],
    'أفواج'        => [ 'rsyi_manage_cohorts', 'rsyi_approve_cohort_transfer' ],
    'تقييم'        => [ 'rsyi_view_evaluations', 'rsyi_manage_evaluation_periods', 'rsyi_submit_admin_evaluation', 'rsyi_submit_peer_evaluation' ],
    'حضور/مواد/امتحانات' => [ 'rsyi_manage_attendance', 'rsyi_upload_study_materials', 'rsyi_manage_exams' ],
    'تقارير وسجلات' => [ 'rsyi_print_daily_report', 'rsyi_view_audit_log' ],
    'النظام'       => [ 'rsyi_manage_settings', 'rsyi_manage_roles' ],
];

// Label map for display
$cap_labels = [
    'rsyi_view_all_students'        => 'عرض جميع الطلاب',
    'rsyi_create_student'           => 'إنشاء طالب',
    'rsyi_edit_student'             => 'تعديل بيانات طالب',
    'rsyi_suspend_student'          => 'إيقاف طالب',
    'rsyi_delete_student'           => 'حذف طالب',
    'rsyi_view_own_profile'         => 'عرض الملف الشخصي',
    'rsyi_upload_own_documents'     => 'رفع وثائقه الشخصية',
    'rsyi_submit_exit_permit'       => 'تقديم طلب خروج',
    'rsyi_submit_overnight_permit'  => 'تقديم طلب مبيت',
    'rsyi_view_own_violations'      => 'عرض مخالفاته',
    'rsyi_acknowledge_warning'      => 'تأكيد الإنذار',
    'rsyi_view_all_documents'       => 'عرض جميع الوثائق',
    'rsyi_approve_document'         => 'قبول وثيقة',
    'rsyi_reject_document'          => 'رفض وثيقة',
    'rsyi_view_all_requests'        => 'عرض جميع التصاريح',
    'rsyi_approve_exit_permit'      => 'قبول تصريح خروج',
    'rsyi_reject_exit_permit'       => 'رفض تصريح خروج',
    'rsyi_approve_overnight_permit' => 'قبول تصريح مبيت',
    'rsyi_reject_overnight_permit'  => 'رفض تصريح مبيت',
    'rsyi_view_all_violations'      => 'عرض جميع المخالفات',
    'rsyi_create_violation'         => 'تسجيل مخالفة',
    'rsyi_assign_violation_points'  => 'إسناد نقاط مخالفة',
    'rsyi_overturn_violation'       => 'إلغاء مخالفة',
    'rsyi_manage_violation_types'   => 'إدارة أنواع المخالفات',
    'rsyi_manage_expulsion'         => 'إدارة الطرد',
    'rsyi_approve_expulsion'        => 'اعتماد الطرد',
    'rsyi_manage_cohorts'           => 'إدارة الأفواج',
    'rsyi_approve_cohort_transfer'  => 'اعتماد نقل الفوج',
    'rsyi_view_evaluations'         => 'عرض التقييمات',
    'rsyi_manage_evaluation_periods'=> 'إدارة دورات التقييم',
    'rsyi_submit_admin_evaluation'  => 'تقديم تقييم إداري',
    'rsyi_submit_peer_evaluation'   => 'تقديم تقييم الأقران',
    'rsyi_manage_attendance'        => 'إدارة الحضور والغياب',
    'rsyi_upload_study_materials'   => 'رفع المواد الدراسية',
    'rsyi_manage_exams'             => 'إدارة الامتحانات',
    'rsyi_print_daily_report'       => 'طباعة التقرير اليومي',
    'rsyi_view_audit_log'           => 'عرض سجل التدقيق',
    'rsyi_manage_settings'          => 'إدارة إعدادات النظام',
    'rsyi_manage_roles'             => 'إدارة الأدوار والصلاحيات',
];

// Active role being edited
$active_role_slug = sanitize_key( $_GET['role'] ?? '' );
$active_role      = $active_role_slug ? get_role( $active_role_slug ) : null;
?>
<h1 class="wp-heading-inline"><?php esc_html_e( 'إدارة الأدوار والصلاحيات', 'rsyi-sa' ); ?></h1>
<hr class="wp-header-end">

<div style="display:grid; grid-template-columns:260px 1fr; gap:24px; margin-top:20px; align-items:start;">

    <!-- ── Role list ── -->
    <div>
        <h3 style="margin-top:0; padding-bottom:8px; border-bottom:1px solid #ddd;">
            <?php esc_html_e( 'الأدوار المتاحة', 'rsyi-sa' ); ?>
        </h3>
        <!-- SA-specific roles -->
        <p style="margin:0 0 6px; font-size:11px; color:#888; direction:rtl;">أدوار شئون الطلاب</p>
        <ul style="margin:0 0 16px; padding:0; list-style:none;">
        <?php foreach ( $definitions as $slug => $def ) :
            $is_active = ( $slug === $active_role_slug );
        ?>
        <li style="margin-bottom:6px;">
            <a href="<?php echo esc_url( add_query_arg( [ 'page' => 'rsyi-roles', 'role' => $slug ], admin_url( 'admin.php' ) ) ); ?>"
               style="display:block; padding:10px 14px; border-radius:4px; text-decoration:none;
                      background:<?php echo $is_active ? '#0073aa' : '#f6f7f7'; ?>;
                      color:<?php echo $is_active ? '#fff' : '#2c3338'; ?>;
                      border:1px solid <?php echo $is_active ? '#0073aa' : '#ccd0d4'; ?>;">
                <?php echo esc_html( $def['label'] ); ?>
                <br><small style="opacity:.7; font-size:11px;"><?php echo esc_html( $slug ); ?></small>
            </a>
        </li>
        <?php endforeach; ?>
        </ul>

        <!-- HR-managed roles (SA caps only) -->
        <?php if ( ! empty( $hr_managed_roles ) ) : ?>
        <p style="margin:0 0 6px; font-size:11px; color:#888; direction:rtl;">أدوار الموارد البشرية (HR)</p>
        <ul style="margin:0; padding:0; list-style:none;">
        <?php foreach ( $hr_managed_roles as $slug => $info ) :
            $is_active = ( $slug === $active_role_slug );
        ?>
        <li style="margin-bottom:6px;">
            <a href="<?php echo esc_url( add_query_arg( [ 'page' => 'rsyi-roles', 'role' => $slug ], admin_url( 'admin.php' ) ) ); ?>"
               style="display:block; padding:10px 14px; border-radius:4px; text-decoration:none;
                      background:<?php echo $is_active ? '#2271b1' : '#f0f4f8'; ?>;
                      color:<?php echo $is_active ? '#fff' : '#2c3338'; ?>;
                      border:1px solid <?php echo $is_active ? '#2271b1' : '#b8c4cc'; ?>;">
                <?php echo esc_html( $info['label'] ); ?>
                <br><small style="opacity:.7; font-size:11px;"><?php echo esc_html( $slug ); ?></small>
            </a>
        </li>
        <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>

    <!-- ── Capabilities editor ── -->
    <div>
    <?php
    $is_hr_role = isset( $hr_managed_roles[ $active_role_slug ] );
    if ( $active_role && ( isset( $definitions[ $active_role_slug ] ) || $is_hr_role ) ) :
        // For HR roles use their live caps; for SA roles use definition caps as baseline
        $live_caps = $active_role->capabilities;
        $role_label = $is_hr_role
            ? $hr_managed_roles[ $active_role_slug ]['label']
            : $definitions[ $active_role_slug ]['label'];
    ?>
        <h2 style="margin-top:0;">
            <?php echo esc_html( $role_label ); ?>
        </h2>
        <?php if ( $is_hr_role ) : ?>
        <div class="notice notice-info inline" style="margin:0 0 16px; padding:8px 12px; direction:rtl;">
            <p style="margin:0;">
                <strong>دور يديره نظام الموارد البشرية (HR System).</strong>
                يمكنك تعديل صلاحيات شئون الطلاب (<code>rsyi_*</code>) على هذا الدور فقط.
                لا يمكن تعديل بنية الدور نفسه من هنا.
            </p>
        </div>
        <?php endif; ?>
        <p style="color:#555; margin-top:0;">
            <?php echo esc_html( $active_role_slug ); ?>
            &mdash; <?php esc_html_e( 'تحديد الصلاحيات المطلوبة ثم اضغط "حفظ"', 'rsyi-sa' ); ?>
        </p>

        <form id="rsyi-role-caps-form">
            <?php wp_nonce_field( 'rsyi_sa_admin', '_nonce' ); ?>
            <input type="hidden" name="action" value="rsyi_save_role_caps">
            <input type="hidden" name="role_slug" value="<?php echo esc_attr( $active_role_slug ); ?>">

            <?php foreach ( $cap_groups as $group_label => $group_caps ) :
                // Only show group if at least one cap is known
                $relevant = array_filter( $group_caps, fn( $c ) => in_array( $c, $all_caps, true ) );
                if ( empty( $relevant ) ) continue;
            ?>
            <div style="background:#fff; border:1px solid #ccd0d4; border-radius:4px; padding:16px 20px; margin-bottom:16px;">
                <h4 style="margin:0 0 12px; border-bottom:1px solid #eee; padding-bottom:8px; direction:rtl;">
                    <?php echo esc_html( $group_label ); ?>
                </h4>
                <table style="width:100%; border-collapse:collapse;">
                <?php foreach ( $relevant as $cap ) :
                    $checked = ! empty( $live_caps[ $cap ] );
                    $label   = $cap_labels[ $cap ] ?? $cap;
                ?>
                <tr>
                    <td style="padding:5px 0; width:40px; text-align:center;">
                        <input type="checkbox" name="caps[]" value="<?php echo esc_attr( $cap ); ?>"
                               id="cap_<?php echo esc_attr( $cap ); ?>"
                               <?php checked( $checked ); ?>
                               style="margin:0;">
                    </td>
                    <td style="padding:5px 0; direction:rtl; text-align:right; font-weight:600;">
                        <label for="cap_<?php echo esc_attr( $cap ); ?>"><?php echo esc_html( $label ); ?></label>
                    </td>
                    <td style="padding:5px 0; color:#888; font-size:11px; direction:ltr; text-align:left;">
                        <?php echo esc_html( $cap ); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </table>
            </div>
            <?php endforeach; ?>

            <p style="margin-top:20px;">
                <button type="submit" class="button button-primary button-large" id="rsyi-roles-save">
                    <?php esc_html_e( 'حفظ الصلاحيات', 'rsyi-sa' ); ?>
                </button>
                <span id="rsyi-roles-msg" style="margin-right:12px; display:none;"></span>
            </p>
        </form>

    <?php elseif ( $active_role_slug ) : ?>
        <div class="notice notice-error"><p><?php esc_html_e( 'الدور غير موجود.', 'rsyi-sa' ); ?></p></div>
    <?php else : ?>
        <div style="background:#fff; border:1px solid #ccd0d4; border-radius:4px; padding:40px; text-align:center; color:#555; direction:rtl;">
            <p style="font-size:16px;"><?php esc_html_e( 'اختر دوراً من القائمة على اليسار لتعديل صلاحياته.', 'rsyi-sa' ); ?></p>
        </div>
    <?php endif; ?>
    </div>

</div>

<script>
jQuery(function($){
    $('#rsyi-role-caps-form').on('submit', function(e){
        e.preventDefault();
        var $btn = $('#rsyi-roles-save');
        var $msg = $('#rsyi-roles-msg');
        $btn.prop('disabled', true);
        $.post(rsyiSA.ajaxUrl, $(this).serialize(), function(res){
            $btn.prop('disabled', false);
            $msg.show().css('color', res.success ? 'green' : 'red').text(res.data.message);
        });
    });
});
</script>
