</main>
<footer class="footer">
    <?php $logo = upload_url(Settings::get('logo_path')); ?>
    <?php if ($logo): ?>
        <img src="<?= e($logo) ?>" alt="<?= e(Settings::get('site_name', 'Venable & Vine')) ?>" class="logo-img-display">
    <?php endif; ?>
    <p><?= e(Settings::get('footer_text', '© Venable & Vine | Family Owned & Operated')) ?></p>
</footer>
<div id="lightbox" class="lightbox" hidden>
    <button class="close-lightbox" aria-label="Close">&times;</button>
    <img class="lightbox-content" id="lightbox-img" alt="">
</div>
<script src="<?= asset('js/public.js') ?>"></script>
<?php if (!empty($extraJs)): foreach ((array)$extraJs as $js): ?>
    <script src="<?= e($js) ?>"></script>
<?php endforeach; endif; ?>
</body>
</html>
