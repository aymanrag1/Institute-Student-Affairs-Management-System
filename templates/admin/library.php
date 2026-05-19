<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap rsyi-wrap" id="rsyi-library-page" dir="rtl">
    <h1 class="wp-heading-inline">📚 مخزن الكتب / Book Library</h1>
    <hr class="wp-header-end">

    <nav class="nav-tab-wrapper rsyi-tabs">
        <a href="#tab-books"  class="nav-tab nav-tab-active" data-tab="books">الكتب / Books</a>
        <a href="#tab-issue"  class="nav-tab" data-tab="issue">صرف كتاب / Issue</a>
        <a href="#tab-log"    class="nav-tab" data-tab="log">سجل الصرف / Issue Log</a>
    </nav>

    <!-- ═══════════════════ TAB: BOOKS ═══════════════════ -->
    <div id="tab-books" class="rsyi-tab-content">
        <div class="rsyi-toolbar" style="margin:16px 0;display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
            <button class="button button-primary" id="btn-add-book">+ إضافة كتاب / Add Book</button>
            <select id="filter-category" style="min-width:160px;">
                <option value="">كل التصنيفات / All Categories</option>
                <option value="curriculum">مناهج أجنبية / Foreign Curricula</option>
                <option value="certificate">شهادات / Certificates</option>
                <option value="general">عام / General</option>
            </select>
            <select id="filter-lang" style="min-width:130px;">
                <option value="">كل اللغات / All Languages</option>
                <option value="ar">العربية / Arabic</option>
                <option value="en">English</option>
                <option value="fr">Français</option>
                <option value="other">أخرى / Other</option>
            </select>
            <input type="text" id="filter-search" placeholder="بحث / Search…" style="min-width:200px;">
            <button class="button" id="btn-filter-books">🔍 بحث / Search</button>
        </div>

        <div id="books-table-wrap">
            <table class="wp-list-table widefat fixed striped" style="direction:rtl;">
                <thead>
                    <tr>
                        <th style="width:60px;">الغلاف</th>
                        <th>العنوان / Title</th>
                        <th>المؤلف / Author</th>
                        <th>التصنيف / Category</th>
                        <th>اللغة / Lang</th>
                        <th>ISBN</th>
                        <th style="width:100px;">متاح / Available</th>
                        <th style="width:120px;">إجراءات / Actions</th>
                    </tr>
                </thead>
                <tbody id="books-list">
                    <tr><td colspan="8" style="text-align:center;">جاري التحميل… / Loading…</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ═══════════════════ TAB: ISSUE ═══════════════════ -->
    <div id="tab-issue" class="rsyi-tab-content" style="display:none;">
        <div style="max-width:600px;margin:20px 0;background:#fff;padding:20px;border:1px solid #ddd;border-radius:6px;">
            <h3 style="margin-top:0;">صرف كتاب للطالب / Issue a Book to Student</h3>
            <table class="form-table">
                <tr>
                    <th><label for="issue-student">الطالب / Student</label></th>
                    <td>
                        <select id="issue-student" style="width:100%;">
                            <option value="">-- اختر طالب / Select Student --</option>
                            <?php
                            $students = \RSYI_SA\Modules\Library::get_all_students();
                            foreach ( $students as $s ) :
                                echo '<option value="' . esc_attr( $s->id ) . '">' . esc_html( $s->student_name_ar ) . ' (' . esc_html( $s->student_id_number ) . ')</option>';
                            endforeach;
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="issue-book">الكتاب / Book</label></th>
                    <td><select id="issue-book" style="width:100%;"><option value="">-- اختر كتاب / Select Book --</option></select></td>
                </tr>
                <tr>
                    <th><label for="issue-due">تاريخ الإعادة / Due Date</label></th>
                    <td><input type="date" id="issue-due" value="<?php echo esc_attr( date( 'Y-m-d', strtotime( '+30 days' ) ) ); ?>" style="width:200px;"></td>
                </tr>
            </table>
            <p><button class="button button-primary button-large" id="btn-issue-book">📤 صرف / Issue</button></p>
            <div id="issue-msg" style="margin-top:10px;display:none;"></div>
        </div>
    </div>

    <!-- ═══════════════════ TAB: LOG ═══════════════════ -->
    <div id="tab-log" class="rsyi-tab-content" style="display:none;">
        <div class="rsyi-toolbar" style="margin:16px 0;display:flex;gap:10px;align-items:center;">
            <select id="log-filter-status">
                <option value="">كل الحالات / All Statuses</option>
                <option value="issued">مصروف / Issued</option>
                <option value="returned">مُعاد / Returned</option>
                <option value="overdue">متأخر / Overdue</option>
            </select>
            <button class="button" id="btn-reload-log">🔄 تحديث / Refresh</button>
        </div>
        <table class="wp-list-table widefat fixed striped" style="direction:rtl;">
            <thead>
                <tr>
                    <th>الكتاب / Book</th>
                    <th>الطالب / Student</th>
                    <th>تاريخ الصرف / Issued</th>
                    <th>تاريخ الإعادة / Due</th>
                    <th>الحالة / Status</th>
                    <th>بواسطة / By</th>
                    <th>إجراء / Action</th>
                </tr>
            </thead>
            <tbody id="log-list">
                <tr><td colspan="7" style="text-align:center;">جاري التحميل… / Loading…</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ═══════════ BOOK MODAL ═══════════ -->
