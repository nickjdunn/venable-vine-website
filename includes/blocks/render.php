<?php

function render_block(array $block): void
{
    $type = $block['type'] ?? '';
    $config = $block['config'] ?? [];
    $section = ['section_type' => $type, 'config' => json_encode($config)];

    $moduleTypes = ['hero', 'story', 'menu_preview', 'gallery', 'reviews', 'find_us', 'contact', 'newsletter', 'social'];
    if (in_array($type, $moduleTypes, true)) {
        $file = ROOT . '/includes/sections/' . $type . '.php';
        if (file_exists($file)) {
            $config = $block['config'] ?? [];
            include $file;
            return;
        }
    }

    match ($type) {
        'title' => render_block_title($config),
        'text' => render_block_text($config),
        'image' => render_block_image($config),
        'button' => render_block_button($config),
        'spacer' => render_block_spacer($config),
        'menu_category' => render_block_menu_category($config),
        default => null,
    };
}

function render_block_title(array $c): void
{
    $level = in_array($c['level'] ?? 'h2', ['h1', 'h2', 'h3'], true) ? $c['level'] : 'h2';
    $align = e($c['align'] ?? 'center');
    echo "<div class=\"block-title align-{$align}\"><{$level}>" . e($c['text'] ?? '') . "</{$level}></div>";
}

function render_block_text(array $c): void
{
    $align = e($c['align'] ?? 'left');
    echo '<div class="block-text align-' . $align . '">' . nl2br(e($c['content'] ?? '')) . '</div>';
}

function render_block_image(array $c): void
{
    $src = upload_url(resolve_image_path($c['src'] ?? ''));
    if (!$src) {
        return;
    }
    echo '<figure class="block-image">';
    echo '<img src="' . e($src) . '" alt="' . e($c['alt'] ?? '') . '" loading="lazy">';
    if (!empty($c['caption'])) {
        echo '<figcaption>' . e($c['caption']) . '</figcaption>';
    }
    echo '</figure>';
}

function render_block_button(array $c): void
{
    $align = e($c['align'] ?? 'center');
    echo '<div class="block-button align-' . $align . '">';
    echo '<a href="' . e($c['link'] ?? '#') . '" class="cta-button">' . e($c['text'] ?? 'Click') . '</a>';
    echo '</div>';
}

function render_block_spacer(array $c): void
{
    $h = max(8, min(200, (int) ($c['height'] ?? 40)));
    echo '<div class="block-spacer" style="height:' . $h . 'px"></div>';
}

function render_block_menu_category(array $c): void
{
    $catId = (int) ($c['category_id'] ?? 0);
    if (!$catId) {
        return;
    }
    $cat = MenuRepository::category($catId);
    if (!$cat || !$cat['is_active']) {
        return;
    }
    $items = MenuRepository::items($catId, true);
    $title = $c['title'] ?: $cat['name'];
    echo '<div class="block-menu-category menu-category">';
    echo '<h3>' . e($title) . '</h3>';
    foreach ($items as $item) {
        echo '<div class="menu-item"><strong>' . e($item['name']) . '</strong>';
        if ($item['price'] !== null) {
            echo ' <span class="menu-price">$' . number_format((float) $item['price'], 2) . '</span>';
        }
        if ($item['description']) {
            echo '<div class="menu-item-description">' . e($item['description']) . '</div>';
        }
        echo '</div>';
    }
    echo '</div>';
}
