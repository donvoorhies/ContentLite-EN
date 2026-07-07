<?php
/**
 * Public gallery page.
 * ?album=ID shows one album with a lightbox, otherwise an album overview.
 */
require_once __DIR__ . '/config.php';

// ─── Datalag ──────────────────────────────────────────────────────────────────
function fetch_all_albums(): array {
    $tAlbums = table_name('gallery_albums');
    $tBilleder = table_name('gallery_images');
    $stmt = db()->prepare(
        "SELECT a.id, a.name, a.description, a.created_at, COUNT(b.id) AS image_count
         FROM {$tAlbums} a
         LEFT JOIN {$tBilleder} b ON b.album_id = a.id
         GROUP BY a.id ORDER BY a.created_at DESC"
    );
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function fetch_album(int $id): ?array {
    $tAlbums = table_name('gallery_albums');
    $stmt = db()->prepare("SELECT * FROM {$tAlbums} WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function fetch_images(int $album_id): array {
    $tBilleder = table_name('gallery_images');
    $stmt = db()->prepare(
        "SELECT id, filename, title, description
         FROM {$tBilleder}
         WHERE album_id = ?
         ORDER BY sort_order ASC, id ASC"
    );
    $stmt->bind_param('i', $album_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

function first_image(int $album_id): ?array {
    $tBilleder = table_name('gallery_images');
    $stmt = db()->prepare(
        "SELECT filename, title FROM {$tBilleder}
         WHERE album_id = ? ORDER BY sort_order ASC, id ASC LIMIT 1"
    );
    $stmt->bind_param('i', $album_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

// ─── Routing ──────────────────────────────────────────────────────────────────
$album_id = isset($_GET['album']) ? (int)$_GET['album'] : 0;
$album    = null;
$images = [];
$albums   = [];

if ($album_id > 0) {
    $album = fetch_album($album_id);
    if (!$album) { header('Location: gallery.php', true, 302); exit; }
    $images = fetch_images($album_id);
    $meta_title       = e($album['name']) . ' – Gallery – ' . SITE_NAME;
    $meta_description = $album['description']
        ? e(mb_strimwidth($album['description'], 0, 160, '…'))
        : SITE_DESCRIPTION;
} else {
    $albums           = fetch_all_albums();
    $meta_title       = 'Gallery – ' . SITE_NAME;
    $meta_description = SITE_DESCRIPTION;
}
$body_class = 'page-gallery';

// ─── Sidefil-specifik CSS ─────────────────────────────────────────────────────
$extra_css = <<<CSS
<style>
/*
 * Gallery page styles.
 * Bruger kun CSS custom properties fra :root i _header.php.
 * Tilpas / overstyr frit i /assets/site.css.
 */

/* ── Tilbage-link ── */
.back-link { display: inline-block; margin-bottom: var(--afstand-lg);
                color: var(--farve-dæmpet); text-decoration: none; font-size: .875rem; }
.back-link:hover { color: var(--farve-tekst); }

/* ════════════════════════════════════
   ALBUMOVERSIGT
   ════════════════════════════════════ */
.album-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: var(--afstand-lg);
}
.album-card {
    display: block;
    text-decoration: none;
    color: var(--farve-tekst);
    border: 1px solid var(--farve-kant);
    border-radius: var(--radius);
    overflow: hidden;
    background: var(--farve-bg);
    box-shadow: var(--skygge-sm);
    transition: box-shadow .2s, transform .2s;
}
.album-card:hover {
    box-shadow: var(--skygge-md);
    transform: translateY(-2px);
    text-decoration: none;
}
.album-cover {
    width: 100%; aspect-ratio: 16/10;
    object-fit: cover; display: block;
    background: var(--farve-kant);
}
.album-cover-tom {
    width: 100%; aspect-ratio: 16/10;
    background: var(--farve-overflade);
    display: flex; align-items: center;
    justify-content: center;
    font-size: 2.5rem; color: var(--farve-kant);
}
.album-body       { padding: var(--afstand-md); }
.album-body h2    { margin: 0 0 .25rem; font-size: 1rem; font-weight: 600; }
.album-body .meta { font-size: .78rem; color: var(--farve-dæmpet); }
.album-body .description { font-size: .85rem; color: var(--farve-dæmpet);
                    margin-top: .35rem; line-height: 1.5; }

/* ════════════════════════════════════
   BILLEDGITTER
   ════════════════════════════════════ */
.image-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: .75rem;
}
.image-item {
    position: relative; overflow: hidden;
    border-radius: var(--radius);
    border: 1px solid var(--farve-kant);
    aspect-ratio: 1; cursor: zoom-in;
    background: var(--farve-overflade);
}
.image-item img {
    width: 100%; height: 100%; object-fit: cover; display: block;
    transition: transform .3s, filter .3s;
}
.image-item:hover img {
    transform: scale(1.04); filter: brightness(.9);
}
.image-overlay {
    position: absolute; bottom: 0; left: 0; right: 0;
    background: linear-gradient(transparent, rgba(0,0,0,.65));
    color: #fff; padding: .9rem .7rem .6rem;
    font-size: .78rem; opacity: 0; transition: opacity .2s;
    pointer-events: none;
}
.image-item:hover .image-overlay { opacity: 1; }
.image-overlay strong { display: block; font-weight: 500; }
.image-overlay span   { font-size: .72rem; opacity: .8; }

/* ════════════════════════════════════
   LIGHTBOX
   ════════════════════════════════════ */
.lightbox {
    display: none; position: fixed; inset: 0; z-index: 9999;
    background: rgba(8,7,6,.92);
    align-items: center; justify-content: center;
    flex-direction: column; gap: .75rem; padding: 1.5rem;
}
.lightbox.open { display: flex; }
.lb-img-wrap   {
    position: relative; display: flex;
    align-items: center; justify-content: center;
    max-width: 92vw; max-height: 80vh;
}
.lb-img-wrap img {
    max-width: 100%; max-height: 80vh;
    object-fit: contain; display: block;
    border-radius: var(--radius);
    box-shadow: 0 16px 48px rgba(0,0,0,.7);
}
.lb-pil {
    position: absolute; top: 50%; transform: translateY(-50%);
    background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2);
    color: #fff; font-size: 1.3rem; width: 44px; height: 44px;
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: background .15s; user-select: none; z-index: 2;
}
.lb-pil:hover { background: rgba(255,255,255,.28); }
.lb-pil.prev  { left: -58px; }
.lb-pil.next  { right: -58px; }
.lb-luk {
    position: fixed; top: 1rem; right: 1.25rem;
    background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.2);
    color: #fff; font-size: 1.5rem; width: 42px; height: 42px;
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    cursor: pointer; transition: background .15s; z-index: 3; line-height: 1;
}
.lb-luk:hover { background: rgba(255,255,255,.28); }
.lb-tekst     { text-align: center; color: rgba(255,255,255,.85); max-width: 600px; }
.lb-tekst strong { display: block; font-size: .95rem; font-weight: 500; }
.lb-tekst span   { font-size: .8rem; opacity: .65; }
.lb-taeller      { font-size: .75rem; color: rgba(255,255,255,.4);
                   font-family: var(--font-mono); }

/* ── Responsiv ── */
@media (max-width: 767px) {
    .album-grid   { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); }
    .image-grid { grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: .5rem; }
    .lb-pil       { display: none; }
}
@media (max-width: 479px) {
    .album-grid   { grid-template-columns: 1fr 1fr; gap: .6rem; }
    .image-grid { grid-template-columns: 1fr 1fr; gap: .4rem; }
}
</style>
CSS;

require __DIR__ . '/assets/_header.php';
?>

<?php if ($album): ?>
<!-- ══════════════════════════════════════
     ALBUM – BILLEDVISNING
     ══════════════════════════════════════ -->

<a href="gallery.php" class="back-link">← All albums</a>

<header>
    <h1><?= e($album['name']) ?></h1>
    <?php if ($album['description']): ?>
        <p><?= e($album['description']) ?></p>
    <?php else: ?>
        <p><?= count($images) ?> image<?= count($images) != 1 ? 's' : '' ?></p>
    <?php endif; ?>
</header>

<?php if (empty($images)): ?>
    <p>This album does not contain any images yet.</p>
<?php else: ?>

<div class="image-grid">
<?php foreach ($images as $idx => $b): ?>
    <div class="image-item"
         role="button" tabindex="0"
         aria-label="View image: <?= e($b['title'] ?: $b['filename']) ?>"
         onclick="openLightbox(<?= $idx ?>)"
         onkeydown="if(event.key==='Enter'||event.key===' ')openLightbox(<?= $idx ?>)">
        <img src="<?= e(UPLOAD_URL . $b['filename']) ?>"
             alt="<?= e($b['title'] ?: '') ?>"
             loading="lazy">
        <?php if ($b['title'] || $b['description']): ?>
        <div class="image-overlay">
            <?php if ($b['title']): ?>
                <strong><?= e($b['title']) ?></strong>
            <?php endif; ?>
            <?php if ($b['description']): ?>
                <span><?= e(mb_strimwidth($b['description'], 0, 80, '…')) ?></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>
</div>

<!-- Lightbox -->
<div class="lightbox" id="lightbox" role="dialog" aria-modal="true" aria-label="Image viewer">
    <button class="lb-luk" id="lbLuk" aria-label="Close (Esc)">×</button>
    <div class="lb-img-wrap">
        <button class="lb-pil prev" id="lbPrev" aria-label="Previous (←)">&#8592;</button>
        <img id="lbImg" src="" alt="">
        <button class="lb-pil next" id="lbNext" aria-label="Next (→)">&#8594;</button>
    </div>
    <div class="lb-tekst">
        <strong id="lbTitel"></strong>
        <span   id="lbBesk"></span>
    </div>
    <div class="lb-taeller" id="lbTaeller"></div>
</div>

<?php
$billeder_js = array_map(fn($b) => [
    'src'   => UPLOAD_URL . $b['filename'],
    'title' => $b['title']       ?? '',
    'besk'  => $b['description'] ?? '',
], $images);
$extra_js = '<script>
(function () {
    var data = ' . json_encode($billeder_js, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ';
    var aktiv = 0;
    var lb    = document.getElementById("lightbox");
    var img   = document.getElementById("lbImg");
    var title = document.getElementById("lbTitel");
    var besk  = document.getElementById("lbBesk");
    var taell = document.getElementById("lbTaeller");

    window.openLightbox = function (idx) {
        aktiv = idx; vis();
        lb.classList.add("open");
        document.body.style.overflow = "hidden";
    };

    function vis() {
        var b = data[aktiv];
        img.src = b.src; img.alt = b.title;
        title.textContent = b.title;
        besk.textContent  = b.besk;
        taell.textContent = (aktiv + 1) + " / " + data.length;
        document.getElementById("lbPrev").style.visibility = aktiv > 0 ? "" : "hidden";
        document.getElementById("lbNext").style.visibility = aktiv < data.length - 1 ? "" : "hidden";
    }
    function luk() { lb.classList.remove("open"); document.body.style.overflow = ""; }

    document.getElementById("lbLuk").addEventListener("click", luk);
    document.getElementById("lbPrev").addEventListener("click", function (e) {
        e.stopPropagation(); if (aktiv > 0) { aktiv--; vis(); }
    });
    document.getElementById("lbNext").addEventListener("click", function (e) {
        e.stopPropagation(); if (aktiv < data.length - 1) { aktiv++; vis(); }
    });
    lb.addEventListener("click", function (e) { if (e.target === lb) luk(); });
    document.addEventListener("keydown", function (e) {
        if (!lb.classList.contains("open")) return;
        if (e.key === "ArrowLeft"  && aktiv > 0)             { aktiv--; vis(); }
        if (e.key === "ArrowRight" && aktiv < data.length-1) { aktiv++; vis(); }
        if (e.key === "Escape") luk();
    });
    var tx = null;
    lb.addEventListener("touchstart", function (e) { tx = e.touches[0].clientX; }, { passive: true });
    lb.addEventListener("touchend",   function (e) {
        if (tx === null) return;
        var d = tx - e.changedTouches[0].clientX;
        if (Math.abs(d) > 50) {
            if (d > 0 && aktiv < data.length-1) { aktiv++; vis(); }
            if (d < 0 && aktiv > 0)             { aktiv--; vis(); }
        }
        tx = null;
    }, { passive: true });
})();
</script>';
?>

<?php endif; // tom album ?>

<?php else: ?>
<!-- ══════════════════════════════════════
     ALBUMOVERSIGT
     ══════════════════════════════════════ -->

<header>
    <h1>Gallery</h1>
    <?php if (!empty($albums)): ?>
        <p><?= count($albums) ?> album<?= count($albums) != 1 ? 's' : '' ?></p>
    <?php endif; ?>
</header>

<?php if (empty($albums)): ?>
    <p>There are no gallery albums yet.</p>
<?php else: ?>
<div class="album-grid">
<?php foreach ($albums as $alb):
    $thumb = first_image($alb['id']);
?>
    <a href="?album=<?= $alb['id'] ?>" class="album-card">
        <?php if ($thumb): ?>
            <img class="album-cover"
                 src="<?= e(UPLOAD_URL . $thumb['filename']) ?>"
                 alt="<?= e($alb['name']) ?>"
                 loading="lazy">
        <?php else: ?>
            <div class="album-cover-tom">🖼</div>
        <?php endif; ?>
        <div class="album-body">
            <h2><?= e($alb['name']) ?></h2>
            <p class="meta"><?= (int)$alb['image_count'] ?> image<?= $alb['image_count'] != 1 ? 's' : '' ?></p>
            <?php if ($alb['description']): ?>
                <p class="description"><?= e(mb_strimwidth($alb['description'], 0, 90, '…')) ?></p>
            <?php endif; ?>
        </div>
    </a>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<?php require __DIR__ . '/assets/_footer.php'; ?>
