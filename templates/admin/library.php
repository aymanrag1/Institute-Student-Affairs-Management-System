<?php
defined( 'ABSPATH' ) || exit;
$cohorts              = \RSYI_SA\Modules\Library_Orders::get_cohorts();
$trainers             = \RSYI_SA\Modules\Library_Orders::get_trainers();
$students             = \RSYI_SA\Modules\Library::get_all_students();
$lib_can_manage       = current_user_can( 'rsyi_lib_manage_warehouse' ) || current_user_can( 'manage_options' );
$lib_can_approve_wd   = current_user_can( 'rsyi_lib_approve_withdrawal' ) || current_user_can( 'manage_options' );
$lib_can_approve_pr   = current_user_can( 'rsyi_lib_approve_purchase' ) || current_user_can( 'manage_options' );

// ── Language helper ─────────────────────────────────────────────────────────
global $_lib_en;
$_lib_en = class_exists( 'RSYI_Language' ) && RSYI_Language::get_lang() === 'en';
if ( ! function_exists( 'rsyi_lib_t' ) ) {
    function rsyi_lib_t( string $ar, string $en ): string {
        global $_lib_en;
        return $_lib_en ? $en : $ar;
    }
}
$_dir = $_lib_en ? 'ltr' : 'rtl';
?>
<div class="wrap rsyi-lib-wrap" id="rsyi-library-page" dir="<?= $_dir ?>">
<h1 class="wp-heading-inline">⚓ <?= rsyi_lib_t('تدريب RYA', 'RYA Training') ?></h1>
<hr class="wp-header-end">

<nav class="nav-tab-wrapper rsyi-tabs" style="margin-bottom:0;">
    <a href="#" class="nav-tab nav-tab-active" data-tab="dashboard">🏠 <?= rsyi_lib_t('الرئيسية', 'Dashboard') ?></a>
    <a href="#" class="nav-tab" data-tab="books">📚 <?= rsyi_lib_t('العناصر', 'Items') ?></a>
    <a href="#" class="nav-tab" data-tab="suppliers">🏭 <?= rsyi_lib_t('الموردون', 'Suppliers') ?></a>
    <a href="#" class="nav-tab" data-tab="add-orders">📥 <?= rsyi_lib_t('إذن الاستلام', 'Receiving Order') ?></a>
    <a href="#" class="nav-tab" data-tab="wd-orders">📤 <?= rsyi_lib_t('إذن الصرف', 'Withdrawal Order') ?></a>
    <a href="#" class="nav-tab" data-tab="ret-orders">↩ <?= rsyi_lib_t('رد الكتب', 'Book Returns') ?></a>
    <a href="#" class="nav-tab" data-tab="pr">🛒 <?= rsyi_lib_t('طلبات الشراء', 'Purchase Requests') ?></a>
    <a href="#" class="nav-tab" data-tab="opening">🔢 <?= rsyi_lib_t('الأرصدة الافتتاحية', 'Opening Balances') ?></a>
    <a href="#" class="nav-tab" data-tab="reports">📊 <?= rsyi_lib_t('التقارير', 'Reports') ?></a>
</nav>

<!-- ═══ DASHBOARD ═══ -->
<div id="tab-dashboard" class="rsyi-tab-pane">
    <div id="lib-stat-cards" style="display:flex;flex-wrap:wrap;gap:16px;margin:20px 0;"></div>
    <h3>⚠ <?= rsyi_lib_t('كتب على وشك النفاد', 'Low Stock Books') ?></h3>
    <div id="lib-low-table-wrap"></div>
</div>

<!-- ═══ BOOKS ═══ -->
<div id="tab-books" class="rsyi-tab-pane" style="display:none;">
    <div class="rsyi-toolbar">
        <?php if($lib_can_manage): ?>
        <button class="button button-primary" id="btn-add-book">+ <?= rsyi_lib_t('إضافة عنصر', 'Add Item') ?></button>
        <button class="button" id="btn-import-books">📥 <?= rsyi_lib_t('استيراد Excel', 'Import Excel') ?></button>
        <button class="button" id="btn-dl-template">📄 <?= rsyi_lib_t('نموذج Excel', 'Excel Template') ?></button>
        <?php endif; ?>
        <select id="filter-cat"><option value=""><?= rsyi_lib_t('كل التصنيفات', 'All Categories') ?></option>
            <option value="curriculum"><?= rsyi_lib_t('مناهج أجنبية', 'Foreign Curriculum') ?></option><option value="certificate"><?= rsyi_lib_t('شهادات', 'Certificates') ?></option><option value="general"><?= rsyi_lib_t('عام', 'General') ?></option>
        </select>
        <input type="text" id="filter-search" placeholder="<?= rsyi_lib_t('بحث…', 'Search…') ?>">
        <button class="button" id="btn-search-books">🔍</button>
    </div>
    <table class="wp-list-table widefat fixed striped" style="direction:<?= $_dir ?>;">
        <thead><tr>
            <th style="width:50px;"><?= rsyi_lib_t('غلاف', 'Cover') ?></th><th><?= rsyi_lib_t('العنوان', 'Title') ?></th><th><?= rsyi_lib_t('المادة', 'Subject') ?></th><th><?= rsyi_lib_t('المستوى', 'Grade') ?></th><th>ISBN</th>
            <th style="width:80px;"><?= rsyi_lib_t('الرصيد', 'Stock') ?></th><th style="width:70px;"><?= rsyi_lib_t('حد أدنى', 'Min') ?></th><th style="width:110px;"><?= rsyi_lib_t('إجراء', 'Action') ?></th>
        </tr></thead>
        <tbody id="books-list"><tr><td colspan="8" style="text-align:center"><?= rsyi_lib_t('جاري التحميل…', 'Loading…') ?></td></tr></tbody>
    </table>
</div>

<!-- ═══ SUPPLIERS ═══ -->
<div id="tab-suppliers" class="rsyi-tab-pane" style="display:none;">
    <?php if($lib_can_manage): ?>
    <div style="max-width:700px;background:#fff;padding:20px;border:1px solid #ddd;border-radius:6px;margin:16px 0;">
        <h3 id="sup-form-title" style="margin-top:0;"><?= rsyi_lib_t('إضافة مورد', 'Add Supplier') ?></h3>
        <input type="hidden" id="sup-id" value="0">
        <table class="form-table">
            <tr><th><label for="sup-name"><?= rsyi_lib_t('الاسم', 'Name') ?> *</label></th><td><input type="text" id="sup-name" class="regular-text"></td></tr>
            <tr><th><label for="sup-phone"><?= rsyi_lib_t('الهاتف', 'Phone') ?></label></th><td><input type="text" id="sup-phone" class="regular-text"></td></tr>
            <tr><th><label for="sup-email"><?= rsyi_lib_t('البريد', 'Email') ?></label></th><td><input type="email" id="sup-email" class="regular-text"></td></tr>
            <tr><th><label for="sup-addr"><?= rsyi_lib_t('العنوان', 'Address') ?></label></th><td><textarea id="sup-addr" class="large-text" rows="2"></textarea></td></tr>
        </table>
        <button class="button button-primary" id="btn-save-supplier">💾 <?= rsyi_lib_t('حفظ', 'Save') ?></button>
        <button class="button" id="btn-cancel-sup" style="display:none;"><?= rsyi_lib_t('إلغاء', 'Cancel') ?></button>
        <div id="sup-msg" style="margin-top:8px;display:none;"></div>
    </div>
    <?php endif; ?>
    <table class="wp-list-table widefat fixed striped" style="direction:<?= $_dir ?>;">
        <thead><tr><th><?= rsyi_lib_t('الاسم', 'Name') ?></th><th><?= rsyi_lib_t('الهاتف', 'Phone') ?></th><th><?= rsyi_lib_t('البريد', 'Email') ?></th><th><?= rsyi_lib_t('العنوان', 'Address') ?></th><th style="width:100px;"><?= rsyi_lib_t('إجراء', 'Action') ?></th></tr></thead>
        <tbody id="suppliers-list"><tr><td colspan="5" style="text-align:center"><?= rsyi_lib_t('جاري التحميل…', 'Loading…') ?></td></tr></tbody>
    </table>
</div>

<!-- ═══ ADD ORDERS (RECEIVING) ═══ -->
<div id="tab-add-orders" class="rsyi-tab-pane" style="display:none;">
    <?php if($lib_can_manage): ?>
    <div class="rsyi-toolbar"><button class="button button-primary" id="btn-new-add-order">+ <?= rsyi_lib_t('إذن استلام جديد', 'New Receiving Order') ?></button></div>
    <?php endif; ?>
    <table class="wp-list-table widefat fixed striped" style="direction:<?= $_dir ?>;">
        <thead><tr><th><?= rsyi_lib_t('رقم الإذن', 'Order No.') ?></th><th><?= rsyi_lib_t('المورد', 'Supplier') ?></th><th><?= rsyi_lib_t('التاريخ', 'Date') ?></th><th><?= rsyi_lib_t('الكمية', 'Qty') ?></th><th><?= rsyi_lib_t('القيمة', 'Value') ?></th><th style="width:140px;"><?= rsyi_lib_t('إجراء', 'Action') ?></th></tr></thead>
        <tbody id="add-orders-list"><tr><td colspan="6" style="text-align:center"><?= rsyi_lib_t('جاري التحميل…', 'Loading…') ?></td></tr></tbody>
    </table>
</div>

<!-- ═══ WITHDRAWAL ORDERS ═══ -->
<div id="tab-wd-orders" class="rsyi-tab-pane" style="display:none;">
    <div class="rsyi-toolbar">
        <?php if($lib_can_manage): ?>
        <button class="button button-primary" id="btn-new-wd-order">+ <?= rsyi_lib_t('إذن صرف جديد', 'New Withdrawal') ?></button>
        <?php endif; ?>
        <select id="wd-filter-status">
            <option value=""><?= rsyi_lib_t('كل الحالات', 'All Statuses') ?></option>
            <option value="draft"><?= rsyi_lib_t('مسودة', 'Draft') ?></option><option value="pending"><?= rsyi_lib_t('في الانتظار', 'Pending') ?></option>
            <option value="approved"><?= rsyi_lib_t('معتمد', 'Approved') ?></option><option value="completed"><?= rsyi_lib_t('مكتمل', 'Completed') ?></option><option value="rejected"><?= rsyi_lib_t('مرفوض', 'Rejected') ?></option>
        </select>
        <button class="button" id="btn-filter-wd">🔍</button>
    </div>
    <table class="wp-list-table widefat fixed striped" style="direction:<?= $_dir ?>;">
        <thead><tr><th><?= rsyi_lib_t('رقم الإذن', 'Order No.') ?></th><th><?= rsyi_lib_t('المجموعة/المدرب', 'Cohort/Trainer') ?></th><th><?= rsyi_lib_t('النوع', 'Type') ?></th><th><?= rsyi_lib_t('الحالة', 'Status') ?></th><th><?= rsyi_lib_t('بواسطة', 'By') ?></th><th><?= rsyi_lib_t('التاريخ', 'Date') ?></th><th style="width:180px;"><?= rsyi_lib_t('إجراء', 'Action') ?></th></tr></thead>
        <tbody id="wd-orders-list"><tr><td colspan="7" style="text-align:center"><?= rsyi_lib_t('جاري التحميل…', 'Loading…') ?></td></tr></tbody>
    </table>
</div>

<!-- ═══ RETURN ORDERS ═══ -->
<div id="tab-ret-orders" class="rsyi-tab-pane" style="display:none;">
    <?php if($lib_can_manage): ?>
    <div class="rsyi-toolbar"><button class="button button-primary" id="btn-new-ret-order">+ <?= rsyi_lib_t('إذن رد جديد', 'New Return Order') ?></button></div>
    <?php endif; ?>
    <table class="wp-list-table widefat fixed striped" style="direction:<?= $_dir ?>;">
        <thead><tr><th><?= rsyi_lib_t('رقم الإذن', 'Order No.') ?></th><th><?= rsyi_lib_t('المجموعة/المدرب', 'Cohort/Trainer') ?></th><th><?= rsyi_lib_t('الحالة', 'Status') ?></th><th><?= rsyi_lib_t('التاريخ', 'Date') ?></th><th style="width:130px;"><?= rsyi_lib_t('إجراء', 'Action') ?></th></tr></thead>
        <tbody id="ret-orders-list"><tr><td colspan="5" style="text-align:center"><?= rsyi_lib_t('جاري التحميل…', 'Loading…') ?></td></tr></tbody>
    </table>
</div>

<!-- ═══ PURCHASE REQUESTS ═══ -->
<div id="tab-pr" class="rsyi-tab-pane" style="display:none;">
    <?php if($lib_can_manage): ?>
    <div class="rsyi-toolbar">
        <button class="button button-primary" id="btn-new-pr">+ <?= rsyi_lib_t('طلب شراء جديد', 'New Purchase Request') ?></button>
        <button class="button" id="btn-auto-pr">🤖 <?= rsyi_lib_t('توليد تلقائي', 'Auto-generate') ?></button>
    </div>
    <?php endif; ?>
    <table class="wp-list-table widefat fixed striped" style="direction:<?= $_dir ?>;">
        <thead><tr><th><?= rsyi_lib_t('رقم الطلب', 'Request No.') ?></th><th><?= rsyi_lib_t('الحالة', 'Status') ?></th><th><?= rsyi_lib_t('طالب بواسطة', 'Requested By') ?></th><th><?= rsyi_lib_t('التاريخ', 'Date') ?></th><th style="width:180px;"><?= rsyi_lib_t('إجراء', 'Action') ?></th></tr></thead>
        <tbody id="pr-list"><tr><td colspan="5" style="text-align:center"><?= rsyi_lib_t('جاري التحميل…', 'Loading…') ?></td></tr></tbody>
    </table>
</div>

<!-- ═══ OPENING BALANCES ═══ -->
<div id="tab-opening" class="rsyi-tab-pane" style="display:none;">
    <?php if($lib_can_manage): ?>
    <div style="max-width:600px;background:#fff;padding:20px;border:1px solid #ddd;border-radius:6px;margin:16px 0;">
        <h3 style="margin-top:0;"><?= rsyi_lib_t('إضافة رصيد افتتاحي', 'Add Opening Balance') ?></h3>
        <table class="form-table">
            <tr><th><label for="ob-book"><?= rsyi_lib_t('الكتاب', 'Book') ?> *</label></th><td>
                <select id="ob-book" style="width:100%;"><option value=""><?= rsyi_lib_t('-- اختر كتاباً --', '-- Select a Book --') ?></option></select>
            </td></tr>
            <tr><th><label for="ob-qty"><?= rsyi_lib_t('الكمية', 'Quantity') ?> *</label></th><td><input type="number" id="ob-qty" value="0" min="0" style="width:100px;"></td></tr>
            <tr><th><label for="ob-price"><?= rsyi_lib_t('سعر الوحدة', 'Unit Price') ?></label></th><td><input type="number" id="ob-price" value="0" min="0" step="0.01" style="width:120px;"></td></tr>
            <tr><th><label for="ob-notes"><?= rsyi_lib_t('ملاحظات', 'Notes') ?></label></th><td><textarea id="ob-notes" class="large-text" rows="2"></textarea></td></tr>
        </table>
        <button class="button button-primary" id="btn-save-opening">💾 <?= rsyi_lib_t('حفظ', 'Save') ?></button>
        <div id="ob-msg" style="margin-top:8px;display:none;"></div>
    </div>
    <?php endif; ?>
    <table class="wp-list-table widefat fixed striped" style="direction:<?= $_dir ?>;">
        <thead><tr><th><?= rsyi_lib_t('الكتاب', 'Book') ?></th><th><?= rsyi_lib_t('الكمية', 'Quantity') ?></th><th><?= rsyi_lib_t('سعر الوحدة', 'Unit Price') ?></th><th><?= rsyi_lib_t('التاريخ', 'Date') ?></th></tr></thead>
        <tbody id="opening-list"><tr><td colspan="4" style="text-align:center"><?= rsyi_lib_t('جاري التحميل…', 'Loading…') ?></td></tr></tbody>
    </table>
