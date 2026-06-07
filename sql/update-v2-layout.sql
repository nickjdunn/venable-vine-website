-- Run once on existing installs (installer auto-runs if columns missing)
ALTER TABLE pages
    ADD COLUMN IF NOT EXISTS layout_desktop JSON NULL,
    ADD COLUMN IF NOT EXISTS layout_mobile JSON NULL;
