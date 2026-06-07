-- Page builder v4: ensure mobile layout exists for existing installs
-- Run if mobile still shows desktop 3-column grid incorrectly.

UPDATE pages SET layout_desktop = (
    SELECT JSON_OBJECT('rows', JSON_ARRAYAGG(
        JSON_OBJECT(
            'id', CONCAT('row_', ps.id),
            'layout', 'full',
            'columns', JSON_ARRAY(JSON_OBJECT(
                'id', CONCAT('col_', ps.id),
                'blocks', JSON_ARRAY(JSON_OBJECT(
                    'id', CONCAT('block_', ps.id),
                    'type', ps.section_type,
                    'config', CAST(ps.config AS JSON),
                    'active', ps.is_active = 1
                ))
            ))
        )
    ))
    FROM page_sections ps WHERE ps.page_id = pages.id AND ps.section_type != 'custom_html'
)
WHERE slug = 'home' AND (layout_desktop IS NULL OR JSON_LENGTH(layout_desktop, '$.rows') = 0)
AND EXISTS (SELECT 1 FROM page_sections WHERE page_id = pages.id);

-- Mobile: regenerate from desktop by visiting Page Builder (auto-migrates) or run PHP ensureLayoutsPersisted
