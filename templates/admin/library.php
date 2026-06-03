<?php
defined( 'ABSPATH' ) || exit;
$cohorts              = \RSYI_SA\Modules\Library_Orders::get_cohorts();
$trainers             = \RSYI_SA\Modules\Library_Orders::get_trainers();
$lib_can_manage       = current_user_can( 'rsyi_lib_manage_warehouse' );
$lib_can_approve_wd   = current_user_can( 'rsyi_lib_approve_withdrawal' );
$lib_can_approve_pr   = current_user_can( 'rsyi_lib_approve_purchase' );
?>
<div class="wrap rsyi-lib-wrap" id="rsyi-library-page" dir="rtl">
<h1 class="wp-heading-inline">📦 مخزن الكتب / Book Warehouse</h1>
<hr class="wp-header-end">

<nav class="nav-tab-wrapper rsyi-tabs" style="margin-bottom:0;">
    <a href="#" class="nav-tab nav-tab-active" data-tab="dashboard">🏠 الرئيسية</a>
    <a href="#" class="nav-tab" data-tab="books">📚 الكتب</a>
    <a href="#" class="nav-tab" data-tab="suppliers">🏭 الموردون</a>
    <a href="#" class="nav-tab" data-tab="add-orders">📥 إذن الاستلام</a>
    <a href="#" class="nav-tab" data-tab="wd-orders">📤 إذن الصرف</a>
    <a href="#" class="nav-tab" data-tab="ret-orders">↩ رد الكتب</a>
    <a href="#" class="nav-tab" data-tab="pr">🛒 طلبات الشراء</a>
    <a href="#" class="nav-tab" data-tab="opening">🔢 الأرصدة الافتتاحية</a>
    <a href="#" class="nav-tab" data-tab="reports">📊 التقارير</a>
</nav>

<!-- ═══ DASHBOARD ═══ -->
<div id="tab-dashboard" class="rsyi-tab-pane">
    <div id="lib-stat-cards" style="display:flex;flex-wrap:wrap;gap:16px;margin:20px 0;"></div>
    <h3>⚠ كتب على وشك النفاد / Low Stock Books</h3>
    <div id="lib-low-table-wrap"></div>
</div>

<!-- ═══ BOOKS ═══ -->
<div id="tab-books" class="rsyi-tab-pane" style="display:none;">
    <div class="rsyi-toolbar">
        <?php if($lib_can_manage): ?>
        <button class="button button-primary" id="btn-add-book">+ إضافة كتاب / Add Book</button>
        <?php endif; ?>
        <select id="filter-cat"><option value="">كل التصنيفات / All</option>
            <option value="curriculum">مناهج أجنبية</option><option value="certificate">شهادات</option><option value="general">عام</option>
        </select>
        <input type="text" id="filter-search" placeholder="بحث / Search…">
        <button class="button" id="btn-search-books">🔍</button>
    </div>
    <table class="wp-list-table widefat fixed striped" style="direction:rtl;">
        <thead><tr>
            <th style="width:50px;">غلاف</th><th>العنوان / Title</th><th>المادة</th><th>المستوى</th><th>ISBN</th>
            <th style="width:80px;">الرصيد</th><th style="width:70px;">حد أدنى</th><th style="width:110px;">إجراء</th>
        </tr></thead>
        <tbody id="books-list"><tr><td colspan="8" style="text-align:center">جاري التحميل…</td></tr></tbody>
    </table>
</div>

<!-- ═══ SUPPLIERS ═══ -->
<div id="tab-suppliers" class="rsyi-tab-pane" style="display:none;">
    <?php if($lib_can_manage): ?>
    <div style="max-width:700px;background:#fff;padding:20px;border:1px solid #ddd;border-radius:6px;margin:16px 0;">
        <h3 id="sup-form-title" style="margin-top:0;">إضافة مورد / Add Supplier</h3>
        <input type="hidden" id="sup-id" value="0">
        <table class="form-table">
            <tr><th><label for="sup-name">الاسم *</label></th><td><input type="text" id="sup-name" class="regular-text"></td></tr>
            <tr><th><label for="sup-phone">الهاتف</label></th><td><input type="text" id="sup-phone" class="regular-text"></td></tr>
            <tr><th><label for="sup-email">البريد</label></th><td><input type="email" id="sup-email" class="regular-text"></td></tr>
            <tr><th><label for="sup-addr">العنوان</label></th><td><textarea id="sup-addr" class="large-text" rows="2"></textarea></td></tr>
        </table>
        <button class="button button-primary" id="btn-save-supplier">💾 حفظ / Save</button>
        <button class="button" id="btn-cancel-sup" style="display:none;">إلغاء</button>
        <div id="sup-msg" style="margin-top:8px;display:none;"></div>
    </div>
    <?php endif; ?>
    <table class="wp-list-table widefat fixed striped" style="direction:rtl;">
        <thead><tr><th>الاسم</th><th>الهاتف</th><th>البريد</th><th>العنوان</th><th style="width:100px;">إجراء</th></tr></thead>
        <tbody id="suppliers-list"><tr><td colspan="5" style="text-align:center">جاري التحميل…</td></tr></tbody>
    </table>
</div>

<!-- ═══ ADD ORDERS (RECEIVING) ═══ -->
<div id="tab-add-orders" class="rsyi-tab-pane" style="display:none;">
    <?php if($lib_can_manage): ?>
    <div class="rsyi-toolbar"><button class="button button-primary" id="btn-new-add-order">+ إذن استلام جديد / New Receiving Order</button></div>
    <?php endif; ?>
    <table class="wp-list-table widefat fixed striped" style="direction:rtl;">
        <thead><tr><th>رقم الإذن</th><th>المورد</th><th>التاريخ</th><th>الكمية</th><th>القيمة</th><th style="width:140px;">إجراء</th></tr></thead>
        <tbody id="add-orders-list"><tr><td colspan="6" style="text-align:center">جاري التحميل…</td></tr></tbody>
    </table>
</div>

<!-- ═══ WITHDRAWAL ORDERS ═══ -->
<div id="tab-wd-orders" class="rsyi-tab-pane" style="display:none;">
    <div class="rsyi-toolbar">
        <?php if($lib_can_manage): ?>
        <button class="button button-primary" id="btn-new-wd-order">+ إذن صرف جديد / New Withdrawal</button>
        <?php endif; ?>
        <select id="wd-filter-status">
            <option value="">كل الحالات / All</option>
            <option value="draft">مسودة</option><option value="pending">في الانتظار</option>
            <option value="approved">معتمد</option><option value="completed">مكتمل</option><option value="rejected">مرفوض</option>
        </select>
        <button class="button" id="btn-filter-wd">🔍</button>
    </div>
    <table class="wp-list-table widefat fixed striped" style="direction:rtl;">
        <thead><tr><th>رقم الإذن</th><th>المجموعة/المدرب</th><th>النوع</th><th>الحالة</th><th>بواسطة</th><th>التاريخ</th><th style="width:180px;">إجراء</th></tr></thead>
        <tbody id="wd-orders-list"><tr><td colspan="7" style="text-align:center">جاري التحميل…</td></tr></tbody>
    </table>
</div>

<!-- ═══ RETURN ORDERS ═══ -->
<div id="tab-ret-orders" class="rsyi-tab-pane" style="display:none;">
    <?php if($lib_can_manage): ?>
    <div class="rsyi-toolbar"><button class="button button-primary" id="btn-new-ret-order">+ إذن رد جديد / New Return</button></div>
    <?php endif; ?>
    <table class="wp-list-table widefat fixed striped" style="direction:rtl;">
        <thead><tr><th>رقم الإذن</th><th>المجموعة/المدرب</th><th>الحالة</th><th>التاريخ</th><th style="width:130px;">إجراء</th></tr></thead>
        <tbody id="ret-orders-list"><tr><td colspan="5" style="text-align:center">جاري التحميل…</td></tr></tbody>
    </table>
