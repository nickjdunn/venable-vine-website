<?php
$grouped = MenuRepository::itemsGrouped(true);
?>
<section id="menu" class="section-menu-preview"<?= section_style_attr($config) ?>>
    <div class="container menu-preview-inner">
        <?php editable_text('title', $config['title'] ?? 'Our Menu', 'h2'); ?>
        <?php if (editor_mode()): ?>
            <?php editor_placeholder(
                'Menu items are pulled from your Menu admin. Featured items appear here on the live site.',
                '/admin/menu.php',
                'Manage Menu'
            ); ?>
        <?php elseif (empty($grouped)): ?>
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
                <?php editable_text('coming_soon_title', $config['coming_soon_title'] ?? 'Coming Soon!', 'h3'); ?>
                <?php editable_multiline('coming_soon_text', $config['coming_soon_text'] ?? '', 'div'); ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($config['link_to_full_menu']) && !editor_mode()): ?>
            <p class="text-center" style="margin-top:2rem;">
                <?php editable_cta('menu_link_text', 'menu_link_url', $config['menu_link_text'] ?? 'View Full Menu', $config['menu_link_url'] ?? '/menu.php'); ?>
            </p>
        <?php elseif (editor_mode() && !empty($config['link_to_full_menu'])): ?>
            <p class="text-center" style="margin-top:2rem;">
                <?php editable_cta('menu_link_text', 'menu_link_url', $config['menu_link_text'] ?? 'View Full Menu', $config['menu_link_url'] ?? '/menu.php'); ?>
            </p>
        <?php endif; ?>
    </div>
</section>
