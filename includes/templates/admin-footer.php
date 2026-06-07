</div>
<script>window.CSRF_TOKEN = <?= json_encode(Csrf::token()) ?>;</script>
<script src="<?= asset('js/admin.js') ?>"></script>
<?php if (!empty($extraAdminJs)): foreach ((array)$extraAdminJs as $js): ?>
    <script src="<?= e($js) ?>"></script>
<?php endforeach; endif; ?>
</body>
</html>
