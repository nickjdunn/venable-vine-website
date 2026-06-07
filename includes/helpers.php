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

function default_section_config(string $type): array
{
    return match ($type) {
        'hero' => [
            'title' => 'Freshly Squeezed. Family Made.',
            'subtitle' => 'Handcrafted lemonades, sweet treats, and honey straight from our hives. Made with love, for you.',
            'background_image' => '',
            'logo_image' => '',
            'cta_text' => 'Find The Truck Today',
            'cta_link' => '/find-us.php',
        ],
        'story' => [
            'title' => 'From Our Family to Yours',
            'paragraph1' => 'Venable & Vine started around our kitchen table, with a love for simple, real ingredients.',
            'paragraph2' => 'Every drink is muddled right in front of you. We\'re proud to be family-owned and operated.',
            'image' => '',
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

function parse_json_config(?string $json): array
{
    if (!$json) {
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
