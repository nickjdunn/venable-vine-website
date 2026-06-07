# Git Version Control Guide

Use Git to track code changes on your computer, back up the project, and deploy updates to cPanel safely.

## What goes in Git vs. what stays on the server

| In Git (committed) | NOT in Git (server only) |
|--------------------|--------------------------|
| PHP code, CSS, JS | `config/database.php` (passwords) |
| `sql/schema.sql` | `config/.installed` |
| `config/database.example.php` | `public/uploads/*` (photos customers upload) |
| Documentation | Admin passwords |

These are already listed in `.gitignore`.

---

## One-time setup on your computer

### 1. Install Git

Download from [git-scm.com](https://git-scm.com/) if you don't have it. Verify:

```bash
git --version
```

### 2. Initialize the repository

Open a terminal in your project folder:

```bash
cd "C:\Users\nickj\Documents\Coding Projects\Venable_Vine_Website"
git init
git add .
git commit -m "Initial commit: Venable & Vine food truck website"
```

### 3. Create a GitHub repository (recommended)

1. Go to [github.com](https://github.com) → **New repository**
2. Name it something like `venable-vine-website`
3. Do **not** add a README (you already have one)
4. Copy the remote URL, then run:

```bash
git remote add origin https://github.com/YOUR_USERNAME/venable-vine-website.git
git branch -M main
git push -u origin main
```

You now have a cloud backup and version history.

---

## Daily workflow (making updates)

When you or an AI assistant change code:

```bash
git status          # see what changed
git add .           # stage changes (or add specific files)
git commit -m "Describe what you changed and why"
git push            # upload to GitHub
```

Example:

```bash
git commit -m "Add new menu item photo upload fix"
git push
```

---

## Deploying updates to cPanel

There are two common approaches:

### Option A: cPanel Git Version Control (easiest)

Many cPanel hosts include **Git Version Control** under **Files**:

1. In cPanel → **Git Version Control** → **Create**
2. Clone your GitHub repo into a folder (e.g. `venable-vine`)
3. Set the domain **document root** to `venable-vine/public`
4. When you push updates to GitHub, click **Pull or Deploy** in cPanel

**Important:** After the first deploy, run the web installer once at `https://yourdomain.com/install.php` to create `config/database.php` and tables. That file stays on the server and is not overwritten by Git pulls.

### Option B: Manual FTP / File Manager

1. `git pull` on your machine (or download ZIP from GitHub)
2. Upload changed files via FTP or cPanel File Manager
3. Never overwrite `config/database.php` or `public/uploads/` on the server

---

## First install on the server (database + app)

After files are on cPanel:

1. **Create MySQL database** in cPanel → MySQL Databases
   - Create database (e.g. `venable_vine`)
   - Create user with a strong password
   - Add user to database → **ALL PRIVILEGES**

2. **Point domain** to the `public/` folder (document root)

3. **Visit the installer** in your browser:
   ```
   https://yourdomain.com/install.php
   ```

4. **Step 1 — Database:** Enter host (usually `localhost`), database name, username, and password. The installer tests the connection and saves `config/database.php`.

5. **Step 2 — Tables:** Click **Create Tables Now** (runs `sql/schema.sql` automatically).

6. **Step 3 — Admin:** Create your sister's admin login (email + password).

7. **Delete `install.php`** from the server when done.

8. Log in at `/admin/login.php` and configure **Settings** (logo, social links, etc.).

---

## Updating the live site after Git changes

```bash
# On your computer
git add .
git commit -m "Update menu page styling"
git push
```

Then on cPanel (Option A):

- Open **Git Version Control** → **Pull or Deploy**

Or upload only the changed files (Option B).

**Database content** (menu items, events, photos uploaded via admin) lives in MySQL and uploads folder — Git updates only change the *code*, not her menu data.

---

## Branching (optional, for bigger changes)

```bash
git checkout -b feature/new-gallery-layout
# make changes, commit
git push -u origin feature/new-gallery-layout
# merge on GitHub via Pull Request, then pull to main on server
```

---

## Troubleshooting

| Problem | Fix |
|---------|-----|
| `Missing config/database.php` | Run `/install.php` or copy `database.example.php` → `database.php` |
| Install says "Already installed" | Delete `config/.installed` on server to re-run (careful on live site) |
| Git push asks for password | Use a [GitHub Personal Access Token](https://github.com/settings/tokens) instead of your account password |
| Pull overwrote uploads | Restore from backup; ensure `public/uploads/*` is in `.gitignore` |

---

## Quick reference

```bash
git init                          # first time only
git add .
git commit -m "Your message"
git push

# See history
git log --oneline

# Undo uncommitted changes to a file
git checkout -- path/to/file
```

For deployment details see [README-DEPLOY.md](README-DEPLOY.md).