</div>

<!-- ═══ REPORTS ═══ -->
<div id="tab-reports" class="rsyi-tab-pane" style="display:none;">
    <div class="rsyi-toolbar" style="margin:16px 0;gap:8px;display:flex;flex-wrap:wrap;">
        <button class="button report-btn btn-active" data-report="stock">📋 <?= rsyi_lib_t('الرصيد الحالي', 'Current Stock') ?></button>
        <button class="button report-btn" data-report="movement">📈 <?= rsyi_lib_t('حركة كتاب', 'Book Movement') ?></button>
        <button class="button report-btn" data-report="low_stock">⚠ <?= rsyi_lib_t('على وشك النفاد', 'Low Stock') ?></button>
        <button class="button report-btn" data-report="zero_stock">❌ <?= rsyi_lib_t('رصيد صفري', 'Zero Stock') ?></button>
        <button class="button report-btn" data-report="cohort_withdrawal">👥 <?= rsyi_lib_t('صرف مجموعة', 'Cohort Withdrawal') ?></button>
        <button class="button report-btn" data-report="supplier">🏭 <?= rsyi_lib_t('مورد', 'Supplier') ?></button>
    </div>
    <div id="report-filters" style="margin-bottom:8px;display:flex;flex-wrap:wrap;gap:8px;align-items:center;"></div>
    <div style="margin-bottom:12px;">
        <button class="button" id="btn-print-report">🖨 <?= rsyi_lib_t('طباعة التقرير', 'Print Report') ?></button>
    </div>
    <div id="report-output"></div>
</div>
</div><!-- end .wrap -->

<!-- ═══════════════════════════════ MODALS ═══════════════════════════════ -->

<!-- Import Excel Modal -->
<div id="import-modal" class="rsyi-modal-overlay" dir="<?= $_dir ?>" style="display:none;">
<div class="rsyi-modal-box" style="max-width:960px;max-height:90vh;overflow-y:auto;">
    <h2 style="margin-top:0;border-bottom:1px solid #eee;padding-bottom:12px;">📥 <?= rsyi_lib_t('استيراد عناصر من Excel', 'Import Items from Excel') ?></h2>
    <p style="color:#666;"><?= rsyi_lib_t('ارفع ملف .xlsx أو .csv يطابق أعمدة النموذج.', 'Upload an .xlsx or .csv file matching the template columns.') ?></p>
    <input type="file" id="import-file" accept=".xlsx,.xls,.csv" style="margin-bottom:12px;">
    <div id="import-preview" style="max-height:320px;overflow:auto;margin:12px 0;border:1px solid #ddd;border-radius:4px;padding:8px;display:none;"></div>
    <p style="margin-top:12px;">
        <button class="button button-primary" id="btn-do-import" style="display:none;">💾 <?= rsyi_lib_t('استيراد الكل', 'Import All') ?></button>
        <button class="button rsyi-modal-close" data-modal="import-modal" style="margin-<?= $_lib_en?'left':'right' ?>:8px;"><?= rsyi_lib_t('إلغاء', 'Cancel') ?></button>
    </p>
    <div id="import-msg" style="display:none;margin-top:8px;"></div>
</div></div>

<!-- Book Modal -->
<div id="book-modal" class="rsyi-modal-overlay" dir="<?= $_dir ?>" style="display:none;">
<div class="rsyi-modal-box" style="max-width:780px;">
    <h2 id="bm-title" style="margin-top:0;border-bottom:1px solid #eee;padding-bottom:12px;"><?= rsyi_lib_t('إضافة عنصر', 'Add Item') ?></h2>
    <input type="hidden" id="bm-id" value="0">
    <div class="rsyi-form-grid">
        <!-- Title — full width -->
        <div class="rsyi-fg-full">
            <label class="rsyi-fg-label"><?= rsyi_lib_t('العنوان', 'Title') ?> <span style="color:red;">*</span></label>
            <input type="text" id="bm-title-en" style="width:100%;">
            <input type="hidden" id="bm-title-ar">
        </div>
        <!-- Row 2 -->
        <div>
            <label class="rsyi-fg-label"><?= rsyi_lib_t('المؤلف', 'Author') ?></label>
            <input type="text" id="bm-author" style="width:100%;">
        </div>
        <div>
            <label class="rsyi-fg-label"><?= rsyi_lib_t('الناشر', 'Publisher') ?></label>
            <input type="text" id="bm-publisher" style="width:100%;">
        </div>
        <!-- Row 3 -->
        <div>
            <label class="rsyi-fg-label"><?= rsyi_lib_t('المادة', 'Subject') ?></label>
            <input type="text" id="bm-subject" style="width:100%;">
        </div>
        <div>
            <label class="rsyi-fg-label"><?= rsyi_lib_t('المجموعة', 'Cohort') ?></label>
            <input type="text" id="bm-grade" style="width:100%;">
        </div>
        <!-- Row 4 -->
        <div>
            <label class="rsyi-fg-label"><?= rsyi_lib_t('لـ', 'For') ?></label>
            <select id="bm-audience" style="width:100%;">
                <option value="trainers"><?= rsyi_lib_t('مدربين', 'Trainers') ?></option>
                <option value="students"><?= rsyi_lib_t('طلاب', 'Students') ?></option>
                <option value="learning_aids"><?= rsyi_lib_t('مساعدات تعلمية', 'Learning Aids') ?></option>
            </select>
        </div>
        <div>
            <label class="rsyi-fg-label"><?= rsyi_lib_t('التصنيف', 'Category') ?></label>
            <select id="bm-cat" style="width:100%;">
                <option value="curriculum"><?= rsyi_lib_t('مناهج أجنبية', 'Foreign Curriculum') ?></option>
                <option value="certificate"><?= rsyi_lib_t('شهادات', 'Certificates') ?></option>
                <option value="general"><?= rsyi_lib_t('عام', 'General') ?></option>
            </select>
        </div>
        <!-- Row 5 -->
        <div>
            <label class="rsyi-fg-label"><?= rsyi_lib_t('اللغة', 'Language') ?></label>
            <select id="bm-lang" style="width:100%;">
                <option value="en">English</option>
                <option value="ar"><?= rsyi_lib_t('العربية', 'Arabic') ?></option>
                <option value="fr">Français</option>
                <option value="other"><?= rsyi_lib_t('أخرى', 'Other') ?></option>
            </select>
        </div>
        <div>
            <label class="rsyi-fg-label">ISBN</label>
            <input type="text" id="bm-isbn" style="width:100%;">
        </div>
        <!-- Row 6 -->
        <div>
            <label class="rsyi-fg-label"><?= rsyi_lib_t('الوحدة', 'Unit') ?></label>
            <select id="bm-unit" style="width:100%;">
                <option value="copy"><?= rsyi_lib_t('نسخة', 'Copy') ?></option>
                <option value="volume"><?= rsyi_lib_t('مجلد', 'Volume') ?></option>
            </select>
        </div>
        <div>
            <label class="rsyi-fg-label"><?= rsyi_lib_t('السعر', 'Price') ?></label>
            <input type="number" id="bm-price" value="0" min="0" step="0.01" style="width:120px;">
        </div>
        <!-- Row 7 -->
        <div>
            <label class="rsyi-fg-label"><?= rsyi_lib_t('حد أدنى', 'Min Stock') ?></label>
            <input type="number" id="bm-min-stock" value="0" min="0" style="width:100px;">
        </div>
        <div>
            <label class="rsyi-fg-label"><?= rsyi_lib_t('حد أقصى', 'Max Stock') ?></label>
            <input type="number" id="bm-max-stock" value="0" min="0" style="width:100px;">
        </div>
        <!-- Description — full width -->
        <div class="rsyi-fg-full">
            <label class="rsyi-fg-label"><?= rsyi_lib_t('الوصف', 'Description') ?></label>
            <textarea id="bm-desc" rows="2" style="width:100%;"></textarea>
        </div>
        <!-- Cover — full width -->
        <div class="rsyi-fg-full">
            <label class="rsyi-fg-label"><?= rsyi_lib_t('صورة الغلاف', 'Cover Image') ?></label>
            <input type="hidden" id="bm-cover-id" value="0">
            <div id="bm-cover-preview" style="margin-bottom:6px;"></div>
            <button type="button" class="button" id="btn-bm-cover"><?= rsyi_lib_t('اختر صورة', 'Choose Image') ?></button>
            <button type="button" class="button" id="btn-bm-cover-clear" style="display:none;"><?= rsyi_lib_t('إزالة', 'Remove') ?></button>
        </div>
    </div>
    <p><button class="button button-primary" id="btn-save-book">💾 <?= rsyi_lib_t('حفظ', 'Save') ?></button>
       <button class="button rsyi-modal-close" data-modal="book-modal" style="margin-right:8px;"><?= rsyi_lib_t('إلغاء', 'Cancel') ?></button></p>
    <div id="bm-msg" style="display:none;margin-top:8px;"></div>
</div></div>

<!-- Add Order Modal -->
<div id="add-order-modal" class="rsyi-modal-overlay" dir="<?= $_dir ?>" style="display:none;">
<div class="rsyi-modal-box" style="max-width:820px;max-height:90vh;overflow-y:auto;">
    <h2 id="ao-title" style="margin-top:0;"><?= rsyi_lib_t('إذن استلام', 'Receiving Order') ?></h2>
    <input type="hidden" id="ao-id" value="0">
    <table class="form-table">
        <tr><th><?= rsyi_lib_t('المورد', 'Supplier') ?></th><td>
            <select id="ao-supplier"><option value="0"><?= rsyi_lib_t('-- بدون مورد --', '-- No Supplier --') ?></option></select>
        </td>
        <th><?= rsyi_lib_t('رقم عرض السعر', 'Quote No.') ?></th><td><input type="text" id="ao-quote-num" class="regular-text" placeholder="RFQ-..."></td></tr>
        <tr><th><?= rsyi_lib_t('ملاحظات', 'Notes') ?></th><td colspan="3"><textarea id="ao-notes" class="large-text" rows="2"></textarea></td></tr>
    </table>
    <div style="margin:12px 0;">
        <strong><?= rsyi_lib_t('الكتب', 'Items') ?>:</strong>
        <button type="button" class="button button-small" id="btn-ao-add-row" style="margin-right:8px;">+ <?= rsyi_lib_t('إضافة سطر', 'Add Row') ?></button>
    </div>
    <table class="wp-list-table widefat" id="ao-items-table" style="direction:<?= $_dir ?>;">
        <thead><tr>
            <th><?= rsyi_lib_t('الكتاب', 'Book') ?></th>
            <th style="width:70px;"><?= rsyi_lib_t('الكمية', 'Qty') ?></th>
            <th style="width:90px;"><?= rsyi_lib_t('سعر الوحدة', 'Unit Price') ?></th>
            <th style="width:70px;"><?= rsyi_lib_t('ضريبة %', 'Tax %') ?></th>
            <th style="width:70px;"><?= rsyi_lib_t('خصم %', 'Disc %') ?></th>
            <th style="width:40px;"></th>
        </tr></thead>
        <tbody id="ao-items-body"></tbody>
        <tfoot><tr><td colspan="3" style="text-align:<?= $_lib_en?'left':'right' ?>;"><strong><?= rsyi_lib_t('الإجمالي', 'Total') ?>: <span id="ao-total">0.00</span></strong></td><td colspan="3"></td></tr></tfoot>
    </table>
    <p style="margin-top:16px;">
        <button class="button button-primary" id="btn-save-add-order">💾 <?= rsyi_lib_t('حفظ', 'Save') ?></button>
        <button class="button rsyi-modal-close" data-modal="add-order-modal" style="margin-right:8px;"><?= rsyi_lib_t('إلغاء', 'Cancel') ?></button>
    </p>
    <div id="ao-msg" style="display:none;margin-top:8px;"></div>
</div></div>

<!-- Withdrawal Order Modal -->
<div id="wd-modal" class="rsyi-modal-overlay" dir="<?= $_dir ?>" style="display:none;">
<div class="rsyi-modal-box" style="max-width:820px;max-height:90vh;overflow-y:auto;">
    <h2 id="wd-title" style="margin-top:0;"><?= rsyi_lib_t('إذن صرف', 'Withdrawal Order') ?></h2>
    <input type="hidden" id="wd-id" value="0">
    <table class="form-table">
        <tr><th><?= rsyi_lib_t('المجموعة', 'Cohort') ?></th><td>
            <select id="wd-cohort"><option value="0"><?= rsyi_lib_t('-- اختر مجموعة --', '-- Select Cohort --') ?></option>
                <?php foreach($cohorts as $c): ?><option value="<?php echo $c->id ?>"><?php echo esc_html($c->name) ?></option><?php endforeach; ?>
            </select>
        </td>
        <th><?= rsyi_lib_t('المدرب', 'Trainer') ?></th><td>
            <select id="wd-trainer"><option value="0"><?= rsyi_lib_t('-- اختر مدرب --', '-- Select Trainer --') ?></option>
                <?php foreach($trainers as $t): ?><option value="<?php echo $t->ID ?>"><?php echo esc_html($t->display_name) ?></option><?php endforeach; ?>
            </select>
        </td></tr>
        <tr><th><?= rsyi_lib_t('النوع', 'Type') ?></th><td>
            <select id="wd-type"><option value="normal"><?= rsyi_lib_t('عادي', 'Normal') ?></option><option value="custody"><?= rsyi_lib_t('عهدة', 'Custody') ?></option></select>
        </td>
        <th><?= rsyi_lib_t('ملاحظات', 'Notes') ?></th><td><textarea id="wd-notes" rows="2" class="large-text"></textarea></td></tr>
    </table>
    <div style="margin:12px 0;">
        <strong><?= rsyi_lib_t('الكتب', 'Items') ?>:</strong>
        <button type="button" class="button button-small" id="btn-wd-add-row">+ <?= rsyi_lib_t('إضافة سطر', 'Add Row') ?></button>
    </div>
    <table class="wp-list-table widefat" id="wd-items-table" style="direction:<?= $_dir ?>;">
        <thead><tr><th><?= rsyi_lib_t('الكتاب', 'Book') ?></th><th style="width:80px;"><?= rsyi_lib_t('الكمية', 'Qty') ?></th><th style="width:80px;"><?= rsyi_lib_t('متاح', 'Available') ?></th><th><?= rsyi_lib_t('الطالب', 'Student') ?></th><th style="width:40px;"></th></tr></thead>
        <tbody id="wd-items-body"></tbody>
    </table>
    <p style="margin-top:16px;">
        <button class="button button-primary" id="btn-save-wd-draft">💾 <?= rsyi_lib_t('حفظ مسودة', 'Save Draft') ?></button>
        <button class="button button-primary" id="btn-submit-wd" style="background:#0073aa;border-color:#0073aa;">📤 <?= rsyi_lib_t('تقديم', 'Submit') ?></button>
        <button class="button rsyi-modal-close" data-modal="wd-modal" style="margin-right:8px;"><?= rsyi_lib_t('إلغاء', 'Cancel') ?></button>
    </p>
    <div id="wd-msg" style="display:none;margin-top:8px;"></div>
</div></div>

