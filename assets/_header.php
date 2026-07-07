<?php
/**
 * Shared site header.
 * Set page-specific meta variables before including this file.
 */

// Ensure config is loaded.
if (!defined('SITE_NAME')) {
    require_once __DIR__ . '/config.php';
}

// Build meta values with sensible fallbacks.
$_title       = isset($meta_title)       ? $meta_title       : SITE_NAME;
$_description = isset($meta_description) ? $meta_description : SITE_DESCRIPTION;
$_og_image  = isset($meta_og_image)  ? $meta_og_image  : SITE_OG_IMAGE;
$_body_class  = isset($body_class)       ? $body_class        : '';
$_kanonisk    = canonical_url();
?>
<!DOCTYPE html>
<html lang="<?= (SITE_LANGUAGE) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Primary meta tags -->
    <title><?= e($_title) ?></title>
    <meta name="description" content="<?= e($_description) ?>">
    <meta name="author"      content="<?= e(SITE_AUTHOR) ?>">
    <link rel="canonical"    href="<?= e($_kanonisk) ?>">

    <!-- Open Graph -->
    <meta property="og:type"        content="website">
    <meta property="og:url"         content="<?= e($_kanonisk) ?>">
    <meta property="og:title"       content="<?= e($_title) ?>">
    <meta property="og:description" content="<?= e($_description) ?>">
    <meta property="og:image"       content="<?= e($_og_image) ?>">
    <meta property="og:locale"      content="<?= e(defined('SITE_OG_LOCALE') ? SITE_OG_LOCALE : 'en_GB') ?>">
    <meta property="og:site_name"   content="<?= e(SITE_NAME) ?>">

    <!-- Twitter / X card -->
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="<?= e($_title) ?>">
    <meta name="twitter:description" content="<?= e($_description) ?>">
    <meta name="twitter:image"       content="<?= e($_og_image) ?>">

    <!-- Favicon -->
    <link rel="icon"             href="/assets/favicon.svg"               type="image/svg+xml">
    <link rel="manifest"         href="/assets/site.webmanifest">

    <!--
    ════════════════════════════════════════════════════════════════════════════
    CSS-VARIABLER (tema-tokens)

    Disse custom properties er det eneste CSS der dikteres globalt.
    Tilpas dem frit til dit design – eller overstyr dem i dit eget stylesheet.
    Alle sitespecifikke komponenter (nyheder, gallery) bruger KUN disse tokens,
    så du kan skifte hele temaet ved at redigere ét sted.
    ════════════════════════════════════════════════════════════════════════════
    -->
    <style>
    :root {
        /* ── Farvepalette ── */
        --farve-bg:          #ffffff;
        --farve-overflade:   #f9f9f8;
        --farve-kant:        #e5e3de;
        --farve-tekst:       #1a1917;
        --farve-dæmpet:      #6b6964;
        --farve-accent:      #2563eb;
        --farve-accent-hover:#1d4ed8;
        --farve-fare:        #dc2626;
        --farve-ok:          #16a34a;

        /* ── Typografi ── */
        --font-brød:   system-ui, -apple-system, 'Segoe UI', sans-serif;
        --font-mono:   ui-monospace, 'Cascadia Code', monospace;
        --font-str:    1rem;
        --linje-hojde: 1.7;

        /* ── Rum ── */
        --afstand-xs:  0.25rem;
        --afstand-sm:  0.5rem;
        --afstand-md:  1rem;
        --afstand-lg:  2rem;
        --afstand-xl:  4rem;

        /* ── Formgivning ── */
        --radius:      4px;
        --skygge-sm:   0 1px 3px rgba(0,0,0,.07);
        --skygge-md:   0 4px 16px rgba(0,0,0,.09);

        /* ── Layout ── */
        --max-bredde:  1200px;   /* juster til dit grid */
        --contents-bredde: 720px;
    }

    /* Tilgængelighed: skip-link */
    .skip-link {
        position: absolute;
        top: -999px; left: 1rem;
        background: var(--farve-accent);
        color: #fff;
        padding: .5rem 1rem;
        border-radius: var(--radius);
        font-size: .875rem;
        z-index: 9999;
        text-decoration: none;
    }
    .skip-link:focus-visible { top: 1rem; }

    #main-nav {
        display: flex;
        align-items: center;
    }

    #nav-toggle {
        display: none;
        background: transparent;
        border: 1px solid var(--farve-kant);
        border-radius: var(--radius);
        padding: .45rem .6rem;
        color: var(--farve-tekst);
        cursor: pointer;
    }

    #nav-list {
        display: flex;
        align-items: center;
        gap: 1rem;
        list-style: none;
        margin: 0;
        padding: 0;
    }

    #nav-list a {
        text-decoration: none;
    }
	
	 @media (max-width: 767px) {
         #nav-toggle           { display: inline-flex; align-items: center; justify-content: center; }
         #main-nav           { width: 100%; }
            #nav-list           {
                width: 100%;
                margin-top: .75rem;
                flex-direction: column;
                align-items: flex-start;
                gap: .5rem;
                overflow: hidden;
                max-height: 0;
                opacity: 0;
                transform: translateY(-4px);
                transition: max-height .38s cubic-bezier(.22, .61, .36, 1), opacity .24s ease-out, transform .24s ease-out;
            }
            #nav-list[data-open="true"] {
                opacity: 1;
                transform: translateY(0);
            }
            @media (prefers-reduced-motion: reduce) {
                #nav-list {
                     transition: none;
                     transform: none;
                }
            }
          }
    </style>

    <!--
    ════════════════════════════════════════════════════════════════════════════
    ↓ DIT EGET STYLESHEET / FRAMEWORK (Bootstrap, Skeleton, H5BP, m.fl.)
    ════════════════════════════════════════════════════════════════════════════

    Eksempler – fjern kommentar ved den du bruger:

    Bootstrap 5 CDN:
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">-->

    Skeleton:
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/skeleton/2.0.4/skeleton.min.css">

        <!--Lokalt stylesheet (altid tilstede uanset framework-valg):
        <link rel="stylesheet" href="/assets/site.css"> -->


    <?php if (!empty($extra_css)) echo $extra_css; ?>
