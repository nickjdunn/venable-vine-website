<?php

function resolve_public_root(string $root): string
{
    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    if ($docRoot !== '' && is_dir($docRoot)) {
        return rtrim(realpath($docRoot) ?: $docRoot, '/\\');
    }
    if (is_dir($root . '/public_html')) {
        return $root . '/public_html';
    }
    return $root . '/public';
}

function paths_match(string $a, string $b): bool
{
    if ($a === '' || $b === '') {
        return false;
    }
    $ra = realpath($a);
    $rb = realpath($b);
    return $ra && $rb && $ra === $rb;
}

/** Whether agent debug tracing is enabled (Admin → Debug toggle). */
function agent_debug_enabled(): bool
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }
    if (!class_exists('Settings', false)) {
        $cached = false;
        return false;
    }
    try {
        $cached = Settings::get('agent_debug_enabled', '0') === '1';
    } catch (Throwable) {
        $cached = false;
    }
    return $cached;
}

/** Debug-mode NDJSON log (session 684396). No-op when agent debug is disabled. */
function agent_debug_log(string $hypothesisId, string $location, string $message, array $data = []): void
{
    if (!agent_debug_enabled()) {
        return;
    }
    $entry = json_encode([
        'sessionId' => '684396',
        'hypothesisId' => $hypothesisId,
        'location' => $location,
        'message' => $message,
        'data' => $data,
        'timestamp' => (int) round(microtime(true) * 1000),
    ], JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
    if (!$entry) {
        return;
    }
    $logFile = ROOT . '/debug-684396.log';
    @file_put_contents($logFile, $entry . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function app_config(string $key, mixed $default = null): mixed
{
    static $config = null;
    if ($config === null) {
        $path = ROOT . '/config/app.php';
        $config = file_exists($path) ? require $path : require ROOT . '/config/app.example.php';
    }
    return $config[$key] ?? $default;
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }
    $val = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return $val;
}

function json_response(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function is_ajax(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
        || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
}

function upload_url(?string $path): string
{
    if (!$path) {
        return '';
    }
    if (str_starts_with($path, 'http')) {
        return $path;
    }
    return '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $relative = ltrim($path, '/');
    $full = PUBLIC_ROOT . '/assets/' . $relative;
    $version = is_file($full) ? (string) filemtime($full) : (string) time();
    return '/assets/' . $relative . '?v=' . $version;
}

function dietary_tags(): array
{
    return [
        'vegan' => 'Vegan',
        'vegetarian' => 'Vegetarian',
        'gluten_free' => 'Gluten Free',
        'dairy_free' => 'Dairy Free',
        'nut_free' => 'Nut Free',
        'spicy' => 'Spicy',
    ];
}

function section_types(): array
{
    return [
        'hero' => 'Hero Banner',
        'story' => 'Story (Text + Image)',
        'menu_preview' => 'Menu Preview',
        'gallery' => 'Photo Gallery',
        'reviews' => 'Customer Reviews',
        'find_us' => 'Find Us / Events',
        'contact' => 'Contact & Reviews Forms',
        'newsletter' => 'Newsletter Signup',
        'social' => 'Social Links',
        'custom_html' => 'Custom HTML Block',
    ];
}

function default_brand_images(): array
{
    return [
        'logo' => 'assets/images/VenableandVineLogo.png',
        'hero_bg' => 'assets/images/BerriesInhand.png',
        'story' => 'assets/images/FoodTruckPicture.jpg',
        'favicon' => 'assets/images/JamIcon.png',
        'lemonade' => 'assets/images/LemonadeWithHoney.jpg',
        'honey' => 'assets/images/HoneyandJamandBerries.png',
        'food' => 'assets/images/ImagesOfFoodOffered.jpg',
    ];
}

/** Resolve a stored image path to a file that exists on disk (handles .webp → .png/.jpg). */
function resolve_image_path(?string $path): string
{
    if (!$path || str_starts_with($path, 'http')) {
        return $path ?? '';
    }
    $relative = ltrim($path, '/');
    if (is_file(PUBLIC_ROOT . '/' . $relative)) {
        return $relative;
    }
    $dir = dirname($relative);
    $base = pathinfo($relative, PATHINFO_FILENAME);
    foreach (['png', 'jpg', 'jpeg', 'webp', 'gif'] as $ext) {
        $try = $dir . '/' . $base . '.' . $ext;
        if (is_file(PUBLIC_ROOT . '/' . $try)) {
            return $try;
        }
    }
    return $relative;
}

function media_picker_field(string $name, ?string $value = '', string $label = 'Image'): void
{
    $value = resolve_image_path($value);
    $url = upload_url($value);
    ?>
    <div class="media-picker-field" data-media-picker>
        <?php if ($label !== ''): ?><label><?= e($label) ?></label><?php endif; ?>
        <input type="hidden" name="<?= e($name) ?>" value="<?= e($value) ?>" data-media-input>
        <div class="media-picker-preview"<?= !$value ? ' style="display:none"' : '' ?>>
            <img src="<?= e($url) ?>" alt="" data-media-preview>
        </div>
        <div class="media-picker-actions">
            <button type="button" class="btn btn-sm btn-outline" data-media-select>Add Photo</button>
            <button type="button" class="btn btn-sm btn-muted" data-media-clear<?= !$value ? ' style="display:none"' : '' ?>>Clear</button>
        </div>
        <input type="file" accept="image/*" data-media-file hidden>
    </div>
    <?php
}

function list_asset_images(): array
{
    $dir = PUBLIC_ROOT . '/assets/images';
    if (!is_dir($dir)) {
        return [];
    }
    $files = [];
    foreach (scandir($dir) as $file) {
        if ($file === '.' || $file === '..' || $file === '.gitkeep') {
            continue;
        }
        if (preg_match('/\.(webp|jpe?g|png|gif)$/i', $file)) {
            $files[] = 'assets/images/' . $file;
        }
    }
    sort($files);
    return $files;
}

function block_types(): array
{
    return [
        'basic' => [
            'title' => 'Title',
            'text' => 'Text',
            'image' => 'Image',
            'button' => 'Button',
            'spacer' => 'Spacer',
        ],
        'modules' => [
            'hero' => 'Hero Banner',
            'menu_category' => 'Menu Category',
            'menu_preview' => 'Full Menu Preview',
            'gallery' => 'Photo Gallery',
            'reviews' => 'Reviews',
            'find_us' => 'Find Us / Events',
            'contact' => 'Contact Forms',
            'newsletter' => 'Newsletter',
            'social' => 'Social Links',
            'story' => 'Story Block',
        ],
    ];
}

function default_block_config(string $type): array
{
    $img = default_brand_images();
    return match ($type) {
        'title' => ['text' => 'Section Title', 'level' => 'h2', 'align' => 'center'],
        'text' => ['content' => 'Write your text here. Tell customers about your food truck!', 'align' => 'left'],
        'image' => ['src' => $img['food'], 'alt' => 'Venable & Vine', 'caption' => ''],
        'button' => ['text' => 'Find The Truck', 'link' => '/find-us.php', 'align' => 'center'],
        'spacer' => ['height' => 40],
        'hero' => [
            'title' => 'Freshly Squeezed. Family Made.',
            'subtitle' => 'Handcrafted lemonades, sweet treats, and honey straight from our hives.',
            'background_image' => $img['hero_bg'],
            'logo_image' => $img['logo'],
            'cta_text' => 'Find The Truck Today',
            'cta_link' => '/find-us.php',
        ],
        'story' => [
            'title' => 'From Our Family to Yours',
            'paragraph1' => 'Venable & Vine started around our kitchen table, with a love for simple, real ingredients. Our kids loved the fresh-squeezed lemonade we\'d make on hot summer days, and we loved the honey from the hives buzzing in our backyard. We thought, why not share this?',
            'paragraph2' => 'Every drink is muddled right in front of you. The \'Vine\' in our name represents the fresh fruit we use, and \'Venable\' is our family name—a promise of quality and care in everything we serve. We\'re proud to be family-owned and operated, and we can\'t wait to share a little piece of our home with you.',
            'image' => $img['story'],
        ],
        'menu_category' => ['category_id' => 0, 'title' => ''],
        'menu_preview' => default_section_config('menu_preview'),
        'gallery' => ['title' => 'A Glimpse of Our Goodness', 'photos' => []],
        'reviews' => default_section_config('reviews'),
        'find_us' => default_section_config('find_us'),
        'contact' => default_section_config('contact'),
        'newsletter' => default_section_config('newsletter'),
        'social' => default_section_config('social'),
        default => [],
    };
}

function default_layout_from_sections(array $sections): array
{
    $rows = [];
    foreach ($sections as $sec) {
        $type = $sec['section_type'];
        if ($type === 'custom_html') {
            continue;
        }
        $config = parse_json_config($sec['config'] ?? null);
        if ($type === 'hero' && empty($config['logo_image'])) {
            $config['logo_image'] = default_brand_images()['logo'];
            $config['background_image'] = $config['background_image'] ?: default_brand_images()['hero_bg'];
        }
        if ($type === 'story' && empty($config['image'])) {
            $config['image'] = default_brand_images()['story'];
        }
        $rows[] = [
            'id' => 'row_' . ($sec['id'] ?? uniqid()),
            'layout' => 'full',
            'columns' => [
                ['id' => 'col_' . ($sec['id'] ?? uniqid()), 'blocks' => [[
                    'id' => 'block_' . ($sec['id'] ?? uniqid()),
                    'type' => $type,
                    'config' => $config,
                    'active' => (bool) ($sec['is_active'] ?? true),
                ]]],
            ],
        ];
    }
    return ['rows' => $rows];
}

function empty_layout(): array
{
    return ['rows' => []];
}

/** Original homepage section stack (matches sql/schema.sql defaults). */
function default_homepage_layout(): array
{
    $types = ['hero', 'menu_preview', 'story', 'gallery', 'reviews', 'find_us', 'contact', 'newsletter', 'social'];
    $sections = [];
    foreach ($types as $i => $type) {
        $sections[] = [
            'id' => $i + 1,
            'section_type' => $type,
            'is_active' => true,
            'config' => default_section_config($type),
        ];
    }
    return normalize_layout(default_layout_from_sections($sections));
}

function default_section_config(string $type): array
{
    $img = default_brand_images();
    return match ($type) {
        'hero' => [
            'title' => 'Freshly Squeezed. Family Made.',
            'subtitle' => 'Handcrafted lemonades, sweet treats, and honey straight from our hives. Made with love, for you.',
            'background_image' => $img['hero_bg'],
            'logo_image' => $img['logo'],
            'cta_text' => 'Find The Truck Today',
            'cta_link' => '/find-us.php',
        ],
        'story' => [
            'title' => 'From Our Family to Yours',
            'paragraph1' => 'Venable & Vine started around our kitchen table, with a love for simple, real ingredients.',
            'paragraph2' => 'Every drink is muddled right in front of you. We\'re proud to be family-owned and operated.',
            'image' => $img['story'],
        ],
        'menu_preview' => [
            'title' => 'Taste the Sunshine',
            'show_coming_soon' => true,
            'coming_soon_title' => 'Coming Soon!',
            'coming_soon_text' => 'Get ready for authentic Agua Frescas, classic Fresas con Crema, and our signature Snowflake Refreshers.',
            'link_to_full_menu' => true,
            'menu_link_text' => 'View Full Menu',
            'menu_link_url' => '/menu.php',
        ],
        'gallery' => ['title' => 'A Glimpse of Our Goodness', 'photos' => []],
        'reviews' => ['title' => 'What Our Customers Say'],
        'find_us' => [
            'title' => 'Where to Find Us',
            'text' => 'For all other news, follow us on Facebook!',
            'show_facebook_button' => true,
            'max_events' => 5,
            'schedule_button_text' => 'Full Schedule & Map',
            'schedule_button_link' => '/find-us.php',
            'facebook_button_text' => 'Visit Facebook',
        ],
        'contact' => [
            'title' => 'Get In Touch',
            'subtitle' => 'We would love to hear from you!',
            'forms' => [],
        ],
        'newsletter' => [
            'title' => 'Stay in the Loop',
            'subtitle' => 'Sign up for updates on where we\'ll be and what\'s new on the menu.',
            'email_placeholder' => 'Your email address',
            'submit_text' => 'Subscribe',
        ],
        'social' => ['title' => 'Follow Us'],
        'custom_html' => ['html' => ''],
        default => [],
    };
}

function module_block_types(): array
{
    return array_keys(block_types()['modules']);
}

function is_module_block(string $type): bool
{
    return in_array($type, module_block_types(), true);
}

/** Ensure every row has a layout mode and valid column structure. */
function normalize_layout(array $layout): array
{
    $rows = [];
    foreach ($layout['rows'] ?? [] as $row) {
        $layoutMode = $row['layout'] ?? 'full';
        if ($layoutMode !== 'columns') {
            $layoutMode = 'full';
        }
        $columns = $row['columns'] ?? [];
        if ($layoutMode === 'full') {
            if (empty($columns)) {
                $columns = [['id' => 'col_1', 'blocks' => []]];
            }
            $col0 = $columns[0];
            $blocks = is_array($col0['blocks'] ?? null) ? $col0['blocks'] : [];
            $columns = [[
                'id' => $col0['id'] ?? 'col_1',
                'blocks' => $blocks,
            ]];
        } else {
            $colCount = max(2, min(3, (int) ($row['column_count'] ?? 3)));
            while (count($columns) < $colCount) {
                $columns[] = ['id' => 'col_' . (count($columns) + 1), 'blocks' => []];
            }
            $columns = array_slice($columns, 0, $colCount);
            foreach ($columns as &$col) {
                $col['blocks'] = is_array($col['blocks'] ?? null) ? $col['blocks'] : [];
            }
            unset($col);
        }
        $entry = [
            'id' => $row['id'] ?? ('row_' . uniqid()),
            'layout' => $layoutMode,
            'columns' => $columns,
        ];
        if ($layoutMode === 'columns') {
            $entry['column_count'] = max(2, min(3, (int) ($row['column_count'] ?? count($columns))));
        }
        $rows[] = $entry;
    }
    return ['rows' => $rows];
}

/** True if layout has column rows or non-module blocks (legacy page builder). */
function layout_needs_homepage_normalize(array $layout): bool
{
    $modules = homepage_module_types();
    foreach (normalize_layout($layout)['rows'] as $row) {
        if (($row['layout'] ?? 'full') === 'columns') {
            return true;
        }
        foreach ($row['columns'] ?? [] as $col) {
            foreach ($col['blocks'] ?? [] as $block) {
                if (!in_array($block['type'] ?? '', $modules, true)) {
                    return true;
                }
            }
        }
    }
    return false;
}

/** Flatten desktop rows into single-column full-width rows for mobile. */
function mobile_layout_from_layout(array $desktop): array
{
    $desktop = normalize_layout($desktop);
    $rows = [];
    foreach ($desktop['rows'] as $row) {
        if (($row['layout'] ?? 'full') === 'columns') {
            foreach ($row['columns'] as $col) {
                foreach ($col['blocks'] ?? [] as $block) {
                    $rows[] = [
                        'id' => 'row_' . uniqid(),
                        'layout' => 'full',
                        'columns' => [[
                            'id' => 'col_' . uniqid(),
                            'blocks' => [$block],
                        ]],
                    ];
                }
            }
        } else {
            foreach ($row['columns'][0]['blocks'] ?? [] as $block) {
                $rows[] = [
                    'id' => 'row_' . uniqid(),
                    'layout' => 'full',
                    'columns' => [[
                        'id' => 'col_' . uniqid(),
                        'blocks' => [$block],
                    ]],
                ];
            }
        }
    }
    return ['rows' => $rows];
}

/** Short preview line for page builder cards. */
function block_preview_text(array $block): string
{
    $type = $block['type'] ?? '';
    $c = $block['config'] ?? [];
    return match ($type) {
        'hero' => (string) ($c['title'] ?? 'Hero Banner'),
        'story' => (string) ($c['title'] ?? 'Story'),
        'title' => (string) ($c['text'] ?? 'Title'),
        'text' => function_exists('mb_substr')
            ? mb_substr(strip_tags((string) ($c['content'] ?? 'Text block')), 0, 60)
            : substr(strip_tags((string) ($c['content'] ?? 'Text block')), 0, 60),
        'image' => basename((string) ($c['src'] ?? 'Image')),
        'button' => (string) ($c['text'] ?? 'Button'),
        'menu_category' => (string) ($c['title'] ?: 'Menu Category'),
        'menu_preview' => (string) ($c['title'] ?? 'Menu Preview'),
        'gallery' => (string) ($c['title'] ?? 'Gallery'),
        'reviews' => (string) ($c['title'] ?? 'Reviews'),
        'find_us' => (string) ($c['title'] ?? 'Find Us'),
        'contact' => (string) ($c['title'] ?? 'Contact'),
        'newsletter' => (string) ($c['title'] ?? 'Newsletter'),
        'social' => (string) ($c['title'] ?? 'Social Links'),
        'spacer' => 'Spacer (' . (int) ($c['height'] ?? 40) . 'px)',
        default => ucfirst(str_replace('_', ' ', $type)),
    };
}

function parse_json_config(mixed $json): array
{
    if (!$json) {
        return [];
    }
    if (is_array($json)) {
        return $json;
    }
    if (!is_string($json)) {
        return [];
    }
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function format_event_datetime(string $datetime): string
{
    $dt = new DateTime($datetime);
    return $dt->format('l, F j, Y');
}

/** Whether section templates render editable markup (homepage editor only). */
function editor_mode(?bool $set = null): bool
{
    static $mode = false;
    if ($set !== null) {
        $mode = $set;
    }
    return $mode;
}

/** Homepage module section types in default order. */
function homepage_module_types(): array
{
    return array_keys(block_types()['modules']);
}

/**
 * Normalize layout to exactly the 9 homepage module sections (drops columns/basic blocks).
 * Preserves user order for saved modules; appends missing defaults.
 */
function normalize_homepage_layout(array $layout): array
{
    $modules = homepage_module_types();
    $ordered = [];
    $seen = [];
    foreach (normalize_layout($layout)['rows'] as $row) {
        foreach ($row['columns'] ?? [] as $col) {
            foreach ($col['blocks'] ?? [] as $block) {
                $type = $block['type'] ?? '';
                if (!in_array($type, $modules, true)) {
                    continue;
                }
                $ordered[] = [
                    'id' => $block['id'] ?? ('block_' . uniqid()),
                    'type' => $type,
                    'config' => is_array($block['config'] ?? null) ? $block['config'] : [],
                    'active' => !isset($block['active']) || $block['active'],
                ];
                $seen[$type] = true;
            }
        }
    }
    foreach (default_homepage_layout()['rows'] as $defRow) {
        $defBlock = $defRow['columns'][0]['blocks'][0];
        $type = $defBlock['type'];
        if (empty($seen[$type])) {
            $ordered[] = $defBlock;
        }
    }
    $rows = [];
    foreach ($ordered as $i => $block) {
        $rows[] = [
            'id' => 'row_' . ($i + 1),
            'layout' => 'full',
            'columns' => [[
                'id' => 'col_' . ($i + 1),
                'blocks' => [$block],
            ]],
        ];
    }
    return ['rows' => $rows];
}

/** Allowed tags for rich text stored in section config. */
function sanitize_rich_text(string $html): string
{
    $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><a>';
    $clean = strip_tags($html, $allowed);
    $clean = preg_replace('/\s*on\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean) ?? $clean;
    $clean = preg_replace('/\s(href|src)\s*=\s*("\s*(javascript|data):[^"]*"|\'\s*(javascript|data):[^\']*\')/i', '', $clean) ?? $clean;
    return trim($clean);
}

/** Output rich or plain text for public section templates. */
function rich_text_out(string $value): void
{
    $value = (string) $value;
    if ($value === '') {
        return;
    }
    if ($value !== strip_tags($value)) {
        echo sanitize_rich_text($value);
        return;
    }
    echo nl2br(e($value));
}

/** Normalize gallery photos array in section config. */
function normalize_gallery_photos(mixed $photos): array
{
    if (!is_array($photos)) {
        return [];
    }
    $out = [];
    foreach ($photos as $photo) {
        if (!is_array($photo)) {
            continue;
        }
        $src = resolve_image_path($photo['src'] ?? '');
        if ($src === '') {
            continue;
        }
        $out[] = [
            'src' => $src,
            'alt' => (string) ($photo['alt'] ?? ''),
            'caption' => (string) ($photo['caption'] ?? ''),
            'title' => (string) ($photo['title'] ?? ''),
        ];
    }
    return $out;
}

/** Migrate legacy gallery (all active media) into block config once. */
function ensure_gallery_config_photos(array $config): array
{
    if (!empty(normalize_gallery_photos($config['photos'] ?? []))) {
        $config['photos'] = normalize_gallery_photos($config['photos']);
        return $config;
    }
    $photos = [];
    foreach (MediaRepository::all(true) as $img) {
        $photos[] = [
            'src' => $img['file_path'],
            'alt' => $img['alt_text'] ?? '',
            'caption' => $img['caption'] ?? '',
            'title' => $img['title'] ?? '',
        ];
    }
    $config['photos'] = $photos;
    return $config;
}

/** Sanitize text fields and image paths in a homepage block config before save. */
function sanitize_block_config(string $type, array $config): array
{
    $richFields = ['subtitle', 'paragraph1', 'paragraph2', 'coming_soon_text', 'text'];
    foreach ($richFields as $key) {
        if (isset($config[$key]) && is_string($config[$key])) {
            $config[$key] = sanitize_rich_text($config[$key]);
        }
    }
    foreach (['title', 'coming_soon_title', 'cta_text', 'menu_link_text', 'schedule_button_text', 'facebook_button_text', 'email_placeholder', 'submit_text'] as $key) {
        if (isset($config[$key])) {
            $config[$key] = strip_tags((string) $config[$key]);
        }
    }
    if ($type === 'gallery') {
        $config['photos'] = normalize_gallery_photos($config['photos'] ?? []);
    }
    if ($type === 'contact') {
        $config['forms'] = normalize_contact_forms($config['forms'] ?? []);
    }
    foreach (['background_color'] as $key) {
        if (!empty($config[$key]) && !preg_match('/^#[0-9A-Fa-f]{3,8}$/', (string) $config[$key])) {
            unset($config[$key]);
        }
    }
    foreach (['padding_top', 'padding_bottom'] as $key) {
        if (isset($config[$key]) && $config[$key] !== '' && $config[$key] !== null) {
            $config[$key] = max(0, min(10, (float) $config[$key]));
        }
    }
    foreach (['background_image', 'logo_image', 'image'] as $key) {
        if (!empty($config[$key])) {
            $config[$key] = resolve_image_path($config[$key]);
        }
    }
    return $config;
}

/** Migrate gallery block photos from legacy media-library display into config. */
function migrate_homepage_gallery_layout(array $layout): array
{
    $changed = false;
    foreach ($layout['rows'] ?? [] as &$row) {
        foreach ($row['columns'] ?? [] as &$col) {
            foreach ($col['blocks'] ?? [] as &$block) {
                if (($block['type'] ?? '') !== 'gallery') {
                    continue;
                }
                $before = json_encode($block['config']['photos'] ?? []);
                $block['config'] = ensure_gallery_config_photos($block['config'] ?? []);
                if (json_encode($block['config']['photos'] ?? []) !== $before) {
                    $changed = true;
                }
            }
        }
    }
    unset($row, $col, $block);
    return [$layout, $changed];
}

/** Build inline style attribute from config, optionally merging extra CSS. */
function section_style_attr(array $config, string $extraCss = ''): string
{
    $styles = [];
    if ($extraCss !== '') {
        $styles[] = rtrim($extraCss, ';');
    }
    if (!empty($config['background_color'])) {
        $styles[] = 'background-color:' . $config['background_color'];
    }
    if (isset($config['padding_top']) && $config['padding_top'] !== '' && $config['padding_top'] !== null) {
        $styles[] = 'padding-top:' . (float) $config['padding_top'] . 'rem';
    }
    if (isset($config['padding_bottom']) && $config['padding_bottom'] !== '' && $config['padding_bottom'] !== null) {
        $styles[] = 'padding-bottom:' . (float) $config['padding_bottom'] . 'rem';
    }
    return $styles ? ' style="' . e(implode(';', $styles)) . '"' : '';
}

/** Shared section appearance settings for the page builder gear menu. */
function section_style_settings(): array
{
    return [
        ['key' => 'background_color', 'label' => 'Background color', 'type' => 'color'],
        ['key' => 'padding_top', 'label' => 'Padding above (rem)', 'type' => 'number', 'min' => 0, 'max' => 10, 'step' => 0.5],
        ['key' => 'padding_bottom', 'label' => 'Padding below (rem)', 'type' => 'number', 'min' => 0, 'max' => 10, 'step' => 0.5],
    ];
}

/** Normalize contact section form slots. */
function normalize_contact_forms(mixed $forms): array
{
    if (!is_array($forms)) {
        return [];
    }
    $out = [];
    foreach ($forms as $slot) {
        if (!is_array($slot)) {
            continue;
        }
        $formId = (int) ($slot['form_id'] ?? 0);
        if ($formId <= 0) {
            continue;
        }
        $out[] = [
            'form_id' => $formId,
            'tab_label' => strip_tags((string) ($slot['tab_label'] ?? '')),
        ];
    }
    return $out;
}

/** Migrate legacy contact toggles or seed default forms. */
function ensure_contact_config_forms(array $config): array
{
    if (!empty(normalize_contact_forms($config['forms'] ?? []))) {
        $config['forms'] = normalize_contact_forms($config['forms']);
        return $config;
    }
    FormRepository::seedDefaults();
    $forms = [];
    $showContact = !isset($config['show_contact']) || $config['show_contact'];
    $showReview = !isset($config['show_review']) || $config['show_review'];
    if ($showContact) {
        $forms[] = ['form_id' => FormRepository::defaultContactFormId(), 'tab_label' => 'Contact Us'];
    }
    if ($showReview) {
        $forms[] = ['form_id' => FormRepository::defaultReviewFormId(), 'tab_label' => 'Leave a Review'];
    }
    $config['forms'] = $forms;
    unset($config['show_contact'], $config['show_review']);
    return $config;
}

/** Migrate contact section forms from legacy toggles into config. */
function migrate_homepage_contact_layout(array $layout): array
{
    $changed = false;
    foreach ($layout['rows'] ?? [] as &$row) {
        foreach ($row['columns'] ?? [] as &$col) {
            foreach ($col['blocks'] ?? [] as &$block) {
                if (($block['type'] ?? '') !== 'contact') {
                    continue;
                }
                $before = json_encode($block['config'] ?? []);
                $block['config'] = ensure_contact_config_forms($block['config'] ?? []);
                if (json_encode($block['config']) !== $before) {
                    $changed = true;
                }
            }
        }
    }
    unset($row, $col, $block);
    return [$layout, $changed];
}

/** Inline-editable single-line text (editor mode only). */
function editable_text(string $field, string $value, string $tag = 'span', string $class = ''): void
{
    $value = (string) $value;
    if (!editor_mode()) {
        $attr = $class !== '' ? ' class="' . e($class) . '"' : '';
        echo '<' . $tag . $attr . '>' . e($value) . '</' . $tag . '>';
        return;
    }
    $cls = trim('se-editable se-editable-trigger ' . $class);
    echo '<' . $tag . ' class="' . e($cls) . '" data-field="' . e($field) . '" data-edit-mode="plain" role="button" tabindex="0">';
    echo e($value ?: 'Click to edit');
    echo '</' . $tag . '>';
}

/** Inline-editable multiline / rich text (opens full editor on click). */
function editable_multiline(string $field, string $value, string $tag = 'p', string $class = ''): void
{
    $value = (string) $value;
    if (!editor_mode()) {
        if ($value === '') {
            return;
        }
        $attr = $class !== '' ? ' class="' . e($class) . '"' : '';
        echo '<' . $tag . $attr . '>';
        rich_text_out($value);
        echo '</' . $tag . '>';
        return;
    }
    $cls = trim('se-editable-rich se-editable-trigger ' . $class);
    $attr = $class !== '' ? ' class="' . e($cls) . '"' : ' class="' . e($cls) . '"';
    echo '<' . $tag . $attr . ' data-field="' . e($field) . '" data-edit-mode="rich" role="button" tabindex="0">';
    if ($value !== '') {
        rich_text_out($value);
    } else {
        echo '<span class="se-edit-hint">Click to edit text…</span>';
    }
    echo '</' . $tag . '>';
}

/** Editable CTA button (text + link fields). */
function editable_cta(string $textField, string $linkField, string $text, string $link, string $class = 'cta-button'): void
{
    $text = (string) $text;
    $link = (string) $link;
    if (!editor_mode()) {
        if ($text === '' || $link === '') {
            return;
        }
        echo '<a href="' . e($link) . '" class="' . e($class) . '">' . e($text) . '</a>';
        return;
    }
    echo '<span class="se-cta-wrap">';
    echo '<a href="' . e($link ?: '#') . '" class="' . e($class) . ' se-editable se-editable-trigger se-editable-cta" data-field="' . e($textField) . '" data-link-field="' . e($linkField) . '" data-edit-mode="plain" data-href="' . e($link) . '" role="button">';
    echo e($text ?: 'Button text');
    echo '</a>';
    echo '</span>';
}

/** Clickable image zone for editor (Add Photo). */
function editable_image(string $field, string $path, string $alt = '', string $imgClass = ''): void
{
    $path = resolve_image_path($path);
    $url = upload_url($path);
    if (!editor_mode()) {
        if (!$url) {
            return;
        }
        echo '<img src="' . e($url) . '" alt="' . e($alt) . '" class="' . e($imgClass) . '" loading="lazy">';
        return;
    }
    echo '<div class="se-image-zone" data-field="' . e($field) . '" data-path="' . e($path) . '">';
    if ($url) {
        echo '<img src="' . e($url) . '" alt="' . e($alt) . '" class="' . e($imgClass) . '">';
    } else {
        echo '<div class="se-image-placeholder"><span>Add Photo</span></div>';
    }
    echo '<button type="button" class="se-image-btn">Add Photo</button>';
    echo '</div>';
}

/** Editor-only placeholder for data managed elsewhere. */
function editor_placeholder(string $message, string $adminUrl, string $linkLabel): void
{
    if (!editor_mode()) {
        return;
    }
    echo '<div class="se-managed-placeholder">';
    echo '<p>' . e($message) . '</p>';
    echo '<a href="' . e($adminUrl) . '" class="btn btn-sm btn-outline" target="_blank">' . e($linkLabel) . ' →</a>';
    echo '</div>';
}

function format_event_time(string $datetime): string
{
    $dt = new DateTime($datetime);
    return $dt->format('g:i A');
}

function maps_link(string $address, ?float $lat = null, ?float $lng = null): string
{
    if ($lat !== null && $lng !== null) {
        return "https://www.google.com/maps/search/?api=1&query={$lat},{$lng}";
    }
    return 'https://www.google.com/maps/search/?api=1&query=' . urlencode($address);
}

function apple_maps_link(string $address, ?float $lat = null, ?float $lng = null): string
{
    if ($lat !== null && $lng !== null) {
        return "https://maps.apple.com/?ll={$lat},{$lng}";
    }
    return 'https://maps.apple.com/?q=' . urlencode($address);
}
