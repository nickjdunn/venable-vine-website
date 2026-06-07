<?php
$grouped = MenuRepository::itemsGrouped(true);
$tags = dietary_tags();
?>
<section id="menu" class="section-menu-preview">
    <div class="container menu-preview-inner">
        <h2><?= e($config['title'] ?? 'Our Menu') ?></h2>
        <?php if (empty($grouped)): ?>
            <p class="text-center">Menu coming soon!</p>
        <?php else: ?>
            <div class="menu-grid">
                <?php foreach ($grouped as $group): ?>
                    <div class="menu-category">
                        <h3><?= e($group['category']['name']) ?></h3>
                        <?php foreach (array_slice($group['items'], 0, 4) as $item): ?>
                            <div class="menu-item">
                                <strong><?= e($item['name']) ?></strong>
                                <?php if ($item['price'] !== null): ?>
                                    <span class="menu-price">$<?= number_format((float) $item['price'], 2) ?></span>
                                <?php endif; ?>
                                <?php if ($item['description']): ?>
                                    <div class="menu-item-description"><?= e($item['description']) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($config['show_coming_soon'])): ?>
            <div class="coming-soon-box">
                <h3><?= e($config['coming_soon_title'] ?? 'Coming Soon!') ?></h3>
                <div><?= e($config['coming_soon_text'] ?? '') ?></div>
            </div>
        <?php endif; ?>
        <?php if (!empty($config['link_to_full_menu'])): ?>
            <p class="text-center" style="margin-top:2rem;">
                <a href="/menu.php" class="cta-button">View Full Menu</a>
            </p>
        <?php endif; ?>
    </div>
</section>
