-- Form builder tables (also auto-created by FormRepository::ensureSchema on first use)

CREATE TABLE IF NOT EXISTS forms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    handler ENUM('contact', 'review', 'newsletter', 'custom') NOT NULL DEFAULT 'custom',
    button_text VARCHAR(255) NOT NULL DEFAULT 'Submit',
    success_message TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS form_fields (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    form_id INT UNSIGNED NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    field_type ENUM('text', 'email', 'textarea', 'rating') NOT NULL DEFAULT 'text',
    label VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    placeholder VARCHAR(255) NULL,
    required TINYINT(1) NOT NULL DEFAULT 0,
    options JSON NULL,
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS form_submissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    form_id INT UNSIGNED NOT NULL,
    data JSON NOT NULL,
    status ENUM('new', 'read') NOT NULL DEFAULT 'new',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
