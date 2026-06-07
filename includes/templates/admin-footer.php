</div>
<script>window.CSRF_TOKEN = <?= json_encode(Csrf::token()) ?>;</script>
<script>window.AGENT_DEBUG_ENABLED = <?= json_encode(agent_debug_enabled()) ?>;</script>
<script src="<?= asset('js/media-picker.js') ?>"></script>
<script src="<?= asset('js/admin-debug.js') ?>"></script>
<script src="<?= asset('js/admin.js') ?>"></script>
<script src="<?= asset('js/admin-tutorial.js') ?>"></script>
<?php if (!empty($extraAdminJs)): foreach ((array)$extraAdminJs as $js): ?>
    <script src="<?= e($js) ?>"></script>
<?php endforeach; endif; ?>
</body>
</html>