</div>

<!-- ═══ PURCHASE REQUESTS ═══ -->
<div id="tab-pr" class="rsyi-tab-pane" style="display:none;">
    <?php if($lib_can_manage): ?>
    <div class="rsyi-toolbar"><button class="button button-primary" id="btn-new-pr">+ طلب شراء جديد / New PR</button></div>
    <?php endif; ?>
    <table class="wp-list-table widefat fixed striped" style="direction:rtl;">
        <thead><tr><th>رقم الطلب</th><th>الحالة</th><th>طالب بواسطة</th><th>التاريخ</th><th style="width:180px;">إجراء</th></tr></thead>
        <tbody id="pr-list"><tr><td colspan="5" style="text-align:center">جاري التحميل…</td></tr></tbody>
    </table>
</div>

<!-- ═══ OPENING BALANCES ═══ -->
<div id="tab-opening" class="rsyi-tab-pane" style="display:none;">
    <?php if($lib_can_manage): ?>
    <div style="max-width:600px;background:#fff;padding:20px;border:1px solid #ddd;border-radius:6px;margin:16px 0;">
        <h3 style="margin-top:0;">إضافة رصيد افتتاحي / Add Opening Balance</h3>
        <table class="form-table">
            <tr><th><label for="ob-book">الكتاب *</label></th><td>
                <select id="ob-book" style="width:100%;"><option value="">-- اختر كتاباً --</option></select>
            </td></tr>
            <tr><th><label for="ob-qty">الكمية *</label></th><td><input type="number" id="ob-qty" value="0" min="0" style="width:100px;"></td></tr>
            <tr><th><label for="ob-price">سعر الوحدة</label></th><td><input type="number" id="ob-price" value="0" min="0" step="0.01" style="width:120px;"></td></tr>
            <tr><th><label for="ob-notes">ملاحظات</label></th><td><textarea id="ob-notes" class="large-text" rows="2"></textarea></td></tr>
        </table>
        <button class="button button-primary" id="btn-save-opening">💾 حفظ</button>
        <div id="ob-msg" style="margin-top:8px;display:none;"></div>
    </div>
    <?php endif; ?>
    <table class="wp-list-table widefat fixed striped" style="direction:rtl;">
        <thead><tr><th>الكتاب</th><th>الكمية</th><th>سعر الوحدة</th><th>التاريخ</th></tr></thead>
        <tbody id="opening-list"><tr><td colspan="4" style="text-align:center">جاري التحميل…</td></tr></tbody>
    </table>
</div>

<!-- ═══ REPORTS ═══ -->
<div id="tab-reports" class="rsyi-tab-pane" style="display:none;">
    <div class="rsyi-toolbar" style="margin:16px 0;gap:8px;display:flex;flex-wrap:wrap;">
        <button class="button report-btn btn-active" data-report="stock">📋 الرصيد الحالي</button>
        <button class="button report-btn" data-report="movement">📈 حركة كتاب</button>
        <button class="button report-btn" data-report="low_stock">⚠ على وشك النفاد</button>
        <button class="button report-btn" data-report="zero_stock">❌ رصيد صفري</button>
        <button class="button report-btn" data-report="cohort_withdrawal">👥 صرف مجموعة</button>
        <button class="button report-btn" data-report="supplier">🏭 مورد</button>
    </div>
    <div id="report-filters" style="margin-bottom:12px;display:flex;flex-wrap:wrap;gap:8px;align-items:center;"></div>
    <div id="report-output"></div>
</div>
</div><!-- end .wrap -->

<!-- ═══════════════════════════════ MODALS ═══════════════════════════════ -->

<!-- Book Modal -->
<div id="book-modal" class="rsyi-modal-overlay" style="display:none;">
<div class="rsyi-modal-box" style="max-width:750px;">
    <h2 id="bm-title" style="margin-top:0;">إضافة كتاب</h2>
    <input type="hidden" id="bm-id" value="0">
    <table class="form-table">
        <tr><th>العنوان بالعربية *</th><td><input type="text" id="bm-title-ar" class="regular-text"></td>
            <th>Title in English *</th><td><input type="text" id="bm-title-en" class="regular-text"></td></tr>
        <tr><th>المؤلف / Author</th><td><input type="text" id="bm-author" class="regular-text"></td>
            <th>الناشر / Publisher</th><td><input type="text" id="bm-publisher" class="regular-text"></td></tr>
        <tr><th>المادة / Subject</th><td><input type="text" id="bm-subject" class="regular-text"></td>
            <th>المستوى / Grade</th><td><input type="text" id="bm-grade" class="regular-text"></td></tr>
        <tr><th>التصنيف / Category</th><td>
                <select id="bm-cat"><option value="curriculum">مناهج أجنبية</option><option value="certificate">شهادات</option><option value="general">عام</option></select>
            </td>
            <th>اللغة / Language</th><td>
                <select id="bm-lang"><option value="en">English</option><option value="ar">العربية</option><option value="fr">Français</option><option value="other">أخرى</option></select>
            </td></tr>
        <tr><th>ISBN</th><td><input type="text" id="bm-isbn" class="regular-text"></td>
            <th>الوحدة / Unit</th><td>
                <select id="bm-unit"><option value="copy">نسخة / Copy</option><option value="volume">مجلد / Volume</option></select>
            </td></tr>
        <tr><th>حد أدنى / Min Stock</th><td><input type="number" id="bm-min-stock" value="0" min="0" style="width:80px;"></td>
            <th>السعر / Price</th><td><input type="number" id="bm-price" value="0" min="0" step="0.01" style="width:100px;"></td></tr>
        <tr><th>الوصف / Description</th><td colspan="3"><textarea id="bm-desc" class="large-text" rows="2"></textarea></td></tr>
        <tr><th>صورة الغلاف / Cover</th><td colspan="3">
            <input type="hidden" id="bm-cover-id" value="0">
            <div id="bm-cover-preview" style="margin-bottom:6px;"></div>
            <button type="button" class="button" id="btn-bm-cover">اختر صورة</button>
            <button type="button" class="button" id="btn-bm-cover-clear" style="display:none;">إزالة</button>
        </td></tr>
    </table>
    <p><button class="button button-primary" id="btn-save-book">💾 حفظ</button>
       <button class="button rsyi-modal-close" data-modal="book-modal" style="margin-right:8px;">إلغاء</button></p>
    <div id="bm-msg" style="display:none;margin-top:8px;"></div>
</div></div>

<!-- Add Order Modal -->
<div id="add-order-modal" class="rsyi-modal-overlay" style="display:none;">
<div class="rsyi-modal-box" style="max-width:820px;max-height:90vh;overflow-y:auto;">
    <h2 id="ao-title" style="margin-top:0;">إذن استلام / Receiving Order</h2>
    <input type="hidden" id="ao-id" value="0">
    <table class="form-table">
        <tr><th>المورد / Supplier</th><td>
            <select id="ao-supplier"><option value="0">-- بدون مورد --</option><?php foreach([] as $s){} ?></select>
        </td></tr>
        <tr><th>الضريبة / Tax</th><td>
            <label><input type="radio" name="ao_tax" value="0" checked> بدون ضريبة</label> &nbsp;
            <label><input type="radio" name="ao_tax" value="1"> شامل ضريبة</label>
            <span id="ao-tax-rate-wrap" style="display:none;margin-right:12px;">نسبة: <input type="number" id="ao-tax-rate" value="14" min="0" max="100" style="width:60px;"> %</span>
        </td></tr>
        <tr><th>ملاحظات / Notes</th><td><textarea id="ao-notes" class="large-text" rows="2"></textarea></td></tr>
    </table>
    <div style="margin:12px 0;">
        <strong>الكتب / Items:</strong>
        <button type="button" class="button button-small" id="btn-ao-add-row" style="margin-right:8px;">+ إضافة سطر</button>
    </div>
    <table class="wp-list-table widefat" id="ao-items-table" style="direction:rtl;">
        <thead><tr><th>الكتاب / Book</th><th style="width:80px;">الكمية</th><th style="width:110px;">سعر الوحدة</th><th style="width:40px;"></th></tr></thead>
        <tbody id="ao-items-body"></tbody>
        <tfoot><tr><td colspan="2" style="text-align:left;"><strong>الإجمالي / Total: <span id="ao-total">0.00</span></strong></td><td colspan="2"></td></tr></tfoot>
    </table>
    <p style="margin-top:16px;">
        <button class="button button-primary" id="btn-save-add-order">💾 حفظ</button>
        <button class="button rsyi-modal-close" data-modal="add-order-modal" style="margin-right:8px;">إلغاء</button>
    </p>
    <div id="ao-msg" style="display:none;margin-top:8px;"></div>
