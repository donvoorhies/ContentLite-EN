<?php
/**
 * Example homepage using the shared templates.
 */
require_once __DIR__ . '/config.php';

$meta_title       = 'Home – ' . SITE_NAME;
$meta_description = SITE_DESCRIPTION;
$body_class       = 'page-home';

require __DIR__ . '/assets/_header.php';
?>

<!--
    Fra dette punkt er du inde i <main id="main-content">.
    Brug semantiske HTML5-elementer (section, article, aside …).
    Layout styres 100 % af dit CSS-framework / /assets/site.css.
-->

<section aria-labelledby="welcome-heading">
    <h1 id="welcome-heading">Welcome to <?= e(SITE_NAME) ?></h1>
    <p>
        This is the homepage. Replace this content with your own.
        The main navigation and footer are shared across the site
        and are loaded automatically from <code>_header.php</code> and
        <code>_footer.php</code> via <code>config.php</code>.
    </p>
</section>

<section aria-labelledby="latest-news-heading">
    <h2 id="latest-news-heading">Latest news</h2>
    <?php
    /*
     * Valgfrit: træk de 3 latest udgivne articles ind på forsiden.
     * Brug samme forberedte statement-mønster som news.php.
     */
    $tCms = table_name('cms_content');
    $stmt = db()->prepare(
        "SELECT id, title, updated_at FROM {$tCms}
         WHERE status = 'published'
         ORDER BY updated_at DESC LIMIT 3"
    );
    $stmt->execute();
    $latest = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    ?>
    <?php if (empty($latest)): ?>
        <p>No news yet.</p>
    <?php else: ?>
    <ul>
        <?php foreach ($latest as $n): ?>
        <li>
            <a href="news.php?article=<?= $n['id'] ?>"><?= e($n['title']) ?></a>
            <small>(<?= date('j. F Y', strtotime($n['updated_at'])) ?>)</small>
        </li>
        <?php endforeach; ?>
    </ul>
    <p><a href="news.php">View all news →</a></p>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/assets/_footer.php'; ?>
