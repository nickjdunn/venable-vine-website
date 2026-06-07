<?php

require_once ROOT . '/includes/blocks/render.php';

function render_page_layout(int $pageId): void
{
    $desktop = PageRepository::getLayout($pageId, 'desktop');
    $mobile = PageRepository::getLayout($pageId, 'mobile');
    if (empty($desktop['rows']) && empty($mobile['rows'])) {
        render_page_sections($pageId);
        return;
    }
    echo '<div class="layout-view layout-desktop">';
    render_layout($desktop);
    echo '</div>';
    echo '<div class="layout-view layout-mobile">';
    if (!empty($mobile['rows'])) {
        render_layout($mobile);
    } else {
        render_layout($desktop);
    }
    echo '</div>';
}

function render_layout(array $layout): void
{
    foreach ($layout['rows'] ?? [] as $row) {
        echo '<div class="layout-row">';
        foreach ($row['columns'] ?? [] as $col) {
            echo '<div class="layout-column">';
            foreach ($col['blocks'] ?? [] as $block) {
                if (empty($block['active']) && array_key_exists('active', $block)) {
                    continue;
                }
                render_block($block);
            }
            echo '</div>';
        }
        echo '</div>';
    }
}
