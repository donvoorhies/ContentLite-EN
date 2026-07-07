<?php
/**
 * Public news page.
 * Shows published articles with pagination and single-article view.
 */
require_once __DIR__ . '/config.php';

function fetch_article(int $id): ?array {
    $tCms = table_name('cms_content');
    $stmt = db()->prepare(
        "SELECT * FROM {$tCms} WHERE id = ? AND status = 'published' LIMIT 1"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function fetch_articles(int $side): array {
    $offset = ($side - 1) * ARTICLES_PER_PAGE;
    $limit  = ARTICLES_PER_PAGE;
    $tCms   = table_name('cms_content');
    $stmt   = db()->prepare(
        "SELECT id, title, content, updated_at
         FROM {$tCms}
         WHERE status = 'published'
         ORDER BY updated_at DESC
         LIMIT ? OFFSET ?"
    );
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function fetch_total_count(): int {
    $tCms = table_name('cms_content');
    $stmt = db()->prepare(
        "SELECT COUNT(*) FROM {$tCms} WHERE status = 'published'"
    );
    $stmt->execute();
    $stmt->bind_result($image_count);
    $stmt->fetch();
    $stmt->close();
    return (int) $image_count;
}

function adjacent_links(string $timestamp): array {
    $tCms = table_name('cms_content');
    $s = db()->prepare(
        "SELECT id, title FROM {$tCms}
         WHERE status = 'published' AND updated_at > ?
         ORDER BY updated_at ASC LIMIT 1"
    );
    $s->bind_param('s', $timestamp); $s->execute();
    $next = $s->get_result()->fetch_assoc(); $s->close();

    $s = db()->prepare(
        "SELECT id, title FROM {$tCms}
         WHERE status = 'published' AND updated_at < ?
         ORDER BY updated_at DESC LIMIT 1"
    );
    $s->bind_param('s', $timestamp); $s->execute();
    $previous = $s->get_result()->fetch_assoc(); $s->close();
    return ['next' => $next, 'previous' => $previous];
}

function excerpt(string $html, int $chars = 280): string {
    $tekst('/\s+/', ' ', trim(strip_tags($html)));
    return mb_strlen($tekst) <= $chars ? $tekst : mb_substr($tekst, 0, $chars) . '…';
}

function page_url(int $side): string {
    $p = $_GET; $p = $side; unset($p['article']);
    return '?' . http_build_query($p);
}

// ─── Routing ──────────────────────────────────────────────────────────────────
$artikel_id  = isset($_GET['article']) ? (int)$_GET['article'] : 0;
$current_page = max(1, (int)($_GET ?? 1));
$total       = fetch_total_count();
$total_pages = max(1, (int)ceil($total / ARTICLES_PER_PAGE));
$current_page = min($current_page, $total_pages);
$enkelt      = null;
$nabo        = [];
$articles    = [];

if ($artikel_id > 0) {
    $enkelt = fetch_article($artikel_id);
    if (!$enkelt) { header('Location: news.php', true, 302); exit; }
    $nabo = adjacent_links($enkelt['updated_at']);
} else {
    $articles = fetch_articles($current_page);
}

// ─── Meta-tags til _header.php ────────────────────────────────────────────────
if ($enkelt) {
    $meta_title       = e($enkelt['title']) . ' – ' . SITE_NAME;
    $meta_description = excerpt($enkelt['content'], 160);
} else {
    $side_suffix      = $current_page > 1 ? ' – Page ' . $current_page : '';
    $meta_title       = 'News' . $side_suffix . ' – ' . SITE_NAME;
    $meta_description = SITE_DESCRIPTION;
}
$body_class = 'page-news';

// ─── Sidefil-specifik CSS ─────────────────────────────────────────────────────
$extra_css = <<<CSS
<style>
/*
 * News page styles.
 * Bruger kun CSS custom properties fra :root i _header.php.
 * Tilpas / overstyr frit i /assets/site.css.
 */

/* ── Artikliste ── */
.article-list   { list-style: none; padding: 0; }
.article-card    { padding: var(--afstand-lg) 0;
                   border-bottom: 1px solid var(--farve-kant); }
.article-card:last-child { border-bottom: none; }
.article-meta    { font-size: .8rem; color: var(--farve-dæmpet); margin-bottom: .4rem; }
.article-card h2 { margin: 0 0 .5rem; line-height: 1.3; font-size: 1.2rem; }
.article-card h2 a { color: var(--farve-tekst); text-decoration: none; }
.article-card h2 a:hover { color: var(--farve-accent); }
.article-excerpt  { color: var(--farve-dæmpet); margin-bottom: .65rem; line-height: 1.6; }
.read-more       { font-size: .875rem; font-weight: 600;
                   color: var(--farve-accent); text-decoration: none; }
.read-more:hover { text-decoration: underline; }

/* ── Paginering ── */
.pagination      { display: flex; flex-wrap: wrap; gap: .35rem;
                   align-items: center; justify-content: center;
                   padding: var(--afstand-xl) 0 0; }
.page-button       { display: inline-flex; align-items: center; justify-content: center;
                   min-width: 2.25rem; height: 2.25rem; padding: 0 .5rem;
                   border: 1px solid var(--farve-kant); border-radius: var(--radius);
                   background: var(--farve-bg); color: var(--farve-tekst);
                   font-size: .875rem; font-weight: 500; text-decoration: none;
                   transition: background .12s; }
.page-button:hover       { background: var(--farve-overflade); text-decoration: none; }
.page-button.aktiv       { background: var(--farve-accent); border-color: var(--farve-accent);
                          color: #fff; pointer-events: none; }
.page-button.disabled    { opacity: .35; pointer-events: none; }
.pagination-info       { font-size: .78rem; color: var(--farve-dæmpet);
                          text-align: center; margin-top: .5rem; }

/* ── Single article ── */
.back-link  { display: inline-block; margin-bottom: var(--afstand-lg);
                 color: var(--farve-dæmpet); text-decoration: none; font-size: .875rem; }
.back-link:hover { color: var(--farve-tekst); }

/* TinyMCE content formatting */
.article-content h2,
.article-content h3     { margin: 1.5rem 0 .5rem; line-height: 1.3; }
.article-content p      { margin-bottom: 1rem; }
.article-content ul,
.article-content ol     { margin: .5rem 0 1rem 1.5rem; }
.article-content blockquote {
    border-left: 3px solid var(--farve-accent);
    margin: 1rem 0; padding: .5rem 1rem;
    color: var(--farve-dæmpet); font-style: italic; }
.article-content img    { max-width: 100%; height: auto; border-radius: var(--radius); }
.article-content pre    { background: var(--farve-overflade); padding: 1rem;
                          border-radius: var(--radius); overflow-x: auto; }
.article-content code   { font-family: var(--font-mono); font-size: .875rem; }
.article-content table  { width: 100%; border-collapse: collapse; margin: 1rem 0; }
.article-content th,
.article-content td     { padding: .5rem .75rem; border: 1px solid var(--farve-kant);
                          text-align: left; }
.article-content th     { background: var(--farve-overflade); font-weight: 600; }

/* ── Previous / next ── */
.adjacent-nav       { display: flex; justify-content: space-between;
                 flex-wrap: wrap; gap: 1rem; margin-top: var(--afstand-lg);
                 padding-top: var(--afstand-lg); border-top: 1px solid var(--farve-kant); }
.adjacent-nav a     { font-size: .875rem; color: var(--farve-dæmpet);
                 text-decoration: none; max-width: 48%; }
.adjacent-nav a:hover   { color: var(--farve-accent); }
.adjacent-nav .prev::before { content: '← '; }
.adjacent-nav .next::after  { content: ' →'; }

@media (max-width: 600px) { .adjacent-nav a { max-width: 100%; } }
</style>
CSS;

require __DIR__ . '/assets/_header.php';
?>

<?php if ($enkelt): ?>
<!-- ══════════════════════════════════════
     ENKELT ARTIKEL
     ══════════════════════════════════════ -->
<article>

    <a href="news.php" class="back-link">← Back to news</a>

    <header>
        <p class="article-meta">
            <time datetime="<?= date('Y-m-d', strtotime($enkelt['updated_at'])) ?>">
                <?= date('j. F Y', strtotime($enkelt['updated_at'])) ?>
            </time>
        </p>
        <h1><?= e($enkelt['title']) ?></h1>
    </header>

    <div class="article-content">
        <?= $enkelt['content'] /*  */ ?>
    </div>

    <?php if ($nabo['previous'] || $nabo['next']): ?>
    <nav class="adjacent-nav" aria-label="Article navigation">
        <?php if ($nabo['previous']): ?>
            <a href="?article=<?= $nabo['previous']['id'] ?>" class="prev">
                <?= e(mb_strimwidth($nabo['previous']['title'], 0, 55, '…')) ?>
            </a>
        <?php else: ?><span></span><?php endif; ?>
        <?php if ($nabo['next']): ?>
            <a href="?article=<?= $nabo['next']['id'] ?>" class="next">
                <?= e(mb_strimwidth($nabo['next']['title'], 0, 55, '…')) ?>
            </a>
        <?php endif; ?>
    </nav>
    <?php endif; ?>

</article>

<?php else: ?>
<!-- ══════════════════════════════════════
     ARTIKLISTE MED PAGINERING
     ══════════════════════════════════════ -->

<header>
    <h1>News</h1>
    <?php if ($total > 0): ?>
        <p class="article-meta">
            <?= $total ?> article<?= $total != 1 ? 's' : '' ?>
            &nbsp;·&nbsp; Page <?= $current_page ?> of <?= $total_pages ?>
        </p>
    <?php endif; ?>
</header>

<?php if (empty($articles)): ?>
    <p>There are no published articles yet.</p>
<?php else: ?>

<ul class="article-list">
<?php foreach ($articles as $a): ?>
    <li class="article-card">
        <p class="article-meta">
            <time datetime="<?= date('Y-m-d', strtotime($a['updated_at'])) ?>">
                <?= date('j. F Y', strtotime($a['updated_at'])) ?>
            </time>
        </p>
        <h2><a href="?article=<?= $a['id'] ?>"><?= e($a['title']) ?></a></h2>
        <p class="article-excerpt"><?= e(excerpt($a['content'])) ?></p>
        <a href="?article=<?= $a['id'] ?>" class="read-more">Read more →</a>
    </li>
<?php endforeach; ?>
</ul>

<?php if ($total_pages > 1): ?>
<nav class="pagination" aria-label="Sidenavigation">

    <a href="<?= page_url($current_page - 1) ?>"
       class="page-button <?= $current_page <= 1 ? 'disabled' : '' ?>"
    aria-label="Previous page">&#8592;</a>

    <?php
    $vis = []; $prev = null;
    for ($i = 1; $i <= $total_pages; $i++) {
        if ($i === 1 || $i === $total_pages ||
            ($i >= $current_page - 2 && $i <= $current_page + 2)) {
            $vis[] = $i;
        }
    }
    foreach ($vis as $s):
        if ($prev !== null && $s - $prev > 1): ?>
            <span class="page-button disabled" aria-hidden="true">…</span>
        <?php endif; ?>
        <a href="<?= page_url($s) ?>"
           class="page-button <?= $s === $current_page ? 'aktiv' : '' ?>"
           <?= $s === $current_page ? 'aria-current="page"' : '' ?>
           aria-label="Page <?= $s ?>"><?= $s ?></a>
    <?php $prev = $s; endforeach; ?>

    <a href="<?= page_url($current_page + 1) ?>"
       class="page-button <?= $current_page >= $total_pages ? 'disabled' : '' ?>"
    aria-label="Next page">&#8594;</a>

</nav>
<p class="pagination-info">
    Showing <?= (($current_page - 1) * ARTICLES_PER_PAGE) + 1 ?>–<?= min($current_page * ARTICLES_PER_PAGE, $total) ?>
    of <?= $total ?> articles
</p>
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/assets/_footer.php'; ?>
