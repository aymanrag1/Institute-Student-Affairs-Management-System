<?php
defined( 'ABSPATH' ) || exit;
/** @var object[] $books        All active books (grouped by category) */
/** @var object[] $my_issues    Student's currently borrowed books     */
$categories = [
    'curriculum'  => 'مناهج أجنبية / Foreign Curricula',
    'certificate' => 'شهادات / Certificates',
    'general'     => 'عام / General',
];
$grouped = [];
foreach ( $books as $b ) {
    $grouped[ $b->category ][] = $b;
}
?>
<div class="rsyi-portal rsyi-library-portal" dir="rtl">

    <!-- My borrowed books -->
    <?php if ( ! empty( $my_issues ) ) : ?>
    <section class="rsyi-section rsyi-my-books">
        <h2 class="rsyi-section-title">📖 كتبي المستعارة / My Borrowed Books</h2>
        <div class="rsyi-table-wrap">
            <table class="rsyi-table">
                <thead>
                    <tr>
                        <th>الكتاب / Book</th>
                        <th>المؤلف / Author</th>
                        <th>تاريخ الإعادة / Due Date</th>
                        <th>الحالة / Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $my_issues as $issue ) :
                        $is_overdue = $issue->status === 'overdue';
                        $status_label = $is_overdue
                            ? '<span style="color:red;font-weight:bold;">⚠ متأخر / Overdue</span>'
                            : '<span style="color:green;">في الموعد / On time</span>';
                    ?>
                    <tr>
                        <td>
                            <?php if ( $issue->cover_url ) : ?>
                                <img src="<?php echo esc_url( $issue->cover_url ); ?>" style="width:36px;height:48px;object-fit:cover;border-radius:3px;vertical-align:middle;margin-left:8px;">
                            <?php endif; ?>
                            <strong><?php echo esc_html( $issue->title_ar ); ?></strong><br>
                            <small><?php echo esc_html( $issue->title_en ); ?></small>
                        </td>
                        <td><?php echo esc_html( $issue->author ?? '—' ); ?></td>
                        <td><?php echo esc_html( $issue->due_date ); ?></td>
                        <td><?php echo $status_label; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

    <!-- Browse available books by category -->
    <?php foreach ( $categories as $cat_key => $cat_label ) :
        if ( empty( $grouped[ $cat_key ] ) ) continue;
    ?>
    <section class="rsyi-section rsyi-book-category">
        <h2 class="rsyi-section-title">
            <?php echo $cat_key === 'curriculum' ? '📘' : ( $cat_key === 'certificate' ? '🏅' : '📚' ); ?>
            <?php echo esc_html( $cat_label ); ?>
        </h2>
        <div class="rsyi-books-grid">
            <?php foreach ( $grouped[ $cat_key ] as $book ) : ?>
            <div class="rsyi-book-card <?php echo $book->available_copies < 1 ? 'rsyi-book-unavailable' : ''; ?>">
                <?php if ( $book->cover_url ) : ?>
                    <div class="rsyi-book-cover">
                        <img src="<?php echo esc_url( $book->cover_url ); ?>" alt="<?php echo esc_attr( $book->title_en ); ?>">
                    </div>
                <?php else : ?>
                    <div class="rsyi-book-cover rsyi-no-cover">📖</div>
                <?php endif; ?>
                <div class="rsyi-book-info">
                    <h4 class="rsyi-book-title"><?php echo esc_html( $book->title_ar ); ?></h4>
                    <p class="rsyi-book-title-en"><?php echo esc_html( $book->title_en ); ?></p>
                    <?php if ( $book->author ) : ?>
                        <p class="rsyi-book-author">✍ <?php echo esc_html( $book->author ); ?></p>
                    <?php endif; ?>
                    <?php if ( $book->isbn ) : ?>
                        <p class="rsyi-book-isbn"><small>ISBN: <?php echo esc_html( $book->isbn ); ?></small></p>
                    <?php endif; ?>
                    <div class="rsyi-book-availability <?php echo $book->available_copies > 0 ? 'available' : 'unavailable'; ?>">
                        <?php if ( $book->available_copies > 0 ) : ?>
                            ✅ متاح / Available (<?php echo intval( $book->available_copies ); ?> / <?php echo intval( $book->total_copies ); ?>)
                        <?php else : ?>
                            ❌ غير متاح / Not Available
                        <?php endif; ?>
                    </div>
                    <?php if ( $book->description ) : ?>
                        <p class="rsyi-book-desc"><small><?php echo esc_html( $book->description ); ?></small></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>

    <?php if ( empty( $books ) ) : ?>
    <div class="rsyi-empty">
        <p>📭 لا توجد كتب متاحة حالياً / No books available at the moment.</p>
    </div>
    <?php endif; ?>
</div>

<style>
.rsyi-library-portal { padding: 20px 0; }
.rsyi-section { margin-bottom: 40px; }
.rsyi-section-title { font-size: 1.4em; border-bottom: 2px solid #0073aa; padding-bottom: 8px; margin-bottom: 20px; }
.rsyi-books-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 20px; }
.rsyi-book-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.07); transition: transform .2s; }
.rsyi-book-card:hover { transform: translateY(-3px); box-shadow: 0 4px 16px rgba(0,0,0,.12); }
.rsyi-book-card.rsyi-book-unavailable { opacity: .65; }
.rsyi-book-cover { width: 100%; height: 160px; overflow: hidden; background: #f0f0f0; display: flex; align-items: center; justify-content: center; }
.rsyi-book-cover img { width: 100%; height: 100%; object-fit: cover; }
.rsyi-no-cover { font-size: 56px; color: #bbb; }
.rsyi-book-info { padding: 12px; }
.rsyi-book-title { margin: 0 0 4px; font-size: 1em; }
.rsyi-book-title-en { margin: 0 0 6px; color: #555; font-size: .85em; }
.rsyi-book-author { margin: 0 0 4px; font-size: .85em; color: #333; }
.rsyi-book-isbn { margin: 0 0 4px; color: #888; }
.rsyi-book-availability { font-size: .82em; font-weight: bold; margin-top: 8px; }
.rsyi-book-availability.available { color: #27ae60; }
.rsyi-book-availability.unavailable { color: #c0392b; }
.rsyi-book-desc { color: #666; margin-top: 6px; font-size: .8em; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
.rsyi-my-books { background: #f7f9fc; border: 1px solid #d0e4f5; border-radius: 8px; padding: 20px; }
.rsyi-empty { text-align: center; color: #888; padding: 40px; font-size: 1.1em; }
</style>
