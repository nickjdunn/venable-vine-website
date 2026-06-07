<?php

require_once ROOT . '/includes/blocks/render.php';

function render_page_layout(int $pageId): void
{
    PageRepository::ensureLayoutsPersisted($pageId);
    $desktop = PageRepository::getLayout($pageId, 'desktop');
    $layout = mobile_layout_from_layout($desktop);

    if (empty($layout['rows'])) {
        // #region agent log
        agent_debug_log('D', 'layout/render.php', 'fallback to legacy page_sections', ['pageId' => $pageId]);
        // #endregion
        render_page_sections($pageId);
        return;
    }

    // #region agent log
    agent_debug_log('B', 'layout/render.php', 'rendering single responsive layout', [
        'pageId' => $pageId,
        'rowCount' => count($layout['rows']),
    ]);
    // #endregion

    echo '<div class="page-layout">';
    render_layout($layout);
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
