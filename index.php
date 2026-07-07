<?php
/**
 * Example homepage using the shared templates.
 */
require_once __DIR__ . '/config.php';

$meta_title       = 'Home – ' . SITE_NAME;
$meta_description = SITE_DESCRIPTION;
$body_class       = 'side-forside';

require __DIR__ . '/assets/_header.php';
?>

<!--
    Fra dette punkt er du inde i <main id="hoved-content">.
    Brug semantiske HTML5-elementer (section, article, aside …).
    Layout styres 100 % af dit CSS-framework / /assets/site.css.
-->

<section aria-labelledby="velkommen-overskrift">
    <h1 id="velkommen-overskrift">Welcome to <?= e(SITE_NAME) ?></h1>
    <p>
        This is the homepage. Replace this content with your own.
        The main navigation and footer are shared across the site
        and are loaded automatically from <code>_header.php</code> and
        <code>_footer.php</code> via <code>config.php</code>.
    </p>
</section>

<section aria-labelledby="seneste-nyheder-overskrift">
    <h2 id="seneste-nyheder-overskrift">Latest news</h2>
    <?php
    /*
     * Valgfrit: træk de 3 seneste udgivne artikler ind på forsiden.
     * Brug samme forberedte statement-mønster som nyheder.php.
     */
    $tCms = table_name('cms_content');
    $stmt = db()->prepare(
        "SELECT id, title, updated_at FROM {$tCms}
         WHERE status = 'published'
         ORDER BY updated_at DESC LIMIT 3"
    );
    $stmt->execute();
    $seneste = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    ?>
    <?php if (empty($seneste)): ?>
        <p>No news yet.</p>
    <?php else: ?>
    <ul>
        <?php foreach ($seneste as $n): ?>
        <li>
            <a href="nyheder.php?artikel=<?= $n['id'] ?>"><?= e($n['title']) ?></a>
            <small>(<?= date('j. F Y', strtotime($n['updated_at'])) ?>)</small>
        </li>
        <?php endforeach; ?>
    </ul>
    <p><a href="nyheder.php">View all news →</a></p>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/assets/_footer.php'; ?>