</div></div>

<!-- Withdrawal Order Modal -->
<div id="wd-modal" class="rsyi-modal-overlay" style="display:none;">
<div class="rsyi-modal-box" style="max-width:820px;max-height:90vh;overflow-y:auto;">
    <h2 id="wd-title" style="margin-top:0;">إذن صرف / Withdrawal Order</h2>
    <input type="hidden" id="wd-id" value="0">
    <table class="form-table">
        <tr><th>المجموعة / Cohort</th><td>
            <select id="wd-cohort"><option value="0">-- اختر مجموعة --</option>
                <?php foreach($cohorts as $c): ?><option value="<?php echo $c->id ?>"><?php echo esc_html($c->name) ?></option><?php endforeach; ?>
            </select>
        </td>
        <th>المدرب / Trainer</th><td>
            <select id="wd-trainer"><option value="0">-- اختر مدرب --</option>
                <?php foreach($trainers as $t): ?><option value="<?php echo $t->ID ?>"><?php echo esc_html($t->display_name) ?></option><?php endforeach; ?>
            </select>
        </td></tr>
        <tr><th>النوع / Type</th><td>
            <select id="wd-type"><option value="normal">عادي / Normal</option><option value="custody">عهدة / Custody</option></select>
        </td>
        <th>ملاحظات / Notes</th><td><textarea id="wd-notes" rows="2" class="large-text"></textarea></td></tr>
    </table>
    <div style="margin:12px 0;">
        <strong>الكتب / Items:</strong>
        <button type="button" class="button button-small" id="btn-wd-add-row">+ إضافة سطر</button>
    </div>
    <table class="wp-list-table widefat" id="wd-items-table" style="direction:rtl;">
        <thead><tr><th>الكتاب / Book</th><th style="width:80px;">الكمية</th><th style="width:100px;">متاح</th><th style="width:40px;"></th></tr></thead>
        <tbody id="wd-items-body"></tbody>
    </table>
    <p style="margin-top:16px;">
        <button class="button button-primary" id="btn-save-wd-draft">💾 حفظ مسودة</button>
        <button class="button button-primary" id="btn-submit-wd" style="background:#0073aa;border-color:#0073aa;">📤 تقديم</button>
        <button class="button rsyi-modal-close" data-modal="wd-modal" style="margin-right:8px;">إلغاء</button>
    </p>
    <div id="wd-msg" style="display:none;margin-top:8px;"></div>
</div></div>

<!-- Return Order Modal -->
<div id="ret-modal" class="rsyi-modal-overlay" style="display:none;">
<div class="rsyi-modal-box" style="max-width:750px;max-height:90vh;overflow-y:auto;">
    <h2 id="ret-title" style="margin-top:0;">إذن رد / Return Order</h2>
    <input type="hidden" id="ret-id" value="0">
    <table class="form-table">
        <tr><th>المجموعة / Cohort</th><td>
            <select id="ret-cohort"><option value="0">-- اختر --</option>
                <?php foreach($cohorts as $c): ?><option value="<?php echo $c->id ?>"><?php echo esc_html($c->name) ?></option><?php endforeach; ?>
            </select>
        </td>
        <th>المدرب / Trainer</th><td>
            <select id="ret-trainer"><option value="0">-- اختر --</option>
                <?php foreach($trainers as $t): ?><option value="<?php echo $t->ID ?>"><?php echo esc_html($t->display_name) ?></option><?php endforeach; ?>
            </select>
        </td></tr>
        <tr><th>ملاحظات</th><td colspan="3"><textarea id="ret-notes" rows="2" class="large-text"></textarea></td></tr>
    </table>
    <div style="margin:12px 0;">
        <strong>الكتب المُعادة / Returned Items:</strong>
        <button type="button" class="button button-small" id="btn-ret-add-row">+ إضافة سطر</button>
    </div>
    <table class="wp-list-table widefat" id="ret-items-table" style="direction:rtl;">
        <thead><tr><th>الكتاب</th><th style="width:80px;">الكمية</th><th style="width:110px;">الحالة</th><th style="width:40px;"></th></tr></thead>
        <tbody id="ret-items-body"></tbody>
    </table>
    <p style="margin-top:16px;">
        <button class="button button-primary" id="btn-save-ret">💾 حفظ</button>
        <button class="button rsyi-modal-close" data-modal="ret-modal" style="margin-right:8px;">إلغاء</button>
    </p>
    <div id="ret-msg" style="display:none;margin-top:8px;"></div>
</div></div>

<!-- PR Modal -->
<div id="pr-modal" class="rsyi-modal-overlay" style="display:none;">
<div class="rsyi-modal-box" style="max-width:750px;max-height:90vh;overflow-y:auto;">
    <h2 id="pr-title" style="margin-top:0;">طلب شراء / Purchase Request</h2>
    <input type="hidden" id="pr-id" value="0">
    <table class="form-table">
        <tr><th>ملاحظات</th><td><textarea id="pr-notes" rows="2" class="large-text"></textarea></td></tr>
    </table>
    <div style="margin:12px 0;">
        <strong>الكتب / Items:</strong>
        <button type="button" class="button button-small" id="btn-pr-add-row">+ إضافة سطر</button>
    </div>
    <table class="wp-list-table widefat" id="pr-items-table" style="direction:rtl;">
        <thead><tr><th>الكتاب</th><th style="width:80px;">الكمية</th><th style="width:160px;">ملاحظة</th><th style="width:40px;"></th></tr></thead>
        <tbody id="pr-items-body"></tbody>
    </table>
    <p style="margin-top:16px;">
        <button class="button button-primary" id="btn-save-pr">💾 حفظ</button>
        <button class="button rsyi-modal-close" data-modal="pr-modal" style="margin-right:8px;">إلغاء</button>
    </p>
    <div id="pr-msg" style="display:none;margin-top:8px;"></div>
</div></div>