<!-- Return Order Modal -->
<div id="ret-modal" class="rsyi-modal-overlay" dir="<?= $_dir ?>" style="display:none;">
<div class="rsyi-modal-box" style="max-width:750px;max-height:90vh;overflow-y:auto;">
    <h2 id="ret-title" style="margin-top:0;"><?= rsyi_lib_t('إذن رد', 'Return Order') ?></h2>
    <input type="hidden" id="ret-id" value="0">
    <table class="form-table">
        <tr><th><?= rsyi_lib_t('المجموعة', 'Cohort') ?></th><td>
            <select id="ret-cohort"><option value="0"><?= rsyi_lib_t('-- اختر --', '-- Select --') ?></option>
                <?php foreach($cohorts as $c): ?><option value="<?php echo $c->id ?>"><?php echo esc_html($c->name) ?></option><?php endforeach; ?>
            </select>
        </td>
        <th><?= rsyi_lib_t('المدرب', 'Trainer') ?></th><td>
            <select id="ret-trainer"><option value="0"><?= rsyi_lib_t('-- اختر --', '-- Select --') ?></option>
                <?php foreach($trainers as $t): ?><option value="<?php echo $t->ID ?>"><?php echo esc_html($t->display_name) ?></option><?php endforeach; ?>
            </select>
        </td></tr>
        <tr><th><?= rsyi_lib_t('ملاحظات', 'Notes') ?></th><td colspan="3"><textarea id="ret-notes" rows="2" class="large-text"></textarea></td></tr>
    </table>
    <div style="margin:12px 0;">
        <strong><?= rsyi_lib_t('الكتب المُعادة', 'Returned Items') ?>:</strong>
        <button type="button" class="button button-small" id="btn-ret-add-row">+ <?= rsyi_lib_t('إضافة سطر', 'Add Row') ?></button>
    </div>
    <table class="wp-list-table widefat" id="ret-items-table" style="direction:<?= $_dir ?>;">
        <thead><tr><th><?= rsyi_lib_t('الكتاب', 'Book') ?></th><th style="width:80px;"><?= rsyi_lib_t('الكمية', 'Qty') ?></th><th style="width:110px;"><?= rsyi_lib_t('الحالة', 'Condition') ?></th><th style="width:40px;"></th></tr></thead>
        <tbody id="ret-items-body"></tbody>
    </table>
    <p style="margin-top:16px;">
        <button class="button button-primary" id="btn-save-ret">💾 <?= rsyi_lib_t('حفظ', 'Save') ?></button>
        <button class="button rsyi-modal-close" data-modal="ret-modal" style="margin-right:8px;"><?= rsyi_lib_t('إلغاء', 'Cancel') ?></button>
    </p>
    <div id="ret-msg" style="display:none;margin-top:8px;"></div>
</div></div>

<!-- Balance Edit Modal -->
<div id="balance-modal" class="rsyi-modal-overlay" dir="<?= $_dir ?>" style="display:none;">
<div class="rsyi-modal-box" style="max-width:420px;">
    <h2 style="margin-top:0;">🔢 <?= rsyi_lib_t('تعديل الرصيد', 'Edit Stock Balance') ?></h2>
    <input type="hidden" id="bal-book-id" value="0">
    <table class="form-table">
        <tr><th><?= rsyi_lib_t('العنصر', 'Item') ?></th><td><strong id="bal-book-name"></strong></td></tr>
        <tr><th><?= rsyi_lib_t('الرصيد الحالي', 'Current Stock') ?></th><td><strong id="bal-current" style="color:#0073aa;font-size:16px;"></strong></td></tr>
        <tr><th><?= rsyi_lib_t('الرصيد الجديد', 'New Stock') ?> *</th><td><input type="number" id="bal-new-stock" class="small-text" min="0" value="0"></td></tr>
        <tr><th><?= rsyi_lib_t('ملاحظات', 'Notes') ?></th><td><textarea id="bal-notes" rows="2" class="large-text" placeholder="<?= rsyi_lib_t('سبب التعديل…', 'Reason for adjustment…') ?>"></textarea></td></tr>
    </table>
    <p style="margin-top:16px;">
        <button class="button button-primary" id="btn-save-balance">💾 <?= rsyi_lib_t('حفظ', 'Save') ?></button>
        <button class="button rsyi-modal-close" data-modal="balance-modal" style="margin-<?= $_lib_en?'left':'right' ?>:8px;"><?= rsyi_lib_t('إلغاء', 'Cancel') ?></button>
    </p>
    <div id="bal-msg" style="display:none;margin-top:8px;"></div>
</div></div>

<!-- WD View Modal (read-only preview before approval) -->
<div id="wd-view-modal" class="rsyi-modal-overlay" dir="<?= $_dir ?>" style="display:none;">
<div class="rsyi-modal-box" style="max-width:800px;max-height:90vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <h2 style="margin:0;">👁 <?= rsyi_lib_t('معاينة إذن الصرف', 'View Withdrawal Order') ?></h2>
        <button class="button rsyi-modal-close" data-modal="wd-view-modal">✕</button>
    </div>
    <div id="wd-view-content" style="font-size:14px;"></div>
</div></div>

<!-- PR Modal -->
<div id="pr-modal" class="rsyi-modal-overlay" dir="<?= $_dir ?>" style="display:none;">
<div class="rsyi-modal-box" style="max-width:750px;max-height:90vh;overflow-y:auto;">
    <h2 id="pr-title" style="margin-top:0;"><?= rsyi_lib_t('طلب شراء', 'Purchase Request') ?></h2>
    <input type="hidden" id="pr-id" value="0">
    <table class="form-table">
        <tr><th><?= rsyi_lib_t('ملاحظات', 'Notes') ?></th><td><textarea id="pr-notes" rows="2" class="large-text"></textarea></td></tr>
    </table>
    <div style="margin:12px 0;">
        <strong><?= rsyi_lib_t('الكتب', 'Items') ?>:</strong>
        <button type="button" class="button button-small" id="btn-pr-add-row">+ <?= rsyi_lib_t('إضافة سطر', 'Add Row') ?></button>
    </div>
    <table class="wp-list-table widefat" id="pr-items-table" style="direction:<?= $_dir ?>;">
        <thead><tr><th><?= rsyi_lib_t('الكتاب', 'Book') ?></th><th style="width:80px;"><?= rsyi_lib_t('الكمية', 'Qty') ?></th><th style="width:130px;"><?= rsyi_lib_t('السعر التقديري', 'Est. Price') ?></th><th style="width:120px;"><?= rsyi_lib_t('ملاحظة', 'Note') ?></th><th style="width:40px;"></th></tr></thead>
        <tbody id="pr-items-body"></tbody>
    </table>
    <p style="margin-top:16px;">
        <button class="button button-primary" id="btn-save-pr">💾 <?= rsyi_lib_t('حفظ', 'Save') ?></button>
        <button class="button rsyi-modal-close" data-modal="pr-modal" style="margin-right:8px;"><?= rsyi_lib_t('إلغاء', 'Cancel') ?></button>
    </p>
    <div id="pr-msg" style="display:none;margin-top:8px;"></div>
</div></div>

<!-- PR Print Preview Modal -->
<div id="pr-print-modal" class="rsyi-modal-overlay" dir="<?= $_dir ?>" style="display:none;">
<div class="rsyi-modal-box" style="max-width:900px;max-height:90vh;overflow-y:auto;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <h2 style="margin:0;">🖨 <?= rsyi_lib_t('معاينة طلب الشراء', 'Purchase Request Print Preview') ?></h2>
        <button class="button rsyi-modal-close" data-modal="pr-print-modal">✕</button>
    </div>
    <p style="color:#888;font-size:13px;margin-top:0;"><?= rsyi_lib_t('يمكنك تعديل السعر التقديري لكل صنف قبل الطباعة', 'You can edit the estimated price for each item before printing') ?></p>
    <div id="pr-print-header" style="margin-bottom:12px;font-size:13px;"></div>
    <table class="wp-list-table widefat" id="pr-print-table" style="direction:<?= $_dir ?>;">
        <thead><tr>
            <th><?= rsyi_lib_t('الصنف', 'Item') ?></th>
            <th style="width:110px;">ISBN</th>
            <th style="width:90px;"><?= rsyi_lib_t('الموجود', 'In Stock') ?></th>
            <th style="width:90px;"><?= rsyi_lib_t('المطلوب', 'Required') ?></th>
            <th style="width:120px;"><?= rsyi_lib_t('السعر التقديري', 'Est. Price') ?></th>
            <th style="width:110px;"><?= rsyi_lib_t('الإجمالي', 'Total') ?></th>
        </tr></thead>
        <tbody id="pr-print-body"></tbody>
        <tfoot><tr>
            <td colspan="5" style="text-align:end;font-weight:700;padding:8px 10px;"><?= rsyi_lib_t('الإجمالي الكلي', 'Grand Total') ?></td>
            <td style="font-weight:700;padding:8px 10px;" id="pr-print-grand-total">0.00</td>
        </tr></tfoot>
    </table>
    <p style="margin-top:16px;">
        <button class="button button-primary" id="btn-do-pr-print">🖨 <?= rsyi_lib_t('طباعة', 'Print') ?></button>
        <button class="button rsyi-modal-close" data-modal="pr-print-modal" style="margin-<?= $_lib_en?'left':'right' ?>:8px;"><?= rsyi_lib_t('إلغاء', 'Cancel') ?></button>
    </p>
</div></div>

