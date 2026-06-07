-- Media library v3: metadata columns + fix image paths for PNG/JPG assets
-- Safe to run on existing installs. Skip errors if columns already exist.

ALTER TABLE gallery_images ADD COLUMN display_name VARCHAR(255) NULL AFTER caption;
ALTER TABLE gallery_images ADD COLUMN alt_text VARCHAR(255) NULL AFTER display_name;
ALTER TABLE gallery_images ADD COLUMN title VARCHAR(255) NULL AFTER alt_text;

UPDATE site_settings SET setting_value = 'assets/images/VenableandVineLogo.png' WHERE setting_key = 'logo_path' AND setting_value LIKE '%VenableandVineLogo.webp';
UPDATE site_settings SET setting_value = 'assets/images/JamIcon.png' WHERE setting_key = 'favicon_path' AND setting_value LIKE '%JamIcon.webp';

UPDATE gallery_images SET file_path = 'assets/images/LemonadeWithHoney.jpg' WHERE file_path LIKE '%LemonadeWithHoney.webp';
UPDATE gallery_images SET file_path = 'assets/images/HoneyandJamandBerries.png' WHERE file_path LIKE '%HoneyandJamandBerries.webp';
UPDATE gallery_images SET file_path = 'assets/images/ImagesOfFoodOffered.jpg' WHERE file_path LIKE '%ImagesOfFoodOffered.webp';

UPDATE pages SET layout_desktop = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
    layout_desktop,
    'BerriesInhand.webp', 'BerriesInhand.png'),
    'VenableandVineLogo.webp', 'VenableandVineLogo.png'),
    'FoodTruckPicture.webp', 'FoodTruckPicture.jpg'),
    'JamIcon.webp', 'JamIcon.png'),
    'LemonadeWithHoney.webp', 'LemonadeWithHoney.jpg'),
    'HoneyandJamandBerries.webp', 'HoneyandJamandBerries.png')
WHERE layout_desktop IS NOT NULL;

UPDATE pages SET layout_mobile = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
    layout_mobile,
    'BerriesInhand.webp', 'BerriesInhand.png'),
    'VenableandVineLogo.webp', 'VenableandVineLogo.png'),
    'FoodTruckPicture.webp', 'FoodTruckPicture.jpg'),
    'JamIcon.webp', 'JamIcon.png'),
    'LemonadeWithHoney.webp', 'LemonadeWithHoney.jpg'),
    'HoneyandJamandBerries.webp', 'HoneyandJamandBerries.png')
WHERE layout_mobile IS NOT NULL;
