<?php

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
    return '/assets/' . ltrim($path, '/');
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
            <button type="button" class="btn btn-sm" data-media-select>Select Image</button>
            <button type="button" class="btn btn-sm btn-outline" data-media-upload>Upload Image</button>
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
            'paragraph1' => 'Venable & Vine started around our kitchen table, with a love for simple, real ingredients.',
            'paragraph2' => 'Every drink is muddled right in front of you.',
            'image' => $img['story'],
        ],
        'menu_category' => ['category_id' => 0, 'title' => ''],
        'menu_preview' => default_section_config('menu_preview'),
        'gallery' => ['title' => 'A Glimpse of Our Goodness'],
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
        ],
        'gallery' => ['title' => 'A Glimpse of Our Goodness'],
        'reviews' => ['title' => 'What Our Customers Say'],
        'find_us' => [
            'title' => 'Where to Find Us',
            'text' => 'For all other news, follow us on Facebook!',
            'show_facebook_button' => true,
            'max_events' => 5,
        ],
        'contact' => [
            'title' => 'Get In Touch',
            'subtitle' => 'We would love to hear from you!',
            'show_contact' => true,
            'show_review' => true,
        ],
        'newsletter' => [
            'title' => 'Stay in the Loop',
            'subtitle' => 'Sign up for updates on where we\'ll be and what\'s new on the menu.',
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
            $columns = [array_merge(['id' => $columns[0]['id'] ?? 'col_1', 'blocks' => []], $columns[0])];
        } else {
            while (count($columns) < 3) {
                $columns[] = ['id' => 'col_' . (count($columns) + 1), 'blocks' => []];
            }
            $columns = array_slice($columns, 0, 3);
        }
        $rows[] = [
            'id' => $row['id'] ?? ('row_' . uniqid()),
            'layout' => $layoutMode,
            'columns' => $columns,
        ];
    }
    return ['rows' => $rows];
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
