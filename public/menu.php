<?php require_once dirname(__DIR__) . '/includes/bootstrap.php';

$pageTitle = 'Menu';
$grouped = MenuRepository::itemsGrouped(true);
$tags = dietary_tags();
require ROOT . '/includes/templates/public-header.php';
?>
<section class="page-header">
    <div class="container">
        <h1>Our Menu</h1>
        <p>Freshly made with love — muddled to order.</p>
    </div>
</section>
<section class="section-menu-full">
    <div class="container menu-full-inner">
        <?php if (empty($grouped)): ?>
            <p class="text-center">Our menu is being updated. Check back soon!</p>
        <?php else: ?>
            <?php foreach ($grouped as $group): ?>
                <div class="menu-category-block" id="cat-<?= (int) $group['category']['id'] ?>">
                    <h2><?= e($group['category']['name']) ?></h2>
                    <div class="menu-items-grid">
                        <?php foreach ($group['items'] as $item): ?>
                            <article class="menu-card<?= $item['is_featured'] ? ' featured' : '' ?>">
                                <?php if ($item['photo_path']): ?>
                                    <img src="<?= e(upload_url($item['photo_path'])) ?>" alt="<?= e($item['name']) ?>" class="menu-card-photo">
                                <?php endif; ?>
                                <div class="menu-card-body">
                                    <div class="menu-card-header">
                                        <h3><?= e($item['name']) ?></h3>
                                        <?php if ($item['is_featured']): ?><span class="badge badge-featured">Featured</span><?php endif; ?>
                                    </div>
                                    <?php if ($item['price'] !== null || $item['price_note']): ?>
                                        <p class="menu-card-price">
                                            <?php if ($item['price'] !== null): ?>$<?= number_format((float) $item['price'], 2) ?><?php endif; ?>
                                            <?php if ($item['price_note']): ?><span class="price-note"><?= e($item['price_note']) ?></span><?php endif; ?>
                                        </p>
                                    <?php endif; ?>
                                    <?php if ($item['description']): ?>
                                        <p class="menu-card-desc"><?= e($item['description']) ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($item['dietary_tags'])): ?>
                                        <div class="dietary-tags">
                                            <?php foreach ($item['dietary_tags'] as $tag): ?>
                                                <?php if (isset($tags[$tag])): ?>
                                                    <span class="dietary-tag"><?= e($tags[$tag]) ?></span>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>
<?php require ROOT . '/includes/templates/public-footer.php'; ?>