<style>
.rsyi-lib-wrap{padding-bottom:40px;}
.rsyi-toolbar{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin:14px 0;}
.rsyi-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:99999;display:flex;align-items:center;justify-content:center;}
.rsyi-modal-box{background:#fff;border-radius:8px;padding:28px;width:96%;}
.rsyi-stat-card{background:#fff;border-inline-start:5px solid #0073aa;border-radius:6px;padding:16px 20px;min-width:140px;box-shadow:0 1px 4px rgba(0,0,0,.1);}
.rsyi-stat-card.warn{border-color:#f0b849;}.rsyi-stat-card.danger{border-color:#dc3232;}.rsyi-stat-card.success{border-color:#46b450;}
.rsyi-stat-card .num{font-size:2em;font-weight:700;line-height:1.1;}
.rsyi-stat-card .lbl{font-size:.82em;color:#666;margin-top:4px;}
.status-draft{background:#999;color:#fff;padding:2px 8px;border-radius:10px;font-size:.8em;}
.status-pending{background:#f0b849;color:#333;padding:2px 8px;border-radius:10px;font-size:.8em;}
.status-approved{background:#0073aa;color:#fff;padding:2px 8px;border-radius:10px;font-size:.8em;}
.status-completed{background:#46b450;color:#fff;padding:2px 8px;border-radius:10px;font-size:.8em;}
.status-rejected{background:#dc3232;color:#fff;padding:2px 8px;border-radius:10px;font-size:.8em;}
.status-pending-r{background:#e67e22;color:#fff;padding:2px 8px;border-radius:10px;font-size:.8em;}
.report-btn{cursor:pointer;}.report-btn.btn-active{background:#0073aa;color:#fff;border-color:#0073aa;}
.rsyi-report-table{width:100%;border-collapse:collapse;margin-top:8px;}
.rsyi-report-table th,.rsyi-report-table td{padding:6px 10px;border:1px solid #ddd;text-align:start;}
.rsyi-report-table thead{background:#f1f1f1;}
.rsyi-report-table tr.stock-ok td{background:#f0fff0;}
.rsyi-report-table tr.stock-low td{background:#fff8e1;}
.rsyi-report-table tr.stock-zero td{background:#ffe8e8;}
.rsyi-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px 24px;margin-top:14px;}
.rsyi-fg-full{grid-column:1/-1;}
.rsyi-fg-label{display:block;font-weight:600;margin-bottom:5px;font-size:13px;color:#1d2327;}
.rsyi-form-grid>div{display:flex;flex-direction:column;}
.rsyi-form-grid input[type=text],.rsyi-form-grid input[type=number],.rsyi-form-grid select,.rsyi-form-grid textarea{border:1px solid #8c8f94;border-radius:4px;padding:6px 8px;box-sizing:border-box;}
.rsyi-form-grid textarea{resize:vertical;}
</style>

<script>
(function($){
    var nonce='<?= wp_create_nonce('rsyi_sa_admin') ?>', ajaxUrl='<?= esc_url(admin_url('admin-ajax.php')) ?>';
    var booksCache=[], suppliersCache=[];
    var studentsData=<?php echo json_encode( array_map( function( $s ) { return [ 'id' => (int)$s->id, 'name' => $s->student_name_ar, 'num' => $s->student_id_number ]; }, $students ) ); ?>;
    var canManage=<?php echo $lib_can_manage?'true':'false'; ?>;
    var canApproveWd=<?php echo $lib_can_approve_wd?'true':'false'; ?>;
    var canApprovePr=<?php echo $lib_can_approve_pr?'true':'false'; ?>;
    var L=<?php echo json_encode([
        'no_books'           => rsyi_lib_t('لا توجد عناصر','No items found'),
        'no_suppliers'       => rsyi_lib_t('لا يوجد موردون','No suppliers'),
        'no_orders'          => rsyi_lib_t('لا توجد أذون','No orders'),
        'no_requests'        => rsyi_lib_t('لا توجد طلبات','No requests'),
        'no_balances'        => rsyi_lib_t('لا توجد أرصدة','No balances'),
        'no_data'            => rsyi_lib_t('لا توجد بيانات','No data'),
        'no_low_stock'       => rsyi_lib_t('لا توجد كتب ناقصة','No low stock books') . ' ✓',
        'select_book'        => rsyi_lib_t('-- اختر كتاباً --','-- Select Book --'),
        'no_supplier'        => rsyi_lib_t('-- بدون مورد --','-- No Supplier --'),
        'all_categories'     => rsyi_lib_t('كل التصنيفات','All Categories'),
        'all_groups'         => rsyi_lib_t('كل المجموعات','All Groups'),
        'all_suppliers'      => rsyi_lib_t('كل الموردين','All Suppliers'),
        'run'                => rsyi_lib_t('تشغيل','Run'),
        'curriculum'         => rsyi_lib_t('مناهج','Curriculum'),
        'certificates'       => rsyi_lib_t('شهادات','Certificates'),
        'general'            => rsyi_lib_t('عام','General'),
        'edit'               => rsyi_lib_t('تعديل','Edit'),
        'delete'             => rsyi_lib_t('حذف','Delete'),
        'submit_btn'         => rsyi_lib_t('تقديم','Submit'),
        'approve'            => rsyi_lib_t('اعتماد','Approve'),
        'reject'             => rsyi_lib_t('رفض','Reject'),
        'complete_wd'        => rsyi_lib_t('إكمال الصرف','Complete Withdrawal'),
        'complete_ret'       => rsyi_lib_t('إتمام الرد','Complete Return'),
        'convert_pr'         => rsyi_lib_t('تحويل لإذن استلام','Convert to Receiving Order'),
        'add_book_title'     => rsyi_lib_t('إضافة عنصر','Add Item'),
        'edit_book_title'    => rsyi_lib_t('تعديل عنصر','Edit Item'),
        'new_ao_title'       => rsyi_lib_t('إذن استلام جديد','New Receiving Order'),
        'edit_ao_title'      => rsyi_lib_t('تعديل إذن الاستلام','Edit Receiving Order'),
        'new_wd_title'       => rsyi_lib_t('إذن صرف جديد','New Withdrawal Order'),
        'edit_wd_title'      => rsyi_lib_t('تعديل إذن الصرف','Edit Withdrawal Order'),
        'new_ret_title'      => rsyi_lib_t('إذن رد جديد','New Return Order'),
        'edit_ret_title'     => rsyi_lib_t('تعديل إذن الرد','Edit Return Order'),
        'new_pr_title'       => rsyi_lib_t('طلب شراء جديد','New Purchase Request'),
        'edit_pr_title'      => rsyi_lib_t('تعديل طلب الشراء','Edit Purchase Request'),
        'add_supplier_lbl'   => rsyi_lib_t('إضافة مورد','Add Supplier'),
        'edit_supplier_lbl'  => rsyi_lib_t('تعديل مورد','Edit Supplier'),
        'stock_label'        => rsyi_lib_t('رصيد','Stock'),
        'normal_type'        => rsyi_lib_t('عادي','Normal'),
        'custody_type'       => rsyi_lib_t('عهدة','Custody'),
        'good_cond'          => rsyi_lib_t('جيدة','Good'),
        'damaged_cond'       => rsyi_lib_t('تالفة','Damaged'),
        'status_draft'       => rsyi_lib_t('مسودة','Draft'),
        'status_pending'     => rsyi_lib_t('في الانتظار','Pending'),
        'status_approved'    => rsyi_lib_t('معتمد','Approved'),
        'status_completed'   => rsyi_lib_t('مكتمل','Completed'),
        'status_rejected'    => rsyi_lib_t('مرفوض','Rejected'),
        'confirm_del_book'   => rsyi_lib_t('حذف العنصر؟','Delete this item?'),
        'confirm_del_sup'    => rsyi_lib_t('حذف المورد؟','Delete this supplier?'),
        'confirm_del_ao'     => rsyi_lib_t('حذف إذن الاستلام وعكس جميع الحركات؟','Delete receiving order and reverse all transactions?'),
        'confirm_submit_wd'  => rsyi_lib_t('تقديم الإذن للاعتماد؟','Submit order for approval?'),
        'confirm_approve_wd' => rsyi_lib_t('اعتماد إذن الصرف؟','Approve withdrawal order?'),
        'confirm_reject_wd'  => rsyi_lib_t('رفض إذن الصرف؟','Reject withdrawal order?'),
        'confirm_complete_wd'=> rsyi_lib_t('تأكيد تنفيذ الصرف الفعلي؟ سيُخصم من المخزون.','Confirm physical withdrawal? Stock will be deducted.'),
        'confirm_del_wd'     => rsyi_lib_t('حذف إذن الصرف؟','Delete withdrawal order?'),
        'confirm_complete_ret'=> rsyi_lib_t('إتمام عملية الرد وإضافة الكتب للمخزون؟','Complete return and add books to stock?'),
        'confirm_del_ret'    => rsyi_lib_t('حذف إذن الرد؟','Delete return order?'),
        'confirm_approve_pr' => rsyi_lib_t('اعتماد طلب الشراء؟','Approve purchase request?'),
        'confirm_reject_pr'  => rsyi_lib_t('رفض طلب الشراء؟','Reject purchase request?'),
        'confirm_convert_pr' => rsyi_lib_t('تحويل الطلب لإذن استلام؟','Convert request to receiving order?'),
        'confirm_del_pr'     => rsyi_lib_t('حذف طلب الشراء؟','Delete purchase request?'),
        'alert_add_book'     => rsyi_lib_t('أضف كتاباً على الأقل','Add at least one book'),
        'alert_add_item'     => rsyi_lib_t('أضف كتاباً','Add a book'),
        'alert_name_req'     => rsyi_lib_t('الاسم مطلوب','Name is required'),
        'alert_title_req'    => rsyi_lib_t('العنوان مطلوب','Title is required'),
        'alert_fetch_err'    => rsyi_lib_t('خطأ في جلب البيانات','Error fetching data'),
        'media_title'        => rsyi_lib_t('اختر صورة','Choose Image'),
        'media_btn'          => rsyi_lib_t('اختر','Choose'),
        'dash_total_books'   => rsyi_lib_t('إجمالي العناصر','Total Items'),
        'dash_total_stock'   => rsyi_lib_t('إجمالي الرصيد','Total Stock'),
        'dash_low_stock'     => rsyi_lib_t('على وشك النفاد','Low Stock'),
        'dash_zero_stock'    => rsyi_lib_t('رصيد صفري','Zero Stock'),
        'dash_pending_wd'    => rsyi_lib_t('إذن صرف منتظر','Pending Withdrawal'),
        'dash_pending_pr'    => rsyi_lib_t('طلب شراء منتظر','Pending PR'),
        'dash_today_adds'    => rsyi_lib_t('استلام اليوم',"Today's Receiving"),
        'dash_today_wd'      => rsyi_lib_t('صرف اليوم',"Today's Withdrawals"),
        'low_tbl_book'       => rsyi_lib_t('الكتاب','Book'),
        'low_tbl_stock'      => rsyi_lib_t('الرصيد','Stock'),
        'low_tbl_min'        => rsyi_lib_t('الحد الأدنى','Min Stock'),
        'rpt_title'          => rsyi_lib_t('العنوان','Title'),
        'rpt_subject'        => rsyi_lib_t('المادة','Subject'),
        'rpt_grade'          => rsyi_lib_t('المستوى','Grade'),
        'rpt_category'       => rsyi_lib_t('التصنيف','Category'),
        'rpt_stock'          => rsyi_lib_t('الرصيد','Stock'),
        'rpt_min'            => rsyi_lib_t('الحد الأدنى','Min Stock'),
        'rpt_date'           => rsyi_lib_t('التاريخ','Date'),
        'rpt_type'           => rsyi_lib_t('النوع','Type'),
        'rpt_qty'            => rsyi_lib_t('الكمية','Qty'),
        'rpt_balance'        => rsyi_lib_t('الرصيد التراكمي','Running Balance'),
        'rpt_by'             => rsyi_lib_t('بواسطة','By'),
        'rpt_shortage'       => rsyi_lib_t('النقص','Shortage'),
        'rpt_order_no'       => rsyi_lib_t('رقم الإذن','Order No.'),
        'rpt_cohort'         => rsyi_lib_t('المجموعة','Group'),
        'rpt_supplier'       => rsyi_lib_t('المورد','Supplier'),
        'rpt_value'          => rsyi_lib_t('القيمة','Value'),
        'print_add'          => rsyi_lib_t('إذن استلام','Receiving Order'),
        'print_wd'           => rsyi_lib_t('إذن صرف','Withdrawal Order'),
        'print_ret'          => rsyi_lib_t('إذن رد','Return Order'),
        'print_order_no'     => rsyi_lib_t('رقم الإذن:','Order No.:'),
        'print_date_lbl'     => rsyi_lib_t('التاريخ:','Date:'),
        'print_supplier_lbl' => rsyi_lib_t('المورد:','Supplier:'),
        'print_cohort_lbl'   => rsyi_lib_t('المجموعة:','Group:'),
        'print_notes_lbl'    => rsyi_lib_t('ملاحظات:','Notes:'),
        'print_book'         => rsyi_lib_t('الكتاب','Book'),
        'print_price'        => rsyi_lib_t('السعر','Price'),
        'print_total'        => rsyi_lib_t('الإجمالي','Total'),
        'print_wh_mgr'       => rsyi_lib_t('مدير المخازن','Warehouse Manager'),
        'print_approved_by'  => rsyi_lib_t('المعتمد','Approved By'),
        'no_student'         => rsyi_lib_t('-- بدون طالب --','-- No Student --'),
        'auto_pr_confirm'    => rsyi_lib_t('إنشاء طلب شراء تلقائي للعناصر الناقصة؟','Auto-generate purchase request for items below max stock?'),
    ]); ?>;

    // ── TABS ──────────────────────────────────────────────────────────────────
    var tabLoaded={};
    $('.rsyi-tabs .nav-tab').on('click',function(e){
        e.preventDefault();
        var tab=$(this).data('tab');
        $('.rsyi-tabs .nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.rsyi-tab-pane').hide();
        $('#tab-'+tab).show();
        if(!tabLoaded[tab]){ tabLoaded[tab]=true; loadTab(tab); }
    });
    function loadTab(tab){
        if(tab==='books') loadBooks();
        else if(tab==='suppliers') loadSuppliers();
        else if(tab==='add-orders') loadAddOrders();
        else if(tab==='wd-orders') loadWdOrders();
        else if(tab==='ret-orders') loadRetOrders();
        else if(tab==='pr') loadPrList();
        else if(tab==='opening') loadOpening();
        else if(tab==='reports') initReports();
    }

    // ── HELPERS ───────────────────────────────────────────────────────────────
    function post(action,data,cb){
        $.ajax({
            url: ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: $.extend({action:action,nonce:nonce},data),
            success: cb,
            error: function(xhr,status,err){
                console.error('[LibAJAX] '+action+' failed: '+status+' '+err);
                console.error('[LibAJAX] Response: '+xhr.responseText.substr(0,500));
                var errDiv=$('#lib-ajax-error');
                if(!errDiv.length){
                    errDiv=$('<div id="lib-ajax-error" style="background:#fff3cd;border:1px solid #ffc107;padding:10px;margin:10px 0;border-radius:4px;font-family:monospace;font-size:12px;"></div>');
                    $('#rsyi-library-page').prepend(errDiv);
                }
                errDiv.html('<strong>AJAX Error ['+action+']:</strong> '+status+' – '+err+'<br><small>'+$('<div>').text(xhr.responseText.substr(0,300)).html()+'</small>');
            }
        });
    }
    function msg(el,text,ok){ $(el).show().text(text).css('color',ok?'green':'red'); }
    function openModal(id){ $('#'+id).css('display','flex'); }
    function statusBadge(s){
        var map={draft:'<?= rsyi_lib_t('مسودة', 'Draft') ?>',pending:'<?= rsyi_lib_t('في الانتظار', 'Pending') ?>',approved:'<?= rsyi_lib_t('معتمد', 'Approved') ?>',completed:'<?= rsyi_lib_t('مكتمل', 'Completed') ?>',rejected:'<?= rsyi_lib_t('مرفوض', 'Rejected') ?>'};
        return '<span class="status-'+(s||'')+'">'+( map[s]||s )+'</span>';
    }
    function buildBookSelect(val){
        var html='<option value=""><?= rsyi_lib_t('-- اختر كتاباً --', '-- Select a Book --') ?></option>';
        booksCache.forEach(function(b){ html+='<option value="'+b.id+'" data-stock="'+b.current_stock+'">'+(b.title_ar||b.title_en)+' (<?= rsyi_lib_t('رصيد', 'Stock') ?>: '+b.current_stock+')</option>'; });
        var s=$('<select class="book-sel" style="width:100%;">').html(html);
        if(val) s.val(val);
        return s;
    }
    function calcAoTotal(){
        var total=0;
        $('#ao-items-body tr').each(function(){
            var qty=parseFloat($(this).find('.ao-qty').val())||0;
            var price=parseFloat($(this).find('.ao-price').val())||0;
            var tax=parseFloat($(this).find('.ao-tax').val())||0;
            var disc=parseFloat($(this).find('.ao-disc').val())||0;
            var line=qty*price;
            if(disc>0) line*=(1-disc/100);
            if(tax>0)  line*=(1+tax/100);
            total+=line;
        });
        $('#ao-total').text(total.toFixed(2));
    }

    // ── MODALS CLOSE ─────────────────────────────────────────────────────────
    $(document).on('click','.rsyi-modal-close',function(){ $('#'+$(this).data('modal')).hide(); });
    $(document).on('click','.rsyi-modal-overlay',function(e){ if($(e.target).is('.rsyi-modal-overlay'))$(this).hide(); });

    // ═══ DASHBOARD ═══════════════════════════════════════════════════════════
    function loadDashboard(){
        post('rsyi_lib_get_dashboard',{},function(r){
            if(!r.success) return;
            var d=r.data;
            var cards=[
                {num:d.total_books,lbl:L.dash_total_books,cls:''},
                {num:d.total_stock,lbl:L.dash_total_stock,cls:'success'},
                {num:d.low_stock,lbl:L.dash_low_stock,cls:'warn'},
                {num:d.zero_stock,lbl:L.dash_zero_stock,cls:'danger'},
                {num:d.pending_wd,lbl:L.dash_pending_wd,cls:'warn'},
                {num:d.pending_pr,lbl:L.dash_pending_pr,cls:'warn'},
                {num:d.today_adds,lbl:L.dash_today_adds,cls:''},
                {num:d.today_withdrawals,lbl:L.dash_today_wd,cls:''},
            ];
            var html='';
            cards.forEach(function(c){ html+='<div class="rsyi-stat-card '+c.cls+'"><div class="num">'+c.num+'</div><div class="lbl">'+c.lbl+'</div></div>'; });
            $('#lib-stat-cards').html(html);
            // Low stock table
            if(d.low_stock_books&&d.low_stock_books.length){
                var t='<table class="wp-list-table widefat striped" style="direction:<?= $_dir ?>;max-width:700px;"><thead><tr><th><?= rsyi_lib_t('الكتاب', 'Book') ?></th><th><?= rsyi_lib_t('الرصيد', 'Stock') ?></th><th><?= rsyi_lib_t('الحد الأدنى', 'Min Stock') ?></th></tr></thead><tbody>';
                d.low_stock_books.forEach(function(b){ t+='<tr><td>'+(b.title_ar||b.title_en)+'</td><td style="color:red;">'+b.current_stock+'</td><td>'+b.min_stock+'</td></tr>'; });
                t+='</tbody></table>';
                $('#lib-low-table-wrap').html(t);
            } else { $('#lib-low-table-wrap').html('<p style="color:green;"><?= rsyi_lib_t('لا توجد كتب ناقصة', 'No low stock books') ?> ✓</p>'); }
        });
    }

    // ═══ BOOKS ═══════════════════════════════════════════════════════════════
    function loadBooksCache(){
        post('rsyi_get_books',{},function(r){ if(r.success) booksCache=r.data; });
    }
    function loadBooks(){
        post('rsyi_get_books',{category:$('#filter-cat').val(),search:$('#filter-search').val()},function(r){
            if(!r.success){ return; }
            var html='';
            if(!r.data.length) html='<tr><td colspan="8" style="text-align:center"><?= rsyi_lib_t('لا توجد عناصر', 'No items found') ?></td></tr>';
            r.data.forEach(function(b){
                var cover=b.cover_url?'<img src="'+b.cover_url+'" style="width:44px;height:56px;object-fit:cover;border-radius:3px;">':'—';
                var sc=(b.current_stock===0)?'style="color:red;"':(b.current_stock<=b.min_stock&&b.min_stock>0?'style="color:orange;"':'');
                html+='<tr><td>'+cover+'</td><td><strong>'+(b.title_ar||'')+'</strong><br><small>'+(b.title_en||'')+'</small></td>'+
                    '<td>'+(b.subject||'—')+'</td><td>'+(b.grade_level||'—')+'</td><td>'+(b.isbn||'—')+'</td>'+
                    '<td '+sc+'>'+b.current_stock+'</td><td>'+(b.min_stock||0)+'</td><td>'+
                    (canManage?'<button class="button button-small btn-edit-book" data-b=\''+JSON.stringify(b)+'\'><?= rsyi_lib_t('تعديل', 'Edit') ?></button> '+
                    '<button class="button button-small btn-edit-balance" data-id="'+b.id+'" data-stock="'+b.current_stock+'" data-name="'+(b.title_ar||b.title_en).replace(/"/g,"&quot;")+'">🔢</button> '+
                    '<button class="button button-small btn-del-book" data-id="'+b.id+'"><?= rsyi_lib_t('حذف', 'Delete') ?></button>':'—')+'</td></tr>';
            });
            $('#books-list').html(html);
            booksCache=r.data;
        });
    }
    $('#btn-search-books,#filter-cat').on('click change',loadBooks);

    $('#btn-add-book').on('click',function(){
        $('#bm-title').text('<?= rsyi_lib_t('إضافة عنصر', 'Add Item') ?>');$('#bm-id').val(0);
        $('#bm-title-en,#bm-author,#bm-publisher,#bm-subject,#bm-grade,#bm-isbn,#bm-desc').val('');
        $('#bm-min-stock,#bm-max-stock,#bm-price').val(0);$('#bm-audience').val('trainers');$('#bm-cat').val('curriculum');$('#bm-lang').val('en');$('#bm-unit').val('copy');
        $('#bm-cover-id').val(0);$('#bm-cover-preview').html('');$('#btn-bm-cover-clear').hide();$('#bm-msg').hide();
        openModal('book-modal');
    });
    $(document).on('click','.btn-edit-book',function(){
        var b=$(this).data('b');
        $('#bm-title').text('<?= rsyi_lib_t('تعديل عنصر', 'Edit Item') ?>');$('#bm-id').val(b.id);
        $('#bm-title-en').val(b.title_en||b.title_ar);$('#bm-author').val(b.author||'');
        $('#bm-publisher').val(b.publisher||'');$('#bm-subject').val(b.subject||'');$('#bm-grade').val(b.grade_level||'');
        $('#bm-isbn').val(b.isbn||'');$('#bm-desc').val(b.description||'');$('#bm-min-stock').val(b.min_stock||0);$('#bm-max-stock').val(b.max_stock||0);
        $('#bm-price').val(b.price||0);$('#bm-audience').val(b.target_audience||'trainers');$('#bm-cat').val(b.category||'general');$('#bm-lang').val(b.language||'en');
        $('#bm-unit').val(b.unit||'copy');$('#bm-cover-id').val(b.cover_image_id||0);
        if(b.cover_url){$('#bm-cover-preview').html('<img src="'+b.cover_url+'" style="max-width:80px;">');$('#btn-bm-cover-clear').show();}
        else{$('#bm-cover-preview').html('');$('#btn-bm-cover-clear').hide();}
        $('#bm-msg').hide(); openModal('book-modal');
    });
    $(document).on('click','.btn-del-book',function(){
        if(!confirm('<?= rsyi_lib_t('حذف العنصر؟', 'Delete this item?') ?>')) return;
        post('rsyi_delete_book',{book_id:$(this).data('id')},function(r){ alert(r.data.message); if(r.success) loadBooks(); });
    });
    $('#btn-save-book').on('click',function(){
        var titleEn=$('#bm-title-en').val().trim();
        var data={book_id:$('#bm-id').val(),title_ar:titleEn,title_en:titleEn,
            author:$('#bm-author').val(),publisher:$('#bm-publisher').val(),subject:$('#bm-subject').val(),
            grade_level:$('#bm-grade').val(),isbn:$('#bm-isbn').val(),description:$('#bm-desc').val(),
            target_audience:$('#bm-audience').val(),max_stock:$('#bm-max-stock').val(),
            min_stock:$('#bm-min-stock').val(),price:$('#bm-price').val(),
            category:$('#bm-cat').val(),book_language:$('#bm-lang').val(),unit:$('#bm-unit').val(),cover_image_id:$('#bm-cover-id').val()};
        if(!titleEn){alert('<?= rsyi_lib_t('العنوان مطلوب', 'Title is required') ?>');return;}
        post('rsyi_save_book',data,function(r){ msg('#bm-msg',r.data.message,r.success); if(r.success){setTimeout(function(){$('#book-modal').hide();loadBooks();loadBooksCache();},700);} });
    });
    var bmUploader;
    $('#btn-bm-cover').on('click',function(){
        if(!bmUploader){ bmUploader=wp.media({title:'<?= rsyi_lib_t('اختر صورة', 'Choose Image') ?>',button:{text:'<?= rsyi_lib_t('اختر', 'Select') ?>'},multiple:false}); bmUploader.on('select',function(){ var a=bmUploader.state().get('selection').first().toJSON(); $('#bm-cover-id').val(a.id);$('#bm-cover-preview').html('<img src="'+a.url+'" style="max-width:80px;">');$('#btn-bm-cover-clear').show(); }); }
        bmUploader.open();
    });
    $('#btn-bm-cover-clear').on('click',function(){ $('#bm-cover-id').val(0);$('#bm-cover-preview').html('');$(this).hide(); });

    // ═══ SUPPLIERS ════════════════════════════════════════════════════════════
    function loadSuppliers(){
        post('rsyi_lib_get_suppliers',{},function(r){
            if(!r.success) return;
            suppliersCache=r.data;
            var html='';
            if(!r.data.length) html='<tr><td colspan="5" style="text-align:center"><?= rsyi_lib_t('لا يوجد موردون', 'No suppliers found') ?></td></tr>';
            r.data.forEach(function(s){
                html+='<tr><td>'+s.name+'</td><td>'+(s.phone||'—')+'</td><td>'+(s.email||'—')+'</td><td>'+(s.address||'—')+'</td><td>'+
                    (canManage?'<button class="button button-small btn-edit-sup" data-s=\''+JSON.stringify(s)+'\'><?= rsyi_lib_t('تعديل', 'Edit') ?></button> '+
                    '<button class="button button-small btn-del-sup" data-id="'+s.id+'"><?= rsyi_lib_t('حذف', 'Delete') ?></button>':'—')+'</td></tr>';
            });
            $('#suppliers-list').html(html);
            // Also update add-order supplier dropdown
            var sopts='<option value="0"><?= rsyi_lib_t('-- بدون مورد --', '-- No Supplier --') ?></option>';
            r.data.forEach(function(s){ sopts+='<option value="'+s.id+'">'+s.name+'</option>'; });
            $('#ao-supplier').html(sopts);
        });
    }
    $('#btn-save-supplier').on('click',function(){
        var data={supplier_id:$('#sup-id').val(),name:$('#sup-name').val().trim(),phone:$('#sup-phone').val(),email:$('#sup-email').val(),address:$('#sup-addr').val()};
        if(!data.name){alert('<?= rsyi_lib_t('الاسم مطلوب', 'Name is required') ?>');return;}
        post('rsyi_lib_save_supplier',data,function(r){ msg('#sup-msg',r.data.message,r.success); if(r.success){loadSuppliers();$('#sup-id').val(0);$('#sup-name,#sup-phone,#sup-email,#sup-addr').val('');$('#sup-form-title').text('<?= rsyi_lib_t('إضافة مورد', 'Add Supplier') ?>');$('#btn-cancel-sup').hide();} });
    });
    $(document).on('click','.btn-edit-sup',function(){
        var s=$(this).data('s'); $('#sup-id').val(s.id);$('#sup-name').val(s.name);$('#sup-phone').val(s.phone||'');
        $('#sup-email').val(s.email||'');$('#sup-addr').val(s.address||'');$('#sup-form-title').text('<?= rsyi_lib_t('تعديل مورد', 'Edit Supplier') ?>');$('#btn-cancel-sup').show();
        $('html,body').animate({scrollTop:$('#sup-form-title').offset().top-100},300);
    });
    $(document).on('click','.btn-del-sup',function(){
        if(!confirm('<?= rsyi_lib_t('حذف المورد؟', 'Delete this supplier?') ?>')) return;
        post('rsyi_lib_delete_supplier',{supplier_id:$(this).data('id')},function(r){ alert(r.data.message); if(r.success) loadSuppliers(); });
    });
    $('#btn-cancel-sup').on('click',function(){ $('#sup-id').val(0);$('#sup-name,#sup-phone,#sup-email,#sup-addr').val('');$('#sup-form-title').text('<?= rsyi_lib_t('إضافة مورد', 'Add Supplier') ?>');$(this).hide(); });

    // ═══ ADD ORDERS ═══════════════════════════════════════════════════════════
    function loadAddOrders(){
        post('rsyi_lib_get_add_orders',{},function(r){
            if(!r.success) return;
            var html='';
            if(!r.data.length) html='<tr><td colspan="6" style="text-align:center"><?= rsyi_lib_t('لا توجد أذون', 'No orders found') ?></td></tr>';
            r.data.forEach(function(o){
                html+='<tr><td>'+o.order_number+'</td><td>'+(o.supplier_name||'—')+'</td><td>'+(o.created_at||'').substr(0,10)+'</td>'+
                    '<td>'+o.total_quantity+'</td><td>'+parseFloat(o.total_value||0).toFixed(2)+'</td><td>'+
                    (canManage?'<button class="button button-small btn-edit-ao" data-id="'+o.id+'">'+L.edit+'</button> '+
                    '<button class="button button-small btn-del-ao" data-id="'+o.id+'">'+L.delete+'</button> ':'')+
                    '<button class="button button-small btn-print-ao" data-id="'+o.id+'">🖨</button></td></tr>';
            });
            $('#add-orders-list').html(html);
        });
    }
    function openAddOrderModal(id){
        $('#ao-title').text(id?L.edit_ao_title:L.new_ao_title);$('#ao-id').val(id||0);
        $('#ao-items-body').html('');$('#ao-notes').val('');$('#ao-total').text('0.00');$('#ao-msg').hide();
        $('#ao-quote-num').val('');
        if(id){
            post('rsyi_lib_get_add_order',{order_id:id},function(r){
                if(!r.success) return;
                var o=r.data; $('#ao-supplier').val(o.supplier_id||0); $('#ao-notes').val(o.notes||'');
                $('#ao-quote-num').val(o.quote_number||'');
                (o.items||[]).forEach(function(i){ addAoRow(i.book_id,i.quantity,i.unit_price,i.tax_rate,i.discount_rate); });
                calcAoTotal();
            });
        } else { addAoRow(); }
        openModal('add-order-modal');
    }
    function addAoRow(bid,qty,price,tax,disc){
        var sel=buildBookSelect(bid);
        var row=$('<tr>').append(
            $('<td>').append(sel),
            $('<td>').append($('<input type="number" class="ao-qty" value="'+(qty||1)+'" min="1" style="width:65px;">')),
            $('<td>').append($('<input type="number" class="ao-price" value="'+(price||0)+'" min="0" step="0.01" style="width:85px;">')),
            $('<td>').append($('<input type="number" class="ao-tax" value="'+(tax||0)+'" min="0" max="100" step="0.01" style="width:60px;">')),
            $('<td>').append($('<input type="number" class="ao-disc" value="'+(disc||0)+'" min="0" max="100" step="0.01" style="width:60px;">')),
            $('<td>').append($('<button type="button" class="button button-small ao-del-row">×</button>'))
        );
        row.find('.ao-qty,.ao-price,.ao-tax,.ao-disc').on('input',calcAoTotal);
        row.find('.ao-del-row').on('click',function(){ $(this).closest('tr').remove(); calcAoTotal(); });
        $('#ao-items-body').append(row);
    }
    $('#btn-new-add-order').on('click',function(){ openAddOrderModal(null); });
    $(document).on('click','.btn-edit-ao',function(){ openAddOrderModal($(this).data('id')); });
    $(document).on('click','.btn-del-ao',function(){
        if(!confirm(L.confirm_del_ao)) return;
        post('rsyi_lib_delete_add_order',{order_id:$(this).data('id')},function(r){ alert(r.data.message); if(r.success) loadAddOrders(); });
    });
    $('#btn-ao-add-row').on('click',function(){ addAoRow(); });
    // Tax/Discount are now per-item — no order-level listeners needed
    $('#btn-save-add-order').on('click',function(){
        var items=[];
        $('#ao-items-body tr').each(function(){
            var bid=$(this).find('.book-sel').val();
            if(bid) items.push({book_id:bid,quantity:$(this).find('.ao-qty').val(),unit_price:$(this).find('.ao-price').val(),tax_rate:$(this).find('.ao-tax').val(),discount_rate:$(this).find('.ao-disc').val()});
        });
        if(!items.length){alert(L.alert_add_book);return;}
        post('rsyi_lib_save_add_order',{order_id:$('#ao-id').val(),supplier_id:$('#ao-supplier').val(),
            quote_number:$('#ao-quote-num').val(),
            notes:$('#ao-notes').val(),items:JSON.stringify(items)},function(r){
            msg('#ao-msg',r.data.message,r.success); if(r.success){setTimeout(function(){$('#add-order-modal').hide();loadAddOrders();loadBooksCache();loadDashboard();},700);}
        });
    });
    $(document).on('click','.btn-print-ao',function(){ printOrder('add',$(this).data('id')); });

    // ═══ WITHDRAWAL ORDERS ════════════════════════════════════════════════════
    function loadWdOrders(){
        post('rsyi_lib_get_wd_orders',{status:$('#wd-filter-status').val()},function(r){
            if(!r.success) return;
            var html='';
            if(!r.data.length) html='<tr><td colspan="7" style="text-align:center">'+L.no_orders+'</td></tr>';
            r.data.forEach(function(o){
                var who=(o.cohort_name||'')+(o.cohort_name&&o.trainer_id?' / ':'')+(o.trainer_id?L.trainer_label+'#'+o.trainer_id:'');
                var acts='';
                if(canManage&&(o.status==='draft')){
                    acts+='<button class="button button-small btn-edit-wd" data-id="'+o.id+'">'+L.edit+'</button> '+
                          '<button class="button button-small btn-submit-wd" data-id="'+o.id+'">'+L.submit_btn+'</button> '+
                          '<button class="button button-small btn-del-wd" data-id="'+o.id+'">'+L.delete+'</button> ';
                }
                if(o.status!=='draft'){
                    acts+='<button class="button button-small btn-view-wd" data-id="'+o.id+'">👁</button> ';
                }
                if(canApproveWd&&o.status==='pending'){
                    acts+='<button class="button button-small btn-approve-wd" data-id="'+o.id+'" style="color:green;">'+L.approve+'</button> '+
                          '<button class="button button-small btn-reject-wd" data-id="'+o.id+'" style="color:red;">'+L.reject+'</button> ';
                }
                if(canManage&&o.status==='approved'){
                    acts+='<button class="button button-small btn-complete-wd" data-id="'+o.id+'" style="color:blue;">'+L.complete_wd+'</button> ';
                }
                if(['approved','completed'].includes(o.status)){
                    acts+='<button class="button button-small btn-print-wd" data-id="'+o.id+'">🖨</button>';
                }
                html+='<tr><td>'+o.order_number+'</td><td>'+who+'</td><td>'+(o.order_type==='custody'?L.custody_type:L.normal_type)+'</td>'+
                    '<td>'+statusBadge(o.status)+'</td><td>'+(o.created_by_name||'')+'</td>'+
                    '<td>'+(o.created_at||'').substr(0,10)+'</td><td>'+acts+'</td></tr>';
            });
            $('#wd-orders-list').html(html);
        });
    }
    $('#btn-filter-wd').on('click',loadWdOrders);$('#wd-filter-status').on('change',loadWdOrders);

    function openWdModal(id){
        $('#wd-title').text(id?L.edit_wd_title:L.new_wd_title);$('#wd-id').val(id||0);
        $('#wd-items-body').html('');$('#wd-notes').val('');$('#wd-msg').hide();
        $('#wd-cohort,#wd-trainer').val(0);$('#wd-type').val('normal');
        if(id){
            post('rsyi_lib_get_wd_order',{order_id:id},function(r){
                if(!r.success) return;
                var o=r.data; $('#wd-cohort').val(o.cohort_id||0);$('#wd-trainer').val(o.trainer_id||0);
                $('#wd-type').val(o.order_type||'normal');$('#wd-notes').val(o.notes||'');
                (o.items||[]).forEach(function(i){ addWdRow(i.book_id,i.quantity,i.real_stock,i.student_id); });
            });
        } else { addWdRow(); }
        openModal('wd-modal');
    }
    function buildStudentSelect(sid){
        var opts='<option value="0">'+L.no_student+'</option>';
        studentsData.forEach(function(s){ opts+='<option value="'+s.id+'">'+s.name+(s.num?' ('+s.num+')':'')+'</option>'; });
        var sel=$('<select class="wd-student" style="width:100%;">').html(opts);
        if(sid) sel.val(sid);
        return sel;
    }
    function addWdRow(bid,qty,stock,sid){
        var sel=buildBookSelect(bid);
        var stockTxt=stock!==undefined?stock:'?';
        var stuSel=buildStudentSelect(sid||0);
        var row=$('<tr>').append(
            $('<td>').append(sel),
            $('<td>').append($('<input type="number" class="wd-qty" value="'+(qty||1)+'" min="1" style="width:70px;">')),
            $('<td class="wd-stock-cell">').text(stockTxt),
            $('<td>').append(stuSel),
            $('<td>').append($('<button type="button" class="button button-small wd-del-row">×</button>'))
        );
        sel.on('change',function(){ row.find('.wd-stock-cell').text($(this).find(':selected').data('stock')||0); });
        row.find('.wd-del-row').on('click',function(){ $(this).closest('tr').remove(); });
        $('#wd-items-body').append(row);
    }
    $('#btn-new-wd-order').on('click',function(){ openWdModal(null); });
    $(document).on('click','.btn-edit-wd',function(){ openWdModal($(this).data('id')); });
    function getWdItems(){ var items=[]; $('#wd-items-body tr').each(function(){ var bid=$(this).find('.book-sel').val(); if(bid) items.push({book_id:bid,quantity:$(this).find('.wd-qty').val(),student_id:$(this).find('.wd-student').val()||0}); }); return items; }
    $('#btn-wd-add-row').on('click',function(){ addWdRow(); });
    $('#btn-save-wd-draft').on('click',function(){
        var items=getWdItems(); if(!items.length){alert(L.alert_add_item);return;}
        post('rsyi_lib_save_wd_order',{order_id:$('#wd-id').val(),cohort_id:$('#wd-cohort').val(),trainer_id:$('#wd-trainer').val(),
            order_type:$('#wd-type').val(),notes:$('#wd-notes').val(),items:JSON.stringify(items)},function(r){
            msg('#wd-msg',r.data.message,r.success); if(r.success){setTimeout(function(){$('#wd-modal').hide();loadWdOrders();},700);}
        });
    });
    $('#btn-submit-wd').on('click',function(){
        var items=getWdItems(); if(!items.length){alert(L.alert_add_item);return;}
        var id=$('#wd-id').val();
        function doSubmit(oid){
            post('rsyi_lib_submit_wd_order',{order_id:oid},function(r){
                msg('#wd-msg',r.data.message,r.success); if(r.success){setTimeout(function(){$('#wd-modal').hide();loadWdOrders();},700);}
            });
        }
        if(id>0){ doSubmit(id); }
        else {
            post('rsyi_lib_save_wd_order',{order_id:0,cohort_id:$('#wd-cohort').val(),trainer_id:$('#wd-trainer').val(),
                order_type:$('#wd-type').val(),notes:$('#wd-notes').val(),items:JSON.stringify(items)},function(r){
                if(r.success) doSubmit(r.data.id);
                else msg('#wd-msg',r.data.message,false);
            });
        }
    });
    $(document).on('click','.btn-submit-wd',function(){
        if(!confirm(L.confirm_submit_wd)) return;
        post('rsyi_lib_submit_wd_order',{order_id:$(this).data('id')},function(r){ alert(r.data.message); if(r.success) loadWdOrders(); });
    });
    $(document).on('click','.btn-approve-wd',function(){
        if(!confirm(L.confirm_approve_wd)) return;
        post('rsyi_lib_approve_wd_order',{order_id:$(this).data('id')},function(r){ alert(r.data.message); if(r.success) loadWdOrders(); });
    });
    $(document).on('click','.btn-reject-wd',function(){
        if(!confirm(L.confirm_reject_wd)) return;
        post('rsyi_lib_reject_wd_order',{order_id:$(this).data('id')},function(r){ alert(r.data.message); if(r.success) loadWdOrders(); });
    });
    $(document).on('click','.btn-complete-wd',function(){
        if(!confirm(L.confirm_complete_wd)) return;
        post('rsyi_lib_complete_wd_order',{order_id:$(this).data('id')},function(r){ alert(r.data.message); if(r.success){loadWdOrders();loadBooksCache();loadDashboard();} });
    });
    $(document).on('click','.btn-del-wd',function(){
        if(!confirm(L.confirm_del_wd)) return;
        post('rsyi_lib_delete_wd_order',{order_id:$(this).data('id')},function(r){ alert(r.data.message); if(r.success) loadWdOrders(); });
    });
    $(document).on('click','.btn-print-wd',function(){ printOrder('withdrawal',$(this).data('id')); });

    // ── View WD Order (read-only preview) ─────────────────────────────────────
    $(document).on('click','.btn-view-wd',function(){
        var id=$(this).data('id');
        post('rsyi_lib_get_wd_order',{order_id:id},function(r){
            if(!r.success){ alert(L.alert_fetch_err); return; }
            var o=r.data, items=o.items||[];
            var html='<table class="wp-list-table widefat striped" style="margin-bottom:16px;border:none;"><tbody>'+
                '<tr><td style="border:none;width:140px;font-weight:600;">'+L.print_order_no+'</td><td style="border:none;">'+o.order_number+'</td>'+
                    '<td style="border:none;width:140px;font-weight:600;">'+L.print_date_lbl+'</td><td style="border:none;">'+(o.created_at||'').substr(0,10)+'</td></tr>'+
                '<tr><td style="border:none;font-weight:600;">'+L.print_cohort_lbl+'</td><td style="border:none;">'+(o.cohort_name||'—')+'</td>'+
                    '<td style="border:none;font-weight:600;"><?= rsyi_lib_t('النوع','Type') ?></td><td style="border:none;">'+(o.order_type==='custody'?L.custody_type:L.normal_type)+'</td></tr>'+
                '<tr><td style="border:none;font-weight:600;"><?= rsyi_lib_t('بواسطة','By') ?></td><td colspan="3" style="border:none;">'+(o.created_by_name||'—')+'</td></tr>'+
                (o.notes?'<tr><td style="border:none;font-weight:600;">'+L.print_notes_lbl+'</td><td colspan="3" style="border:none;">'+o.notes+'</td></tr>':'')+
                '</tbody></table>';
            html+='<table class="wp-list-table widefat striped"><thead><tr>'+
                '<th><?= rsyi_lib_t('العنصر','Item') ?></th><th>ISBN</th>'+
                '<th style="width:80px;"><?= rsyi_lib_t('الكمية','Qty') ?></th>'+
                '<th><?= rsyi_lib_t('الطالب','Student') ?></th></tr></thead><tbody>';
            if(!items.length) html+='<tr><td colspan="4" style="text-align:center;">'+L.no_books+'</td></tr>';
            items.forEach(function(i){
                html+='<tr><td>'+(i.title_ar||i.title_en||'—')+'</td><td>'+(i.isbn||'—')+'</td>'+
                    '<td>'+i.quantity+'</td><td>'+(i.student_name||i.student_id_number||'—')+'</td></tr>';
            });
            html+='</tbody></table>';
            $('#wd-view-content').html(html);
            openModal('wd-view-modal');
        });
    });

    // ── Edit Stock Balance ────────────────────────────────────────────────────
    $(document).on('click','.btn-edit-balance',function(){
        var btn=$(this);
        $('#bal-book-id').val(btn.data('id'));
        $('#bal-book-name').text(btn.data('name'));
        $('#bal-current').text(btn.data('stock'));
        $('#bal-new-stock').val(btn.data('stock'));
        $('#bal-notes').val('');
        $('#bal-msg').hide();
        openModal('balance-modal');
    });
    $('#btn-save-balance').on('click',function(){
        var bid=$('#bal-book-id').val();
        var ns=$('#bal-new-stock').val();
        if(ns===''||ns<0){ alert('<?= rsyi_lib_t('أدخل رصيداً صحيحاً','Enter a valid stock value') ?>'); return; }
        post('rsyi_lib_edit_balance',{book_id:bid,new_stock:ns,notes:$('#bal-notes').val()},function(r){
            msg('#bal-msg',r.data.message,r.success);
            if(r.success){ setTimeout(function(){ $('#balance-modal').hide(); loadBooks(); loadBooksCache(); loadDashboard(); },700); }
        });
    });

    // ═══ RETURN ORDERS ════════════════════════════════════════════════════════
    function loadRetOrders(){
        post('rsyi_lib_get_ret_orders',{},function(r){
            if(!r.success) return;
            var html='';
            if(!r.data.length) html='<tr><td colspan="5" style="text-align:center">'+L.no_orders+'</td></tr>';
            r.data.forEach(function(o){
                var who=(o.cohort_name||'')+(o.trainer_id?L.trainer_label+'#'+o.trainer_id:'');
                html+='<tr><td>'+o.order_number+'</td><td>'+who+'</td><td>'+statusBadge(o.status==='pending'?'pending-r':o.status)+'</td>'+
                    '<td>'+(o.created_at||'').substr(0,10)+'</td><td>'+
                    (canManage&&o.status==='pending'?'<button class="button button-small btn-edit-ret" data-id="'+o.id+'">'+L.edit+'</button> '+
                    '<button class="button button-small btn-complete-ret" data-id="'+o.id+'" style="color:green;">'+L.complete_ret+'</button> ':'')+
                    (canManage?'<button class="button button-small btn-del-ret" data-id="'+o.id+'">'+L.delete+'</button>':'')+'</td></tr>';
            });
            $('#ret-orders-list').html(html);
        });
    }
    function openRetModal(id){
        $('#ret-title').text(id?L.edit_ret_title:L.new_ret_title);$('#ret-id').val(id||0);
        $('#ret-items-body').html('');$('#ret-notes').val('');$('#ret-msg').hide();
        $('#ret-cohort,#ret-trainer').val(0);
        if(id){
            post('rsyi_lib_get_ret_order',{order_id:id},function(r){
                if(!r.success) return;
                var o=r.data; $('#ret-cohort').val(o.cohort_id||0);$('#ret-trainer').val(o.trainer_id||0);$('#ret-notes').val(o.notes||'');
                (o.items||[]).forEach(function(i){ addRetRow(i.book_id,i.quantity,i.condition_v); });
            });
        } else { addRetRow(); }
        openModal('ret-modal');
    }
    function addRetRow(bid,qty,cond){
        var sel=buildBookSelect(bid);
        var condSel=$('<select class="ret-cond" style="width:100%;"><option value="good">'+L.good_cond+'</option><option value="damaged">'+L.damaged_cond+'</option></select>');
        if(cond) condSel.val(cond);
        var row=$('<tr>').append(
            $('<td>').append(sel),
            $('<td>').append($('<input type="number" class="ret-qty" value="'+(qty||1)+'" min="1" style="width:70px;">')),
            $('<td>').append(condSel),
            $('<td>').append($('<button type="button" class="button button-small ret-del-row">×</button>'))
        );
        row.find('.ret-del-row').on('click',function(){ $(this).closest('tr').remove(); });
        $('#ret-items-body').append(row);
    }
    $('#btn-new-ret-order').on('click',function(){ openRetModal(null); });
    $(document).on('click','.btn-edit-ret',function(){ openRetModal($(this).data('id')); });
    $('#btn-ret-add-row').on('click',function(){ addRetRow(); });
    $('#btn-save-ret').on('click',function(){
        var items=[]; $('#ret-items-body tr').each(function(){ var bid=$(this).find('.book-sel').val(); if(bid) items.push({book_id:bid,quantity:$(this).find('.ret-qty').val(),condition_v:$(this).find('.ret-cond').val()}); });
        if(!items.length){alert(L.alert_add_item);return;}
        post('rsyi_lib_save_ret_order',{order_id:$('#ret-id').val(),cohort_id:$('#ret-cohort').val(),trainer_id:$('#ret-trainer').val(),
            notes:$('#ret-notes').val(),items:JSON.stringify(items)},function(r){
            msg('#ret-msg',r.data.message,r.success); if(r.success){setTimeout(function(){$('#ret-modal').hide();loadRetOrders();},700);}
        });
    });
    $(document).on('click','.btn-complete-ret',function(){
        if(!confirm(L.confirm_complete_ret)) return;
        post('rsyi_lib_complete_ret_order',{order_id:$(this).data('id')},function(r){ alert(r.data.message); if(r.success){loadRetOrders();loadBooksCache();loadDashboard();} });
    });
    $(document).on('click','.btn-del-ret',function(){
        if(!confirm(L.confirm_del_ret)) return;
        post('rsyi_lib_delete_ret_order',{order_id:$(this).data('id')},function(r){ alert(r.data.message); if(r.success) loadRetOrders(); });
    });

    // ═══ PURCHASE REQUESTS ════════════════════════════════════════════════════
    function loadPrList(){
        post('rsyi_lib_get_pr_list',{},function(r){
            if(!r.success) return;
            var html='';
            if(!r.data.length) html='<tr><td colspan="5" style="text-align:center">'+L.no_requests+'</td></tr>';
            r.data.forEach(function(p){
                var acts='';
                if(canManage&&p.status==='pending') acts+='<button class="button button-small btn-edit-pr" data-id="'+p.id+'">'+L.edit+'</button> ';
                if(canApprovePr&&p.status==='pending'){
                    acts+='<button class="button button-small btn-approve-pr" data-id="'+p.id+'" style="color:green;">'+L.approve+'</button> '+
                          '<button class="button button-small btn-reject-pr" data-id="'+p.id+'" style="color:red;">'+L.reject+'</button> ';
                }
                if(canManage&&p.status==='approved') acts+='<button class="button button-small btn-convert-pr" data-id="'+p.id+'" style="color:blue;">'+L.convert_pr+'</button> ';
                if(canManage&&['pending','rejected'].includes(p.status)) acts+='<button class="button button-small btn-del-pr" data-id="'+p.id+'">'+L.delete+'</button>';
                acts+=' <button class="button button-small btn-print-pr" data-id="'+p.id+'">🖨</button>';
                html+='<tr><td>'+p.request_number+'</td><td>'+statusBadge(p.status)+'</td><td>'+(p.requested_by_name||'')+'</td>'+
                    '<td>'+(p.created_at||'').substr(0,10)+'</td><td>'+acts+'</td></tr>';
            });
            $('#pr-list').html(html);
        });
    }
    function openPrModal(id){
        $('#pr-title').text(id?L.edit_pr_title:L.new_pr_title);$('#pr-id').val(id||0);
        $('#pr-items-body').html('');$('#pr-notes').val('');$('#pr-msg').hide();
        if(id){
            post('rsyi_lib_get_pr',{pr_id:id},function(r){
                if(!r.success) return;
                $('#pr-notes').val(r.data.notes||'');
                (r.data.items||[]).forEach(function(i){ addPrRow(i.book_id,i.quantity,i.unit_price||i.last_purchase_price||0,i.notes); });
            });
        } else { addPrRow(); }
        openModal('pr-modal');
    }
    function addPrRow(bid,qty,price,notes){
        var sel=buildBookSelect(bid);
        var priceVal=parseFloat(price)||0;
        // Auto-fill price from cache if not provided
        if(!priceVal && bid){
            var cached=booksCache.find(function(b){ return b.id==bid; });
            if(cached) priceVal=parseFloat(cached.last_price)||0;
        }
        var priceInput=$('<input type="number" class="pr-price small-text" value="'+priceVal.toFixed(2)+'" min="0" step="0.01" style="width:100px;">');
        var row=$('<tr>').append(
            $('<td>').append(sel),
            $('<td>').append($('<input type="number" class="pr-qty" value="'+(qty||1)+'" min="1" style="width:70px;">')),
            $('<td>').append(priceInput),
            $('<td>').append($('<input type="text" class="pr-notes-r regular-text" value="'+(notes||'')+'">')),
            $('<td>').append($('<button type="button" class="button button-small pr-del-row">×</button>'))
        );
        // Auto-fill price when book changes
        sel.on('change',function(){
            var bookId=$(this).val();
            var cached=booksCache.find(function(b){ return b.id==bookId; });
            if(cached) row.find('.pr-price').val(parseFloat(cached.last_price||0).toFixed(2));
        });
        row.find('.pr-del-row').on('click',function(){ $(this).closest('tr').remove(); });
        $('#pr-items-body').append(row);
    }
    $('#btn-new-pr').on('click',function(){ openPrModal(null); });
    $('#btn-auto-pr').on('click',function(){
        if(!confirm(L.auto_pr_confirm)) return;
        var btn=$(this); btn.prop('disabled',true);
        post('rsyi_lib_auto_pr',{},function(r){
            btn.prop('disabled',false);
            alert(r.data.message);
            if(r.success) loadPrList();
        });
    });
    $(document).on('click','.btn-edit-pr',function(){ openPrModal($(this).data('id')); });
    $('#btn-pr-add-row').on('click',function(){ addPrRow(); });
    $('#btn-save-pr').on('click',function(){
        var items=[]; $('#pr-items-body tr').each(function(){ var bid=$(this).find('.book-sel').val(); if(bid) items.push({book_id:bid,quantity:$(this).find('.pr-qty').val(),unit_price:$(this).find('.pr-price').val()||0,notes:$(this).find('.pr-notes-r').val()}); });
        if(!items.length){alert(L.alert_add_item);return;}
        post('rsyi_lib_save_pr',{pr_id:$('#pr-id').val(),notes:$('#pr-notes').val(),items:JSON.stringify(items)},function(r){
            msg('#pr-msg',r.data.message,r.success); if(r.success){setTimeout(function(){$('#pr-modal').hide();loadPrList();},700);}
        });
    });
    $(document).on('click','.btn-approve-pr',function(){
        if(!confirm(L.confirm_approve_pr)) return;
        post('rsyi_lib_approve_pr',{pr_id:$(this).data('id')},function(r){ alert(r.data.message); if(r.success) loadPrList(); });
    });
    $(document).on('click','.btn-reject-pr',function(){
        if(!confirm(L.confirm_reject_pr)) return;
        post('rsyi_lib_reject_pr',{pr_id:$(this).data('id')},function(r){ alert(r.data.message); if(r.success) loadPrList(); });
    });
    $(document).on('click','.btn-convert-pr',function(){
        if(!confirm(L.confirm_convert_pr)) return;
        post('rsyi_lib_convert_pr_to_add',{pr_id:$(this).data('id')},function(r){ alert(r.data.message); if(r.success){loadPrList();loadAddOrders();loadDashboard();} });
    });
    $(document).on('click','.btn-del-pr',function(){
        if(!confirm(L.confirm_del_pr)) return;
        post('rsyi_lib_delete_pr',{pr_id:$(this).data('id')},function(r){ alert(r.data.message); if(r.success) loadPrList(); });
    });

    // ═══ OPENING BALANCES ════════════════════════════════════════════════════
    function loadOpening(){
        post('rsyi_lib_get_opening',{},function(r){
            if(!r.success) return;
            var html='';
            if(!r.data.length) html='<tr><td colspan="4" style="text-align:center">'+L.no_balances+'</td></tr>';
            r.data.forEach(function(o){ html+='<tr><td>'+(o.title_ar||'')+'<br><small>'+(o.title_en||'')+'</small></td><td>'+o.quantity+'</td><td>'+parseFloat(o.unit_price||0).toFixed(2)+'</td><td>'+(o.created_at||'').substr(0,10)+'</td></tr>'; });
            $('#opening-list').html(html);
            // populate ob-book select
            var opts='<option value="">'+L.select_book+'</option>';
            booksCache.forEach(function(b){ opts+='<option value="'+b.id+'">'+(b.title_ar||b.title_en)+'</option>'; });
            $('#ob-book').html(opts);
        });
    }
    $('#btn-save-opening').on('click',function(){
        post('rsyi_lib_save_opening',{book_id:$('#ob-book').val(),quantity:$('#ob-qty').val(),unit_price:$('#ob-price').val(),notes:$('#ob-notes').val()},function(r){
            msg('#ob-msg',r.data.message,r.success); if(r.success){$('#ob-book').val('');$('#ob-qty').val(0);$('#ob-price').val(0);$('#ob-notes').val('');loadOpening();loadDashboard();}
        });
    });

    // ═══ REPORTS ═════════════════════════════════════════════════════════════
    var currentReport='stock';
    function initReports(){ showReportFilters('stock'); runReport('stock'); }
    $('.report-btn').on('click',function(){
        $('.report-btn').removeClass('btn-active');$(this).addClass('btn-active');
        var rpt=$(this).data('report'); currentReport=rpt;
        showReportFilters(rpt); runReport(rpt);
    });
    function showReportFilters(rpt){
        var html='';
        if(rpt==='stock'){
            html='<select id="rf-cat"><option value="">'+L.all_categories+'</option><option value="curriculum">'+L.curriculum+'</option><option value="certificate">'+L.certificates+'</option><option value="general">'+L.general+'</option></select>'+
                 '<button class="button" id="btn-run-report">'+L.run+'</button>';
        } else if(rpt==='movement'){
            var bookOpts='<option value="">'+L.select_book+'</option>';
            booksCache.forEach(function(b){ bookOpts+='<option value="'+b.id+'">'+(b.title_ar||b.title_en)+'</option>'; });
            html='<select id="rf-book">'+bookOpts+'</select>'+
                 '<input type="date" id="rf-from"><input type="date" id="rf-to"><button class="button" id="btn-run-report">'+L.run+'</button>';
        } else if(rpt==='cohort_withdrawal'){
            var cOpts='<option value="0">'+L.all_groups+'</option>';
            <?php foreach($cohorts as $c): ?>
            cOpts+='<option value="<?php echo $c->id ?>"><?php echo esc_js($c->name) ?></option>';
            <?php endforeach; ?>
            html='<select id="rf-cohort">'+cOpts+'</select>'+
                 '<input type="date" id="rf-from"><input type="date" id="rf-to"><button class="button" id="btn-run-report">'+L.run+'</button>';
        } else if(rpt==='supplier'){
            var sOpts='<option value="0">'+L.all_suppliers+'</option>';
            suppliersCache.forEach(function(s){ sOpts+='<option value="'+s.id+'">'+s.name+'</option>'; });
            html='<select id="rf-sup">'+sOpts+'</select><button class="button" id="btn-run-report">'+L.run+'</button>';
        } else {
            html='<button class="button" id="btn-run-report">'+L.run+'</button>';
        }
        $('#report-filters').html(html);
        $('#btn-run-report').on('click',function(){ runReport(currentReport); });
    }
    function runReport(rpt){
        var params={};
        if(rpt==='stock') params.category=$('#rf-cat').val()||'';
        else if(rpt==='movement'){ params.book_id=$('#rf-book').val()||0; params.date_from=$('#rf-from').val()||''; params.date_to=$('#rf-to').val()||''; }
        else if(rpt==='cohort_withdrawal'){ params.cohort_id=$('#rf-cohort').val()||0; params.date_from=$('#rf-from').val()||''; params.date_to=$('#rf-to').val()||''; }
        else if(rpt==='supplier') params.supplier_id=$('#rf-sup').val()||0;

        post('rsyi_lib_report_'+rpt,params,function(r){
            if(!r.success){ $('#report-output').html('<p style="color:red;">'+r.data.message+'</p>'); return; }
            renderReport(rpt,r.data);
        });
    }
    function renderReport(rpt,data){
        var html='<table class="rsyi-report-table">';
        if(!data.length){ $('#report-output').html('<p>'+L.no_data+'</p>'); return; }
        if(rpt==='stock'){
            html+='<thead><tr><th>'+L.rpt_title+'</th><th>'+L.rpt_subject+'</th><th>'+L.rpt_grade+'</th><th>ISBN</th><th>'+L.rpt_category+'</th><th>'+L.rpt_stock+'</th><th>'+L.rpt_min+'</th></tr></thead><tbody>';
            data.forEach(function(b){ html+='<tr class="stock-'+b.stock_status+'"><td>'+b.title_ar+'<br><small>'+b.title_en+'</small></td><td>'+(b.subject||'—')+'</td><td>'+(b.grade_level||'—')+'</td><td>'+(b.isbn||'—')+'</td><td>'+b.category+'</td><td><strong>'+b.current_stock+'</strong></td><td>'+b.min_stock+'</td></tr>'; });
        } else if(rpt==='movement'){
            html+='<thead><tr><th>'+L.rpt_date+'</th><th>'+L.rpt_type+'</th><th>'+L.rpt_qty+'</th><th>'+L.rpt_balance+'</th><th>'+L.rpt_by+'</th></tr></thead><tbody>';
            data.forEach(function(t){ var qty=parseInt(t.quantity); html+='<tr><td>'+(t.created_at||'').substr(0,16)+'</td><td>'+t.transaction_type+'</td><td style="color:'+(qty>0?'green':'red')+';">'+(qty>0?'+':'')+qty+'</td><td>'+t.running_balance+'</td><td>'+(t.created_by_name||'')+'</td></tr>'; });
        } else if(rpt==='low_stock'||rpt==='zero_stock'){
            html+='<thead><tr><th>'+L.rpt_title+'</th><th>'+L.rpt_subject+'</th><th>'+L.rpt_grade+'</th><th>'+L.rpt_stock+'</th><th>'+L.rpt_min+'</th>'+(rpt==='low_stock'?'<th>'+L.rpt_shortage+'</th>':'')+'</tr></thead><tbody>';
            data.forEach(function(b){ html+='<tr><td>'+b.title_ar+'<br><small>'+b.title_en+'</small></td><td>'+(b.subject||'—')+'</td><td>'+(b.grade_level||'—')+'</td><td style="color:red;"><strong>'+b.current_stock+'</strong></td><td>'+b.min_stock+'</td>'+(rpt==='low_stock'?'<td style="color:orange;">'+b.shortage+'</td>':'')+'</tr>'; });
        } else if(rpt==='cohort_withdrawal'){
            html+='<thead><tr><th>'+L.rpt_order_no+'</th><th>'+L.rpt_cohort+'</th><th>'+L.rpt_title+'</th><th>'+L.rpt_qty+'</th><th>'+L.rpt_date+'</th></tr></thead><tbody>';
            data.forEach(function(r){ html+='<tr><td>'+r.order_number+'</td><td>'+(r.cohort_name||'—')+'</td><td>'+r.title_ar+'<br><small>'+r.title_en+'</small></td><td>'+r.quantity+'</td><td>'+(r.created_at||'').substr(0,10)+'</td></tr>'; });
        } else if(rpt==='supplier'){
            html+='<thead><tr><th>'+L.rpt_supplier+'</th><th>'+L.rpt_order_no+'</th><th>'+L.rpt_date+'</th><th>'+L.rpt_qty+'</th><th>'+L.rpt_value+'</th></tr></thead><tbody>';
            data.forEach(function(r){ html+='<tr><td>'+(r.supplier_name||'—')+'</td><td>'+r.order_number+'</td><td>'+(r.created_at||'').substr(0,10)+'</td><td>'+r.total_quantity+'</td><td>'+parseFloat(r.total_value||0).toFixed(2)+'</td></tr>'; });
        }
        html+='</tbody></table>';
        $('#report-output').html(html);
    }

    // ═══ PURCHASE REQUEST PRINT ══════════════════════════════════════════════
    var prPrintData=null;
    $(document).on('click','.btn-print-pr',function(){
        var id=$(this).data('id');
        post('rsyi_lib_get_pr_print_data',{pr_id:id},function(r){
            if(!r.success){ alert(L.alert_fetch_err); return; }
            prPrintData=r.data;
            var pr=r.data.pr, items=r.data.items||[];
            // Header info
            var hdr='<strong><?= rsyi_lib_t('رقم الطلب','Request No.') ?>:</strong> '+pr.request_number+
                ' &nbsp;|&nbsp; <strong><?= rsyi_lib_t('التاريخ','Date') ?>:</strong> '+(pr.created_at||'').substr(0,10)+
                (pr.requested_by_name?' &nbsp;|&nbsp; <strong><?= rsyi_lib_t('بواسطة','By') ?>:</strong> '+pr.requested_by_name:'')+
                (pr.notes?' &nbsp;|&nbsp; <strong><?= rsyi_lib_t('ملاحظات','Notes') ?>:</strong> '+pr.notes:'');
            $('#pr-print-header').html(hdr);
            // Build editable rows
            var tbody='';
            items.forEach(function(i,idx){
                var lp=parseFloat(i.last_purchase_price||0);
                var qty=parseInt(i.quantity||0);
                tbody+='<tr>'+
                    '<td>'+(i.title_ar||i.title_en||'—')+'</td>'+
                    '<td>'+(i.isbn||'—')+'</td>'+
                    '<td>'+parseInt(i.current_stock||0)+'</td>'+
                    '<td>'+qty+'</td>'+
                    '<td><input type="number" class="pr-est-price small-text" data-idx="'+idx+'" data-qty="'+qty+'" value="'+lp.toFixed(2)+'" min="0" step="0.01" style="width:90px;"></td>'+
                    '<td class="pr-line-total">'+( qty*lp ).toFixed(2)+'</td>'+
                    '</tr>';
            });
            $('#pr-print-body').html(tbody);
            calcPrPrintTotal();
            openModal('pr-print-modal');
        });
    });
    function calcPrPrintTotal(){
        var grand=0;
        $('#pr-print-body tr').each(function(){
            var price=parseFloat($(this).find('.pr-est-price').val())||0;
            var qty=parseInt($(this).find('.pr-est-price').data('qty'))||0;
            var line=qty*price;
            grand+=line;
            $(this).find('.pr-line-total').text(line.toFixed(2));
        });
        $('#pr-print-grand-total').text(grand.toFixed(2));
    }
    $(document).on('input','.pr-est-price',calcPrPrintTotal);

    $('#btn-do-pr-print').on('click',function(){
        if(!prPrintData) return;
        var pr=prPrintData.pr, items=prPrintData.items||[];
        var pdir='<?= $_lib_en ? 'ltr' : 'rtl' ?>';
        var logo=prPrintData.logo?'<img src="'+prPrintData.logo+'" style="max-height:70px;">':'';
        var instName=prPrintData.institute_name||'';
        var rows='', grand=0;
        $('#pr-print-body tr').each(function(idx){
            var price=parseFloat($(this).find('.pr-est-price').val())||0;
            var qty=parseInt($(this).find('.pr-est-price').data('qty'))||0;
            var stock=parseInt($(this).find('td:eq(2)').text())||0;
            var isbn=$(this).find('td:eq(1)').text();
            var title=$(this).find('td:eq(0)').text();
            var line=qty*price; grand+=line;
            rows+='<tr><td>'+title+'</td><td>'+isbn+'</td><td>'+stock+'</td><td>'+qty+'</td><td>'+price.toFixed(2)+'</td><td>'+line.toFixed(2)+'</td></tr>';
        });
        var html='<!DOCTYPE html><html dir="'+pdir+'"><head><meta charset="UTF-8">'+
            '<title><?= rsyi_lib_t('طلب عرض السعر','Purchase Request') ?> - '+pr.request_number+'</title>'+
            '<style>body{font-family:Arial,sans-serif;direction:'+pdir+';padding:20px;}'+
            'table{width:100%;border-collapse:collapse;}th,td{border:1px solid #999;padding:6px 10px;text-align:'+(pdir==='rtl'?'right':'left')+';}thead{background:#eee;}'+
            '.sig{display:inline-block;width:200px;border-top:1px solid #333;margin-top:60px;text-align:center;margin:0 20px;}</style></head><body>'+
            '<div style="text-align:center;margin-bottom:20px;">'+logo+'<h2 style="margin:6px 0;">'+instName+'</h2>'+
            '<h3><?= rsyi_lib_t('طلب عرض السعر','Request for Quotation') ?></h3></div>'+
            '<table style="margin-bottom:16px;border:none;">'+
            '<tr><td style="border:none;"><strong><?= rsyi_lib_t('رقم الطلب','Request No.') ?>:</strong> '+pr.request_number+'</td>'+
            '<td style="border:none;"><strong><?= rsyi_lib_t('التاريخ','Date') ?>:</strong> '+(pr.created_at||'').substr(0,10)+'</td></tr>'+
            (pr.requested_by_name?'<tr><td colspan="2" style="border:none;"><strong><?= rsyi_lib_t('طالب بواسطة','Requested By') ?>:</strong> '+pr.requested_by_name+'</td></tr>':'')+
            (pr.notes?'<tr><td colspan="2" style="border:none;"><strong><?= rsyi_lib_t('ملاحظات','Notes') ?>:</strong> '+pr.notes+'</td></tr>':'')+
            '</table>'+
            '<table><thead><tr>'+
            '<th><?= rsyi_lib_t('الصنف','Item') ?></th><th>ISBN</th>'+
            '<th><?= rsyi_lib_t('الموجود','In Stock') ?></th>'+
            '<th><?= rsyi_lib_t('المطلوب','Required') ?></th>'+
            '<th><?= rsyi_lib_t('السعر التقديري','Est. Price') ?></th>'+
            '<th><?= rsyi_lib_t('الإجمالي','Total') ?></th>'+
            '</tr></thead><tbody>'+rows+
            '<tr><td colspan="5" style="text-align:end;font-weight:700;"><?= rsyi_lib_t('الإجمالي الكلي','Grand Total') ?></td>'+
            '<td style="font-weight:700;">'+grand.toFixed(2)+'</td></tr>'+
            '</tbody></table>'+
            '<div style="margin-top:50px;display:flex;justify-content:space-around;">'+
            '<div class="sig"><?= rsyi_lib_t('كبير المدربين','Chief Instructor') ?></div>'+
            '<div class="sig"><?= rsyi_lib_t('مدير الشؤون','Affairs Manager') ?></div>'+
            '</div></body></html>';
        var win=window.open('','_blank'); win.document.write(html); win.document.close(); win.print();
    });

    // ═══ REPORTS PRINT ═══════════════════════════════════════════════════════
    $('#btn-print-report').on('click',function(){
        var table=$('#report-output').html();
        if(!table||!$.trim(table)){ alert('<?= rsyi_lib_t('لا توجد بيانات للطباعة','No data to print') ?>'); return; }
        var pdir='<?= $_lib_en ? 'ltr' : 'rtl' ?>';
        var logo='<?php echo esc_js(get_option('rsyi_logo_url','')); ?>';
        var instName='<?php echo esc_js(get_option('rsyi_institute_name','Red Sea Yacht Institute')); ?>';
        var reportTitles={
            stock:'<?= rsyi_lib_t('تقرير الرصيد الحالي','Current Stock Report') ?>',
            movement:'<?= rsyi_lib_t('تقرير حركة كتاب','Book Movement Report') ?>',
            low_stock:'<?= rsyi_lib_t('تقرير على وشك النفاد','Low Stock Report') ?>',
            zero_stock:'<?= rsyi_lib_t('تقرير رصيد صفري','Zero Stock Report') ?>',
            cohort_withdrawal:'<?= rsyi_lib_t('تقرير صرف مجموعة','Cohort Withdrawal Report') ?>',
            supplier:'<?= rsyi_lib_t('تقرير مورد','Supplier Report') ?>'
        };
        var title=reportTitles[currentReport]||'<?= rsyi_lib_t('تقرير','Report') ?>';
        var now=new Date().toISOString().substr(0,10);
        var logoHtml=logo?'<img src="'+logo+'" style="max-height:60px;">':'';
        var html='<!DOCTYPE html><html dir="'+pdir+'"><head><meta charset="UTF-8"><title>'+title+'</title>'+
            '<style>body{font-family:Arial,sans-serif;direction:'+pdir+';padding:20px;}'+
            'table{width:100%;border-collapse:collapse;}th,td{border:1px solid #999;padding:6px 10px;font-size:12px;text-align:'+(pdir==='rtl'?'right':'left')+';}'+
            'thead{background:#eee;}.stock-ok td{background:#f0fff0;}.stock-low td{background:#fff8e1;}.stock-zero td{background:#ffe8e8;}'+
            '</style></head><body>'+
            '<div style="text-align:center;margin-bottom:16px;">'+logoHtml+
            '<h2 style="margin:6px 0;">'+instName+'</h2><h3 style="margin:4px 0;">'+title+'</h3>'+
            '<p style="color:#666;font-size:12px;margin:0;"><?= rsyi_lib_t('تاريخ الطباعة','Print Date') ?>: '+now+'</p></div>'+
            table+
            '</body></html>';
        var win=window.open('','_blank'); win.document.write(html); win.document.close(); win.print();
    });

    // ═══ PRINT ════════════════════════════════════════════════════════════════
    function printOrder(type,id){
        post('rsyi_lib_get_print_data',{type:type,order_id:id},function(r){
            if(!r.success){ alert(L.alert_fetch_err); return; }
            var d=r.data, o=d.order, items=d.items||[];
            var logo=d.logo?'<img src="'+d.logo+'" style="max-height:70px;">':'';
            var pdir='<?= $_lib_en ? 'ltr' : 'rtl' ?>';
            var ptype=(type==='add'?L.print_add:type==='withdrawal'?L.print_wd:L.print_ret);
            var rows='', total=0;

            if(type==='withdrawal'){
                // Points 4 & 5: use last_purchase_price + ISBN; Chief Instructor sig only
                items.forEach(function(i){
                    var qty=parseInt(i.quantity||0);
                    var price=parseFloat(i.last_purchase_price||i.unit_price||0);
                    var line=qty*price;
                    total+=line;
                    rows+='<tr><td>'+(i.title_ar||i.title_en||'')+'</td><td>'+(i.isbn||'—')+'</td><td>'+qty+'</td><td>'+price.toFixed(2)+'</td><td>'+line.toFixed(2)+'</td></tr>';
                });
                var ciName=d.chief_instructor_name||'';
                var ciSig=d.chief_instructor_sig?'<img src="'+d.chief_instructor_sig+'" style="max-height:60px;display:block;margin:0 auto 6px;">':'';
                var sigBlock='<div style="margin-top:40px;display:flex;justify-content:space-around;">'+
                    '<div class="sig">'+ciSig+'<?= rsyi_lib_t('كبير المدربين','Chief Instructor') ?>'+(ciName?' / '+ciName:'')+'</div>'+
                    '<div class="sig">'+ciSig+'<?= rsyi_lib_t('الاعتماد','Approved By') ?>'+(ciName?' / '+ciName:'')+'</div>'+
                    '</div>';
                var html='<!DOCTYPE html><html dir="'+pdir+'"><head><meta charset="UTF-8"><title>'+ptype+'</title>'+
                    '<style>body{font-family:Arial,sans-serif;direction:'+pdir+';padding:20px;}'+
                    'table{width:100%;border-collapse:collapse;}th,td{border:1px solid #999;padding:6px 10px;text-align:'+(pdir==='rtl'?'right':'left')+';}'+
                    'thead{background:#eee;}.sig{display:inline-block;width:220px;border-top:1px solid #333;margin-top:60px;text-align:center;margin:0 20px;}</style></head><body>'+
                    '<div style="text-align:center;margin-bottom:20px;">'+logo+'<h2 style="margin:6px 0;">'+d.institute_name+'</h2><h3>'+ptype+'</h3></div>'+
                    '<table style="margin-bottom:16px;border:none;"><tr><td style="border:none;"><strong>'+L.print_order_no+'</strong> '+o.order_number+'</td><td style="border:none;"><strong>'+L.print_date_lbl+'</strong> '+(o.created_at||'').substr(0,10)+'</td></tr>'+
                    (o.cohort_name?'<tr><td colspan="2" style="border:none;"><strong>'+L.print_cohort_lbl+'</strong> '+o.cohort_name+'</td></tr>':'')+
                    (o.notes?'<tr><td colspan="2" style="border:none;"><strong>'+L.print_notes_lbl+'</strong> '+o.notes+'</td></tr>':'')+'</table>'+
                    '<table><thead><tr><th>'+L.print_book+'</th><th>ISBN</th><th>'+L.rpt_qty+'</th><th><?= rsyi_lib_t('آخر سعر شراء','Last Purchase Price') ?></th><th>'+L.print_total+'</th></tr></thead><tbody>'+rows+
                    '<tr><td colspan="4"><strong>'+L.print_total+'</strong></td><td><strong>'+total.toFixed(2)+'</strong></td></tr>'+
                    '</tbody></table>'+sigBlock+
                    '</body></html>';
                var win=window.open('','_blank'); win.document.write(html); win.document.close(); win.print();
            } else {
                items.forEach(function(i){ var qty=parseInt(i.quantity||0); var price=parseFloat(i.unit_price||0); rows+='<tr><td>'+(i.title_ar||i.title_en||'')+'</td><td>'+(i.isbn||'—')+'</td><td>'+qty+'</td><td>'+price.toFixed(2)+'</td><td>'+(qty*price).toFixed(2)+'</td></tr>'; total+=qty*price; });
                var html='<!DOCTYPE html><html dir="'+pdir+'"><head><meta charset="UTF-8"><title>'+ptype+'</title>'+
                    '<style>body{font-family:Arial,sans-serif;direction:'+pdir+';padding:20px;}'+
                    'table{width:100%;border-collapse:collapse;}th,td{border:1px solid #999;padding:6px 10px;text-align:'+(pdir==='rtl'?'right':'left')+';}'+
                    'thead{background:#eee;}.sig{display:inline-block;width:200px;border-top:1px solid #333;margin-top:60px;text-align:center;margin-left:40px;}</style></head><body>'+
                    '<div style="text-align:center;margin-bottom:20px;">'+logo+'<h2 style="margin:6px 0;">'+d.institute_name+'</h2><h3>'+ptype+'</h3></div>'+
                    '<table style="margin-bottom:16px;border:none;"><tr><td style="border:none;"><strong>'+L.print_order_no+'</strong> '+o.order_number+'</td><td style="border:none;"><strong>'+L.print_date_lbl+'</strong> '+(o.created_at||'').substr(0,10)+'</td></tr>'+
                    (o.supplier_name?'<tr><td colspan="2" style="border:none;"><strong>'+L.print_supplier_lbl+'</strong> '+o.supplier_name+'</td></tr>':'')+
                    (o.cohort_name?'<tr><td colspan="2" style="border:none;"><strong>'+L.print_cohort_lbl+'</strong> '+o.cohort_name+'</td></tr>':'')+
                    (o.notes?'<tr><td colspan="2" style="border:none;"><strong>'+L.print_notes_lbl+'</strong> '+o.notes+'</td></tr>':'')+'</table>'+
                    '<table><thead><tr><th>'+L.print_book+'</th><th>ISBN</th><th>'+L.rpt_qty+'</th><th>'+L.print_price+'</th><th>'+L.print_total+'</th></tr></thead><tbody>'+rows+
                    '<tr><td colspan="4"><strong>'+L.print_total+'</strong></td><td><strong>'+total.toFixed(2)+'</strong></td></tr>'+
                    '</tbody></table>'+
                    '<div style="margin-top:40px;display:flex;justify-content:space-around;">'+
                    '<div class="sig">'+L.print_wh_mgr+'</div>'+
                    (o.approved_by_name?'<div class="sig">'+L.print_approved_by+': '+o.approved_by_name+'</div>':'<div class="sig">'+L.print_approved_by+'</div>')+
                    '</div>'+
                    '</body></html>';
                var win=window.open('','_blank'); win.document.write(html); win.document.close(); win.print();
            }
        });
    }

    // ── EXCEL IMPORT ─────────────────────────────────────────────────────────
    var importRows=[];
    $('#btn-import-books').on('click',function(){
        importRows=[];$('#import-preview').hide().html('');$('#btn-do-import').hide();$('#import-msg').hide();$('#import-file').val('');
        openModal('import-modal');
    });
    $('#btn-dl-template').on('click',function(){
        if(typeof XLSX==='undefined'){ alert('SheetJS not loaded'); return; }
        var cols=['Title','Title EN','Author','Publisher','Subject','Cohort','For (Trainers/Students/Learning Aids)','Category (curriculum/certificate/general)','Language (en/ar/fr/other)','ISBN','Unit (copy/volume)','Price','Min Stock','Max Stock','Description'];
        var wb=XLSX.utils.book_new();
        var ws=XLSX.utils.aoa_to_sheet([cols,['Example Item','Example Item EN','Author Name','Publisher','Math','Cohort A','Trainers','curriculum','en','978-0000000000','copy',0,0,0,'']]);
        XLSX.utils.book_append_sheet(wb,ws,'Items');
        XLSX.writeFile(wb,'rya_items_template.xlsx');
    });
    $('#import-file').on('change',function(e){
        var f=e.target.files[0]; if(!f) return;
        if(typeof XLSX==='undefined'){ alert('SheetJS not loaded'); return; }
        var reader=new FileReader();
        reader.onload=function(ev){
            try{
                var wb=XLSX.read(ev.target.result,{type:'binary'});
                var ws=wb.Sheets[wb.SheetNames[0]];
                var rows=XLSX.utils.sheet_to_json(ws,{defval:''});
                importRows=rows;
                if(!rows.length){$('#import-preview').show().html('<p style="color:orange;padding:8px;">No data rows found.</p>');$('#btn-do-import').hide();return;}
                var keys=Object.keys(rows[0]);
                var t='<table class="wp-list-table widefat striped" style="font-size:12px;"><thead><tr>';
                keys.forEach(function(k){t+='<th style="white-space:nowrap;">'+$('<div>').text(k).html()+'</th>';});
                t+='</tr></thead><tbody>';
                rows.slice(0,15).forEach(function(r){t+='<tr>';keys.forEach(function(k){t+='<td>'+$('<div>').text(String(r[k])).html()+'</td>';});t+='</tr>';});
                if(rows.length>15) t+='<tr><td colspan="'+keys.length+'" style="text-align:center;color:#888;padding:6px;">…و'+(rows.length-15)+' صف آخر / …and '+(rows.length-15)+' more rows</td></tr>';
                t+='</tbody></table>';
                $('#import-preview').show().html(t);
                $('#btn-do-import').show();
            }catch(ex){$('#import-preview').show().html('<p style="color:red;padding:8px;">Error reading file: '+ex.message+'</p>');$('#btn-do-import').hide();}
        };
        reader.readAsBinaryString(f);
    });
    $('#btn-do-import').on('click',function(){
        if(!importRows.length) return;
        var btn=$(this); btn.prop('disabled',true);
        post('rsyi_import_books',{items:JSON.stringify(importRows)},function(r){
            btn.prop('disabled',false);
            msg('#import-msg',r.data.message,r.success);
            if(r.success){setTimeout(function(){$('#import-modal').hide();loadBooks();loadBooksCache();loadDashboard();},1000);}
        });
    });

    // ── INIT ─────────────────────────────────────────────────────────────────
    $(document).ready(function(){
        loadDashboard();
        loadBooksCache();
        tabLoaded['dashboard']=true;
    });

})(jQuery);
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js" crossorigin="anonymous"></script>