</head>
<body<?= $_body_class ? ' class="' . e($_body_class) . '"' : '' ?>>

<!-- Tilgængelighed: skip-link til hovedcontent -->
<a href="#main-content" class="skip-link">Skip to content</a>

<!-- ════════════════════════════════════════════════════════════════════════════
     SITETOPPE
     Kun semantisk HTML5. Layout styres af dit CSS.
     ════════════════════════════════════════════════════════════════════════════ -->
<header role="banner">
    <div class="site-header-inner"><!-- valgfrit wrapper-element til dit grid -->

        <!-- Logo / sitetitle -->
        <a href="/" class="site-logo" rel="home">
            <!--
                Erstat enten med:
                  <img src="/assets/logo.svg" alt="<?= e(SITE_NAME) ?>" width="160" height="40">
                eller behold tekst-logoen:
            -->
            <?= e(SITE_NAME) ?>
        </a>

        <!-- Primær navigation -->
        <nav id="main-nav" aria-label="Primary navigation">

            <!-- Hamburger-knap (kun synlig på mobil via dit CSS) -->
            <button type="button"
                    id="nav-toggle"
                    aria-controls="nav-list"
                    aria-expanded="false"
                    aria-label="Open menu">
                <span aria-hidden="true">&#9776;</span>
            </button>

            <ul id="nav-list" role="list">
            <?php foreach (SITE_NAV as $punkt): ?>
                <li>
                    <a href="<?= e($punkt['href']) ?>"
                       <?= nav_is_active($punkt['match']) ? 'aria-current="page"' : '' ?>>
                        <?= e($punkt['label']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
            </ul>

        </nav><!-- #main-nav -->

    </div><!-- .site-header-inner -->
</header>

<script>
(function () {
    var knap = document.getElementById('nav-toggle');
    var list = document.getElementById('nav-list');
    if (!knap || !list || knap.dataset.navInit === 'true') return;
    knap.dataset.navInit = 'true';

    function isMobile() {
        return window.matchMedia('(max-width: 767px)').matches;
    }

    function setOpenState(open) {
        list.setAttribute('data-open', String(open));
        knap.setAttribute('aria-expanded', String(open));
        if (isMobile()) {
            if (open) {
                list.style.maxHeight = list.scrollHeight + 'px';
                list.style.opacity = '1';
            } else {
                list.style.maxHeight = '0px';
                list.style.opacity = '0';
            }
        }
    }

    knap.addEventListener('click', function () {
        if (!isMobile()) return;
        var open = list.getAttribute('data-open') === 'true';
        setOpenState(!open);
    });

    window.addEventListener('resize', function () {
        if (!isMobile()) {
            list.style.maxHeight = '';
            list.style.opacity = '';
            setOpenState(false);
        } else {
            setOpenState(list.getAttribute('data-open') === 'true');
        }
    });

    if (isMobile()) {
        setOpenState(list.getAttribute('data-open') === 'true');
    } else {
        setOpenState(false);
    }
})();
</script>

<!-- Hoved-landmark – id bruges af skip-link -->
<main id="main-content" tabindex="-1">
<?php
/*
 * ── BEMÆRK ─────────────────────────────────────────────────────────────────
 * <main> er åbnet men IKKE lukket her.
 * _footer.php lukker </main> og afslutter dokumentet.
 * ──────────────────────────────────────────────────────────────────────────
 */
