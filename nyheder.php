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
    $stmt->bind_result($antal);
    $stmt->fetch();
    $stmt->close();
    return (int) $antal;
}

function adjacent_links(string $tidspunkt): array {
    $tCms = table_name('cms_content');
    $s = db()->prepare(
        "SELECT id, title FROM {$tCms}
         WHERE status = 'published' AND updated_at > ?
         ORDER BY updated_at ASC LIMIT 1"
    );
    $s->bind_param('s', $tidspunkt); $s->execute();
    $naeste = $s->get_result()->fetch_assoc(); $s->close();

    $s = db()->prepare(
        "SELECT id, title FROM {$tCms}
         WHERE status = 'published' AND updated_at < ?
         ORDER BY updated_at DESC LIMIT 1"
    );
    $s->bind_param('s', $tidspunkt); $s->execute();
    $forrige = $s->get_result()->fetch_assoc(); $s->close();
    return ['naeste' => $naeste, 'forrige' => $forrige];
}

function excerpt(string $html, int $tegn = 280): string {
    $tekst = preg_replace('/\s+/', ' ', trim(strip_tags($html)));
    return mb_strlen($tekst) <= $tegn ? $tekst : mb_substr($tekst, 0, $tegn) . '…';
}

function page_url(int $side): string {
    $p = $_GET; $p['side'] = $side; unset($p['artikel']);
    return '?' . http_build_query($p);
}

// ─── Routing ──────────────────────────────────────────────────────────────────
$artikel_id  = isset($_GET['artikel']) ? (int)$_GET['artikel'] : 0;
$aktuel_side = max(1, (int)($_GET['side'] ?? 1));
$total       = fetch_total_count();
$sider_i_alt = max(1, (int)ceil($total / ARTICLES_PER_PAGE));
$aktuel_side = min($aktuel_side, $sider_i_alt);
$enkelt      = null;
$nabo        = [];
$artikler    = [];

if ($artikel_id > 0) {
    $enkelt = fetch_article($artikel_id);
    if (!$enkelt) { header('Location: nyheder.php', true, 302); exit; }
    $nabo = adjacent_links($enkelt['updated_at']);
} else {
    $artikler = fetch_articles($aktuel_side);
}

// ─── Meta-tags til _header.php ────────────────────────────────────────────────
if ($enkelt) {
    $meta_title       = e($enkelt['title']) . ' – ' . SITE_NAME;
    $meta_description = excerpt($enkelt['content'], 160);
} else {
    $side_suffix      = $aktuel_side > 1 ? ' – Page ' . $aktuel_side : '';
    $meta_title       = 'News' . $side_suffix . ' – ' . SITE_NAME;
    $meta_description = SITE_DESCRIPTION;
}
$body_class = 'side-nyheder';

// ─── Sidefil-specifik CSS ─────────────────────────────────────────────────────
$ekstra_css = <<<CSS
<style>
/*
 * Nyheder-specifikke styles.
 * Bruger kun CSS custom properties fra :root i _header.php.
 * Tilpas / overstyr frit i /assets/site.css.
 */

/* ── Artikliste ── */
.artikel-liste   { list-style: none; padding: 0; }
.artikel-kort    { padding: var(--afstand-lg) 0;
                   border-bottom: 1px solid var(--farve-kant); }
.artikel-kort:last-child { border-bottom: none; }
.artikel-meta    { font-size: .8rem; color: var(--farve-dæmpet); margin-bottom: .4rem; }
.artikel-kort h2 { margin: 0 0 .5rem; line-height: 1.3; font-size: 1.2rem; }
.artikel-kort h2 a { color: var(--farve-tekst); text-decoration: none; }
.artikel-kort h2 a:hover { color: var(--farve-accent); }
.artikel-excerpt  { color: var(--farve-dæmpet); margin-bottom: .65rem; line-height: 1.6; }
.laes-mere       { font-size: .875rem; font-weight: 600;
                   color: var(--farve-accent); text-decoration: none; }
.laes-mere:hover { text-decoration: underline; }

/* ── Paginering ── */
.paginering      { display: flex; flex-wrap: wrap; gap: .35rem;
                   align-items: center; justify-content: center;
                   padding: var(--afstand-xl) 0 0; }
.side-knap       { display: inline-flex; align-items: center; justify-content: center;
                   min-width: 2.25rem; height: 2.25rem; padding: 0 .5rem;
                   border: 1px solid var(--farve-kant); border-radius: var(--radius);
                   background: var(--farve-bg); color: var(--farve-tekst);
                   font-size: .875rem; font-weight: 500; text-decoration: none;
                   transition: background .12s; }
