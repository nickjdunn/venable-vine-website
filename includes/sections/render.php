<?php

function render_section(array $section): void
{
    $type = $section['section_type'];
    $config = parse_json_config($section['config'] ?? null);
    $file = ROOT . '/includes/sections/' . $type . '.php';
    if (file_exists($file)) {
        include $file;
    }
}

function render_page_sections(int $pageId): void
{
    $sections = PageRepository::getSections($pageId, true);
    foreach ($sections as $section) {
        render_section($section);
    }
}
