<?php

require_once ROOT . '/includes/blocks/render.php';

function section_editor_labels(): array
{
    return block_types()['modules'];
}

function section_editor_settings(string $type): array
{
    $style = section_style_settings();
    $specific = match ($type) {
        'menu_preview' => [
            ['key' => 'show_coming_soon', 'label' => 'Show Coming Soon box', 'type' => 'checkbox'],
            ['key' => 'link_to_full_menu', 'label' => 'Show link to full menu', 'type' => 'checkbox'],
        ],
        'find_us' => [
            ['key' => 'show_facebook_button', 'label' => 'Show Facebook button', 'type' => 'checkbox'],
            ['key' => 'max_events', 'label' => 'Max events to show', 'type' => 'number'],
        ],
        default => [],
    };
    return array_merge($specific, $style);
}

function render_block_in_editor(array $block, string $rowId): void
{
    $type = $block['type'] ?? '';
    $config = $block['config'] ?? [];
    if ($type === 'gallery') {
        $config = ensure_gallery_config_photos($config);
    }
    if ($type === 'contact') {
        $config = ensure_contact_config_forms($config);
    }
    $blockId = $block['id'] ?? '';
    $active = !isset($block['active']) || $block['active'];
    $labels = section_editor_labels();
    $label = $labels[$type] ?? ucfirst(str_replace('_', ' ', $type));

    echo '<div class="se-section-wrap' . ($active ? '' : ' se-section-hidden') . '"';
    echo ' data-row-id="' . e($rowId) . '"';
    echo ' data-block-id="' . e($blockId) . '"';
    echo ' data-block-type="' . e($type) . '"';
    echo ' data-active="' . ($active ? '1' : '0') . '">';

    echo '<div class="se-section-chrome">';
    echo '<span class="se-drag-handle" title="Drag to reorder">☰</span>';
    echo '<span class="se-section-label">' . e($label) . '</span>';
    echo '<button type="button" class="se-btn se-toggle-vis" title="Show/hide on live site">' . ($active ? '👁' : '👁‍🗨') . '</button>';
    echo '<button type="button" class="se-btn se-settings-btn" title="Section settings">⚙</button>';
    echo '</div>';

    echo '<div class="se-section-body">';
    editor_mode(true);
    $file = ROOT . '/includes/sections/' . $type . '.php';
    if (is_file($file)) {
        include $file;
    }
    editor_mode(false);
    echo '</div></div>';
}

function render_homepage_editor(array $layout): void
{
    $layout = normalize_homepage_layout($layout);
    echo '<div id="editor-canvas" class="se-canvas page-layout">';
    foreach ($layout['rows'] as $row) {
        if (($row['layout'] ?? 'full') !== 'full') {
            continue;
        }
        foreach ($row['columns'][0]['blocks'] ?? [] as $block) {
            render_block_in_editor($block, $row['id'] ?? '');
        }
    }
    echo '</div>';
}

function layout_from_editor_blocks(array $blocks): array
{
    $rows = [];
    foreach ($blocks as $i => $block) {
        $rows[] = [
            'id' => $block['rowId'] ?? ('row_' . ($i + 1)),
            'layout' => 'full',
            'columns' => [[
                'id' => $block['colId'] ?? ('col_' . ($i + 1)),
                'blocks' => [[
                    'id' => $block['id'],
                    'type' => $block['type'],
                    'config' => $block['config'] ?? [],
                    'active' => !empty($block['active']),
                ]],
            ]],
        ];
    }
    return normalize_homepage_layout(['rows' => $rows]);
}