.side-knap:hover       { background: var(--farve-overflade); text-decoration: none; }
.side-knap.aktiv       { background: var(--farve-accent); border-color: var(--farve-accent);
                          color: #fff; pointer-events: none; }
.side-knap.disabled    { opacity: .35; pointer-events: none; }
.paginering-info       { font-size: .78rem; color: var(--farve-dæmpet);
                          text-align: center; margin-top: .5rem; }

/* ── Enkelt artikel ── */
.tilbage-link  { display: inline-block; margin-bottom: var(--afstand-lg);
                 color: var(--farve-dæmpet); text-decoration: none; font-size: .875rem; }
.tilbage-link:hover { color: var(--farve-tekst); }

/* Formatering af TinyMCE-content */
.artikel-content h2,
.artikel-content h3     { margin: 1.5rem 0 .5rem; line-height: 1.3; }
.artikel-content p      { margin-bottom: 1rem; }
.artikel-content ul,
.artikel-content ol     { margin: .5rem 0 1rem 1.5rem; }
.artikel-content blockquote {
    border-left: 3px solid var(--farve-accent);
    margin: 1rem 0; padding: .5rem 1rem;
    color: var(--farve-dæmpet); font-style: italic; }
.artikel-content img    { max-width: 100%; height: auto; border-radius: var(--radius); }
.artikel-content pre    { background: var(--farve-overflade); padding: 1rem;
                          border-radius: var(--radius); overflow-x: auto; }
.artikel-content code   { font-family: var(--font-mono); font-size: .875rem; }
.artikel-content table  { width: 100%; border-collapse: collapse; margin: 1rem 0; }
.artikel-content th,
.artikel-content td     { padding: .5rem .75rem; border: 1px solid var(--farve-kant);
                          text-align: left; }
.artikel-content th     { background: var(--farve-overflade); font-weight: 600; }

/* ── Forrige / næste ── */
.nabopil       { display: flex; justify-content: space-between;
                 flex-wrap: wrap; gap: 1rem; margin-top: var(--afstand-lg);
                 padding-top: var(--afstand-lg); border-top: 1px solid var(--farve-kant); }
.nabopil a     { font-size: .875rem; color: var(--farve-dæmpet);
                 text-decoration: none; max-width: 48%; }
.nabopil a:hover   { color: var(--farve-accent); }
.nabopil .prev::before { content: '← '; }
.nabopil .next::after  { content: ' →'; }

@media (max-width: 600px) { .nabopil a { max-width: 100%; } }
</style>
CSS;

require __DIR__ . '/assets/_header.php';
?>

<?php if ($enkelt): ?>
<!-- ══════════════════════════════════════
     ENKELT ARTIKEL
     ══════════════════════════════════════ -->
<article>

    <a href="nyheder.php" class="tilbage-link">← Back to news</a>

    <header>
        <p class="artikel-meta">
            <time datetime="<?= date('Y-m-d', strtotime($enkelt['updated_at'])) ?>">
                <?= date('j. F Y', strtotime($enkelt['updated_at'])) ?>
            </time>
        </p>
        <h1><?= e($enkelt['title']) ?></h1>
    </header>

    <div class="artikel-content">
        <?= $enkelt['content'] /* HTML fra TinyMCE – allerede saniteret ved gem */ ?>
    </div>

    <?php if ($nabo['forrige'] || $nabo['naeste']): ?>
    <nav class="nabopil" aria-label="Article navigation">
        <?php if ($nabo['forrige']): ?>
            <a href="?artikel=<?= $nabo['forrige']['id'] ?>" class="prev">
                <?= e(mb_strimwidth($nabo['forrige']['title'], 0, 55, '…')) ?>
            </a>
        <?php else: ?><span></span><?php endif; ?>
        <?php if ($nabo['naeste']): ?>
            <a href="?artikel=<?= $nabo['naeste']['id'] ?>" class="next">
                <?= e(mb_strimwidth($nabo['naeste']['title'], 0, 55, '…')) ?>
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
        <p class="artikel-meta">
            <?= $total ?> article<?= $total != 1 ? 's' : '' ?>
            &nbsp;·&nbsp; Page <?= $aktuel_side ?> of <?= $sider_i_alt ?>
        </p>
    <?php endif; ?>
</header>

<?php if (empty($artikler)): ?>
    <p>There are no published articles yet.</p>
<?php else: ?>

<ul class="artikel-liste">
<?php foreach ($artikler as $a): ?>
    <li class="artikel-kort">
        <p class="artikel-meta">
            <time datetime="<?= date('Y-m-d', strtotime($a['updated_at'])) ?>">
                <?= date('j. F Y', strtotime($a['updated_at'])) ?>
            </time>
        </p>
        <h2><a href="?artikel=<?= $a['id'] ?>"><?= e($a['title']) ?></a></h2>
        <p class="artikel-excerpt"><?= e(excerpt($a['content'])) ?></p>
        <a href="?artikel=<?= $a['id'] ?>" class="laes-mere">Read more →</a>
    </li>
<?php endforeach; ?>
</ul>

<?php if ($sider_i_alt > 1): ?>
<nav class="paginering" aria-label="Sidenavigation">

    <a href="<?= page_url($aktuel_side - 1) ?>"
       class="side-knap <?= $aktuel_side <= 1 ? 'disabled' : '' ?>"
    aria-label="Previous page">&#8592;</a>

    <?php
    $vis = []; $prev = null;
    for ($i = 1; $i <= $sider_i_alt; $i++) {
        if ($i === 1 || $i === $sider_i_alt ||
            ($i >= $aktuel_side - 2 && $i <= $aktuel_side + 2)) {
            $vis[] = $i;
        }
    }
    foreach ($vis as $s):
        if ($prev !== null && $s - $prev > 1): ?>
            <span class="side-knap disabled" aria-hidden="true">…</span>
        <?php endif; ?>
        <a href="<?= page_url($s) ?>"
           class="side-knap <?= $s === $aktuel_side ? 'aktiv' : '' ?>"
           <?= $s === $aktuel_side ? 'aria-current="page"' : '' ?>
           aria-label="Page <?= $s ?>"><?= $s ?></a>
    <?php $prev = $s; endforeach; ?>

    <a href="<?= page_url($aktuel_side + 1) ?>"
       class="side-knap <?= $aktuel_side >= $sider_i_alt ? 'disabled' : '' ?>"
    aria-label="Next page">&#8594;</a>

</nav>
<p class="paginering-info">
    Showing <?= (($aktuel_side - 1) * ARTICLES_PER_PAGE) + 1 ?>–<?= min($aktuel_side * ARTICLES_PER_PAGE, $total) ?>
    of <?= $total ?> articles
</p>
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/assets/_footer.php'; ?>
