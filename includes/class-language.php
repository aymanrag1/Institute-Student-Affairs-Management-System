<?php
/**
 * RSYI Student Affairs – Bilingual Support (Arabic / English)
 *
 * Strategy:
 *  - Default language = Arabic (source strings are a mix of AR and EN).
 *  - In Arabic  mode: English source strings are translated to Arabic via $en_to_ar.
 *  - In English mode: Arabic  source strings are translated to English via $ar_to_en.
 *  - User preference stored in user-meta key  rsyi_sa_lang  ('ar' | 'en').
 *  - A floating switcher button is injected into every portal/admin page.
 *
 * @package RSYI_Student_Affairs
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RSYI_Language {

	/** Currently active language for this request ('ar' | 'en'). */
	private static string $lang = 'ar';

	/* ------------------------------------------------------------------ */
	/*  Boot                                                                */
	/* ------------------------------------------------------------------ */

	public static function init(): void {
		// Determine language as early as possible (after WP user is loaded).
		add_action( 'init', [ __CLASS__, 'load_language' ], 1 );

		// Apply gettext filter.
		add_filter( 'gettext', [ __CLASS__, 'filter_gettext' ], 5, 3 );

		// AJAX – save preference.
		add_action( 'wp_ajax_rsyi_switch_lang',        [ __CLASS__, 'ajax_switch_lang' ] );
		add_action( 'wp_ajax_nopriv_rsyi_switch_lang', [ __CLASS__, 'ajax_switch_lang' ] );

		// Inject switcher button into every front-end and admin page.
		add_action( 'wp_footer',    [ __CLASS__, 'render_switcher' ] );
		add_action( 'admin_footer', [ __CLASS__, 'render_switcher' ] );

		// Enqueue tiny CSS/JS for the switcher.
		add_action( 'wp_enqueue_scripts',    [ __CLASS__, 'enqueue_assets' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
	}

	/* ------------------------------------------------------------------ */
	/*  Language detection                                                   */
	/* ------------------------------------------------------------------ */

	public static function load_language(): void {
		$uid = get_current_user_id();
		if ( $uid ) {
			$saved = get_user_meta( $uid, 'rsyi_sa_lang', true );
			if ( in_array( $saved, [ 'ar', 'en' ], true ) ) {
				self::$lang = $saved;
			}
		}
	}

	public static function get_lang(): string {
		return self::$lang;
	}

	/* ------------------------------------------------------------------ */
	/*  AJAX handler – switch language                                      */
	/* ------------------------------------------------------------------ */

	public static function ajax_switch_lang(): void {
		check_ajax_referer( 'rsyi_switch_lang_nonce', 'nonce' );

		$new_lang = sanitize_text_field( $_POST['lang'] ?? '' );
		if ( ! in_array( $new_lang, [ 'ar', 'en' ], true ) ) {
			wp_send_json_error( 'invalid' );
		}

		$uid = get_current_user_id();
		if ( $uid ) {
			update_user_meta( $uid, 'rsyi_sa_lang', $new_lang );
		} else {
			// Guest: store in session-like transient keyed by cookie.
			setcookie( 'rsyi_sa_lang', $new_lang, time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
		}

		self::$lang = $new_lang;
		wp_send_json_success( [ 'lang' => $new_lang ] );
	}

	/* ------------------------------------------------------------------ */
	/*  Gettext filter                                                       */
	/* ------------------------------------------------------------------ */

	public static function filter_gettext( string $translation, string $text, string $domain ): string {
		if ( 'rsyi-sa' !== $domain ) {
			return $translation;
		}

		if ( 'en' === self::$lang ) {
			// Translate Arabic source strings to English.
			return self::$ar_to_en[ $text ] ?? $translation;
		}

		// Arabic mode: translate English source strings to Arabic.
		return self::$en_to_ar[ $text ] ?? $translation;
	}

	/* ------------------------------------------------------------------ */
	/*  Switcher button                                                      */
	/* ------------------------------------------------------------------ */

	public static function render_switcher(): void {
		$lang      = self::$lang;
		$other     = ( 'ar' === $lang ) ? 'en' : 'ar';
		$label     = ( 'ar' === $lang ) ? 'EN' : 'ع';
		$nonce     = wp_create_nonce( 'rsyi_switch_lang_nonce' );
		$ajax_url  = admin_url( 'admin-ajax.php' );
		$dir_class = ( 'ar' === $lang ) ? 'rsyi-lang-ar' : 'rsyi-lang-en';
		?>
		<div id="rsyi-lang-switcher" class="<?php echo esc_attr( $dir_class ); ?>">
			<button id="rsyi-lang-btn"
					data-lang="<?php echo esc_attr( $other ); ?>"
					data-nonce="<?php echo esc_attr( $nonce ); ?>"
					data-ajax="<?php echo esc_url( $ajax_url ); ?>"
					title="<?php echo ( 'ar' === $lang ) ? 'Switch to English' : 'التبديل للعربية'; ?>">
				<?php echo esc_html( $label ); ?>
			</button>
		</div>
		<style>
		#rsyi-lang-switcher{position:fixed;bottom:24px;left:24px;z-index:99999}
		#rsyi-lang-btn{width:44px;height:44px;border-radius:50%;border:2px solid #0073aa;background:#fff;color:#0073aa;font-size:15px;font-weight:700;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,.2);transition:all .2s}
		#rsyi-lang-btn:hover{background:#0073aa;color:#fff}
		</style>
		<script>
		(function(){
			var btn = document.getElementById('rsyi-lang-btn');
			if(!btn) return;
			btn.addEventListener('click', function(){
				btn.disabled = true;
				btn.textContent = '…';
				var fd = new FormData();
				fd.append('action','rsyi_switch_lang');
				fd.append('nonce', btn.dataset.nonce);
				fd.append('lang',  btn.dataset.lang);
				fetch(btn.dataset.ajax, {method:'POST', body:fd})
					.then(function(r){return r.json();})
					.then(function(d){
						if(d.success){ location.reload(); }
						else { btn.disabled=false; btn.textContent='?'; }
					})
					.catch(function(){ btn.disabled=false; btn.textContent='!'; });
			});
		})();
		</script>
		<?php
	}

	/* ------------------------------------------------------------------ */
	/*  Assets (minimal – styling handled inline above)                     */
	/* ------------------------------------------------------------------ */

	public static function enqueue_assets(): void {
		// Body direction class.
		$lang = self::$lang;
		add_filter( 'body_class', function( $classes ) use ( $lang ) {
			$classes[] = 'rsyi-lang-' . $lang;
			return $classes;
		} );
	}


	/* ================================================================== */
	/*  TRANSLATION TABLES                                                  */
	/* ================================================================== */

	/**
	 * Arabic source text  →  English translation.
	 * Used when the user has selected English mode.
	 */
	private static array $ar_to_en = [
		// ── Numbers / units ────────────────────────────────────────────
		'%d دقيقة'                           => '%d min',
		'%s درجة'                            => '%s pts',

		// ── Letters / symbols ──────────────────────────────────────────
		'0 = تطابق تام. مثال: إجابة 10 وهامش 0.5 يقبل 9.5 → 10.5'
			=> '0 = exact match. E.g. answer 10 with margin 0.5 accepts 9.5 → 10.5',
		'8 أحرف على الأقل'                  => 'At least 8 characters',

		// ── Mixed EN/AR status strings ─────────────────────────────────
		'Action Required – Pending Warnings' => 'Action Required – Pending Warnings',
		'Adding…'                            => 'Adding…',
		'Approved – Expelled'               => 'Approved – Expelled',
		'Awaiting review…'                  => 'Awaiting review…',
		'Case Rejected – Student Not Expelled' => 'Case Rejected – Student Not Expelled',
		'Checking GitHub…'                  => 'Checking GitHub…',
		'Creating account…'                 => 'Creating account…',
		'Creating pages…'                   => 'Creating pages…',
		'Criteria Scores (0 – 10)'          => 'Criteria Scores (0 – 10)',
		'Decision making – understanding and comprehending the mission'
			=> 'Decision making – understanding and comprehending the mission',
		'General / عام'                     => 'General',
		'Generating…'                       => 'Generating…',
		'GitHub Token غير صحيح أو الصلاحيات غير كافية (repo: read).'
			=> 'GitHub Token is incorrect or insufficient permissions (repo: read).',
		'Loading…'                          => 'Loading…',
		'Pending…'                          => 'Pending…',
		'Problem solving – confidentiality in information transfer'
			=> 'Problem solving – confidentiality in information transfer',
		'Redirecting to document upload…'   => 'Redirecting to document upload…',
		'Rejected – Overturned'             => 'Rejected – Overturned',
		'Required only for private repositories. Create a token at: GitHub → Settings → Developer settings → Personal access tokens. Required scope: repo (read).'
			=> 'Required only for private repositories. Create a token at: GitHub → Settings → Developer settings → Personal access tokens. Required scope: repo (read).',
		'Saving…'                           => 'Saving…',
		'Score (0–10)'                      => 'Score (0–10)',
		'Search…'                           => 'Search…',
		'Student Affairs – RSYI'            => 'Student Affairs – RSYI',
		'This is safe to run multiple times – existing pages will not be overwritten.'
			=> 'This is safe to run multiple times – existing pages will not be overwritten.',
		'Upload Documents Now →'            => 'Upload Documents Now →',
		'Uploaded successfully. Reloading…' => 'Uploaded successfully. Reloading…',
		'Uploading…'                        => 'Uploading…',

		// ── Arabic strings (aleph order) ───────────────────────────────
		'آخر الأحداث'                       => 'Recent Activity',
		'أدخل رقماً…'                       => 'Enter a number…',
		'أدنى درجة'                         => 'Minimum Score',
		'أذونات الخروج السابقة'             => 'Previous Exit Permits',
		'أذونات الخروج'                     => 'Exit Permits',
		'أذونات المبيت السابقة'             => 'Previous Overnight Permits',
		'أذونات المبيت'                     => 'Overnight Permits',
		'أذونات خروج معلقة'                 => 'Pending Exit Permits',
		'أذونات مبيت معلقة'                 => 'Pending Overnight Permits',
		'أزواج التوصيل'                     => 'Matching Pairs',
		'أسئلة الامتحان'                    => 'Exam Questions',
		'أضف العناصر بالترتيب الصحيح — يُعرض للطالب مخلوطاً.'
			=> 'Add items in the correct order — displayed to the student shuffled.',
		'أضف صورة للسؤال من حقل الصورة أعلاه — ضع ✓ على الإجابة الصحيحة.'
			=> 'Add an image via the image field above — mark ✓ on the correct answer.',
		'أضف صورة من حقل الصورة أعلاه، ثم حدد المناطق (x%, y%, عرض%, ارتفاع%).'
			=> 'Add an image via the field above, then define regions (x%, y%, width%, height%).',
		'أعلى درجة'                         => 'Highest Score',
		'أنشئ تقرير PDF مجمّع لأذونات الخروج والمبيت لتاريخ محدد.'
			=> 'Generate a combined PDF report for exit and overnight permits on a specific date.',
		'أنواع الملفات المسموحة:'           => 'Allowed file types:',
		'إجابتك قيد التصحيح. ستظهر نتيجتك بعد الانتهاء.'
			=> 'Your answer is being graded. Your result will appear after grading is complete.',
		'إجراءات سريعة'                     => 'Quick Actions',
		'إجراءات'                           => 'Actions',
		'إجمالي الطلاب'                     => 'Total Students',
		'إجمالي النقاط'                     => 'Total Points',
		'إجمالي النقاط:'                    => 'Total Points:',
		'إجمالي نقاط المخالفات'             => 'Total Violation Points',
		'إحصائيات'                          => 'Statistics',
		'إدارة الأدوار والصلاحيات'          => 'Roles & Permissions Management',
		'إدارة الدفعات'                     => 'Cohort Management',
		'إذن الخروج'                        => 'Exit Permit',
		'إذن المبيت'                        => 'Overnight Permit',
		'إذن خروج'                          => 'Exit Permit',
		'إذن مبيت'                          => 'Overnight Permit',
		'إزالة الصورة'                      => 'Remove Image',
		'إصلاح قاعدة البيانات'              => 'Fix Database',
		'إضافة خيار'                        => 'Add Option',
		'إضافة زوج'                         => 'Add Pair',
		'إضافة سؤال جديد'                   => 'Add New Question',
		'إضافة طالب جديد'                   => 'Add New Student',
		'إضافة طالب'                        => 'Add Student',
		'إضافة عنصر'                        => 'Add Item',
		'إضافة منطقة'                       => 'Add Region',
		'إظهار النتيجة للطالب'              => 'Show Result to Student',
		'إعادة التصحيح مسموحة'              => 'Re-grading Allowed',
		'إعادة التصحيح مقيّدة'              => 'Re-grading Restricted',
		'إعادة تصحيح'                       => 'Re-grade',
		'إعدادات التصحيح والنتائج'          => 'Grading & Results Settings',
		'إعدادات الرفع'                     => 'Upload Settings',
		'إعدادات الكود'                     => 'Code Settings',
		'إقرار واستلام ✍'                   => 'Acknowledgement & Receipt ✍',
		'إلزامي'                            => 'Mandatory',
		'إلغاء مخالفة'                      => 'Cancel Violation',
		'إلغاء'                             => 'Cancel',
		'إلى تاريخ'                         => 'To Date',
		'إلى تاريخ/وقت'                     => 'To Date/Time',
		'إلى دفعة'                          => 'To Cohort',
		'إلى'                               => 'To',
		'إنشاء الامتحان'                    => 'Create Exam',
		'إنشاء التقرير'                     => 'Generate Report',
		'إنشاء الدفعة'                      => 'Create Cohort',
		'إنشاء امتحان جديد'                 => 'Create New Exam',
		'إنشاء امتحان'                      => 'Create Exam',
		'إنشاء حساب الطالب'                 => 'Create Student Account',
		'إنشاء دفعة جديدة'                  => 'Create New Cohort',
		'إنشاء'                             => 'Create',
		'ابدأ الامتحان ←'                   => 'Start Exam ←',
		'اتركه فارغاً لاستخدام 50% من الدرجة القصوى.'
			=> 'Leave blank to use 50% of the maximum score.',
		'احسب التقديرات تلقائياً'           => 'Auto-calculate Grades',
		'اختر دوراً من القائمة على اليسار لتعديل صلاحياته.'
			=> 'Select a role from the list on the left to edit its permissions.',
		'اختر صورة السؤال'                  => 'Select Question Image',
		'اختر صورة'                         => 'Select Image',
		'اختر فوجاً لعرض رصد الدرجات.'     => 'Select a cohort to view grade tracking.',
		'اختر فوجاً وتاريخاً لعرض قائمة الحضور.'
			=> 'Select a cohort and date to view the attendance list.',
		'اختر كل الإجابات الصحيحة'         => 'Select All Correct Answers',
		'اختر ملفاً للرفع'                  => 'Choose a File to Upload',
		'اختر'                              => 'Select',
		'اختياري'                           => 'Optional',
		'استجابة غير متوقعة من GitHub API (HTTP %d).'
			=> 'Unexpected response from GitHub API (HTTP %d).',
		'استيراد Excel'                     => 'Import Excel',
		'استيراد الطلاب من Excel'            => 'Import Students from Excel',
		'اسحب العناصر لترتيبها'             => 'Drag Items to Reorder',
		'اسم الدفعة'                        => 'Cohort Name',
		'اسم الطالب'                        => 'Student Name',
		'اسم المستخدم مطلوب.'               => 'Username is required.',
		'اسم المستخدم موجود بالفعل.'        => 'Username already exists.',
		'اسم المستخدم'                      => 'Username',
		'اضغط على الصورة للإشارة إلى إجابتك'
			=> 'Click on the image to indicate your answer',
		'افتح الملف في Excel لتعبئة بيانات الطلاب ثم احفظه.'
			=> 'Open the file in Excel to fill in student data, then save it.',
		'اكتب إجابتك هنا بعد الاستماع…'    => 'Write your answer here after listening…',
		'اكتب إجابتك هنا…'                  => 'Write your answer here…',
		'اكتب الإجابة المقبولة...'          => 'Enter the accepted answer...',
		'اكتب الكود هنا…'                   => 'Write code here…',
		'اكتمل الاستيراد'                   => 'Import Complete',
		'الأدوار المتاحة'                   => 'Available Roles',
		'الأنواع المسموحة: PDF, Word, PowerPoint, Excel, ZIP. الحجم الأقصى: 20 ميجابايت.'
			=> 'Allowed types: PDF, Word, PowerPoint, Excel, ZIP. Max size: 20 MB.',
		'الإجابات القصيرة والمقالات تظل تحتاج تصحيحاً يدوياً.'
			=> 'Short answers and essays still require manual grading.',
		'الإجابة الصحيحة'                   => 'Correct Answer',
		'الإجراء'                           => 'Action',
		'الإحصائيات'                        => 'Statistics',
		'الإقرار'                           => 'Acknowledgement',
		'الاسم الإنجليزي الكامل مطلوب.'    => 'Full English name is required.',
		'الاسم الإنجليزي الكامل'            => 'Full English Name',
		'الاسم الإنجليزي'                   => 'English Name',
		'الاسم العربي الكامل مطلوب.'        => 'Full Arabic name is required.',
		'الاسم العربي الكامل'               => 'Full Arabic Name',
		'الاسم العربي'                      => 'Arabic Name',
		'الاسم والرمز مطلوبان.'             => 'Name and code are required.',
		'الاسم'                             => 'Name',
		'الافتراضي: 50%'                    => 'Default: 50%',
		'الامتحان غير موجود أو غير متاح.'   => 'Exam not found or not available.',
		'الامتحان غير موجود.'               => 'Exam not found.',
		'الامتحان لم يبدأ بعد. يبدأ في: '  => 'The exam has not started yet. Starts at: ',
		'الامتحان'                          => 'Exam',
		'الامتحانات'                        => 'Exams',
		'البريد الإلكتروني غير صالح.'       => 'Invalid email address.',
		'البريد الإلكتروني مسجل بالفعل.'    => 'Email already registered.',
		'البريد الإلكتروني مطلوب.'          => 'Email is required.',
		'البريد الإلكتروني'                 => 'Email',
		'البيانات الشخصية'                  => 'Personal Information',
		'التاريخ والوقت بالدقيقة (سيُتاح الامتحان للطلاب من هذا الوقت).'
			=> 'Date and time (exam will be available to students from this time).',
		'التاريخ والوقت بالدقيقة (يُغلق الامتحان تلقائياً).'
			=> 'Date and time (exam closes automatically).',
		'التاريخ:'                          => 'Date:',
		'التحذير غير موجود.'                => 'Warning not found.',
		'التصحيح التلقائي مفعّل'            => 'Auto-grading Enabled',
		'التصحيح التلقائي'                  => 'Auto-grading',
		'التصحيح اليدوي'                    => 'Manual Grading',
		'التصحيح لا يفرق بين الحروف الكبيرة والصغيرة.'
			=> 'Grading is case-insensitive.',
		'التصحيح يدوي من المدرس.'           => 'Manual grading by the teacher.',
		'التصحيح'                           => 'Grading',
		'التقدير'                           => 'Grade',
		'التقرير اليومي – أذونات الخروج والمبيت'
			=> 'Daily Report – Exit & Overnight Permits',
		'الحالة الحالية لا تسمح بهذه العملية.'
			=> 'Current status does not allow this operation.',
		'الحالة الحالية للطلب لا تسمح بهذه العملية.'
			=> 'Current request status does not allow this operation.',
		'الحالة'                            => 'Status',
		'الحجم الأقصى (MB):'               => 'Max Size (MB):',
		'الحد الأقصى:'                      => 'Maximum:',
		'الحضور والغياب'                    => 'Attendance & Absence',
		'الخيارات'                          => 'Options',
		'الدرجة / %d'                       => 'Score / %d',
		'الدرجة القصوى'                     => 'Maximum Score',
		'الدرجة القصوى:'                    => 'Maximum Score:',
		'الدرجة'                            => 'Score',
		'الدفعات'                           => 'Cohorts',
		'الدور غير موجود.'                  => 'Role not found.',
		'الرافع'                            => 'Uploader',
		'الرمز (Code)'                      => 'Code',
		'الرمز'                             => 'Code',
		'السؤال'                            => 'Question',
		'السبب'                             => 'Reason',
		'السماح بإعادة التصحيح'             => 'Allow Re-grading',
		'السيستم يصحح أسئلة MCQ / صح-خطأ / توصيل / إكمال / ترتيب تلقائياً'
			=> 'System auto-grades MCQ / True-False / Matching / Fill-blank / Ordering',
		'الصفحة التالية ←'                  => 'Next Page ←',
		'الصيغ المدعومة: .xlsx, .csv'       => 'Supported formats: .xlsx, .csv',
		'الطالب غير موجود.'                 => 'Student not found.',
		'الطالب مسجل بالفعل في هذا الفوج.'  => 'Student is already enrolled in this cohort.',
		'الطالب يرفع ملفاً — التصحيح يدوي.' => 'Student uploads a file — manual grading.',
		'الطالب يرى درجته بعد انتهاء الامتحان'
			=> 'Student sees their grade after the exam ends',
		'الطالب يستمع للتسجيل ثم يكتب إجابته — التصحيح يدوي.'
			=> 'Student listens to the recording then writes their answer — manual grading.',
		'الطالب يُرتّب العناصر بالسحب والإفلات.'
			=> 'Student arranges items by drag and drop.',
		'الطالب'                            => 'Student',
		'الطلاب'                            => 'Students',
		'الطلب غير موجود أو لا يمكن رفضه الآن.'
			=> 'Request not found or cannot be rejected now.',
		'الطلب غير موجود.'                  => 'Request not found.',
		'العبارة'                           => 'Premise',
		'العتبة'                            => 'Threshold',
		'العدد'                             => 'Count',
		'العمود الأول: العبارة — العمود الثاني: المطابق الصحيح.'
			=> 'First column: Premise — Second column: Correct match.',
		'العمود'                            => 'Column',
		'العميد'                            => 'Dean',
		'العناصر بالترتيب الصحيح'           => 'Items in Correct Order',
		'العنوان'                           => 'Title',
		'الفوج المستهدف غير موجود.'         => 'Target cohort not found.',
		'الفوج'                             => 'Cohort',
		'الفوج:'                            => 'Cohort:',
		'القضية غير موجودة أو تمت معالجتها.' => 'Case not found or already processed.',
		'الكل'                              => 'All',
		'الكيان'                            => 'Entity',
		'المادة / الموضوع'                  => 'Subject / Topic',
		'المادة غير موجودة.'               => 'Material not found.',
		'المادة'                            => 'Material',
		'المادة:'                           => 'Material:',
		'المتوسط'                           => 'Average',
		'المخالفات السلوكية'                => 'Behavioral Violations',
		'المخالفة غير موجودة أو تم إلغاؤها مسبقاً.'
			=> 'Violation not found or already cancelled.',
		'المدة (دقيقة)'                     => 'Duration (minutes)',
		'المدة:'                            => 'Duration:',
		'المدرس يستطيع تعديل الدرجة بعد التصحيح التلقائي'
			=> 'Teacher can adjust the grade after auto-grading',
		'المستخدم'                          => 'User',
		'المستودع غير موجود، أو لا توجد إصدارات (Releases) منشورة بعد، أو المستودع خاص ويحتاج Token.'
			=> 'Repository not found, no published releases yet, or private repository requires a Token.',
		'المسجّل بواسطة'                    => 'Recorded By',
		'المطابقة'                          => 'Match',
		'المعدل %'                          => 'Average %',
		'المعرّف'                           => 'ID',
		'الملف الصوتي'                      => 'Audio File',
		'الملف غير متاح حالياً.'            => 'File is currently unavailable.',
		'الملف غير موجود.'                  => 'File not found.',
		'الملف لا يحتوي على بيانات.'        => 'The file contains no data.',
		'الملف'                             => 'File',
		'المواد الدراسية'                   => 'Study Materials',
		'المواد المرفوعة'                   => 'Uploaded Materials',
		'الموافقة على الفصل'                => 'Expulsion Approval',
		'النتائج غير متاحة للعرض حالياً.'   => 'Results are not available at this time.',
		'النتائج'                           => 'Results',
		'النتيجة تظهر للطالب'               => 'Result shown to student',
		'النتيجة مخفية عن الطالب'           => 'Result hidden from student',
		'النتيجة'                           => 'Result',
		'النسبة%'                           => 'Percentage%',
		'النسبة'                            => 'Percentage',
		'النظام'                            => 'System',
		'النقاط وقت التحذير'                => 'Points at Warning',
		'النقاط'                            => 'Points',
		'النوع'                             => 'Type',
		'الوثائق'                           => 'Documents',
		'الوثيقة غير موجودة أو ليست في حالة انتظار.'
			=> 'Document not found or not in pending status.',
		'الوثيقة غير موجودة.'              => 'Document not found.',
		'الوصف'                             => 'Description',
		'انتظار العميد'                     => 'Awaiting Dean',
		'انتظار المشرف الأكاديمي'           => 'Awaiting Academic Supervisor',
		'انتظار قرار العميد'                => 'Awaiting Dean\'s Decision',
		'انتظار مدير شؤون الطلاب'           => 'Awaiting Student Affairs Director',
		'انتظار مشرف السكن'                 => 'Awaiting Dorm Supervisor',
		'انتهى الوقت'                       => 'Time\'s Up',
		'انتهى وقت الامتحان.'               => 'Exam time has ended.',
		'بحث باسم الطالب…'                  => 'Search by student name…',
		'بدء الاستيراد'                     => 'Start Import',
		'بداية الامتحان'                    => 'Exam Start',
		'بقرار العميد'                      => 'By Dean\'s Decision',
		'بيانات الحساب'                     => 'Account Data',
		'بيانات غير صالحة.'                 => 'Invalid data.',
		'بيانات غير مكتملة.'               => 'Incomplete data.',
		'بيانات ناقصة أو بريد إلكتروني غير صالح.'
			=> 'Incomplete data or invalid email.',
		'تأكيد تنفيذ الإذن؟'               => 'Confirm permit execution?',
		'تأكيد تنفيذ قرار الفصل نهائياً؟'  => 'Confirm final execution of expulsion decision?',
		'تاريخ البداية مطلوب.'              => 'Start date is required.',
		'تاريخ البداية'                     => 'Start Date',
		'تاريخ التحذير'                     => 'Warning Date',
		'تاريخ الحادثة'                     => 'Incident Date',
		'تاريخ الرفع'                       => 'Upload Date',
		'تاريخ الميلاد (YYYY-MM-DD)'        => 'Date of Birth (YYYY-MM-DD)',
		'تاريخ الميلاد'                     => 'Date of Birth',
		'تاريخ النهاية مطلوب.'              => 'End date is required.',
		'تاريخ النهاية'                     => 'End Date',
		'تجاوز 40 نقطة'                     => 'Exceeded 40 Points',
		'تحديث'                             => 'Update',
		'تحديد الصلاحيات المطلوبة ثم اضغط "حفظ"'
			=> 'Select the required permissions then click "Save"',
		'تحذير سلوكي – وصلت إلى %d نقطة'   => 'Behavioral Warning – You have reached %d points',
		'تحذير عند %d نقطة'                 => 'Warning at %d points',
		'تحميل نموذج Excel (CSV)'           => 'Download Excel Template (CSV)',
		'ترتيب الطلاب'                      => 'Student Ranking',
		'تسجيل المخالفة'                    => 'Record Violation',
		'تسجيل مخالفة جديدة'               => 'Record New Violation',
		'تسليم الامتحان'                    => 'Submit Exam',
		'تصحيح تلقائي الآن'                 => 'Auto-grade Now',
		'تصدير CSV'                         => 'Export CSV',
		'تصفية'                             => 'Filter',
		'تعديل الامتحان'                    => 'Edit Exam',
		'تعديل السؤال'                      => 'Edit Question',
		'تعديل بيانات الطالب'               => 'Edit Student Data',
		'تعديل'                             => 'Edit',
		'تعذّر الوصول إلى GitHub: %s'       => 'Failed to access GitHub: %s',
		'تعليمات'                           => 'Instructions',
		'تغيير دفعة'                        => 'Change Cohort',
		'تفاصيل المخالفات'                  => 'Violation Details',
		'تفاصيل'                            => 'Details',
		'تفعيل'                             => 'Activate',
		'تقديم الطلب'                       => 'Submit Request',
		'تم إرسال طلب التحويل للعميد.'      => 'Transfer request sent to the Dean.',
		'تم إضافة السؤال.'                  => 'Question added.',
		'تم إلغاء المخالفة.'                => 'Violation cancelled.',
		'تم إنشاء الامتحان بنجاح.'          => 'Exam created successfully.',
		'تم إنشاء التقرير.'                 => 'Report generated.',
		'تم إنشاء الحساب بنجاح.'            => 'Account created successfully.',
		'تم إنشاء الفوج.'                   => 'Cohort created.',
		'تم إنشاء ملف الطالب بنجاح.'        => 'Student file created successfully.',
		'تم اعتماد قرار الطرد وتنفيذه.'     => 'Expulsion decision approved and executed.',
		'تم الإقرار بالتحذير.'              => 'Warning acknowledged.',
		'تم الإقرار بهذا التحذير مسبقاً.'   => 'This warning has already been acknowledged.',
		'تم التسليم بنجاح. ستظهر نتيجتك لاحقاً.'
			=> 'Submitted successfully. Your result will appear later.',
		'تم التسليم ✓'                      => 'Submitted ✓',
		'تم التصحيح التلقائي لـ %d طالب.'   => 'Auto-graded %d student(s).',
		'تم الرفع ✓'                        => 'Uploaded ✓',
		'تم تحديث الامتحان بنجاح.'          => 'Exam updated successfully.',
		'تم تحديث البيانات بنجاح.'          => 'Data updated successfully.',
		'تم تحديث الدرجة.'                  => 'Score updated.',
		'تم تحديث السؤال.'                  => 'Question updated.',
		'تم تحديث الفوج.'                   => 'Cohort updated.',
		'تم تحديث قاعدة البيانات بنجاح.'    => 'Database updated successfully.',
		'تم تسجيل المخالفة بنجاح.'          => 'Violation recorded successfully.',
		'تم تسليم الامتحان بنجاح!'          => 'Exam submitted successfully!',
		'تم تسليم الامتحان بنجاح.'          => 'Exam submitted successfully.',
		'تم تفعيل حسابك – Red Sea Yacht Institute'
			=> 'Your account has been activated – Red Sea Yacht Institute',
		'تم تقديم طلب إذن الخروج بنجاح.'   => 'Exit permit request submitted successfully.',
		'تم تقديم طلب إذن المبيت بنجاح.'   => 'Overnight permit request submitted successfully.',
		'تم تنفيذ إذن الخروج.'              => 'Exit permit executed.',
		'تم تنفيذ إذن المبيت.'              => 'Overnight permit executed.',
		'تم تنفيذ قرار طرد الطالب %s'       => 'Expulsion decision for student %s executed.',
		'تم حذف الامتحان ونتائجه.'          => 'Exam and its results deleted.',
		'تم حذف السؤال.'                    => 'Question deleted.',
		'تم حذف المادة بنجاح.'              => 'Material deleted successfully.',
		'تم حذف الوثيقة.'                   => 'Document deleted.',
		'تم حفظ حضور %d طالب.'              => 'Attendance saved for %d student(s).',
		'تم حفظ نتائج %d طالب.'             => 'Results saved for %d student(s).',
		'تم رفض %s #%d'                     => '%s #%d rejected.',
		'تم رفض الطلب وإغلاقه.'             => 'Request rejected and closed.',
		'تم رفض الوثيقة.'                   => 'Document rejected.',
		'تم رفض طلب التحويل.'               => 'Transfer request rejected.',
		'تم رفض قرار الطرد.'                => 'Expulsion decision rejected.',
		'تم رفض وثيقتك – يرجى إعادة الرفع' => 'Your document was rejected – please re-upload',
		'تم رفع المادة بنجاح.'              => 'Material uploaded successfully.',
		'تم رفع الوثيقة بنجاح وهي قيد المراجعة.'
			=> 'Document uploaded successfully and is under review.',
		'تمت الموافقة النهائية على إذن الخروج.'
			=> 'Exit permit finally approved.',
		'تمت الموافقة النهائية على إذن المبيت.'
			=> 'Overnight permit finally approved.',
		'تمت الموافقة على %s #%d'           => '%s #%d approved.',
		'تمت الموافقة على التحويل وتنفيذه.' => 'Transfer approved and executed.',
		'تمت الموافقة على الوثيقة.'         => 'Document approved.',
		'تمت الموافقة على طلب تحويل الفوج'  => 'Cohort transfer request approved',
		'تمت الموافقة على وثيقتك'           => 'Your document has been approved',
		'تمت الموافقة في الخطوة الأولى (المشرف).'
			=> 'Approved in step 1 (Supervisor).',
		'تمت الموافقة في الخطوة الأولى.'    => 'Approved in step 1.',
		'تمت الموافقة في الخطوة الثانية (المدير).'
			=> 'Approved in step 2 (Manager).',
		'تنسيق التاريخ غير صالح.'           => 'Invalid date format.',
		'تنفيذ الفصل'                       => 'Execute Expulsion',
		'تنفيذ'                             => 'Execute',
		'توزيع التقديرات'                   => 'Grade Distribution',
		'توليد تلقائي'                      => 'Auto-generate',
		'جاري الإنشاء…'                     => 'Creating…',
		'جاري التسليم…'                     => 'Submitting…',
		'جاري الرفع…'                       => 'Uploading…',
		'جاري تحليل الملف…'                 => 'Analyzing file…',
		'جارٍ الإنشاء...'                   => 'Creating...',
		'جارٍ تحميل الأسئلة…'               => 'Loading questions…',
		'جارٍ تحميل الإحصائيات...'          => 'Loading statistics...',
		'جلسة غير صالحة.'                   => 'Invalid session.',
		'حاضر'                              => 'Present',
		'حالة طرد جديدة تستوجب موافقتك'     => 'New expulsion case requires your approval',
		'حجم الملف يتجاوز 20 ميجابايت.'     => 'File size exceeds 20 MB.',
		'حجم الملف يتجاوز الحد المسموح به (10 ميغابايت).'
			=> 'File size exceeds the allowed limit (10 MB).',
		'حدث خطأ. يرجى المحاولة مجدداً.'   => 'An error occurred. Please try again.',
		'حدث خطأ.'                          => 'An error occurred.',
		'حذف هذا السؤال نهائياً؟'           => 'Permanently delete this question?',
		'حذف'                               => 'Delete',
		'حفظ التعديلات'                     => 'Save Changes',
		'حفظ التغييرات'                     => 'Save Changes',
		'حفظ الحضور'                        => 'Save Attendance',
		'حفظ السؤال'                        => 'Save Question',
		'حفظ الصلاحيات'                     => 'Save Permissions',
		'حفظ النتائج'                       => 'Save Results',
		'خطأ في الاتصال'                    => 'Connection Error',
		'خطأ في الاتصال. يرجى المحاولة مجدداً.'
			=> 'Connection error. Please try again.',
		'خطأ في رفع الملف.'                 => 'File upload error.',
		'خطأ'                               => 'Error',
		'درجة النجاح'                       => 'Passing Score',
		'درجتك'                             => 'Your Score',
		'درجتك:'                            => 'Your Score:',
		'دفعة'                              => 'Cohort',
		'رابط التنزيل غير صالح أو منتهي الصلاحية.'
			=> 'Download link is invalid or expired.',
		'رابط ملف الصوت (MP3/OGG)...'       => 'Audio file URL (MP3/OGG)...',
		'راسب ✗'                            => 'Failed ✗',
		'راسب'                              => 'Failed',
		'رصد الدرجات'                       => 'Grade Tracking',
		'رفض الفصل'                         => 'Reject Expulsion',
		'رفض'                               => 'Reject',
		'رفع المادة'                        => 'Upload Material',
		'رفع مادة جديدة'                    => 'Upload New Material',
		'رفع ملف الطلاب'                    => 'Upload Students File',
		'رقم السؤال'                        => 'Question Number',
		'رقم الهاتف'                        => 'Phone Number',
		'رقم الهوية القومية'                => 'National ID Number',
		'رقم ملف الطالب'                    => 'Student File Number',
		'رمز الفوج موجود بالفعل.'           => 'Cohort code already exists.',
		'رُفض الفصل'                        => 'Expulsion Rejected',
		'سبب الإلغاء:'                      => 'Cancellation Reason:',
		'سبب الطلب مطلوب.'                  => 'Request reason is required.',
		'سبب الفصل'                         => 'Expulsion Reason',
		'ستظهر نتيجتك بعد التصحيح.'         => 'Your result will appear after grading.',
		'سجل الأحداث'                       => 'Activity Log',
		'سجل التحذيرات'                     => 'Warnings Log',
		'سجل كامل لجميع العمليات المُنجزة على النظام. لا يمكن حذف هذه السجلات.'
			=> 'Complete log of all system operations. These records cannot be deleted.',
		'سجلي السلوكي'                      => 'My Behavioral Record',
		'سلّم؟'                             => 'Submit?',
		'سيتم تصحيح إجابات جميع الطلاب تلقائياً. هل تريد المتابعة؟'
			=> 'All student answers will be auto-graded. Do you want to continue?',
		'سيتم حذف الامتحان وجميع نتائجه وأسئلته وإجاباته. هل أنت متأكد؟'
			=> 'The exam and all its results, questions, and answers will be deleted. Are you sure?',
		'سيتم حذف المادة والملف المرفق. هل أنت متأكد؟'
			=> 'The material and its attached file will be deleted. Are you sure?',
		'سيُستخدم أيضاً كاسم للمستخدم تلقائياً.'
			=> 'Will also be used as the username automatically.',
		'شرح الإجابة (اختياري)'             => 'Answer Explanation (optional)',
		'صح'                                => 'True',
		'صف إجمالي'                         => 'Total Row',
		'صف'                                => 'Row',
		'صلاحية العميد مطلوبة.'             => 'Dean permission required.',
		'صلاحية غير كافية.'                 => 'Insufficient permissions.',
		'صورة (اختياري)'                    => 'Image (optional)',
		'صيغة الملف غير مدعومة. استخدم .xlsx أو .csv'
			=> 'Unsupported file format. Use .xlsx or .csv',
		'ضع علامة ✓ على الإجابة الصحيحة — يُعرض للطالب كقائمة منسدلة.'
			=> 'Mark ✓ on the correct answer — displayed to the student as a dropdown.',
		'ضع علامة ✓ على الإجابة الصحيحة.'  => 'Mark ✓ on the correct answer.',
		'ضع ✓ على كل الإجابات الصحيحة (قد تكون أكثر من واحدة).'
			=> 'Mark ✓ on all correct answers (may be more than one).',
		'طالب'                              => 'Student',
		'طباعة التقرير اليومي'              => 'Print Daily Report',
		'طباعة'                             => 'Print',
		'طلاب نشطون'                        => 'Active Students',
		'طلب %s جديد بانتظار موافقتك'       => 'New %s request awaiting your approval',
		'طلب إذن خروج'                      => 'Exit Permit Request',
		'طلب إذن مبيت'                      => 'Overnight Permit Request',
		'طلب التحويل غير موجود أو تمت معالجته.'
			=> 'Transfer request not found or already processed.',
		'طلباتي'                            => 'My Requests',
		'طلبات تغيير الدفعة'                => 'Cohort Change Requests',
		'طُلب بواسطة'                       => 'Requested By',
		'عدد الطلاب'                        => 'Number of Students',
		'عرض الملف'                         => 'View File',
		'عرض'                               => 'View',
		'عنوان IP'                          => 'IP Address',
		'عنوان الامتحان مطلوب.'             => 'Exam title is required.',
		'عنوان الامتحان'                    => 'Exam Title',
		'عنوان المادة مطلوب.'               => 'Material title is required.',
		'عنوان المادة'                      => 'Material Title',
		'غائب'                              => 'Absent',
		'فشل الاتصال بالخادم.'              => 'Server connection failed.',
		'فشل الاتصال.'                      => 'Connection failed.',
		'فشل الرفع'                         => 'Upload failed',
		'فشل تحليل الملف.'                  => 'File parsing failed.',
		'فشل حفظ الملف.'                    => 'File save failed.',
		'فشل رفع الملف.'                    => 'File upload failed.',
		'فشل في إنشاء الامتحان. يرجى المحاولة مرة أخرى.'
			=> 'Failed to create exam. Please try again.',
		'فشل'                               => 'Failed',
		'فصل طالب'                          => 'Expel a Student',
		'في انتظار التصحيح.'               => 'Awaiting grading.',
		'قائمة الامتحانات'                  => 'Exam List',
		'قبول'                              => 'Accept',
		'قرار الطرد من المعهد'              => 'Expulsion Decision from the Institute',
		'قضايا طرد معلقة'                   => 'Pending Expulsion Cases',
		'قضية طرد'                          => 'Expulsion Case',
		'قيد المراجعة'                      => 'Under Review',
		'كلمة المرور يجب أن تكون 8 أحرف على الأقل.'
			=> 'Password must be at least 8 characters.',
		'كلمة المرور'                       => 'Password',
		'كود البداية (اختياري):'            => 'Starter code (optional):',
		'لا توجد أذونات.'                   => 'No permits.',
		'لا توجد أسئلة بعد.'                => 'No questions yet.',
		'لا توجد إجابات مُسلَّمة لهذا الامتحان.'
			=> 'No submitted answers for this exam.',
		'لا توجد امتحانات بعد.'             => 'No exams yet.',
		'لا توجد امتحانات متاحة حالياً.'    => 'No exams available at the moment.',
		'لا توجد امتحانات مطابقة للفلتر المحدد.'
			=> 'No exams match the selected filter.',
		'لا توجد بيانات للتحديث.'           => 'No data to update.',
		'لا توجد تحذيرات.'                  => 'No warnings.',
		'لا توجد دفعات بعد.'                => 'No cohorts yet.',
		'لا توجد سجلات بعد.'                => 'No records yet.',
		'لا توجد طلبات تغيير دفعة.'         => 'No cohort change requests.',
		'لا توجد طلبات.'                    => 'No requests.',
		'لا توجد قضايا فصل.'                => 'No expulsion cases.',
		'لا توجد مخالفات مسجلة.'            => 'No violations recorded.',
		'لا توجد مخالفات.'                  => 'No violations.',
		'لا توجد مواد مرفوعة بعد.'          => 'No materials uploaded yet.',
		'لا توجد وثائق.'                    => 'No documents.',
		'لا يمكن الموافقة على هذه الوثيقة في حالتها الحالية.'
			=> 'Cannot approve this document in its current status.',
		'لا يوجد أفواج. يرجى إنشاء فوج أولاً.'
			=> 'No cohorts. Please create a cohort first.',
		'لا يوجد سجل تغييرات.'             => 'No changelog.',
		'لا يوجد طلاب نشطون في فوج هذا الامتحان.'
			=> 'No active students in this exam\'s cohort.',
		'لا يوجد طلاب نشطون في هذا الفوج.' => 'No active students in this cohort.',
		'لغة البرمجة:'                      => 'Programming Language:',
		'لقد سلّمت هذا الامتحان مسبقاً.'   => 'You have already submitted this exam.',
		'للعرض فقط — يُحسب Timer الطالب من وقت البداية/النهاية.'
			=> 'Display only — Student\'s timer is calculated from start/end time.',
		'للمصحح فقط — لا تصحيح تلقائي.'    => 'For grader only — no auto-grading.',
		'لم يبدأ بعد'                       => 'Not started yet',
		'لم يبدأ وقت الامتحان بعد.'         => 'Exam time has not started yet.',
		'لم يتم الإقرار بعد'               => 'Not acknowledged yet',
		'لم يتم العثور على ملفك الشخصي. يرجى التواصل مع الإدارة.'
			=> 'Your profile was not found. Please contact administration.',
		'لم يتم العثور على ملفك الشخصي.'   => 'Your profile was not found.',
		'لوحة تحكم شؤون الطلاب'            => 'Student Affairs Dashboard',
		'ليس لديك صلاحية الوصول للامتحانات.'
			=> 'You do not have permission to access exams.',
		'ليس لديك صلاحية الوصول لهذه الصفحة.'
			=> 'You do not have permission to access this page.',
		'ليس لديك صلاحية عرض الدرجات.'     => 'You do not have permission to view grades.',
		'ليس لديك صلاحية عرض المواد الدراسية.'
			=> 'You do not have permission to view study materials.',
		'ليس لديك صلاحية عرض سجل الحضور.'  => 'You do not have permission to view attendance.',
		'ليس لديك صلاحية للوصول إلى هذا الملف.'
			=> 'You do not have permission to access this file.',
		'متأخر'                             => 'Late',
		'متوسط الامتحان'                    => 'Exam Average',
		'مثال: BATCH-2024-01'               => 'Example: BATCH-2024-01',
		'مثال: الدفعة الأولى 2024'          => 'Example: First Batch 2024',
		'مثال: الملاحة البحرية'             => 'Example: Marine Navigation',
		'مثال: محمد أحمد علي'               => 'Example: Mohamed Ahmed Ali',
		'مجموع الدرجات:'                    => 'Total Score:',
		'مخالفة'                            => 'Violation',
		'مراجع بواسطة'                      => 'Reviewed By',
		'مرفوض'                             => 'Rejected',
		'مسار الملف غير صالح.'              => 'Invalid file path.',
		'معرف الامتحان مطلوب.'              => 'Exam ID is required.',
		'معرف السؤال مطلوب.'                => 'Question ID is required.',
		'معرف المادة مطلوب.'                => 'Material ID is required.',
		'معطل'                              => 'Disabled',
		'معهد البحر الأحمر للتخطيط البحري – الجونة، مصر'
			=> 'Red Sea Yacht Institute – El Gouna, Egypt',
		'مفتاح الألوان:'                    => 'Color Key:',
		'مفتوح الآن'                        => 'Open Now',
		'مقبول'                             => 'Accepted',
		'ملاحظات'                           => 'Notes',
		'ملاحظة اختيارية'                   => 'Optional note',
		'ملاحظة'                            => 'Note',
		'ملغاة'                             => 'Cancelled',
		'ملف Excel / CSV'                   => 'Excel / CSV file',
		'ملف الطالب غير موجود.'             => 'Student file not found.',
		'ملف طالب'                          => 'Student File',
		'ممتاز ≥ 80%'                       => 'Excellent ≥ 80%',
		'من تاريخ'                          => 'From Date',
		'من تاريخ/وقت'                      => 'From Date/Time',
		'من دفعة'                           => 'From Cohort',
		'من'                                => 'From',
		'مناطق الضغط'                       => 'Click Regions',
		'منفَّذ'                            => 'Executed',
		'موافق عليه'                        => 'Approved',
		'موافقة م.1'                        => 'Approval S.1',
		'موافقة م.2'                        => 'Approval S.2',
		'موافقة نهائية'                     => 'Final Approval',
		'موافقة وتنفيذ'                     => 'Approve & Execute',
		'موافقة'                            => 'Approval',
		'مُغلقة'                            => 'Closed',
		'مُقرَّر الفصل'                     => 'Expulsion Decided',
		'مُنفَّذ'                           => 'Executed',
		'مُوافق عليه'                       => 'Approved',
		'ناجح ✓'                            => 'Passed ✓',
		'ناجح'                              => 'Passed',
		'نتيجة الاستيراد'                   => 'Import Result',
		'نجح'                               => 'Passed',
		'نسبة النجاح'                       => 'Pass Rate',
		'نشط'                               => 'Active',
		'نشطة'                              => 'Active',
		'نص السؤال مطلوب.'                  => 'Question text is required.',
		'نص السؤال'                         => 'Question Text',
		'نقطة'                              => 'Point',
		'نموذج الإجابة'                     => 'Answer Template',
		'نهاية الامتحان'                    => 'Exam End',
		'نوع الامتحان'                      => 'Exam Type',
		'نوع السؤال'                        => 'Question Type',
		'نوع المخالفة غير موجود.'           => 'Violation type not found.',
		'نوع المخالفة'                      => 'Violation Type',
		'نوع الملف غير مسموح به. يُقبل: JPEG, PNG, WebP, PDF.'
			=> 'File type not allowed. Accepted: JPEG, PNG, WebP, PDF.',
		'نوع الملف غير مسموح.'              => 'File type not allowed.',
		'نوع الوثيقة غير صالح.'             => 'Invalid document type.',
		'نوع الوثيقة'                       => 'Document Type',
		'هامش الخطأ المسموح:'               => 'Allowed Error Margin:',
		'هذا الامتحان ليس لفوجك.'           => 'This exam does not belong to your cohort.',
		'هذا النوع من المخالفات يتطلب صلاحية العميد.'
			=> 'This violation type requires Dean permission.',
		'هل أنت متأكد من تسليم الامتحان؟ لا يمكن التراجع بعد التسليم.'
			=> 'Are you sure you want to submit the exam? This cannot be undone.',
		'هل تؤكد الموافقة على تغيير الدفعة؟'
			=> 'Confirm approval for cohort change?',
		'هل تؤكد الموافقة؟'                 => 'Confirm approval?',
		'هل تؤكد قبول هذه الوثيقة؟'        => 'Confirm acceptance of this document?',
		'هل تقر بأنك اطلعت على هذا التحذير وفهمت محتواه؟'
			=> 'Do you acknowledge that you have read and understood this warning?',
		'وثائق قيد المراجعة'                => 'Documents Under Review',
		'وثيقة'                             => 'Document',
		'وصف'                               => 'Description',
		'يبدأ في:'                          => 'Starts at:',
		'يبدأ'                              => 'Starts',
		'يبدأ:'                             => 'Starts:',
		'يجب أن يحتوي الملف على الأعمدة التالية (الصف الأول عناوين):'
			=> 'The file must contain the following columns (first row as headers):',
		'يجب أن يكون تاريخ النهاية بعد تاريخ البداية.'
			=> 'End date must be after start date.',
		'يجب أن يكون حسابك مفعلاً لتقديم الطلبات.'
			=> 'Your account must be activated to submit requests.',
		'يجب أن يكون حسابك مفعلاً.'        => 'Your account must be activated.',
		'يجب الإقرار بالتحذيرات التالية:'   => 'You must acknowledge the following warnings:',
		'يجب تحديد سبب إلغاء المخالفة.'    => 'You must specify a reason for cancelling the violation.',
		'يجب تحديد سبب الرفض.'              => 'You must specify a rejection reason.',
		'يجب تسجيل الدخول أولاً.'           => 'You must log in first.',
		'يجب تسجيل الدخول للوصول إلى هذا الملف.'
			=> 'You must log in to access this file.',
		'يجب تسجيل الدخول.'                 => 'You must log in.',
		'يجب تفعيل حسابك أولاً (رفع جميع الوثائق المطلوبة).'
			=> 'Your account must be activated first (upload all required documents).',
		'يرجى اختيار الفوج أولاً.'          => 'Please select a cohort first.',
		'يرجى اختيار الفوج.'                => 'Please select a cohort.',
		'يرجى اختيار ملف.'                  => 'Please select a file.',
		'يمكن تنفيذ الأذونات الموافق عليها فقط.'
			=> 'Only approved permits can be executed.',
		'ينتهي'                             => 'Ends',
		'ينتهي:'                            => 'Ends:',
		'يُعرض للطالب بعد التصحيح...'       => 'Displayed to student after grading...',
		'يُملأ تلقائياً من البريد الإلكتروني'
			=> 'Auto-filled from email',

		// ── Dropdowns ─────────────────────────────────────────────────
		'— اختر إجابة —'                    => '— Select an Answer —',
		'— اختر الفوج —'                    => '— Select Cohort —',
		'— اختر —'                          => '— Select —',
		'— جميع الأفواج —'                  => '— All Cohorts —',
		'— جميع المواد —'                   => '— All Materials —',
		'— كل الأفواج —'                    => '— All Cohorts —',
		'— كل الحالات —'                    => '— All Statuses —',

		// ── Navigation / Icons ─────────────────────────────────────────
		'← العودة لقائمة الامتحانات'        => '← Back to Exam List',
		'→ الصفحة السابقة'                  => '→ Previous Page',
		'⬇ تحميل التقرير'                   => '⬇ Download Report',
		'⚠ تحذير: رصيدك السلوكي مرتفع جداً. الوصول إلى 40 نقطة سيستوجب فتح قضية طرد.'
			=> '⚠ Warning: Your behavioral score is very high. Reaching 40 points will trigger an expulsion case.',

		// ── Question / exam types ─────────────────────────────────────
		'اختيار متعدد — إجابة واحدة'        => 'Multiple Choice — Single Answer',
		'اختيار متعدد — أكثر من إجابة'      => 'Multiple Choice — Multiple Answers',
		'صح / خطأ'                          => 'True / False',
		'قائمة منسدلة'                      => 'Dropdown',
		'إكمال الناقص'                      => 'Fill in the Blank',
		'إجابة عددية'                       => 'Numeric Answer',
		'إجابة قصيرة'                       => 'Short Answer',
		'مقالة / إنشاء'                     => 'Essay / Long Answer',
		'توصيل / مطابقة'                    => 'Matching',
		'ترتيب العناصر'                     => 'Ordering',
		'سحب وإفلات'                        => 'Drag and Drop',
		'اختيار يعتمد على صورة'             => 'Image Choice',
		'Hotspot — ضغط على صورة'            => 'Hotspot — Click on Image',
		'سؤال برمجي'                        => 'Coding Question',
		'رفع ملف'                           => 'File Upload',
		'سؤال صوتي'                         => 'Audio Question',
		'مفتوح (بدون دفعة)'                 => 'Open (No Cohort)',

		// ── Admin menu items ───────────────────────────────────────────
		'لوحة التحكم'                       => 'Dashboard',
		'الطلاب النشطون'                    => 'Active Students',
		'إضافة طالب'                        => 'Add Student',
		'استيراد الطلاب'                    => 'Import Students',
		'الحضور'                            => 'Attendance',
		'التقرير اليومي'                    => 'Daily Report',
		'تقرير الدرجات'                     => 'Grade Report',
		'الامتحانات'                        => 'Exams',
		'المواد'                            => 'Materials',
		'الأذونات'                          => 'Permits',
		'المخالفات السلوكية'                => 'Behavioral Violations',
		'قضايا الطرد'                       => 'Expulsion Cases',
		'الوثائق'                           => 'Documents',
		'التقييمات'                         => 'Evaluations',
		'الدفعات'                           => 'Cohorts',
		'الأدوار'                           => 'Roles',
		'سجل الأحداث'                       => 'Audit Log',
		'الإعدادات'                         => 'Settings',
		'تحديث البرنامج'                    => 'Update Plugin',
	];


	/**
	 * English source text  →  Arabic translation.
	 * Used when the user has selected Arabic mode (default).
	 */
	private static array $en_to_ar = [
		// ── Admin notices / setup ──────────────────────────────────────
		'%d portal page(s) are missing. Click "Create Portal Pages" to fix this.'
			=> '%d صفحة بوابة مفقودة. انقر على "إنشاء صفحات البوابة" لإصلاح ذلك.',
		'3-Step Approval Workflow'           => 'سير عمل الموافقة (3 خطوات)',
		'Absent'                             => 'غائب',
		'Account Status'                     => 'حالة الحساب',
		'Account created successfully. Please upload your required documents.'
			=> 'تم إنشاء الحساب بنجاح. يرجى رفع الوثائق المطلوبة.',
		'Acknowledge & Continue'             => 'إقرار والمتابعة',
		'Actions'                            => 'إجراءات',
		'Activate'                           => 'تفعيل',
		'Active'                             => 'نشط',
		'Add Student'                        => 'إضافة طالب',
		'Admin / Supervisor (6 criteria, /10 each):'
			=> 'المسؤول / المشرف (6 معايير، /10 لكل منها):',
		'Aggregation Table'                  => 'جدول التجميع',
		'All documents approved. Your account is now active!'
			=> 'تمت الموافقة على جميع الوثائق. حسابك الآن نشط!',
		'Already have an account?'           => 'لديك حساب بالفعل؟',
		'An error occurred.'                 => 'حدث خطأ.',
		'Appears on the student dashboard and PDF reports.'
			=> 'يظهر على لوحة تحكم الطالب وتقارير PDF.',
		'Approval Workflow'                  => 'سير عمل الموافقة',
		'Approve (Dorm)'                     => 'موافقة (السكن)',
		'Approve (Manager)'                  => 'موافقة (المدير)',
		'Approve Expulsion'                  => 'الموافقة على الفصل',
		'Approve this document?'             => 'الموافقة على هذه الوثيقة؟',
		'Approve'                            => 'موافقة',
		'Approved'                           => 'موافق عليه',
		'Arabic Name'                        => 'الاسم العربي',
		'Are you sure you want to approve this expulsion?'
			=> 'هل أنت متأكد من الموافقة على هذا الفصل؟',
		'Are you sure you want to permanently delete student:'
			=> 'هل أنت متأكد من حذف الطالب نهائياً:',
		'Assigned By'                        => 'مُسند بواسطة',
		'Attendance Rate'                    => 'نسبة الحضور',
		'Attendance Record'                  => 'سجل الحضور',
		'Attendance'                         => 'الحضور',
		'Audit Log'                          => 'سجل الأحداث',
		'Average'                            => 'المتوسط',
		'Back to Dashboard'                  => 'العودة للوحة التحكم',
		'Back to Documents'                  => 'العودة للوثائق',
		'Back to Exit Permits'               => 'العودة لأذونات الخروج',
		'Back to Expulsion Cases'            => 'العودة لقضايا الطرد',
		'Back to Overnight Permits'          => 'العودة لأذونات المبيت',
		'Back to Students'                   => 'العودة للطلاب',
		'Back to Violations'                 => 'العودة للمخالفات',
		'Behavior Points'                    => 'نقاط السلوك',
		'Behavior Record'                    => 'سجل السلوك',
		'Case Details'                       => 'تفاصيل القضية',
		'Case not found.'                    => 'القضية غير موجودة.',
		'Check for Updates Now'              => 'التحقق من التحديثات الآن',
		'Choose Institute Logo'              => 'اختر شعار المعهد',
		'Choose Logo from Media Library'     => 'اختر الشعار من مكتبة الوسائط',
		'Cohort *'                           => 'الفوج *',
		'Cohort Peer Evaluation'             => 'تقييم الزملاء في الفوج',
		'Cohort'                             => 'الفوج',
		'Cohort:'                            => 'الفوج:',
		'Cohorts'                            => 'الدفعات',
		'Column Totals'                      => 'مجاميع الأعمدة',
		'Connected'                          => 'متصل',
		'Connected. Latest GitHub release: %s'
			=> 'متصل. أحدث إصدار على GitHub: %s',
		'Connection Status'                  => 'حالة الاتصال',
		'Connection error. Please try again.' => 'خطأ في الاتصال. يرجى المحاولة مجدداً.',
		'Connection failed.'                 => 'فشل الاتصال.',
		'Connection to server failed.'       => 'فشل الاتصال بالخادم.',
		'Create My Account'                  => 'إنشاء حسابي',
		'Create New Evaluation Period'       => 'إنشاء فترة تقييم جديدة',
		'Create Period'                      => 'إنشاء فترة',
		'Create Portal Pages'               => 'إنشاء صفحات البوابة',
		'Created'                            => 'تاريخ الإنشاء',
		'Criteria Legend:'                   => 'دليل المعايير:',
		'Criterion'                          => 'المعيار',
		'Currently registered violation types: %s'
			=> 'أنواع المخالفات المسجلة حالياً: %s',
		'Daily Report PDF'                   => 'التقرير اليومي PDF',
		'Dashboard'                          => 'لوحة التحكم',
		'Date Range'                         => 'نطاق التاريخ',
		'Date of Birth'                      => 'تاريخ الميلاد',
		'Date'                               => 'التاريخ',
		'Deactivate'                         => 'تعطيل',
		'Dealing with colleagues'            => 'التعامل مع الزملاء',
		'Dean Decision'                      => 'قرار العميد',
		'Dean Name'                          => 'اسم العميد',
		'Dean Notes'                         => 'ملاحظات العميد',
		'Dean'                               => 'العميد',
		'Decided By'                         => 'قرر بواسطة',
		'Decision Date'                      => 'تاريخ القرار',
		'Decision making ability'            => 'القدرة على اتخاذ القرار',
		'Delete'                             => 'حذف',
		'Description'                        => 'الوصف',
		'Do you confirm that you have read and understood this warning?'
			=> 'هل تؤكد أنك اطلعت على هذا التحذير وفهمت محتواه؟',
		'Document Completion'               => 'اكتمال الوثائق',
		'Documents Required'                => 'الوثائق المطلوبة',
		'Documents'                          => 'الوثائق',
		'Done. Added %1$d new type(s). Total: %2$d.'
			=> 'تم. تمت إضافة %1$d نوع جديد. الإجمالي: %2$d.',
		'Dorm Supervisor'                    => 'مشرف السكن',
		'Download Letter'                    => 'تحميل الخطاب',
		'Download course materials'          => 'تحميل المواد الدراسية',
		'Download'                           => 'تحميل',
		'Email Address *'                    => 'عنوان البريد الإلكتروني *',
		'Email'                              => 'البريد الإلكتروني',
		'End'                                => 'النهاية',
		'English Name'                       => 'الاسم الإنجليزي',
		'English letters and numbers only. Cannot be changed later.'
			=> 'أحرف إنجليزية وأرقام فقط. لا يمكن تغييره لاحقاً.',
		'Enter Admin / Supervisor Evaluation' => 'إدخال تقييم المسؤول / المشرف',
		'Enter Evaluation'                   => 'إدخال التقييم',
		'Enter institute name'               => 'أدخل اسم المعهد',
		'Enter rejection reason:'            => 'أدخل سبب الرفض:',
		'Error.'                             => 'خطأ.',
		'Evaluation Criteria (each /10):'    => 'معايير التقييم (كل منها /10):',
		'Evaluation Period'                  => 'فترة التقييم',
		'Evaluation period created.'         => 'تم إنشاء فترة التقييم.',
		'Evaluation saved successfully.'     => 'تم حفظ التقييم بنجاح.',
		'Evaluations'                        => 'التقييمات',
		'Exam'                               => 'الامتحان',
		'Exams'                              => 'الامتحانات',
		'Excused'                            => 'بعذر',
		'Executed'                           => 'منفَّذ',
		'Existing Periods'                   => 'الفترات الحالية',
		'Exit & overnight permits'           => 'أذونات الخروج والمبيت',
		'Exit Permit #%d'                    => 'إذن خروج #%d',
		'Exit Permits'                       => 'أذونات الخروج',
		'Expelled'                           => 'مفصول',
		'Expulsion Approved'                 => 'تمت الموافقة على الفصل',
		'Expulsion Case #%d'                 => 'قضية طرد #%d',
		'Expulsion Cases'                    => 'قضايا الطرد',
		'Fail'                               => 'راسب',
		'Failed to connect to GitHub API. Make sure the repository is public or enter a valid token.'
			=> 'فشل الاتصال بـ GitHub API. تأكد من أن المستودع عام أو أدخل رمز صالح.',
		'Failed to create evaluation period.' => 'فشل إنشاء فترة التقييم.',
		'Failed'                             => 'فشل',
		'File:'                              => 'الملف:',
		'Filter'                             => 'تصفية',
		'From'                               => 'من',
		'Full Name in Arabic *'              => 'الاسم الكامل بالعربية *',
		'Full Name in English *'             => 'الاسم الكامل بالإنجليزية *',
		'Full name of the dean'              => 'الاسم الكامل للعميد',
		'General'                            => 'عام',
		'Generate Expulsion Letter'          => 'إنشاء خطاب الطرد',
		'GitHub Auto-Update'                 => 'التحديث التلقائي من GitHub',
		'GitHub Token (optional)'            => 'رمز GitHub (اختياري)',
		'Good use of authority / privileges' => 'حسن استخدام السلطة / الصلاحيات',
		'Grade Report'                       => 'تقرير الدرجات',
		'Grade'                              => 'التقدير',
		'Grand Total'                        => 'المجموع الكلي',
		'Hint: Create a Personal Access Token and enter it in the Token field above.'
			=> 'تلميح: أنشئ رمز وصول شخصي وأدخله في حقل الرمز أعلاه.',
		'Hint: Make sure the server can reach the internet (github.com).'
			=> 'تلميح: تأكد من أن الخادم يستطيع الوصول للإنترنت (github.com).',
		'Hint: No Releases published on GitHub yet. Use: git tag v1.0.0 && git push origin v1.0.0'
			=> 'تلميح: لم يتم نشر إصدارات على GitHub بعد. استخدم: git tag v1.0.0 && git push origin v1.0.0',
		'Hint: Verify the repository name and that it is Public.'
			=> 'تلميح: تحقق من اسم المستودع وأنه عام.',
		'Import from Excel'                  => 'استيراد من Excel',
		'Inactive'                           => 'غير نشط',
		'Incident Date'                      => 'تاريخ الحادثة',
		'Institute Information'              => 'معلومات المعهد',
		'Institute Logo'                     => 'شعار المعهد',
		'Institute Name'                     => 'اسم المعهد',
		'Institute name cannot be empty.'    => 'اسم المعهد لا يمكن أن يكون فارغاً.',
		'Insufficient permissions.'          => 'صلاحية غير كافية.',
		'Invalid period ID.'                 => 'معرف الفترة غير صالح.',
		'Invalid period.'                    => 'فترة غير صالحة.',
		'Invalid student ID.'                => 'معرف الطالب غير صالح.',
		'Late'                               => 'متأخر',
		'Load'                               => 'تحميل',
		'Log New Violation'                  => 'تسجيل مخالفة جديدة',
		'Log Violation'                      => 'تسجيل مخالفة',
		'Manage Documents'                   => 'إدارة الوثائق',
		'Manage Periods'                     => 'إدارة الفترات',
		'Mark as Executed'                   => 'تحديد كمنفذ',
		'Max allowed for your role: %d'      => 'الحد الأقصى المسموح لدورك: %d',
		'Missing'                            => 'مفقود',
		'My Documents'                       => 'وثائقي',
		'My Grades'                          => 'درجاتي',
		'Name and cohort are required.'      => 'الاسم والفوج مطلوبان.',
		'National ID Number'                 => 'رقم الهوية القومية',
		'National ID'                        => 'الهوية القومية',
		'New Student Account'                => 'حساب طالب جديد',
		'New update available: version %s. Go to the Plugins page to update.'
			=> 'تحديث جديد متاح: الإصدار %s. اذهب إلى صفحة الإضافات للتحديث.',
		'No Active Evaluation Periods'       => 'لا توجد فترات تقييم نشطة',
		'No active students found in this cohort for the selected period.'
			=> 'لم يتم العثور على طلاب نشطين في هذا الفوج للفترة المحددة.',
		'No attendance records available yet.' => 'لا توجد سجلات حضور بعد.',
		'No evaluation periods have been created yet.'
			=> 'لم يتم إنشاء فترات تقييم بعد.',
		'No exam results available yet.'     => 'لا توجد نتائج امتحانات بعد.',
		'No file uploaded yet.'              => 'لم يتم رفع أي ملف بعد.',
		'No logo uploaded yet.'              => 'لم يتم رفع شعار بعد.',
		'No other active students found in your cohort to evaluate.'
			=> 'لم يتم العثور على طلاب نشطين آخرين في فوجك للتقييم.',
		'No students found.'                 => 'لم يتم العثور على طلاب.',
		'No study materials available yet.'  => 'لا توجد مواد دراسية بعد.',
		'Not Connected'                      => 'غير متصل',
		'Not Uploaded'                       => 'لم يُرفع',
		'Not checked yet. WordPress will check automatically within 12 hours.'
			=> 'لم يتم الفحص بعد. سيتحقق WordPress تلقائياً خلال 12 ساعة.',
		'Note: This only adds missing types and does not delete existing ones.'
			=> 'ملاحظة: يُضيف فقط الأنواع المفقودة ولا يحذف الموجودة.',
		'Notes (optional)'                   => 'ملاحظات (اختيارية)',
		'Notes (optional):'                  => 'ملاحظات (اختيارية):',
		'Notes'                              => 'ملاحظات',
		'Only active cohorts are shown.'     => 'تُعرض الدفعات النشطة فقط.',
		'Overnight Permit #%d'               => 'إذن مبيت #%d',
		'Overnight Permits'                  => 'أذونات المبيت',
		'Page'                               => 'الصفحة',
		'Pass'                               => 'ناجح',
		'Passed'                             => 'ناجح',
		'Password *'                         => 'كلمة المرور *',
		'Peer (5 criteria, /10 each):'       => 'الزملاء (5 معايير، /10 لكل منها):',
		'Peer Evaluation (Cohort)'           => 'تقييم الزملاء (الفوج)',
		'Peer Evaluation'                    => 'تقييم الزملاء',
		'Peer evaluation saved successfully.' => 'تم حفظ تقييم الزملاء بنجاح.',
		'Pending Dean Decision'              => 'انتظار قرار العميد',
		'Pending Dean'                       => 'انتظار العميد',
		'Pending Documents'                  => 'وثائق معلقة',
		'Pending Dorm Supervisor'            => 'انتظار مشرف السكن',
		'Pending SA Manager'                 => 'انتظار مدير شؤون الطلاب',
		'Pending Supervisor'                 => 'انتظار المشرف',
		'Pending'                            => 'معلق',
		'Period Name'                        => 'اسم الفترة',
		'Period activated.'                  => 'تم تفعيل الفترة.',
		'Period and evaluatee are required.' => 'الفترة والمُقيَّم مطلوبان.',
		'Period deactivated.'                => 'تم تعطيل الفترة.',
		'Period not found.'                  => 'الفترة غير موجودة.',
		'Permit Details'                     => 'تفاصيل الإذن',
		'Permit not found.'                  => 'الإذن غير موجود.',
		'Permits & Requests'                 => 'الأذونات والطلبات',
		'Phone Number'                       => 'رقم الهاتف',
		'Phone'                              => 'الهاتف',
		'Please enter at least one score before submitting.'
			=> 'يرجى إدخال درجة واحدة على الأقل قبل الإرسال.',
		'Please select a file first.'        => 'يرجى اختيار ملف أولاً.',
		'Please select a file.'              => 'يرجى اختيار ملف.',
		'Please select an evaluation period to view the aggregation table.'
			=> 'يرجى اختيار فترة تقييم لعرض جدول التجميع.',
		'Please select an evaluation period.' => 'يرجى اختيار فترة تقييم.',
		'Points'                             => 'النقاط',
		'Portal pages created successfully. The page list has been updated.'
			=> 'تم إنشاء صفحات البوابة بنجاح. تم تحديث قائمة الصفحات.',
		'Present'                            => 'حاضر',
		'Problem solving ability'            => 'القدرة على حل المشكلات',
		'Profile Information'                => 'معلومات الملف الشخصي',
		'Profile not found. Please contact administration.'
			=> 'لم يتم العثور على الملف الشخصي. يرجى التواصل مع الإدارة.',
		'Quick Access'                       => 'وصول سريع',
		'RSYI HR System plugin is not active. Student Affairs requires it to manage roles and departments.'
			=> 'إضافة نظام HR غير مفعّلة. تتطلبها شؤون الطلاب لإدارة الأدوار والأقسام.',
		'RSYI HR System v%1$s active. Total employees: %2$d'
			=> 'نظام HR RSYI v%1$s مفعّل. إجمالي الموظفين: %2$d',
		'RSYI HR System'                     => 'نظام HR RSYI',
		'Rate a student on the 6 criteria below (/10 each). The system will record your role automatically.'
			=> 'قيّم الطالب على المعايير الستة التالية (/10 لكل منها). سيسجل النظام دورك تلقائياً.',
		'Rate each student in your cohort on the 5 criteria below. Each criterion is scored from 0 to 10.'
			=> 'قيّم كل طالب في فوجك على المعايير الخمسة التالية. كل معيار من 0 إلى 10.',
		'Rate your cohort members'           => 'قيّم أعضاء فوجك',
		'Re-seed Default Violation Types'    => 'إعادة تهيئة أنواع المخالفات الافتراضية',
		'Re-upload'                          => 'إعادة الرفع',
		'Reached 40 behavior points'         => 'وصل إلى 40 نقطة سلوكية',
		'Reason'                             => 'السبب',
		'Recent Active Violations'           => 'المخالفات النشطة الأخيرة',
		'Registered'                         => 'مسجّل',
		'Reject / Overturn'                  => 'رفض / إلغاء',
		'Reject'                             => 'رفض',
		'Rejected'                           => 'مرفوض',
		'Rejection reason (optional):'       => 'سبب الرفض (اختياري):',
		'Rejection reason:'                  => 'سبب الرفض:',
		'Remove Logo'                        => 'إزالة الشعار',
		'Replace & Upload'                   => 'استبدال ورفع',
		'Repository must be public, or enter a Personal Access Token below for private repos.'
			=> 'يجب أن يكون المستودع عاماً، أو أدخل رمز وصول شخصي أدناه للمستودعات الخاصة.',
		'Repository'                         => 'المستودع',
		'Required Documents'                 => 'الوثائق المطلوبة',
		'Result'                             => 'النتيجة',
		'Review the case and approve or reject the expulsion.'
			=> 'راجع القضية وافقها أو ارفضها.',
		'Role not found.'                    => 'الدور غير موجود.',
		'Role slug is required.'             => 'رمز الدور مطلوب.',
		'Roles & Permissions'                => 'الأدوار والصلاحيات',
		'Save Evaluation'                    => 'حفظ التقييم',
		'Save GitHub Settings'               => 'حفظ إعدادات GitHub',
		'Save Settings'                      => 'حفظ الإعدادات',
		'Saved permissions for "%s".'        => 'تم حفظ الصلاحيات لـ "%s".',
		'Score /10'                          => 'الدرجة /10',
		'Score'                              => 'الدرجة',
		'Security check failed.'             => 'فشل التحقق الأمني.',
		'Select Evaluation Period'           => 'اختر فترة التقييم',
		'Select Evaluation Period:'          => 'اختر فترة التقييم:',
		'Self-discipline in behavior and timing'
			=> 'الانضباط الذاتي في السلوك والمواعيد',
		'Sense of responsibility'            => 'الإحساس بالمسؤولية',
		'Session Details'                    => 'تفاصيل الجلسة',
		'Session'                            => 'الجلسة',
		'Settings saved successfully.'       => 'تم حفظ الإعدادات بنجاح.',
		'Settings'                           => 'الإعدادات',
		'Sign In'                            => 'تسجيل الدخول',
		'Start'                              => 'البداية',
		'Status'                             => 'الحالة',
		'Student Affairs Manager'            => 'مدير شؤون الطلاب',
		'Student Affairs'                    => 'شؤون الطلاب',
		'Student Being Evaluated'            => 'الطالب المُقيَّم',
		'Student Portal Pages'               => 'صفحات بوابة الطالب',
		'Student Portal'                     => 'بوابة الطالب',
		'Student Registration Portal'        => 'بوابة تسجيل الطالب',
		'Student Supervisor'                 => 'المشرف الأكاديمي',
		'Student deleted successfully.'      => 'تم حذف الطالب بنجاح.',
		'Student has been expelled.'         => 'تم فصل الطالب.',
		'Student not found.'                 => 'الطالب غير موجود.',
		'Student'                            => 'الطالب',
		'Students'                           => 'الطلاب',
		'Study Materials'                    => 'المواد الدراسية',
		'Submit Evaluation'                  => 'إرسال التقييم',
		'Submitted'                          => 'مُسلَّم',
		'Suspended'                          => 'موقوف',
		'System Integration'                 => 'تكامل النظام',
		'System is up to date. Current version %s is the latest.'
			=> 'النظام محدّث. الإصدار الحالي %s هو الأحدث.',
		'The plugin needs 6 WordPress pages for the student portal. Click the button below to create them automatically.'
			=> 'تحتاج الإضافة إلى 6 صفحات WordPress لبوابة الطالب. انقر على الزر أدناه لإنشائها تلقائياً.',
		'There are no open evaluation periods for your cohort at this time. Please check back later.'
			=> 'لا توجد فترات تقييم مفتوحة لفوجك حالياً. يرجى المراجعة لاحقاً.',
		'This role cannot be modified here.' => 'لا يمكن تعديل هذا الدور هنا.',
		'This section allows future integration of the Student Affairs system with other institute systems (Warehouse, HR, etc.).'
			=> 'يسمح هذا القسم بالتكامل المستقبلي مع أنظمة المعهد الأخرى (المخزن، HR، إلخ).',
		'This will delete the student account and all related records. This action cannot be undone.'
			=> 'سيؤدي هذا إلى حذف حساب الطالب وجميع السجلات المرتبطة. لا يمكن التراجع عن هذا الإجراء.',
		'To'                                 => 'إلى',
		'Total Exams'                        => 'إجمالي الامتحانات',
		'Total Points'                       => 'إجمالي النقاط',
		'Total'                              => 'الإجمالي',
		'Triggered By'                       => 'أثاره',
		'Type'                               => 'النوع',
		'URL'                                => 'الرابط',
		'Under Review'                       => 'قيد المراجعة',
		'Unknown error.'                     => 'خطأ غير معروف.',
		'Upload & track required documents'  => 'رفع الوثائق المطلوبة ومتابعتها',
		'Upload Document'                    => 'رفع وثيقة',
		'Upload Progress'                    => 'تقدم الرفع',
		'Upload all 8 required documents. Each will be reviewed by the administration.'
			=> 'ارفع جميع الوثائق الـ8 المطلوبة. ستراجع كل منها الإدارة.',
		'Upload failed.'                     => 'فشل الرفع.',
		'Upload on behalf of student'        => 'رفع نيابة عن الطالب',
		'Uploaded:'                          => 'تم الرفع:',
		'Use This Image'                     => 'استخدم هذه الصورة',
		'Used in PDF reports and expulsion letters.'
			=> 'يُستخدم في تقارير PDF وخطابات الطرد.',
		'Used in expulsion letters and report signatures.'
			=> 'يُستخدم في خطابات الطرد وتوقيعات التقارير.',
		'Username *'                         => 'اسم المستخدم *',
		'View Document'                      => 'عرض الوثيقة',
		'View Full Profile'                  => 'عرض الملف الشخصي كاملاً',
		'View Student'                       => 'عرض الطالب',
		'View Violations'                    => 'عرض المخالفات',
		'View exam results & grades'         => 'عرض نتائج الامتحانات والدرجات',
		'View your behavior points'          => 'عرض نقاط سلوكك',
		'View'                               => 'عرض',
		'Violation Type'                     => 'نوع المخالفة',
		'Violation Types'                    => 'أنواع المخالفات',
		'Violation types table is empty. Click the button below to add the default types.'
			=> 'جدول أنواع المخالفات فارغ. انقر على الزر أدناه لإضافة الأنواع الافتراضية.',
		'Violations'                         => 'المخالفات',
		'Welcome,'                           => 'مرحباً،',
		'When a new Release is published on GitHub, an update notification will appear automatically on the Plugins page.'
			=> 'عند نشر إصدار جديد على GitHub، ستظهر إشعار تحديث تلقائياً على صفحة الإضافات.',
		'You are already logged in.'         => 'أنت مسجّل الدخول بالفعل.',
		'You cannot evaluate yourself.'      => 'لا يمكنك تقييم نفسك.',
		'You have reached %d behavior points. You must acknowledge this warning to continue.'
			=> 'لقد وصلت إلى %d نقطة سلوكية. يجب إقرارك بهذا التحذير للمتابعة.',
		'Your account must be active to submit evaluations.'
			=> 'يجب أن يكون حسابك نشطاً لتقديم التقييمات.',
		'Your account will be activated after all 8 required documents are uploaded and approved.'
			=> 'سيتم تفعيل حسابك بعد رفع والموافقة على جميع الوثائق الـ8 المطلوبة.',
		'Your attendance history'            => 'سجل حضورك',
		'approved'                           => 'موافق عليه',
		'default'                            => 'افتراضي',
		'documents approved'                 => 'وثائق معتمدة',
		'e.g. January 2026 Evaluation'       => 'مثال: تقييم يناير 2026',
		'e.g. Mohamed Ahmed Ali'             => 'مثال: محمد أحمد علي',
		'pts'                                => 'نقطة',
		'student@example.com'               => 'student@example.com',
		'to'                                 => 'إلى',
		'01xxxxxxxxx'                        => '01xxxxxxxxx',

		// ── Portal navigation ──────────────────────────────────────────
		'← Sign Out'                         => '← تسجيل الخروج',
	];

} // end class RSYI_Language
