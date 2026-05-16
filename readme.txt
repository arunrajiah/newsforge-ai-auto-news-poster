=== ArunAI – Auto News Poster ===
Contributors: arunrajiah
Tags: ai, news, auto-posting, openai, rss
Requires at least: 5.1
Tested up to: 6.9
Stable tag: 1.0.8
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Auto-generate unique blog posts from the latest news using OpenAI, Claude, or any compatible AI. Supports scheduling, DALL-E 3 images, and RSS feeds.

== Description ==

ArunAI – Auto News Poster transforms the latest news articles into unique, engaging blog posts using OpenAI, Anthropic Claude, or any OpenAI-compatible API. Perfect for news sites, blogs, and content creators who want to stay on top of trending topics without the manual effort.

**Key Features:**

* **Multi-provider AI generation** — OpenAI GPT, Anthropic Claude, or any OpenAI-compatible custom endpoint
* **WP-Cron scheduling** — run generation automatically: hourly, every 6 hours, twice daily, or daily
* **DALL-E 3 featured images** — automatically generate and attach editorial images to every post (OpenAI only)
* **RSS feed management** — add, remove, and test feeds from the settings page; results cached for 30 minutes
* **Live progress UI** — per-article generation with a progress bar and real-time status messages
* **Duplicate detection** — already-posted articles are automatically skipped
* **AES-256 encrypted API keys** — stored encrypted using a per-install key; never displayed again after saving
* **Draft-first workflow** — all posts saved as drafts for your review before publishing
* **Source attribution** — every post links back to the original news source
* **60-second rate limit** — prevents accidental API overuse on manual generation
* **Customisable content** — choose tone, word count, and post categories

**How It Works:**

1. Configure your AI provider and paste your API key
2. Add RSS feeds from your favourite news sources (or use the built-in defaults: BBC, CNN, Reuters)
3. Select categories and content preferences
4. **Manual mode:** click "Generate 5 Posts" — articles are fetched and generated one at a time with live feedback
5. **Automatic mode:** choose a schedule in Settings > Automation and the plugin runs on its own
6. Review the draft posts and publish what you like

**Includes:**

