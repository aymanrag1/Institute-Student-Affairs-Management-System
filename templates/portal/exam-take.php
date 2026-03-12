<?php
/**
 * Portal: Exam Taking Interface
 * Variables: $exam, $questions, $profile, $now
 *
 * @package RSYI_StudentAffairs
 */
defined( 'ABSPATH' ) || exit;

$ends_ts     = $exam->ends_at ? strtotime( $exam->ends_at ) : 0;
$now_ts      = strtotime( $now );
$remaining_s = $ends_ts ? max( 0, $ends_ts - $now_ts ) : 0;
$list_url    = remove_query_arg( 'exam_id', get_permalink() );
?>
<div class="rsyi-portal-section rsyi-exam-take" dir="rtl" id="rsyi-exam-wrap">

    <!-- Header bar -->
    <div class="rsyi-exam-header">
        <div class="rsyi-exam-header__title">
            <h2><?php echo esc_html( $exam->title ); ?></h2>
            <?php if ( $exam->subject ) : ?>
                <span class="rsyi-exam-header__subject"><?php echo esc_html( $exam->subject ); ?></span>
            <?php endif; ?>
        </div>
        <?php if ( $ends_ts ) : ?>
        <div class="rsyi-exam-timer" id="rsyi-exam-timer"
             data-ends="<?php echo esc_attr( $ends_ts ); ?>"
             data-autosave="1">
            <span class="rsyi-timer-icon">⏱</span>
            <span id="rsyi-timer-display">--:--:--</span>
        </div>
        <?php endif; ?>
    </div>

    <?php if ( $exam->description ) : ?>
        <div class="rsyi-exam-desc"><?php echo esc_html( $exam->description ); ?></div>
    <?php endif; ?>

    <!-- Questions form -->
    <form id="rsyi-exam-form" novalidate>
        <?php foreach ( $questions as $idx => $q ) :
            $qn = (int) $q->question_number ?: ( $idx + 1 );
            $type = $q->question_type ?? 'essay';
            $opts = $q->options ? json_decode( $q->options, true ) : null;
        ?>
        <div class="rsyi-question" id="rsyi-q-<?php echo esc_attr( $q->id ); ?>">
            <div class="rsyi-question__header">
                <span class="rsyi-question__num"><?php echo esc_html( $qn ); ?></span>
                <span class="rsyi-question__text"><?php echo esc_html( $q->question_text ); ?></span>
                <span class="rsyi-question__marks">
                    (<?php printf( esc_html__( '%s درجة', 'rsyi-sa' ), esc_html( $q->marks ) ); ?>)
                </span>
            </div>

            <?php if ( ! empty( $q->image_url ) ) : ?>
            <div class="rsyi-question__image">
                <img src="<?php echo esc_url( $q->image_url ); ?>" alt="">
            </div>
            <?php endif; ?>

            <div class="rsyi-question__answer">
                <?php if ( $type === 'mcq' && is_array( $opts ) ) : ?>
                    <!-- MCQ — single choice -->
                    <div class="rsyi-mcq-options">
                        <?php foreach ( $opts as $oi => $opt ) : ?>
                        <label class="rsyi-mcq-option">
                            <input type="radio"
                                   name="answers[<?php echo esc_attr( $q->id ); ?>]"
                                   value="<?php echo esc_attr( $oi ); ?>">
                            <span><?php echo esc_html( $opt['text'] ?? '' ); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ( $type === 'multi_select' && is_array( $opts ) ) : ?>
                    <!-- Multi-select — multiple choices -->
                    <div class="rsyi-mcq-options">
                        <p class="description" style="margin-bottom:8px;"><?php esc_html_e( 'اختر كل الإجابات الصحيحة', 'rsyi-sa' ); ?></p>
                        <?php foreach ( $opts as $oi => $opt ) : ?>
                        <label class="rsyi-mcq-option">
                            <input type="checkbox"
                                   name="answers[<?php echo esc_attr( $q->id ); ?>][]"
                                   value="<?php echo esc_attr( $oi ); ?>">
                            <span><?php echo esc_html( $opt['text'] ?? '' ); ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ( $type === 'dropdown' && is_array( $opts ) ) : ?>
                    <!-- Dropdown -->
                    <select name="answers[<?php echo esc_attr( $q->id ); ?>]" class="rsyi-dropdown-answer" style="min-width:200px;">
                        <option value=""><?php esc_html_e( '— اختر إجابة —', 'rsyi-sa' ); ?></option>
                        <?php foreach ( $opts as $oi => $opt ) : ?>
                        <option value="<?php echo esc_attr( $oi ); ?>"><?php echo esc_html( $opt['text'] ?? '' ); ?></option>
                        <?php endforeach; ?>
                    </select>

                <?php elseif ( $type === 'true_false' ) : ?>
                    <!-- True / False -->
                    <div class="rsyi-tf-options">
                        <label class="rsyi-mcq-option">
                            <input type="radio"
                                   name="answers[<?php echo esc_attr( $q->id ); ?>]"
                                   value="true">
                            <span><?php esc_html_e( 'صح', 'rsyi-sa' ); ?></span>
                        </label>
                        <label class="rsyi-mcq-option">
                            <input type="radio"
                                   name="answers[<?php echo esc_attr( $q->id ); ?>]"
                                   value="false">
                            <span><?php esc_html_e( 'خطأ', 'rsyi-sa' ); ?></span>
                        </label>
                    </div>

                <?php elseif ( $type === 'matching' && is_array( $opts ) ) : ?>
                    <!-- Matching -->
                    <?php
                    $premises = $q->premises ?? array_column( $opts, 'premise' );
                    $matches  = $q->shuffled_matches ?? array_column( $opts, 'match' );
                    ?>
                    <table class="rsyi-matching-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'العبارة', 'rsyi-sa' ); ?></th>
                                <th><?php esc_html_e( 'المطابقة', 'rsyi-sa' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $premises as $pi => $premise ) : ?>
                            <tr>
                                <td><?php echo esc_html( $premise ); ?></td>
                                <td>
                                    <select name="answers[<?php echo esc_attr( $q->id ); ?>][<?php echo esc_attr( $pi ); ?>]"
                                            class="rsyi-match-select">
                                        <option value=""><?php esc_html_e( '— اختر —', 'rsyi-sa' ); ?></option>
                                        <?php foreach ( $matches as $match ) : ?>
                                        <option value="<?php echo esc_attr( $match ); ?>"><?php echo esc_html( $match ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                <?php elseif ( $type === 'fill_blank' ) : ?>
                    <!-- Fill in the blank -->
                    <input type="text"
                           name="answers[<?php echo esc_attr( $q->id ); ?>]"
                           class="rsyi-fill-input regular-text"
                           placeholder="<?php esc_attr_e( 'اكتب إجابتك هنا…', 'rsyi-sa' ); ?>">

                <?php elseif ( $type === 'numeric' ) : ?>
                    <!-- Numeric answer -->
                    <input type="number" step="any"
                           name="answers[<?php echo esc_attr( $q->id ); ?>]"
                           class="rsyi-numeric-answer"
                           style="width:150px;"
                           placeholder="<?php esc_attr_e( 'أدخل رقماً…', 'rsyi-sa' ); ?>">

                <?php elseif ( ( $type === 'ordering' || $type === 'drag_drop' ) && isset( $q->options_display ) && is_array( $q->options_display ) ) : ?>
                    <!-- Ordering / Drag-and-drop list -->
                    <div class="rsyi-ordering-wrap">
                        <p class="description" style="margin-bottom:6px;"><?php esc_html_e( 'اسحب العناصر لترتيبها', 'rsyi-sa' ); ?></p>
                        <ul class="rsyi-ordering-list" id="rsyi-order-<?php echo esc_attr( $q->id ); ?>">
                            <?php foreach ( $q->options_display as $item ) : ?>
                            <li class="rsyi-ordering-item" draggable="true">
                                <span class="rsyi-drag-handle">☰</span>
                                <?php echo esc_html( $item['text'] ?? '' ); ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <input type="hidden"
                               name="answers[<?php echo esc_attr( $q->id ); ?>]"
                               id="rsyi-order-val-<?php echo esc_attr( $q->id ); ?>"
                               class="rsyi-ordering-value">
                    </div>

                <?php elseif ( $type === 'image_choice' && is_array( $opts ) ) : ?>
                    <!-- Image choice — radio buttons with image labels -->
                    <div class="rsyi-image-choice-options" style="display:flex;flex-wrap:wrap;gap:12px;">
                        <?php foreach ( $opts as $oi => $opt ) : ?>
                        <label class="rsyi-image-choice-option" style="cursor:pointer;text-align:center;border:2px solid #ddd;border-radius:4px;padding:8px;">
                            <input type="radio"
                                   name="answers[<?php echo esc_attr( $q->id ); ?>]"
                                   value="<?php echo esc_attr( $oi ); ?>"
                                   style="display:block;margin:0 auto 6px;">
                            <?php if ( ! empty( $opt['image_url'] ) ) : ?>
                                <img src="<?php echo esc_url( $opt['image_url'] ); ?>" alt="" style="max-width:120px;max-height:120px;display:block;margin:0 auto;">
                            <?php endif; ?>
                            <?php if ( ! empty( $opt['text'] ) ) : ?>
                                <span style="font-size:13px;"><?php echo esc_html( $opt['text'] ); ?></span>
                            <?php endif; ?>
                        </label>
                        <?php endforeach; ?>
                    </div>

                <?php elseif ( $type === 'hotspot' && ! empty( $q->image_url ) ) : ?>
                    <!-- Hotspot — click on image -->
                    <div class="rsyi-hotspot-wrap" style="position:relative;display:inline-block;cursor:crosshair;"
                         id="rsyi-hotspot-<?php echo esc_attr( $q->id ); ?>">
                        <img src="<?php echo esc_url( $q->image_url ); ?>" alt=""
                             style="max-width:100%;display:block;"
                             id="rsyi-hotspot-img-<?php echo esc_attr( $q->id ); ?>">
                        <div class="rsyi-hotspot-marker" id="rsyi-hotspot-marker-<?php echo esc_attr( $q->id ); ?>"
                             style="display:none;position:absolute;width:20px;height:20px;background:rgba(255,50,50,0.7);border-radius:50%;transform:translate(-50%,-50%);pointer-events:none;"></div>
                    </div>
                    <input type="hidden"
                           name="answers[<?php echo esc_attr( $q->id ); ?>]"
                           id="rsyi-hotspot-val-<?php echo esc_attr( $q->id ); ?>"
                           class="rsyi-hotspot-value">
                    <p class="description"><?php esc_html_e( 'اضغط على الصورة للإشارة إلى إجابتك', 'rsyi-sa' ); ?></p>

                <?php elseif ( $type === 'coding' ) : ?>
                    <!-- Coding -->
                    <textarea name="answers[<?php echo esc_attr( $q->id ); ?>]"
                              rows="10"
                              class="rsyi-coding-answer"
                              style="font-family:monospace;direction:ltr;text-align:left;"
                              placeholder="<?php esc_attr_e( 'اكتب الكود هنا…', 'rsyi-sa' ); ?>"></textarea>

                <?php elseif ( $type === 'file_upload' ) : ?>
                    <!-- File upload -->
                    <div class="rsyi-file-upload-wrap">
                        <input type="file"
                               id="rsyi-file-<?php echo esc_attr( $q->id ); ?>"
                               class="rsyi-file-input"
                               data-qid="<?php echo esc_attr( $q->id ); ?>">
                        <input type="hidden"
                               name="answers[<?php echo esc_attr( $q->id ); ?>]"
                               id="rsyi-file-val-<?php echo esc_attr( $q->id ); ?>"
                               class="rsyi-file-answer-val">
                        <p class="description rsyi-file-status" id="rsyi-file-status-<?php echo esc_attr( $q->id ); ?>">
                            <?php esc_html_e( 'اختر ملفاً للرفع', 'rsyi-sa' ); ?>
                        </p>
                    </div>

                <?php elseif ( $type === 'audio' ) : ?>
                    <!-- Audio question — student records or types response -->
                    <div class="rsyi-audio-wrap">
                        <?php if ( ! empty( $q->image_url ) ) : // reusing image_url for audio src ?>
                            <audio controls style="width:100%;margin-bottom:10px;">
                                <source src="<?php echo esc_url( $q->image_url ); ?>">
                            </audio>
                        <?php endif; ?>
                        <textarea name="answers[<?php echo esc_attr( $q->id ); ?>]"
                                  rows="4"
                                  class="rsyi-audio-answer"
                                  placeholder="<?php esc_attr_e( 'اكتب إجابتك هنا بعد الاستماع…', 'rsyi-sa' ); ?>"></textarea>
                    </div>

                <?php elseif ( $type === 'short_answer' ) : ?>
                    <!-- Short answer -->
                    <textarea name="answers[<?php echo esc_attr( $q->id ); ?>]"
                              rows="3"
                              class="rsyi-short-answer"
                              placeholder="<?php esc_attr_e( 'اكتب إجابتك هنا…', 'rsyi-sa' ); ?>"></textarea>

                <?php else : ?>
                    <!-- Essay / default -->
                    <textarea name="answers[<?php echo esc_attr( $q->id ); ?>]"
                              rows="6"
                              class="rsyi-essay-answer"
                              placeholder="<?php esc_attr_e( 'اكتب إجابتك هنا…', 'rsyi-sa' ); ?>"></textarea>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <div class="rsyi-exam-submit-bar">
            <button type="button" id="rsyi-submit-exam-btn" class="rsyi-btn rsyi-btn-primary rsyi-btn-large">
                <?php esc_html_e( 'تسليم الامتحان', 'rsyi-sa' ); ?>
            </button>
            <span id="rsyi-exam-submit-msg" style="margin-right:12px;"></span>
        </div>
    </form>
</div>

<script>
(function($){
    /* ---------- Timer ---------- */
    var timerEl = document.getElementById('rsyi-timer-display');
    var wrapEl  = document.getElementById('rsyi-exam-timer');
    var autoSubmitted = false;

    if (timerEl && wrapEl) {
        var endsAt = parseInt(wrapEl.getAttribute('data-ends'), 10) * 1000;

        function updateTimer() {
            var remaining = Math.max(0, endsAt - Date.now());
            var h = Math.floor(remaining / 3600000);
            var m = Math.floor((remaining % 3600000) / 60000);
            var s = Math.floor((remaining % 60000) / 1000);
            timerEl.textContent =
                String(h).padStart(2,'0') + ':' +
                String(m).padStart(2,'0') + ':' +
                String(s).padStart(2,'0');

            if (remaining <= 0 && !autoSubmitted) {
                autoSubmitted = true;
                timerEl.textContent = '00:00:00';
                submitExam(true);
                return;
            }
            if (remaining <= 300000) {   // last 5 minutes
                wrapEl.classList.add('rsyi-timer-urgent');
            }
        }
        updateTimer();
        setInterval(updateTimer, 1000);
    }

    /* ---------- Ordering drag-and-drop ---------- */
    function initOrdering() {
        document.querySelectorAll('.rsyi-ordering-list').forEach(function(list) {
            var qid = list.id.replace('rsyi-order-', '');
            var valInput = document.getElementById('rsyi-order-val-' + qid);
            var dragging = null;

            function updateHidden() {
                var items = list.querySelectorAll('.rsyi-ordering-item');
                var order = [];
                items.forEach(function(el){ order.push(el.textContent.trim().replace(/^☰\s*/, '')); });
                if (valInput) valInput.value = JSON.stringify(order);
            }
            updateHidden();

            list.addEventListener('dragstart', function(e){
                dragging = e.target.closest('.rsyi-ordering-item');
                dragging.classList.add('rsyi-dragging');
            });
            list.addEventListener('dragend', function(){
                if (dragging) dragging.classList.remove('rsyi-dragging');
                dragging = null;
                updateHidden();
            });
            list.addEventListener('dragover', function(e){
                e.preventDefault();
                var target = e.target.closest('.rsyi-ordering-item');
                if (target && target !== dragging) {
                    var rect = target.getBoundingClientRect();
                    var mid  = rect.top + rect.height / 2;
                    if (e.clientY < mid) {
                        list.insertBefore(dragging, target);
                    } else {
                        list.insertBefore(dragging, target.nextSibling);
                    }
                }
            });
        });
    }
    initOrdering();

    /* ---------- Hotspot click handler ---------- */
    document.querySelectorAll('.rsyi-hotspot-wrap').forEach(function(wrap) {
        var img    = wrap.querySelector('img');
        var marker = wrap.querySelector('.rsyi-hotspot-marker');
        var qid    = wrap.id.replace('rsyi-hotspot-', '');
        var valInp = document.getElementById('rsyi-hotspot-val-' + qid);
        wrap.addEventListener('click', function(e) {
            var rect = img.getBoundingClientRect();
            var x = ((e.clientX - rect.left) / rect.width * 100).toFixed(2);
            var y = ((e.clientY - rect.top)  / rect.height * 100).toFixed(2);
            if (marker) {
                marker.style.left = x + '%';
                marker.style.top  = y + '%';
                marker.style.display = 'block';
            }
            if (valInp) valInp.value = JSON.stringify({ x: parseFloat(x), y: parseFloat(y) });
        });
    });

    /* ---------- File upload handler ---------- */
    document.querySelectorAll('.rsyi-file-input').forEach(function(input) {
        input.addEventListener('change', function() {
            var qid    = this.getAttribute('data-qid');
            var status = document.getElementById('rsyi-file-status-' + qid);
            var valInp = document.getElementById('rsyi-file-val-' + qid);
            var file   = this.files[0];
            if (!file) return;
            var fd = new FormData();
            fd.append('action', 'rsyi_upload_exam_file');
            fd.append('_nonce', rsyiPortal.nonce);
            fd.append('file', file);
            if (status) status.textContent = '<?php echo esc_js( __( 'جاري الرفع…', 'rsyi-sa' ) ); ?>';
            fetch(rsyiPortal.ajaxUrl, { method: 'POST', body: fd })
                .then(function(r){ return r.json(); })
                .then(function(res) {
                    if (res.success && res.data && res.data.url) {
                        if (valInp) valInp.value = res.data.url;
                        if (status) status.textContent = '<?php echo esc_js( __( 'تم الرفع ✓', 'rsyi-sa' ) ); ?>';
                    } else {
                        if (status) status.textContent = '<?php echo esc_js( __( 'فشل الرفع', 'rsyi-sa' ) ); ?>';
                    }
                })
                .catch(function() {
                    if (status) status.textContent = '<?php echo esc_js( __( 'خطأ في الاتصال', 'rsyi-sa' ) ); ?>';
                });
        });
    });

    /* ---------- Collect answers ---------- */
    function collectAnswers() {
        var answers = {};
        var form = document.getElementById('rsyi-exam-form');

        $(form).find('[name^="answers["]').each(function(){
            var m = this.name.match(/^answers\[(\d+)\](?:\[(\d+)\])?(\\[\\])?$/);
            if (!m) return;
            var qid = m[1];
            var sub = m[2];

            if (this.type === 'radio' && !this.checked) return;
            if (this.type === 'checkbox') {
                // multi_select: collect into array
                if (this.checked) {
                    if (!answers[qid]) answers[qid] = [];
                    answers[qid].push(this.value);
                }
                return;
            }

            if (sub !== undefined) {
                // matching sub-key
                if (!answers[qid]) answers[qid] = {};
                answers[qid][sub] = this.value;
            } else if (this.classList.contains('rsyi-ordering-value') || this.classList.contains('rsyi-hotspot-value')) {
                try { answers[qid] = JSON.parse(this.value); } catch(e) { answers[qid] = this.value; }
            } else {
                answers[qid] = this.value;
            }
        });
        return answers;
    }

    /* ---------- Submit ---------- */
    function submitExam(auto) {
        var btn = document.getElementById('rsyi-submit-exam-btn');
        var msgEl = document.getElementById('rsyi-exam-submit-msg');

        if (!auto) {
            if (!confirm('<?php echo esc_js( __( 'هل أنت متأكد من تسليم الامتحان؟ لا يمكن التراجع بعد التسليم.', 'rsyi-sa' ) ); ?>')) return;
        }

        if (btn) { btn.disabled = true; }
        if (msgEl) { msgEl.textContent = '<?php echo esc_js( __( 'جاري التسليم…', 'rsyi-sa' ) ); ?>'; }

        var answers = collectAnswers();

        $.post(rsyiPortal.ajaxUrl, {
            action:    'rsyi_submit_exam',
            _nonce:    rsyiPortal.nonce,
            exam_id:   <?php echo (int) $exam->id; ?>,
            answers:   JSON.stringify(answers)
        }, function(res){
            if (res.success) {
                var html = '<div class="rsyi-notice rsyi-notice-success">'
                    + '<?php echo esc_js( __( 'تم تسليم الامتحان بنجاح!', 'rsyi-sa' ) ); ?>';
                if (res.data && res.data.show_results && res.data.score !== undefined && res.data.score !== null) {
                    html += '<br><?php echo esc_js( __( 'درجتك:', 'rsyi-sa' ) ); ?> <strong>'
                          + res.data.score + ' / <?php echo (int) $exam->max_score; ?></strong>';
                    if (res.data.grade) {
                        html += ' (' + res.data.grade + ')';
                    }
                    if (res.data.is_passing) {
                        html += ' — <?php echo esc_js( __( 'ناجح ✓', 'rsyi-sa' ) ); ?>';
                    } else {
                        html += ' — <?php echo esc_js( __( 'راسب ✗', 'rsyi-sa' ) ); ?>';
                    }
                } else {
                    html += '<br><?php echo esc_js( __( 'ستظهر نتيجتك بعد التصحيح.', 'rsyi-sa' ) ); ?>';
                }
                html += '</div>';
                document.getElementById('rsyi-exam-wrap').innerHTML = html
                    + '<p><a href="<?php echo esc_js( $list_url ); ?>"><?php echo esc_js( __( '← العودة لقائمة الامتحانات', 'rsyi-sa' ) ); ?></a></p>';
            } else {
                if (msgEl) msgEl.textContent = (res.data && res.data.message) ? res.data.message : '<?php echo esc_js( __( 'حدث خطأ. يرجى المحاولة مجدداً.', 'rsyi-sa' ) ); ?>';
                if (btn) btn.disabled = false;
            }
        }).fail(function(){
            if (msgEl) msgEl.textContent = '<?php echo esc_js( __( 'خطأ في الاتصال. يرجى المحاولة مجدداً.', 'rsyi-sa' ) ); ?>';
            if (btn) btn.disabled = false;
        });
    }

    document.getElementById('rsyi-submit-exam-btn') &&
        document.getElementById('rsyi-submit-exam-btn').addEventListener('click', function(){ submitExam(false); });

})(jQuery);
</script>