<div id="book-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:99999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:8px;padding:28px;max-width:700px;width:96%;max-height:90vh;overflow-y:auto;direction:rtl;">
        <h2 id="modal-title" style="margin-top:0;">إضافة كتاب / Add Book</h2>
        <input type="hidden" id="modal-book-id" value="0">
        <table class="form-table">
            <tr><th><label for="m-title-ar">العنوان بالعربية *</label></th><td><input type="text" id="m-title-ar" class="regular-text" required></td></tr>
            <tr><th><label for="m-title-en">Title in English *</label></th><td><input type="text" id="m-title-en" class="regular-text" required></td></tr>
            <tr><th><label for="m-author">المؤلف / Author</label></th><td><input type="text" id="m-author" class="regular-text"></td></tr>
            <tr>
                <th><label for="m-category">التصنيف / Category *</label></th>
                <td>
                    <select id="m-category">
                        <option value="curriculum">مناهج أجنبية / Foreign Curricula</option>
                        <option value="certificate">شهادات / Certificates</option>
                        <option value="general">عام / General</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="m-lang">اللغة / Language</label></th>
                <td>
                    <select id="m-lang">
                        <option value="en">English</option>
                        <option value="ar">العربية</option>
                        <option value="fr">Français</option>
                        <option value="other">أخرى / Other</option>
                    </select>
                </td>
            </tr>
            <tr><th><label for="m-isbn">ISBN</label></th><td><input type="text" id="m-isbn" class="regular-text"></td></tr>
            <tr><th><label for="m-copies">عدد النسخ / Total Copies</label></th><td><input type="number" id="m-copies" value="1" min="1" style="width:80px;"></td></tr>
            <tr><th><label for="m-desc">الوصف / Description</label></th><td><textarea id="m-desc" class="large-text" rows="3"></textarea></td></tr>
            <tr>
                <th>صورة الغلاف / Cover</th>
                <td>
                    <input type="hidden" id="m-cover-id" value="0">
                    <div id="m-cover-preview" style="margin-bottom:8px;"></div>
                    <button type="button" class="button" id="btn-upload-cover">اختر صورة / Choose Image</button>
                    <button type="button" class="button" id="btn-clear-cover" style="display:none;">إزالة / Remove</button>
                </td>
            </tr>
        </table>
        <p style="margin-top:16px;">
            <button class="button button-primary" id="btn-save-book">💾 حفظ / Save</button>
            <button class="button" id="btn-modal-close" style="margin-right:8px;">إلغاء / Cancel</button>
        </p>
        <div id="modal-msg" style="margin-top:10px;display:none;"></div>
    </div>
</div>

