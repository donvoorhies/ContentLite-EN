<?php
/**
 * Shared site footer.
 * Closes <main>, renders <footer>, and ends the HTML document.
 */
if (!defined('SITE_NAME')) {
    require_once __DIR__ . '/config.php';
}
?>

</main><!-- #main-content -->

<!-- ════════════════════════════════════════════════════════════════════════════
     SIDEFOD
     Semantisk HTML5 – layout styres af dit CSS.
     ════════════════════════════════════════════════════════════════════════════ -->
<footer role="contentinfo">
    <div class="site-footer-indre">

        <!-- Sekundær navigation / footer-links -->
        <nav aria-label="Footer navigation">
            <ul role="list">
            <?php foreach (SITE_NAV as $punkt): ?>
                <li>
                    <a href="<?= e($punkt['href']) ?>"
                       <?= nav_is_active($punkt['match']) ? 'aria-current="page"' : '' ?>>
                        <?= e($punkt['label']) ?>
                    </a>
                </li>
            <?php endforeach; ?>
            </ul>
        </nav>

        <!-- Copyright -->
        <p class="copyright">
            &copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>.
            All rights reserved.
        </p>

    </div><!-- .site-footer-indre -->
</footer>

<!--
════════════════════════════════════════════════════════════════════════════════
↓ DIT EGET JAVASCRIPT / FRAMEWORK

Eksempler:

Bootstrap 5 bundle:
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

Lokalt script (altid tilstede):

<script src="/assets/site.js"></script>-->

<?php if (!empty($extra_js)) echo $extra_js; ?>

</body>
</html>