<style>
.rsyi-lib-wrap{padding-bottom:40px;}
.rsyi-toolbar{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin:14px 0;}
.rsyi-modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:99999;display:flex;align-items:center;justify-content:center;}
.rsyi-modal-box{background:#fff;border-radius:8px;padding:28px;width:96%;}
.rsyi-stat-card{background:#fff;border-right:5px solid #0073aa;border-radius:6px;padding:16px 20px;min-width:140px;box-shadow:0 1px 4px rgba(0,0,0,.1);}
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
.rsyi-report-table th,.rsyi-report-table td{padding:6px 10px;border:1px solid #ddd;text-align:right;}
.rsyi-report-table thead{background:#f1f1f1;}
.rsyi-report-table tr.stock-ok td{background:#f0fff0;}
.rsyi-report-table tr.stock-low td{background:#fff8e1;}
.rsyi-report-table tr.stock-zero td{background:#ffe8e8;}
</style>

<script>
(function($){
    var nonce=rsyiSA.nonce, ajaxUrl=rsyiSA.ajaxUrl;
    var booksCache=[], suppliersCache=[];
    var canManage=<?php echo $lib_can_manage?'true':'false'; ?>;
    var canApproveWd=<?php echo $lib_can_approve_wd?'true':'false'; ?>;
    var canApprovePr=<?php echo $lib_can_approve_pr?'true':'false'; ?>;

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
    function post(action,data,cb){ $.post(ajaxUrl,$.extend({action:action,nonce:nonce},data),cb); }
    function msg(el,text,ok){ $(el).show().text(text).css('color',ok?'green':'red'); }
    function openModal(id){ $('#'+id).css('display','flex'); }
    function statusBadge(s){
        var map={draft:'مسودة',pending:'في الانتظار',approved:'معتمد',completed:'مكتمل',rejected:'مرفوض'};
        return '<span class="status-'+(s||'')+'">'+( map[s]||s )+'</span>';
    }
    function buildBookSelect(val){
        var html='<option value="">-- اختر كتاباً --</option>';
        booksCache.forEach(function(b){ html+='<option value="'+b.id+'" data-stock="'+b.current_stock+'">'+(b.title_ar||b.title_en)+' (رصيد: '+b.current_stock+')</option>'; });
        var s=$('<select class="book-sel" style="width:100%;">').html(html);
        if(val) s.val(val);
        return s;
    }
    function calcAoTotal(){
        var total=0;
        $('#ao-items-body tr').each(function(){
            var qty=parseFloat($(this).find('.ao-qty').val())||0;
            var price=parseFloat($(this).find('.ao-price').val())||0;
            total+=qty*price;
        });
        var taxOn=$('input[name=ao_tax]:checked').val()==='1';
        if(taxOn){ var rate=parseFloat($('#ao-tax-rate').val())||0; total*=(1+rate/100); }
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
                {num:d.total_books,lbl:'إجمالي الكتب / Total Books',cls:''},
                {num:d.total_stock,lbl:'إجمالي الرصيد / Total Stock',cls:'success'},
                {num:d.low_stock,lbl:'على وشك النفاد / Low Stock',cls:'warn'},
                {num:d.zero_stock,lbl:'رصيد صفري / Zero Stock',cls:'danger'},
                {num:d.pending_wd,lbl:'إذن صرف منتظر / Pending WD',cls:'warn'},
                {num:d.pending_pr,lbl:'طلب شراء منتظر / Pending PR',cls:'warn'},
                {num:d.today_adds,lbl:'استلام اليوم / Today Adds',cls:''},
                {num:d.today_withdrawals,lbl:'صرف اليوم / Today WD',cls:''},
            ];
            var html='';
            cards.forEach(function(c){ html+='<div class="rsyi-stat-card '+c.cls+'"><div class="num">'+c.num+'</div><div class="lbl">'+c.lbl+'</div></div>'; });
            $('#lib-stat-cards').html(html);
            // Low stock table
            if(d.low_stock_books&&d.low_stock_books.length){
                var t='<table class="wp-list-table widefat striped" style="direction:rtl;max-width:700px;"><thead><tr><th>الكتاب</th><th>الرصيد</th><th>الحد الأدنى</th></tr></thead><tbody>';
                d.low_stock_books.forEach(function(b){ t+='<tr><td>'+(b.title_ar||b.title_en)+'</td><td style="color:red;">'+b.current_stock+'</td><td>'+b.min_stock+'</td></tr>'; });
                t+='</tbody></table>';
                $('#lib-low-table-wrap').html(t);
            } else { $('#lib-low-table-wrap').html('<p style="color:green;">لا توجد كتب ناقصة / No low stock books ✓</p>'); }
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
            if(!r.data.length) html='<tr><td colspan="8" style="text-align:center">لا توجد كتب</td></tr>';
            r.data.forEach(function(b){
                var cover=b.cover_url?'<img src="'+b.cover_url+'" style="width:44px;height:56px;object-fit:cover;border-radius:3px;">':'—';
                var sc=(b.current_stock===0)?'style="color:red;"':(b.current_stock<=b.min_stock&&b.min_stock>0?'style="color:orange;"':'');
                html+='<tr><td>'+cover+'</td><td><strong>'+(b.title_ar||'')+'</strong><br><small>'+(b.title_en||'')+'</small></td>'+
                    '<td>'+(b.subject||'—')+'</td><td>'+(b.grade_level||'—')+'</td><td>'+(b.isbn||'—')+'</td>'+
                    '<td '+sc+'>'+b.current_stock+'</td><td>'+(b.min_stock||0)+'</td><td>'+
                    (canManage?'<button class="button button-small btn-edit-book" data-b=\''+JSON.stringify(b)+'\'>تعديل</button> '+
                    '<button class="button button-small btn-del-book" data-id="'+b.id+'">حذف</button>':'—')+'</td></tr>';
            });
            $('#books-list').html(html);
            booksCache=r.data;
        });
    }
    $('#btn-search-books,#filter-cat').on('click change',loadBooks);

    $('#btn-add-book').on('click',function(){
        $('#bm-title').text('إضافة كتاب / Add Book');$('#bm-id').val(0);
        $('#bm-title-ar,#bm-title-en,#bm-author,#bm-publisher,#bm-subject,#bm-grade,#bm-isbn,#bm-desc').val('');
        $('#bm-min-stock,#bm-price').val(0);$('#bm-cat').val('curriculum');$('#bm-lang').val('en');$('#bm-unit').val('copy');
        $('#bm-cover-id').val(0);$('#bm-cover-preview').html('');$('#btn-bm-cover-clear').hide();$('#bm-msg').hide();
        openModal('book-modal');
    });
    $(document).on('click','.btn-edit-book',function(){
        var b=$(this).data('b');
        $('#bm-title').text('تعديل كتاب / Edit Book');$('#bm-id').val(b.id);
        $('#bm-title-ar').val(b.title_ar);$('#bm-title-en').val(b.title_en);$('#bm-author').val(b.author||'');
        $('#bm-publisher').val(b.publisher||'');$('#bm-subject').val(b.subject||'');$('#bm-grade').val(b.grade_level||'');
        $('#bm-isbn').val(b.isbn||'');$('#bm-desc').val(b.description||'');$('#bm-min-stock').val(b.min_stock||0);
        $('#bm-price').val(b.price||0);$('#bm-cat').val(b.category||'general');$('#bm-lang').val(b.language||'en');
        $('#bm-unit').val(b.unit||'copy');$('#bm-cover-id').val(b.cover_image_id||0);
        if(b.cover_url){$('#bm-cover-preview').html('<img src="'+b.cover_url+'" style="max-width:80px;">');$('#btn-bm-cover-clear').show();}
        else{$('#bm-cover-preview').html('');$('#btn-bm-cover-clear').hide();}
        $('#bm-msg').hide(); openModal('book-modal');
    });
    $(document).on('click','.btn-del-book',function(){
        if(!confirm('حذف الكتاب؟')) return;
        post('rsyi_delete_book',{book_id:$(this).data('id')},function(r){ alert(r.data.message); if(r.success) loadBooks(); });
    });
    $('#btn-save-book').on('click',function(){
        var data={book_id:$('#bm-id').val(),title_ar:$('#bm-title-ar').val().trim(),title_en:$('#bm-title-en').val().trim(),
            author:$('#bm-author').val(),publisher:$('#bm-publisher').val(),subject:$('#bm-subject').val(),
            grade_level:$('#bm-grade').val(),isbn:$('#bm-isbn').val(),description:$('#bm-desc').val(),
            min_stock:$('#bm-min-stock').val(),price:$('#bm-price').val(),
            category:$('#bm-cat').val(),book_language:$('#bm-lang').val(),unit:$('#bm-unit').val(),cover_image_id:$('#bm-cover-id').val()};
        if(!data.title_ar||!data.title_en){alert('العنوان مطلوب');return;}
        post('rsyi_save_book',data,function(r){ msg('#bm-msg',r.data.message,r.success); if(r.success){setTimeout(function(){$('#book-modal').hide();loadBooks();loadBooksCache();},700);} });
    });
    var bmUploader;
    $('#btn-bm-cover').on('click',function(){
        if(!bmUploader){ bmUploader=wp.media({title:'اختر صورة',button:{text:'اختر'},multiple:false}); bmUploader.on('select',function(){ var a=bmUploader.state().get('selection').first().toJSON(); $('#bm-cover-id').val(a.id);$('#bm-cover-preview').html('<img src="'+a.url+'" style="max-width:80px;">');$('#btn-bm-cover-clear').show(); }); }
        bmUploader.open();
    });
    $('#btn-bm-cover-clear').on('click',function(){ $('#bm-cover-id').val(0);$('#bm-cover-preview').html('');$(this).hide(); });

    // ═══ SUPPLIERS ════════════════════════════════════════════════════════════
    function loadSuppliers(){
        post('rsyi_lib_get_suppliers',{},function(r){
            if(!r.success) return;
            suppliersCache=r.data;
            var html='';
            if(!r.data.length) html='<tr><td colspan="5" style="text-align:center">لا يوجد موردون</td></tr>';
            r.data.forEach(function(s){
                html+='<tr><td>'+s.name+'</td><td>'+(s.phone||'—')+'</td><td>'+(s.email||'—')+'</td><td>'+(s.address||'—')+'</td><td>'+
                    (canManage?'<button class="button button-small btn-edit-sup" data-s=\''+JSON.stringify(s)+'\'>تعديل</button> '+
                    '<button class="button button-small btn-del-sup" data-id="'+s.id+'">حذف</button>':'—')+'</td></tr>';
            });
            $('#suppliers-list').html(html);
            // Also update add-order supplier dropdown
            var sopts='<option value="0">-- بدون مورد --</option>';
            r.data.forEach(function(s){ sopts+='<option value="'+s.id+'">'+s.name+'</option>'; });
            $('#ao-supplier').html(sopts);
        });
    }
    $('#btn-save-supplier').on('click',function(){
        var data={supplier_id:$('#sup-id').val(),name:$('#sup-name').val().trim(),phone:$('#sup-phone').val(),email:$('#sup-email').val(),address:$('#sup-addr').val()};
        if(!data.name){alert('الاسم مطلوب');return;}
        post('rsyi_lib_save_supplier',data,function(r){ msg('#sup-msg',r.data.message,r.success); if(r.success){loadSuppliers();$('#sup-id').val(0);$('#sup-name,#sup-phone,#sup-email,#sup-addr').val('');$('#sup-form-title').text('إضافة مورد');$('#btn-cancel-sup').hide();} });
    });
    $(document).on('click','.btn-edit-sup',function(){
        var s=$(this).data('s'); $('#sup-id').val(s.id);$('#sup-name').val(s.name);$('#sup-phone').val(s.phone||'');
        $('#sup-email').val(s.email||'');$('#sup-addr').val(s.address||'');$('#sup-form-title').text('تعديل مورد');$('#btn-cancel-sup').show();
        $('html,body').animate({scrollTop:$('#sup-form-title').offset().top-100},300);
    });
    $(document).on('click','.btn-del-sup',function(){
        if(!confirm('حذف المورد؟')) return;
        post('rsyi_lib_delete_supplier',{supplier_id:$(this).data('id')},function(r){ alert(r.data.message); if(r.success) loadSuppliers(); });
    });
    $('#btn-cancel-sup').on('click',function(){ $('#sup-id').val(0);$('#sup-name,#sup-phone,#sup-email,#sup-addr').val('');$('#sup-form-title').text('إضافة مورد');$(this).hide(); });

    // ═══ ADD ORDERS ═══════════════════════════════════════════════════════════
    function loadAddOrders(){
        post('rsyi_lib_get_add_orders',{},function(r){
            if(!r.success) return;
            var html='';
            if(!r.data.length) html='<tr><td colspan="6" style="text-align:center">لا توجد أذون</td></tr>';
            r.data.forEach(function(o){
                html+='<tr><td>'+o.order_number+'</td><td>'+(o.supplier_name||'—')+'</td><td>'+(o.created_at||'').substr(0,10)+'</td>'+
                    '<td>'+o.total_quantity+'</td><td>'+parseFloat(o.total_value||0).toFixed(2)+'</td><td>'+
                    (canManage?'<button class="button button-small btn-edit-ao" data-id="'+o.id+'">تعديل</button> '+
                    '<button class="button button-small btn-del-ao" data-id="'+o.id+'">حذف</button> ':'')+
                    '<button class="button button-small btn-print-ao" data-id="'+o.id+'">🖨</button></td></tr>';
            });
            $('#add-orders-list').html(html);
        });
    }
    function openAddOrderModal(id){
        $('#ao-title').text(id?'تعديل إذن الاستلام':'إذن استلام جديد');$('#ao-id').val(id||0);
        $('#ao-items-body').html('');$('#ao-notes').val('');$('#ao-total').text('0.00');$('#ao-msg').hide();
        $('input[name=ao_tax][value=0]').prop('checked',true);$('#ao-tax-rate-wrap').hide();
        if(id){
            post('rsyi_lib_get_add_order',{order_id:id},function(r){
                if(!r.success) return;
                var o=r.data; $('#ao-supplier').val(o.supplier_id||0); $('#ao-notes').val(o.notes||'');
                if(o.tax_enabled){$('input[name=ao_tax][value=1]').prop('checked',true);$('#ao-tax-rate').val(o.tax_rate||14);$('#ao-tax-rate-wrap').show();}
                (o.items||[]).forEach(function(i){ addAoRow(i.book_id,i.quantity,i.unit_price); });
                calcAoTotal();
            });
        } else { addAoRow(); }
        openModal('add-order-modal');
    }
    function addAoRow(bid,qty,price){
        var sel=buildBookSelect(bid);
        var row=$('<tr>').append(
            $('<td>').append(sel),
            $('<td>').append($('<input type="number" class="ao-qty" value="'+(qty||1)+'" min="1" style="width:70px;">')),
            $('<td>').append($('<input type="number" class="ao-price" value="'+(price||0)+'" min="0" step="0.01" style="width:90px;">')),
            $('<td>').append($('<button type="button" class="button button-small ao-del-row">×</button>'))
        );
        row.find('.ao-qty,.ao-price').on('input',calcAoTotal);
        row.find('.ao-del-row').on('click',function(){ $(this).closest('tr').remove(); calcAoTotal(); });
        $('#ao-items-body').append(row);
    }
    $('#btn-new-add-order').on('click',function(){ openAddOrderModal(null); });
    $(document).on('click','.btn-edit-ao',function(){ openAddOrderModal($(this).data('id')); });
    $(document).on('click','.btn-del-ao',function(){
        if(!confirm('حذف إذن الاستلام وعكس جميع الحركات؟')) return;
        post('rsyi_lib_delete_add_order',{order_id:$(this).data('id')},function(r){ alert(r.data.message); if(r.success) loadAddOrders(); });
    });
    $('#btn-ao-add-row').on('click',function(){ addAoRow(); });
    $('input[name=ao_tax]').on('change',function(){ $('#ao-tax-rate-wrap').toggle($(this).val()==='1'); calcAoTotal(); });
    $('#ao-tax-rate').on('input',calcAoTotal);
    $('#btn-save-add-order').on('click',function(){
        var items=[];
        $('#ao-items-body tr').each(function(){
            var bid=$(this).find('.book-sel').val();
            if(bid) items.push({book_id:bid,quantity:$(this).find('.ao-qty').val(),unit_price:$(this).find('.ao-price').val()});
        });
        if(!items.length){alert('أضف كتاباً على الأقل');return;}
        post('rsyi_lib_save_add_order',{order_id:$('#ao-id').val(),supplier_id:$('#ao-supplier').val(),
            tax_enabled:$('input[name=ao_tax]:checked').val(),tax_rate:$('#ao-tax-rate').val(),
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
            if(!r.data.length) html='<tr><td colspan="7" style="text-align:center">لا توجد أذون</td></tr>';
            r.data.forEach(function(o){
                var who=(o.cohort_name||'')+(o.cohort_name&&o.trainer_id?' / ':'')+(o.trainer_id?'مدرب#'+o.trainer_id:'');
                var acts='';
                if(canManage&&(o.status==='draft')){
                    acts+='<button class="button button-small btn-edit-wd" data-id="'+o.id+'">تعديل</button> '+
                          '<button class="button button-small btn-submit-wd" data-id="'+o.id+'">تقديم</button> '+
                          '<button class="button button-small btn-del-wd" data-id="'+o.id+'">حذف</button> ';
                }
                if(canApproveWd&&o.status==='pending'){
                    acts+='<button class="button button-small btn-approve-wd" data-id="'+o.id+'" style="color:green;">اعتماد</button> '+
                          '<button class="button button-small btn-reject-wd" data-id="'+o.id+'" style="color:red;">رفض</button> ';
                }
                if(canManage&&o.status==='approved'){
                    acts+='<button class="button button-small btn-complete-wd" data-id="'+o.id+'" style="color:blue;">إكمال الصرف</button> ';
                }
                if(['approved','completed'].includes(o.status)){
                    acts+='<button class="button button-small btn-print-wd" data-id="'+o.id+'">🖨</button>';
                }
                html+='<tr><td>'+o.order_number+'</td><td>'+who+'</td><td>'+(o.order_type==='custody'?'عهدة':'عادي')+'</td>'+
                    '<td>'+statusBadge(o.status)+'</td><td>'+(o.created_by_name||'')+'</td>'+
                    '<td>'+(o.created_at||'').substr(0,10)+'</td><td>'+acts+'</td></tr>';
            });
            $('#wd-orders-list').html(html);
        });
    }
    $('#btn-filter-wd').on('click',loadWdOrders);$('#wd-filter-status').on('change',loadWdOrders);

    function openWdModal(id){
        $('#wd-title').text(id?'تعديل إذن الصرف':'إذن صرف جديد');$('#wd-id').val(id||0);
        $('#wd-items-body').html('');$('#wd-notes').val('');$('#wd-msg').hide();
        $('#wd-cohort,#wd-trainer').val(0);$('#wd-type').val('normal');
        if(id){
            post('rsyi_lib_get_wd_order',{order_id:id},function(r){
                if(!r.success) return;
                var o=r.data; $('#wd-cohort').val(o.cohort_id||0);$('#wd-trainer').val(o.trainer_id||0);
                $('#wd-type').val(o.order_type||'normal');$('#wd-notes').val(o.notes||'');
                (o.items||[]).forEach(function(i){ addWdRow(i.book_id,i.quantity,i.real_stock); });
            });
        } else { addWdRow(); }
        openModal('wd-modal');
    }
    function addWdRow(bid,qty,stock){
        var sel=buildBookSelect(bid);
        var stockTxt=stock!==undefined?stock:'?';
        var row=$('<tr>').append(
            $('<td>').append(sel),
            $('<td>').append($('<input type="number" class="wd-qty" value="'+(qty||1)+'" min="1" style="width:70px;">')),
            $('<td class="wd-stock-cell">').text(stockTxt),
            $('<td>').append($('<button type="button" class="button button-small wd-del-row">×</button>'))
        );
        sel.on('change',function(){ row.find('.wd-stock-cell').text($(this).find(':selected').data('stock')||0); });
        row.find('.wd-del-row').on('click',function(){ $(this).closest('tr').remove(); });
        $('#wd-items-body').append(row);
    }
    $('#btn-new-wd-order').on('click',function(){ openWdModal(null); });
    $(document).on('click','.btn-edit-wd',function(){ openWdModal($(this).data('id')); });
    function getWdItems(){ var items=[]; $('#wd-items-body tr').each(function(){ var bid=$(this).find('.book-sel').val(); if(bid) items.push({book_id:bid,quantity:$(this).find('.wd-qty').val()}); }); return items; }
    $('#btn-wd-add-row').on('click',function(){ addWdRow(); });
    $('#btn-save-wd-draft').on('click',function(){
        var items=getWdItems(); if(!items.length){alert('أضف كتاباً');return;}
        post('rsyi_lib_save_wd_order',{order_id:$('#wd-id').val(),cohort_id:$('#wd-cohort').val(),trainer_id:$('#wd-trainer').val(),
            order_type:$('#wd-type').val(),notes:$('#wd-notes').val(),items:JSON.stringify(items)},function(r){
            msg('#wd-msg',r.data.message,r.success); if(r.success){setTimeout(function(){$('#wd-modal').hide();loadWdOrders();},700);}
        });
    });
    $('#btn-submit-wd').on('click',function(){
        var items=getWdItems(); if(!items.length){alert('أضف كتاباً');return;}
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
        if(!confirm('تقديم الإذن للاعتماد؟')) return;
        post('rsyi_lib_submit_wd_order',{order_id:$(this).data('id')},function(r){ alert(r.data.message); if(r.success) loadWdOrders(); });
    });
    $(document).on('click','.btn-approve-wd',function(){
        if(!confirm('اعتماد إذن الصرف؟')) return;
        post('rsyi_lib_approve_wd_order',{order_id:$(this).data('id')},function(r){ alert(r.data.message); if(r.success) loadWdOrders(); });
    });
    $(document).on('click','.btn-reject-wd',function(){
        if(!confirm('رفض إذن الصرف؟')) return;
        post('rsyi_lib_reject_wd_order',{order_id:$(this).data('id')},function(r){ alert(r.data.message); if(r.success) loadWdOrders(); });
    });
    $(document).on('click','.btn-complete-wd',function(){
        if(!confirm('تأكيد تنفيذ الصرف الفعلي؟ سيُخصم من المخزون.')) return;
        post('rsyi_lib_complete_wd_order',{order_id:$(this).data('id')},function(r){ alert(r.data.message); if(r.success){loadWdOrders();loadBooksCache();loadDashboard();} });
    });
    $(document).on('click','.btn-del-wd',function(){
        if(!confirm('حذف إذن الصرف؟')) return;
        post('rsyi_lib_delete_wd_order',{order_id:$(this).data('id')},function(r){ alert(r.data.message); if(r.success) loadWdOrders(); });
    });
    $(document).on('click','.btn-print-wd',function(){ printOrder('withdrawal',$(this).data('id')); });

    // ═══ RETURN ORDERS ════════════════════════════════════════════════════════
    function loadRetOrders(){
        post('rsyi_lib_get_ret_orders',{},function(r){
            if(!r.success) return;
            var html='';
            if(!r.data.length) html='<tr><td colspan="5" style="text-align:center">لا توجد أذون</td></tr>';
            r.data.forEach(function(o){
                var who=(o.cohort_name||'')+(o.trainer_id?'مدرب#'+o.trainer_id:'');
                html+='<tr><td>'+o.order_number+'</td><td>'+who+'</td><td>'+statusBadge(o.status==='pending'?'pending-r':o.status)+'</td>'+
                    '<td>'+(o.created_at||'').substr(0,10)+'</td><td>'+
                    (canManage&&o.status==='pending'?'<button class="button button-small btn-edit-ret" data-id="'+o.id+'">تعديل</button> '+
                    '<button class="button button-small btn-complete-ret" data-id="'+o.id+'" style="color:green;">إتمام الرد</button> ':'')+
                    (canManage?'<button class="button button-small btn-del-ret" data-id="'+o.id+'">حذف</button>':'')+'</td></tr>';
            });
            $('#ret-orders-list').html(html);
        });
    }
    function openRetModal(id){
        $('#ret-title').text(id?'تعديل إذن الرد':'إذن رد جديد');$('#ret-id').val(id||0);
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
        var condSel=$('<select class="ret-cond" style="width:100%;"><option value="good">جيدة / Good</option><option value="damaged">تالفة / Damaged</option></select>');
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
        if(!items.length){alert('أضف كتاباً');return;}
        post('rsyi_lib_save_ret_order',{order_id:$('#ret-id').val(),cohort_id:$('#ret-cohort').val(),trainer_id:$('#ret-trainer').val(),
            notes:$('#ret-notes').val(),items:JSON.stringify(items)},function(r){
            msg('#ret-msg',r.data.message,r.success); if(r.success){setTimeout(function(){$('#ret-modal').hide();loadRetOrders();},700);}
        });
    });
    $(document).on('click','.btn-complete-ret',function(){
        if(!confirm('إتمام عملية الرد وإضافة الكتب للمخزون؟')) return;
        post('rsyi_lib_complete_ret_order',{order_id:$(this).data('id')},function(r){ alert(r.data.message); if(r.success){loadRetOrders();loadBooksCache();loadDashboard();} });
    });
    $(document).on('click','.btn-del-ret',function(){
        if(!confirm('حذف إذن الرد؟')) return;
        post('rsyi_lib_delete_ret_order',{order_id:$(this).data('id')},function(r){ alert(r.data.message); if(r.success) loadRetOrders(); });
    });

    // ═══ PURCHASE REQUESTS ════════════════════════════════════════════════════
    function loadPrList(){
        post('rsyi_lib_get_pr_list',{},function(r){
            if(!r.success) return;
            var html='';
            if(!r.data.length) html='<tr><td colspan="5" style="text-align:center">لا توجد طلبات</td></tr>';
            r.data.forEach(function(p){
                var acts='';
                if(canManage&&p.status==='pending') acts+='<button class="button button-small btn-edit-pr" data-id="'+p.id+'">تعديل</button> ';
                if(canApprovePr&&p.status==='pending'){
                    acts+='<button class="button button-small btn-approve-pr" data-id="'+p.id+'" style="color:green;">اعتماد</button> '+
                          '<button class="button button-small btn-reject-pr" data-id="'+p.id+'" style="color:red;">رفض</button> ';
                }
                if(canManage&&p.status==='approved') acts+='<button class="button button-small btn-convert-pr" data-id="'+p.id+'" style="color:blue;">تحويل لإذن استلام</button> ';
                if(canManage&&['pending','rejected'].includes(p.status)) acts+='<button class="button button-small btn-del-pr" data-id="'+p.id+'">حذف</button>';
                html+='<tr><td>'+p.request_number+'</td><td>'+statusBadge(p.status)+'</td><td>'+(p.requested_by_name||'')+'</td>'+
                    '<td>'+(p.created_at||'').substr(0,10)+'</td><td>'+acts+'</td></tr>';
            });
            $('#pr-list').html(html);
        });
    }
    function openPrModal(id){
        $('#pr-title').text(id?'تعديل طلب الشراء':'طلب شراء جديد');$('#pr-id').val(id||0);
        $('#pr-items-body').html('');$('#pr-notes').val('');$('#pr-msg').hide();
        if(id){
            post('rsyi_lib_get_pr',{pr_id:id},function(r){
                if(!r.success) return;
                $('#pr-notes').val(r.data.notes||'');
                (r.data.items||[]).forEach(function(i){ addPrRow(i.book_id,i.quantity,i.notes); });
            });
        } else { addPrRow(); }
        openModal('pr-modal');
    }
    function addPrRow(bid,qty,notes){
        var sel=buildBookSelect(bid);
        var row=$('<tr>').append(
            $('<td>').append(sel),
            $('<td>').append($('<input type="number" class="pr-qty" value="'+(qty||1)+'" min="1" style="width:70px;">')),
            $('<td>').append($('<input type="text" class="pr-notes-r regular-text" value="'+(notes||'')+'">')),
            $('<td>').append($('<button type="button" class="button button-small pr-del-row">×</button>'))
        );
        row.find('.pr-del-row').on('click',function(){ $(this).closest('tr').remove(); });
        $('#pr-items-body').append(row);
    }
    $('#btn-new-pr').on('click',function(){ openPrModal(null); });
    $(document).on('click','.btn-edit-pr',function(){ openPrModal($(this).data('id')); });
    $('#btn-pr-add-row').on('click',function(){ addPrRow(); });
    $('#btn-save-pr').on('click',function(){
        var items=[]; $('#pr-items-body tr').each(function(){ var bid=$(this).find('.book-sel').val(); if(bid) items.push({book_id:bid,quantity:$(this).find('.pr-qty').val(),notes:$(this).find('.pr-notes-r').val()}); });
        if(!items.length){alert('أضف كتاباً');return;}
        post('rsyi_lib_save_pr',{pr_id:$('#pr-id').val(),notes:$('#pr-notes').val(),items:JSON.stringify(items)},function(r){
            msg('#pr-msg',r.data.message,r.success); if(r.success){setTimeout(function(){$('#pr-modal').hide();loadPrList();},700);}
        });
    });
    $(document).on('click','.btn-approve-pr',function(){
        if(!confirm('اعتماد طلب الشراء؟')) return;
        post('rsyi_lib_approve_pr',{pr_id:$(this).data('id')},function(r){ alert(r.data.message); if(r.success) loadPrList(); });
    });
    $(document).on('click','.btn-reject-pr',function(){
        if(!confirm('رفض طلب الشراء؟')) return;
        post('rsyi_lib_reject_pr',{pr_id:$(this).data('id')},function(r){ alert(r.data.message); if(r.success) loadPrList(); });
    });
    $(document).on('click','.btn-convert-pr',function(){
        if(!confirm('تحويل الطلب لإذن استلام؟')) return;
        post('rsyi_lib_convert_pr_to_add',{pr_id:$(this).data('id')},function(r){ alert(r.data.message); if(r.success){loadPrList();loadAddOrders();loadDashboard();} });
    });
    $(document).on('click','.btn-del-pr',function(){
        if(!confirm('حذف طلب الشراء؟')) return;
        post('rsyi_lib_delete_pr',{pr_id:$(this).data('id')},function(r){ alert(r.data.message); if(r.success) loadPrList(); });
    });

    // ═══ OPENING BALANCES ════════════════════════════════════════════════════
    function loadOpening(){
        post('rsyi_lib_get_opening',{},function(r){
            if(!r.success) return;
            var html='';
            if(!r.data.length) html='<tr><td colspan="4" style="text-align:center">لا توجد أرصدة</td></tr>';
            r.data.forEach(function(o){ html+='<tr><td>'+(o.title_ar||'')+'<br><small>'+(o.title_en||'')+'</small></td><td>'+o.quantity+'</td><td>'+parseFloat(o.unit_price||0).toFixed(2)+'</td><td>'+(o.created_at||'').substr(0,10)+'</td></tr>'; });
            $('#opening-list').html(html);
            // populate ob-book select
            var opts='<option value="">-- اختر كتاباً --</option>';
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
            html='<select id="rf-cat"><option value="">كل التصنيفات</option><option value="curriculum">مناهج</option><option value="certificate">شهادات</option><option value="general">عام</option></select>'+
                 '<button class="button" id="btn-run-report">تشغيل</button>';
        } else if(rpt==='movement'){
            var bookOpts='<option value="">-- اختر كتاباً --</option>';
            booksCache.forEach(function(b){ bookOpts+='<option value="'+b.id+'">'+(b.title_ar||b.title_en)+'</option>'; });
            html='<select id="rf-book">'+bookOpts+'</select>'+
                 '<input type="date" id="rf-from"><input type="date" id="rf-to"><button class="button" id="btn-run-report">تشغيل</button>';
        } else if(rpt==='cohort_withdrawal'){
            var cOpts='<option value="0">كل المجموعات</option>';
            <?php foreach($cohorts as $c): ?>
            cOpts+='<option value="<?php echo $c->id ?>"><?php echo esc_js($c->name) ?></option>';
            <?php endforeach; ?>
            html='<select id="rf-cohort">'+cOpts+'</select>'+
                 '<input type="date" id="rf-from"><input type="date" id="rf-to"><button class="button" id="btn-run-report">تشغيل</button>';
        } else if(rpt==='supplier'){
            var sOpts='<option value="0">كل الموردين</option>';
            suppliersCache.forEach(function(s){ sOpts+='<option value="'+s.id+'">'+s.name+'</option>'; });
            html='<select id="rf-sup">'+sOpts+'</select><button class="button" id="btn-run-report">تشغيل</button>';
        } else {
            html='<button class="button" id="btn-run-report">تشغيل</button>';
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
        if(!data.length){ $('#report-output').html('<p>لا توجد بيانات / No data</p>'); return; }
        if(rpt==='stock'){
            html+='<thead><tr><th>العنوان</th><th>المادة</th><th>المستوى</th><th>ISBN</th><th>التصنيف</th><th>الرصيد</th><th>الحد الأدنى</th></tr></thead><tbody>';
            data.forEach(function(b){ html+='<tr class="stock-'+b.stock_status+'"><td>'+b.title_ar+'<br><small>'+b.title_en+'</small></td><td>'+(b.subject||'—')+'</td><td>'+(b.grade_level||'—')+'</td><td>'+(b.isbn||'—')+'</td><td>'+b.category+'</td><td><strong>'+b.current_stock+'</strong></td><td>'+b.min_stock+'</td></tr>'; });
        } else if(rpt==='movement'){
            html+='<thead><tr><th>التاريخ</th><th>النوع</th><th>الكمية</th><th>الرصيد التراكمي</th><th>بواسطة</th></tr></thead><tbody>';
            data.forEach(function(t){ var qty=parseInt(t.quantity); html+='<tr><td>'+(t.created_at||'').substr(0,16)+'</td><td>'+t.transaction_type+'</td><td style="color:'+(qty>0?'green':'red')+';">'+(qty>0?'+':'')+qty+'</td><td>'+t.running_balance+'</td><td>'+(t.created_by_name||'')+'</td></tr>'; });
        } else if(rpt==='low_stock'||rpt==='zero_stock'){
            html+='<thead><tr><th>العنوان</th><th>المادة</th><th>المستوى</th><th>الرصيد</th><th>الحد الأدنى</th>'+(rpt==='low_stock'?'<th>النقص</th>':'')+'</tr></thead><tbody>';
            data.forEach(function(b){ html+='<tr><td>'+b.title_ar+'<br><small>'+b.title_en+'</small></td><td>'+(b.subject||'—')+'</td><td>'+(b.grade_level||'—')+'</td><td style="color:red;"><strong>'+b.current_stock+'</strong></td><td>'+b.min_stock+'</td>'+(rpt==='low_stock'?'<td style="color:orange;">'+b.shortage+'</td>':'')+'</tr>'; });
        } else if(rpt==='cohort_withdrawal'){
            html+='<thead><tr><th>رقم الإذن</th><th>المجموعة</th><th>الكتاب</th><th>الكمية</th><th>التاريخ</th></tr></thead><tbody>';
            data.forEach(function(r){ html+='<tr><td>'+r.order_number+'</td><td>'+(r.cohort_name||'—')+'</td><td>'+r.title_ar+'<br><small>'+r.title_en+'</small></td><td>'+r.quantity+'</td><td>'+(r.created_at||'').substr(0,10)+'</td></tr>'; });
        } else if(rpt==='supplier'){
            html+='<thead><tr><th>المورد</th><th>رقم الإذن</th><th>التاريخ</th><th>الكمية</th><th>القيمة</th></tr></thead><tbody>';
            data.forEach(function(r){ html+='<tr><td>'+(r.supplier_name||'—')+'</td><td>'+r.order_number+'</td><td>'+(r.created_at||'').substr(0,10)+'</td><td>'+r.total_quantity+'</td><td>'+parseFloat(r.total_value||0).toFixed(2)+'</td></tr>'; });
        }
        html+='</tbody></table>';
        $('#report-output').html(html);
    }

    // ═══ PRINT ════════════════════════════════════════════════════════════════
    function printOrder(type,id){
        post('rsyi_lib_get_print_data',{type:type,order_id:id},function(r){
            if(!r.success){ alert('خطأ في جلب البيانات'); return; }
            var d=r.data, o=d.order, items=d.items||[];
            var logo=d.logo?'<img src="'+d.logo+'" style="max-height:70px;">':'';
            var rows=''; var total=0;
            items.forEach(function(i){ var qty=parseInt(i.quantity||0); var price=parseFloat(i.unit_price||0); rows+='<tr><td>'+(i.title_ar||i.title_en||'')+'</td><td>'+(i.isbn||'—')+'</td><td>'+qty+'</td><td>'+price.toFixed(2)+'</td><td>'+(qty*price).toFixed(2)+'</td></tr>'; total+=qty*price; });
            var html='<!DOCTYPE html><html dir="rtl"><head><meta charset="UTF-8"><title>طباعة</title>'+
                '<style>body{font-family:Arial,sans-serif;direction:rtl;padding:20px;}'+
                'table{width:100%;border-collapse:collapse;}th,td{border:1px solid #999;padding:6px 10px;text-align:right;}'+
                'thead{background:#eee;}.sig{display:inline-block;width:200px;border-top:1px solid #333;margin-top:60px;text-align:center;margin-left:40px;}</style></head><body>'+
                '<div style="text-align:center;margin-bottom:20px;">'+logo+'<h2 style="margin:6px 0;">'+d.institute_name+'</h2><h3>'+(type==='add'?'إذن استلام':type==='withdrawal'?'إذن صرف':'إذن رد')+'</h3></div>'+
                '<table style="margin-bottom:16px;border:none;"><tr><td style="border:none;"><strong>رقم الإذن:</strong> '+o.order_number+'</td><td style="border:none;"><strong>التاريخ:</strong> '+(o.created_at||'').substr(0,10)+'</td></tr>'+
                (o.supplier_name?'<tr><td colspan="2" style="border:none;"><strong>المورد:</strong> '+o.supplier_name+'</td></tr>':'')+
                (o.cohort_name?'<tr><td colspan="2" style="border:none;"><strong>المجموعة:</strong> '+o.cohort_name+'</td></tr>':'')+
                (o.notes?'<tr><td colspan="2" style="border:none;"><strong>ملاحظات:</strong> '+o.notes+'</td></tr>':'')+'</table>'+
                '<table><thead><tr><th>الكتاب</th><th>ISBN</th><th>الكمية</th><th>السعر</th><th>الإجمالي</th></tr></thead><tbody>'+rows+
                '<tr><td colspan="4" style="text-align:left;"><strong>الإجمالي</strong></td><td><strong>'+total.toFixed(2)+'</strong></td></tr>'+
                '</tbody></table>'+
                '<div style="margin-top:40px;display:flex;justify-content:space-around;">'+
                '<div class="sig">مدير المخازن<br>Warehouse Manager</div>'+
                (o.approved_by_name?'<div class="sig">المعتمد: '+o.approved_by_name+'<br>Approved By</div>':'<div class="sig">المعتمد<br>Approved By</div>')+
                '</div>'+
                '</body></html>';
            var win=window.open('','_blank'); win.document.write(html); win.document.close(); win.print();
        });
    }

    // ── INIT ─────────────────────────────────────────────────────────────────
    $(document).ready(function(){
        loadDashboard();
        loadBooksCache();
        tabLoaded['dashboard']=true;
    });

})(jQuery);
</script>
