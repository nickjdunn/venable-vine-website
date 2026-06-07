<?php

require_once ROOT . '/includes/blocks/render.php';

function render_page_layout(int $pageId): void
{
    PageRepository::ensureLayoutsPersisted($pageId);
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
    render_layout($mobile);
    echo '</div>';
}

function render_layout(array $layout): void
{
    $layout = normalize_layout($layout);
    foreach ($layout['rows'] as $row) {
        $isFull = ($row['layout'] ?? 'full') !== 'columns';
        $rowClass = $isFull ? 'layout-row layout-row--full' : 'layout-row layout-row--columns';
        echo '<div class="' . $rowClass . '">';
        if ($isFull) {
            foreach ($row['columns'][0]['blocks'] ?? [] as $block) {
                if (isset($block['active']) && !$block['active']) {
                    continue;
                }
                echo '<div class="layout-block layout-block--full">';
                render_block($block);
                echo '</div>';
            }
        } else {
            foreach ($row['columns'] as $col) {
                echo '<div class="layout-column">';
                foreach ($col['blocks'] ?? [] as $block) {
                    if (isset($block['active']) && !$block['active']) {
                        continue;
                    }
                    render_block($block);
                }
                echo '</div>';
            }
        }
        echo '</div>';
    }
}
