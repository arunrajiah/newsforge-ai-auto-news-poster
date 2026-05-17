# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Install dev dependencies
composer install

# Run all tests
composer test

# Run a single test class
./vendor/bin/phpunit tests/AiGeneratorTest.php

# Lint (WordPress Coding Standards)
composer lint

# Auto-fix lint violations
composer lint-fix          # alias for phpcbf
./vendor/bin/phpcbf admin/settings-page.php   # single file
```

## Local Development Environment

```bash
docker compose up -d       # starts WordPress at http://localhost:8877
docker compose down
```

WordPress admin: `http://localhost:8877/wp-admin/`  
Plugin settings: `http://localhost:8877/wp-admin/options-general.php?page=arunai-auto-news-poster`  
Default DB credentials: `wordpress / wordpress` (see `docker-compose.yml`).  
The plugin directory is volume-mounted read-only into the container, so PHP changes are reflected immediately without rebuilding.

## Deployment Workflow

1. **Edit & commit** → push to GitHub (CI runs automatically)
2. **Test** in the Docker instance — verify the feature works end-to-end
3. **SVN trunk** — only after CI is green and manual testing passes

```bash
# Sync changed plugin files to SVN trunk and commit
svn checkout https://plugins.svn.wordpress.org/arunai-auto-news-poster/trunk/ /tmp/svn-aanp-trunk
rsync -av --exclude='.git' --exclude='vendor' --exclude='assets/screenshots' \
  --exclude='docker-compose.yml' \
  /path/to/repo/ /tmp/svn-aanp-trunk/
svn commit /tmp/svn-aanp-trunk/ -m "describe change"

# Update WordPress.org screenshots (SVN assets/ directory)
svn checkout https://plugins.svn.wordpress.org/arunai-auto-news-poster/assets/ /tmp/svn-aanp-assets
# copy PNGs, then:
svn commit /tmp/svn-aanp-assets/ -m "describe change"
```

## Architecture

### Request / data flow

```
AJAX (admin JS)
  ↓
AANP_Admin_Settings  (includes/class-admin-settings.php)
  ├─ ajax_fetch_articles  →  AANP_News_Fetch::fetch_latest_news()
  └─ ajax_generate_single →  AANP_AI_Generator::generate_content()
                              └─ AANP_Post_Creator::create_post()
                                  └─ AANP_Image_Generator::generate_and_attach()  (optional)

WP-Cron (AANP_Scheduler::CRON_HOOK)
  └─ AANP_Scheduler::run()  — same pipeline as the manual AJAX flow
```

### Key classes

| File | Class | Purpose |
|------|-------|---------|
| `newsforge-ai-auto-news-poster.php` | `AANP_Plugin` | Bootstrap: loads includes, registers cron, runs migrations |
| `includes/class-admin-settings.php` | `AANP_Admin_Settings` | Settings API registration, AJAX handlers, API-key encryption |
| `includes/class-news-fetch.php` | `AANP_News_Fetch` | Parses RSS feeds; results cached via transients (30 min TTL) |
| `includes/class-ai-generator.php` | `AANP_AI_Generator` | Builds prompts; calls OpenAI / Anthropic / custom endpoint |
| `includes/class-post-creator.php` | `AANP_Post_Creator` | Creates draft posts; duplicate detection; stats queries |
| `includes/class-image-generator.php` | `AANP_Image_Generator` | DALL-E 3 image generation and media-library attachment |
| `includes/class-scheduler.php` | `AANP_Scheduler` | WP-Cron registration and execution |

### Admin UI (tabbed)

- **`admin/settings-page.php`** — PHP template for all three tabs (Dashboard / Settings / RSS Feeds). Stats and recent-posts data come from `AANP_Post_Creator::get_stats()` and `get_recent_posts()`.
- **`assets/css/admin.css`** — Design-token-based CSS (CSS variables for colour, shadow, radius). Contains the dark navy hero, card layout, pill tabs, schedule radio grid, and toggle switches. WordPress core overrides require `!important` on button background/border.
- **`assets/js/admin.js`** — Tab switching (persisted in `localStorage`), two-phase AJAX generation (fetch list → generate one-at-a-time with progress bar), feed test/remove, cooldown timer.

### Settings storage

All settings in a single `aanp_settings` option (array). API keys are AES-256-CBC encrypted using a key derived from `wp_salt('auth')`. The `AANP_DEFAULT_FEEDS` constant (defined in the main plugin file) is the authoritative default feed list.

### Database

One custom table: `wp_aanp_generated_posts` (`id`, `post_id`, `source_url`, `generated_at`). Used for duplicate detection (fast indexed lookup) and stats queries. Schema migrations are version-gated via `aanp_db_version` option and run on `init` if behind.

## PHPCS Notes

- Standard: `WordPress-Core` + `WordPress-Extra` (configured in `.phpcs.xml`)
- `tests/` and `assets/` are excluded from PHPCS
- Translators comments for `printf`/`esc_html__` with placeholders must be on the line **immediately before** the `printf(` call, as a single-line `// translators:` comment, with the entire `printf(...)` on one line
- The main plugin file (`newsforge-ai-auto-news-poster.php`) is excluded from `InvalidClassFileName` because it intentionally mixes a class and a standalone function
