<?php

class FormRepository
{
    public static function ensureSchema(): void
    {
        $pdo = Database::connection();
        $pdo->exec("CREATE TABLE IF NOT EXISTS forms (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(100) NOT NULL UNIQUE,
            handler ENUM('contact', 'review', 'newsletter', 'custom') NOT NULL DEFAULT 'custom',
            button_text VARCHAR(255) NOT NULL DEFAULT 'Submit',
            success_message TEXT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE IF NOT EXISTS form_fields (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pdo->exec("CREATE TABLE IF NOT EXISTS form_submissions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            form_id INT UNSIGNED NOT NULL,
            data JSON NOT NULL,
            status ENUM('new', 'read') NOT NULL DEFAULT 'new',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (form_id) REFERENCES forms(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public static function seedDefaults(): void
    {
        self::ensureSchema();
        if (self::findBySlug('contact')) {
            return;
        }
        $contactId = self::create([
            'name' => 'Contact Us',
            'slug' => 'contact',
            'handler' => 'contact',
            'button_text' => 'Send Message',
            'success_message' => 'Thank you! Your message has been sent.',
            'fields' => [
                ['field_type' => 'text', 'label' => 'Your Name', 'name' => 'name', 'required' => true],
                ['field_type' => 'email', 'label' => 'Your Email', 'name' => 'email', 'required' => true],
                ['field_type' => 'textarea', 'label' => 'Message', 'name' => 'message', 'required' => true],
            ],
        ]);
        self::create([
            'name' => 'Leave a Review',
            'slug' => 'review',
            'handler' => 'review',
            'button_text' => 'Submit Review',
            'success_message' => 'Thank you! Your review will appear after approval.',
            'fields' => [
                ['field_type' => 'text', 'label' => 'Your Name', 'name' => 'name', 'required' => true],
                ['field_type' => 'rating', 'label' => 'Your Rating', 'name' => 'rating', 'required' => true],
                ['field_type' => 'textarea', 'label' => 'Your Review', 'name' => 'text', 'required' => true],
            ],
        ]);
        unset($contactId);
    }

    public static function all(bool $activeOnly = false): array
    {
        self::seedDefaults();
        $sql = 'SELECT * FROM forms';
        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY name ASC';
        $rows = Database::connection()->query($sql)->fetchAll();
        return array_map(fn($row) => self::format($row), $rows);
    }

    public static function find(int $id): ?array
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare('SELECT * FROM forms WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ? self::format($row, true) : null;
    }

    public static function findBySlug(string $slug): ?array
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare('SELECT * FROM forms WHERE slug = ?');
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        return $row ? self::format($row, true) : null;
    }

    public static function defaultContactFormId(): int
    {
        self::seedDefaults();
        return (int) (self::findBySlug('contact')['id'] ?? 0);
    }

    public static function defaultReviewFormId(): int
    {
        self::seedDefaults();
        return (int) (self::findBySlug('review')['id'] ?? 0);
    }

    public static function create(array $data): int
    {
        self::ensureSchema();
        $db = Database::connection();
        $stmt = $db->prepare(
            'INSERT INTO forms (name, slug, handler, button_text, success_message, is_active) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['name'],
            $data['slug'],
            $data['handler'] ?? 'custom',
            $data['button_text'] ?? 'Submit',
            $data['success_message'] ?? null,
            !empty($data['is_active']) ? 1 : 1,
        ]);
        $id = (int) $db->lastInsertId();
        self::saveFields($id, $data['fields'] ?? []);
        return $id;
    }

    public static function update(int $id, array $data): void
    {
        self::ensureSchema();
        $db = Database::connection();
        $stmt = $db->prepare(
            'UPDATE forms SET name = ?, slug = ?, handler = ?, button_text = ?, success_message = ?, is_active = ? WHERE id = ?'
        );
        $stmt->execute([
            $data['name'],
            $data['slug'],
            $data['handler'] ?? 'custom',
            $data['button_text'] ?? 'Submit',
            $data['success_message'] ?? null,
            !empty($data['is_active']) ? 1 : 0,
            $id,
        ]);
        if (array_key_exists('fields', $data)) {
            self::saveFields($id, $data['fields']);
        }
    }

    public static function delete(int $id): void
    {
        self::ensureSchema();
        Database::connection()->prepare('DELETE FROM forms WHERE id = ?')->execute([$id]);
    }

    public static function saveFields(int $formId, array $fields): void
    {
        $db = Database::connection();
        $db->prepare('DELETE FROM form_fields WHERE form_id = ?')->execute([$formId]);
        $stmt = $db->prepare(
            'INSERT INTO form_fields (form_id, sort_order, field_type, label, name, placeholder, required, options)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($fields as $i => $field) {
            $stmt->execute([
                $formId,
                (int) ($field['sort_order'] ?? $i),
                $field['field_type'] ?? 'text',
                $field['label'] ?? 'Field',
                $field['name'] ?? ('field_' . ($i + 1)),
                $field['placeholder'] ?? null,
                !empty($field['required']) ? 1 : 0,
                isset($field['options']) ? json_encode($field['options']) : null,
            ]);
        }
    }

    public static function fieldsFor(int $formId): array
    {
        self::ensureSchema();
        $stmt = Database::connection()->prepare(
            'SELECT * FROM form_fields WHERE form_id = ? ORDER BY sort_order ASC, id ASC'
        );
        $stmt->execute([$formId]);
        return array_map(function ($row) {
            return [
                'id' => (int) $row['id'],
                'field_type' => $row['field_type'],
                'label' => $row['label'],
                'name' => $row['name'],
                'placeholder' => $row['placeholder'] ?? '',
                'required' => (bool) $row['required'],
                'options' => $row['options'] ? json_decode($row['options'], true) : [],
            ];
        }, $stmt->fetchAll());
    }

    public static function createSubmission(int $formId, array $data): int
    {
        self::ensureSchema();
        $db = Database::connection();
        $stmt = $db->prepare('INSERT INTO form_submissions (form_id, data, status) VALUES (?, ?, ?)');
        $stmt->execute([$formId, json_encode($data), 'new']);
        return (int) $db->lastInsertId();
    }

    public static function format(array $row, bool $withFields = false): array
    {
        $form = [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'slug' => $row['slug'],
            'handler' => $row['handler'],
            'button_text' => $row['button_text'],
            'success_message' => $row['success_message'] ?? '',
            'is_active' => (bool) ($row['is_active'] ?? true),
            'created_at' => $row['created_at'] ?? null,
        ];
        if ($withFields) {
            $form['fields'] = self::fieldsFor((int) $row['id']);
        }
        return $form;
    }
}