<script>
(function($){
    var nonce = rsyiSA.nonce, ajaxUrl = rsyiSA.ajaxUrl;
    var mediaUploader;

    // ── Tabs ─────────────────────────────────────────────────────────
    $('.rsyi-tabs .nav-tab').on('click', function(e){
        e.preventDefault();
        var tab = $(this).data('tab');
        $('.rsyi-tabs .nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.rsyi-tab-content').hide();
        $('#tab-' + tab).show();
        if (tab === 'log') loadLog();
    });

    // ── Books Tab ─────────────────────────────────────────────────────
    function catLabel(c){ return {curriculum:'مناهج أجنبية / Foreign Curricula', certificate:'شهادات / Certificates', general:'عام / General'}[c] || c; }
    function statusBadge(s){ var map={issued:'<span style="color:#0073aa;">مصروف</span>',returned:'<span style="color:green;">مُعاد</span>',overdue:'<span style="color:red;">متأخر</span>'}; return map[s]||s; }

    function loadBooks(){
        $.post(ajaxUrl, {action:'rsyi_get_books', nonce:nonce,
            category:$('#filter-category').val(), book_language:$('#filter-lang').val(), search:$('#filter-search').val()
        }, function(r){
            if(!r.success){ alert(r.data.message); return; }
            var html='';
            if(!r.data.length){ html='<tr><td colspan="8" style="text-align:center;">لا توجد كتب / No books</td></tr>'; }
            else r.data.forEach(function(b){
                var cover = b.cover_url ? '<img src="'+b.cover_url+'" style="width:48px;height:60px;object-fit:cover;border-radius:3px;">' : '—';
                html += '<tr data-id="'+b.id+'">' +
                    '<td>'+cover+'</td>' +
                    '<td><strong>'+b.title_ar+'</strong><br><small>'+b.title_en+'</small></td>' +
                    '<td>'+( b.author||'—' )+'</td>' +
                    '<td>'+catLabel(b.category)+'</td>' +
                    '<td>'+b.language+'</td>' +
                    '<td>'+( b.isbn||'—' )+'</td>' +
                    '<td>'+b.available_copies+' / '+b.total_copies+'</td>' +
                    '<td>' +
                      '<button class="button button-small btn-edit-book" data-book=\''+JSON.stringify(b)+'\'>تعديل</button> ' +
                      '<button class="button button-small btn-del-book" data-id="'+b.id+'">حذف</button>' +
                    '</td>' +
                '</tr>';
            });
            $('#books-list').html(html);
            // Reload issue-book dropdown
            var opts = '<option value="">-- اختر كتاب / Select Book --</option>';
            r.data.forEach(function(b){ if(b.available_copies>0) opts+='<option value="'+b.id+'">'+b.title_ar+' / '+b.title_en+' ('+b.available_copies+')</option>'; });
            $('#issue-book').html(opts);
        });
    }
    loadBooks();

    $('#btn-filter-books').on('click', loadBooks);
    $('[id^=filter-]').on('change', loadBooks);

    // ── Edit ─────────────────────────────────────────────────────────
    $(document).on('click','.btn-edit-book',function(){
        var b = $(this).data('book');
        $('#modal-title').text('تعديل كتاب / Edit Book');
        $('#modal-book-id').val(b.id);
        $('#m-title-ar').val(b.title_ar);
        $('#m-title-en').val(b.title_en);
        $('#m-author').val(b.author);
        $('#m-category').val(b.category);
        $('#m-lang').val(b.language);
        $('#m-isbn').val(b.isbn);
        $('#m-copies').val(b.total_copies);
        $('#m-desc').val(b.description);
        $('#m-cover-id').val(b.cover_image_id||0);
        if(b.cover_url){ $('#m-cover-preview').html('<img src="'+b.cover_url+'" style="max-width:100px;">'); $('#btn-clear-cover').show(); }
        else { $('#m-cover-preview').html(''); $('#btn-clear-cover').hide(); }
        openModal();
    });

    // ── Delete ────────────────────────────────────────────────────────
    $(document).on('click','.btn-del-book',function(){
        if(!confirm('هل أنت متأكد من الحذف؟ / Confirm delete?')) return;
        var id = $(this).data('id');
        $.post(ajaxUrl, {action:'rsyi_delete_book', nonce:nonce, book_id:id}, function(r){
            alert(r.data.message);
            if(r.success) loadBooks();
        });
    });

    // ── Add ───────────────────────────────────────────────────────────
    $('#btn-add-book').on('click', function(){
        $('#modal-title').text('إضافة كتاب / Add Book');
        $('#modal-book-id').val(0);
        $('#m-title-ar,#m-title-en,#m-author,#m-isbn,#m-desc').val('');
        $('#m-copies').val(1);
        $('#m-category').val('curriculum');
        $('#m-lang').val('en');
        $('#m-cover-id').val(0);
        $('#m-cover-preview').html('');
        $('#btn-clear-cover').hide();
        $('#modal-msg').hide();
        openModal();
    });

    // ── Modal helpers ─────────────────────────────────────────────────
    function openModal(){ $('#book-modal').css('display','flex'); }
    function closeModal(){ $('#book-modal').hide(); }
    $('#btn-modal-close').on('click', closeModal);
    $('#book-modal').on('click', function(e){ if($(e.target).is('#book-modal')) closeModal(); });

    // ── Media Uploader ────────────────────────────────────────────────
    $('#btn-upload-cover').on('click', function(){
        if(mediaUploader){ mediaUploader.open(); return; }
        mediaUploader = wp.media({ title:'اختر صورة الغلاف / Select Cover', button:{text:'اختر / Select'}, multiple:false });
        mediaUploader.on('select', function(){
            var att = mediaUploader.state().get('selection').first().toJSON();
            $('#m-cover-id').val(att.id);
            $('#m-cover-preview').html('<img src="'+att.url+'" style="max-width:100px;">');
            $('#btn-clear-cover').show();
        });
        mediaUploader.open();
    });
    $('#btn-clear-cover').on('click', function(){
        $('#m-cover-id').val(0);
        $('#m-cover-preview').html('');
        $(this).hide();
    });

    // ── Save book ─────────────────────────────────────────────────────
    $('#btn-save-book').on('click', function(){
        var data = {
            action:'rsyi_save_book', nonce:nonce,
            book_id:$('#modal-book-id').val(),
            title_ar:$('#m-title-ar').val().trim(),
            title_en:$('#m-title-en').val().trim(),
            author:$('#m-author').val(),
            category:$('#m-category').val(),
            book_language:$('#m-lang').val(),
            isbn:$('#m-isbn').val(),
            total_copies:$('#m-copies').val(),
            description:$('#m-desc').val(),
            cover_image_id:$('#m-cover-id').val()
        };
        if(!data.title_ar || !data.title_en){ alert('العنوان مطلوب / Title required'); return; }
        $.post(ajaxUrl, data, function(r){
            $('#modal-msg').show().text(r.data.message).css('color', r.success?'green':'red');
            if(r.success){ setTimeout(function(){ closeModal(); loadBooks(); }, 800); }
        });
    });

    // ── Issue Tab ─────────────────────────────────────────────────────
    $('#btn-issue-book').on('click', function(){
        var data = {
            action:'rsyi_issue_book', nonce:nonce,
            student_id:$('#issue-student').val(),
            book_id:$('#issue-book').val(),
            due_date:$('#issue-due').val()
        };
        if(!data.student_id || !data.book_id || !data.due_date){ alert('اكمل جميع البيانات / Fill all fields'); return; }
        $.post(ajaxUrl, data, function(r){
            var msg=$('#issue-msg').show().text(r.data.message).css('color', r.success?'green':'red');
            if(r.success){ loadBooks(); $('#issue-book').val(''); }
        });
    });

    // ── Log Tab ───────────────────────────────────────────────────────
    function loadLog(){
        $.post(ajaxUrl, {action:'rsyi_get_issues', nonce:nonce, status:$('#log-filter-status').val()}, function(r){
            if(!r.success){ return; }
            var html='';
            if(!r.data.length){ html='<tr><td colspan="7" style="text-align:center;">لا توجد سجلات / No records</td></tr>'; }
            else r.data.forEach(function(i){
                html+='<tr>'+
                    '<td>'+(i.title_ar||'')+'<br><small>'+(i.title_en||'')+'</small></td>'+
                    '<td>'+(i.student_name_ar||i.student_id||'')+'<br><small>'+(i.student_id_number||'')+'</small></td>'+
                    '<td>'+(i.issued_at||'').substring(0,10)+'</td>'+
                    '<td>'+( i.due_date||'' )+'</td>'+
                    '<td>'+statusBadge(i.status)+'</td>'+
                    '<td>'+(i.issued_by_name||'')+'</td>'+
                    '<td>'+(i.status==='issued'||i.status==='overdue' ? '<button class="button button-small btn-return" data-id="'+i.id+'">إعادة / Return</button>' : '—')+'</td>'+
                '</tr>';
            });
            $('#log-list').html(html);
        });
    }
    $('#btn-reload-log').on('click', loadLog);
    $('#log-filter-status').on('change', loadLog);

    $(document).on('click','.btn-return',function(){
        var notes = prompt('ملاحظات الإعادة / Return notes (optional):') || '';
        var id = $(this).data('id');
        $.post(ajaxUrl, {action:'rsyi_return_book', nonce:nonce, issue_id:id, return_notes:notes}, function(r){
            alert(r.data.message);
            if(r.success){ loadLog(); loadBooks(); }
        });
    });

})(jQuery);
</script>