* Manual generation of up to 30 posts per batch
* Automatic WP-Cron scheduling (hourly / every 6 h / twice daily / daily)
* DALL-E 3 featured image generation (OpenAI provider)
* OpenAI, Anthropic, and Custom API support
* RSS feed management with per-feed testing
* Duplicate detection
* Transient-cached feed fetching
* AES-256 API key encryption
* PHPUnit-tested, CI-verified codebase (39 tests)

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/arunai-auto-news-poster/`, or install through the WordPress plugins screen.
2. Activate the plugin through the **Plugins** screen.
3. Go to **Settings > RSS AI Post Generator**.
4. Select your AI provider and enter your API key.
5. Add RSS feed URLs (or use the built-in defaults).
6. Click **Generate 5 Posts** to start creating content.

== Frequently Asked Questions ==

= What AI providers are supported? =

OpenAI (GPT-3.5-turbo), Anthropic (Claude 3 Sonnet), and any OpenAI-compatible custom API endpoint (e.g. Ollama, LM Studio, OpenRouter).

= Do I need an API key? =

Yes. You need a key from OpenAI, Anthropic, or your custom provider. The plugin does not include AI credits.

= Are the generated posts unique? =

Yes. The AI is prompted to rewrite the article in its own words and explicitly told not to copy text from the source.

= Can I customise the generated content? =

Yes — tone (neutral / professional / friendly), word count (short ~300 w / medium ~500 w / long ~800 w), and WordPress categories.

= Are posts published automatically? =

No. All posts are saved as drafts for you to review and edit before publishing, even when scheduled generation runs automatically.

= How many posts can I generate at once? =

Up to 30 per batch (manual or scheduled).

= Why do I have to wait 60 seconds between manual batches? =

To prevent accidental API overuse. The cooldown applies to manual generation only; scheduled runs are not rate-limited.

= How does automatic scheduling work? =

Choose a schedule (Hourly / Every 6 Hours / Twice Daily / Daily) in Settings > Automation and save. The plugin registers a WP-Cron event that fires generation automatically. The next run time is shown in the settings. WP-Cron requires incoming traffic — on low-traffic sites, set up a system cron to call `wp-cron.php` on a regular interval.

= How much do DALL-E 3 images cost? =

Approximately $0.08 USD per image at the standard resolution (1792×1024) on OpenAI's standard tier. Image generation only fires when the **Featured Images** option is enabled and the provider is OpenAI.

= How are API keys stored? =

Keys are encrypted with AES-256-CBC using a 32-byte key derived from your site's `wp_salt('auth')` value. They are never displayed in plaintext after saving.

= What happens if an article was already posted? =

The plugin checks its own tracking table (`wp_aanp_generated_posts`) and silently skips any source URL that has already been processed.

= Is the plugin secure? =

Yes. It follows WordPress security best practices: nonce verification, `manage_options` capability checks, `$wpdb->prepare()` for all queries, and output escaping throughout.

== External Services ==

This plugin connects to the following external services. By using this plugin you agree to these services' terms and privacy policies.

**OpenAI API** (required when LLM Provider is set to OpenAI)

* What is sent: article headline and summary text (from RSS feeds) to generate blog post content; the post title when DALL-E 3 image generation is enabled
* Why: to generate unique blog post content and, optionally, featured images using DALL-E 3
* Service URL: https://api.openai.com
* Privacy Policy: https://openai.com/policies/privacy-policy
* Terms of Service: https://openai.com/policies/terms-of-use

**Anthropic API** (required when LLM Provider is set to Anthropic)

* What is sent: article headline and summary text (from RSS feeds) to generate blog post content
* Why: to generate unique blog post content using Claude
* Service URL: https://api.anthropic.com
* Privacy Policy: https://www.anthropic.com/privacy
* Terms of Service: https://www.anthropic.com/legal/consumer-terms

**RSS Feeds** (publicly available news sources)

The plugin fetches articles from RSS feeds configured by the site administrator. The default built-in feeds are:

* BBC News — https://feeds.bbci.co.uk/news/rss.xml — Privacy: https://www.bbc.com/privacy
* CNN — https://rss.cnn.com/rss/edition.rss — Privacy: https://www.cnn.com/privacy
* Reuters — https://feeds.reuters.com/reuters/topNews — Privacy: https://www.thomsonreuters.com/en/privacy-statement.html

What is sent: a standard HTTP GET request (no personal data, no cookies). The feed URLs are configurable — administrators can add, remove, or replace any feed.

No data is transmitted to any external service without the site administrator explicitly configuring an API key and/or feed URL in the plugin settings.

== Screenshots ==

1. Dashboard — hero header, live statistics (total, today, week, month), Generate Posts Now button, and recent generated posts table with status badges.
2. Settings — AI Provider card (provider selector, encrypted API key) and Content card (word count, writing tone, post categories).
3. Settings (continued) — Auto-Scheduling card with five WP-Cron options and Featured Images card with DALL-E 3 toggle; Save Settings button.
4. RSS Feeds — feed source list with per-feed Test and Remove buttons, Add Feed control, and Save Feeds button.

== Changelog ==

= 1.0.8 =
* Renamed plugin to "ArunAI – Auto News Poster" per WordPress.org directory guidelines
* Removed all feature-gating code: no license checks, no locked functionality
* Removed get_max_posts_per_batch() and class-pro-features.php; all features fully available
* Fixed Plugin URI and support URLs to point to the correct GitHub repository
* Fixed Reuters privacy policy link (replaced 401 URL with working Thomson Reuters page)
* Removed "free version" comment from batch-size code

= 1.0.7 =
* WP-Cron scheduling — new Automation section in settings; choose hourly / every 6 h / twice daily / daily; next-run time shown inline
* DALL-E 3 featured images — auto-generate and attach featured images for every post (OpenAI provider only, 1792×1024)
* Fixed PHP 7.4 syntax incompatibility in class-image-generator.php (union return type)
* New classes: AANP_Scheduler, AANP_Image_Generator
* New tests: SchedulerTest, ImageGeneratorTest (39 tests total)
* Release workflow: rolling latest zip published on every push to main

= 1.0.6 =
* Fixed all WordPress Plugin Check errors (i18n, escaping, missing translators comments)
* Removed all inline styles from admin templates; moved to admin.css
* Added semantic CSS classes for stat boxes and status indicators
* Corrected readme.txt stable tag

= 1.0.5 =
* AES-256-CBC API key encryption with wp_salt('auth')-derived key
* 60-second rate limiting between generation batches
* Two-phase AJAX generation flow with live progress bar
* Per-feed Test button (aanp_test_feed AJAX action)
* Custom API endpoint and model name settings
* Pro license key field with active/inactive badge
* Duplicate post detection via dedicated tracking table with post meta fallback
* RSS feed transient cache (30-minute TTL)
* PHP 7.4 type hints across all classes
* GitHub Actions CI pipeline (PHP lint, PHPCS, PHPUnit)
* Full PHPUnit test suite (31 tests)

= 1.0.3 – 1.0.4 =
* Fixed WordPress i18n NonSingularStringLiteralText errors
* readme.txt stable tag corrections

= 1.0.0 =
* Initial release: OpenAI and Anthropic integration, RSS feed parsing, batch draft creation, admin settings UI

== Upgrade Notice ==

= 1.0.8 =
Plugin renamed to "RSS AI Post Generator". All features (scheduling, featured images, 30 posts per batch) are now fully free — no license key required.

= 1.0.7 =
Adds automatic WP-Cron scheduling and DALL-E 3 featured image generation. No database migration required. New Automation settings section appears after upgrade.

= 1.0.6 =
Maintenance release — fixes Plugin Check warnings and cleans up inline styles. No settings migration required.

= 1.0.5 =
Important security update: API keys are now encrypted at rest. Existing plaintext keys will be re-encrypted on next settings save. Rate limiting and duplicate detection are also new in this release.

== Support ==

* Bug reports / feature requests: https://github.com/arunrajiah/ai-auto-news-poster/issues
* Documentation: https://github.com/arunrajiah/ai-auto-news-poster

== Privacy Policy ==

This plugin sends article headlines and summaries to your configured AI provider for content generation. Please review your provider's privacy policy:

* OpenAI: https://openai.com/policies/privacy-policy
* Anthropic: https://www.anthropic.com/privacy

**Data handling:**

* API keys are encrypted and stored in WordPress options — never transmitted except to your AI provider
* No personal user data is sent to AI providers
* Generated content is stored in your local WordPress database
* RSS feed URLs are stored in plugin settings
* The plugin logs errors locally (PHP `error_log`) for debugging

== Third-Party Services ==

**AI Providers (requires API key and may incur costs):**

* OpenAI API — https://openai.com/policies/privacy-policy
* Anthropic API — https://www.anthropic.com/privacy

**Default RSS Feeds (publicly available, no registration required):**

* BBC News — https://www.bbc.com/privacy
* CNN — https://www.cnn.com/privacy
* Reuters — https://www.thomsonreuters.com/en/privacy-statement.html

Users can add, remove, or replace these feeds at any time.

== License ==

This plugin is licensed under GPL v2 or later. You are free to use, modify, and distribute it under the terms of that licence.
