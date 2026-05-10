<?php
/**
 * Admin Settings Class
 *
 * @package AI_Auto_News_Poster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AANP_Admin_Settings {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'init_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'wp_ajax_aanp_generate_posts', array( $this, 'ajax_generate_posts' ) );
		add_action( 'wp_ajax_aanp_fetch_articles', array( $this, 'ajax_fetch_articles' ) );
		add_action( 'wp_ajax_aanp_generate_single', array( $this, 'ajax_generate_single' ) );
		add_action( 'wp_ajax_aanp_test_feed', array( $this, 'ajax_test_feed' ) );
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'ArunAI – Auto News Poster', 'arunai-auto-news-poster' ),
			__( 'ArunAI – Auto News Poster', 'arunai-auto-news-poster' ),
			'manage_options',
			'arunai-auto-news-poster',
			array( $this, 'settings_page' )
		);
	}

	/**
	 * Initialize settings
	 */
	public function init_settings() {
		register_setting( 'aanp_settings_group', 'aanp_settings', array( $this, 'sanitize_settings' ) );

		// Main settings section
		add_settings_section(
			'aanp_main_section',
			__( 'Main Settings', 'arunai-auto-news-poster' ),
			array( $this, 'main_section_callback' ),
			'arunai-auto-news-poster'
		);

		// LLM Provider field
		add_settings_field(
			'llm_provider',
			__( 'LLM Provider', 'arunai-auto-news-poster' ),
			array( $this, 'llm_provider_callback' ),
			'arunai-auto-news-poster',
			'aanp_main_section'
		);

		// API Key field
		add_settings_field(
			'api_key',
			__( 'API Key', 'arunai-auto-news-poster' ),
			array( $this, 'api_key_callback' ),
			'arunai-auto-news-poster',
			'aanp_main_section'
		);

		// Categories field
		add_settings_field(
			'categories',
			__( 'Post Categories', 'arunai-auto-news-poster' ),
			array( $this, 'categories_callback' ),
			'arunai-auto-news-poster',
			'aanp_main_section'
		);

		// Word count field
		add_settings_field(
			'word_count',
			__( 'Word Count', 'arunai-auto-news-poster' ),
			array( $this, 'word_count_callback' ),
			'arunai-auto-news-poster',
			'aanp_main_section'
		);

		// Tone field
		add_settings_field(
			'tone',
			__( 'Tone of Voice', 'arunai-auto-news-poster' ),
			array( $this, 'tone_callback' ),
			'arunai-auto-news-poster',
			'aanp_main_section'
		);

		// Custom API endpoint field
		add_settings_field(
			'custom_api_endpoint',
			__( 'Custom API Endpoint', 'arunai-auto-news-poster' ),
			array( $this, 'custom_api_endpoint_callback' ),
			'arunai-auto-news-poster',
			'aanp_main_section'
		);

		// Custom API model field
		add_settings_field(
			'custom_api_model',
			__( 'Custom API Model', 'arunai-auto-news-poster' ),
			array( $this, 'custom_api_model_callback' ),
			'arunai-auto-news-poster',
			'aanp_main_section'
		);

		// RSS Feeds section.
		add_settings_section(
			'aanp_rss_section',
			__( 'RSS Feeds', 'arunai-auto-news-poster' ),
			array( $this, 'rss_section_callback' ),
			'arunai-auto-news-poster'
		);

		// RSS Feeds field.
		add_settings_field(
			'rss_feeds',
			__( 'RSS Feed URLs', 'arunai-auto-news-poster' ),
			array( $this, 'rss_feeds_callback' ),
			'arunai-auto-news-poster',
			'aanp_rss_section'
		);

		// Automation section.
		add_settings_section(
			'aanp_automation_section',
			__( 'Automation', 'arunai-auto-news-poster' ),
			array( $this, 'automation_section_callback' ),
			'arunai-auto-news-poster'
		);

		// Scheduling field.
		add_settings_field(
			'schedule',
			__( 'Auto-Generate Schedule', 'arunai-auto-news-poster' ),
			array( $this, 'schedule_callback' ),
			'arunai-auto-news-poster',
			'aanp_automation_section'
		);

		// Featured images field.
		add_settings_field(
			'featured_images',
			__( 'Featured Images', 'arunai-auto-news-poster' ),
			array( $this, 'featured_images_callback' ),
			'arunai-auto-news-poster',
			'aanp_automation_section'
		);
	}

	/**
	 * Enqueue admin scripts
	 */
	public function enqueue_admin_scripts( $hook ) {
		if ( 'settings_page_arunai-auto-news-poster' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			'aanp-admin-js',
			AANP_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			AANP_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		wp_enqueue_style(
			'aanp-admin-css',
			AANP_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			AANP_VERSION
		);

		wp_localize_script(
			'aanp-admin-js',
			'aanp_ajax',
			array(
				'ajax_url'             => admin_url( 'admin-ajax.php' ),
				'nonce'                => wp_create_nonce( 'aanp_nonce' ),
				'generating_text'      => __( 'Generating posts...', 'arunai-auto-news-poster' ),
				'success_text'         => __( 'Posts generated successfully!', 'arunai-auto-news-poster' ),
				'error_text'           => __( 'Error generating posts. Please try again.', 'arunai-auto-news-poster' ),
				'cooldown_seconds'     => self::RATE_LIMIT_SECONDS,
				/* translators: %d: seconds remaining */
				'cooldown_text'        => __( 'Please wait %d seconds…', 'arunai-auto-news-poster' ),
				'pro_coming_soon_text' => __( 'Pro version coming soon!', 'arunai-auto-news-poster' ),
			)
		);
	}

	/**
	 * Settings page
	 */
	public function settings_page() {
		include AANP_PLUGIN_DIR . 'admin/settings-page.php';
	}

	/**
	 * Main section callback
	 */
	public function main_section_callback() {
		echo '<p>' . esc_html__( 'Configure your ArunAI – Auto News Poster settings below.', 'arunai-auto-news-poster' ) . '</p>';
	}

	/**
	 * RSS section callback
	 */
	public function rss_section_callback() {
		echo '<p>' . esc_html__( 'Manage RSS feeds for news sources.', 'arunai-auto-news-poster' ) . '</p>';
	}

	/**
	 * LLM Provider callback
	 */
	public function llm_provider_callback() {
		$options = get_option( 'aanp_settings', array() );
		$value   = isset( $options['llm_provider'] ) ? $options['llm_provider'] : 'openai';

		echo '<select name="aanp_settings[llm_provider]" id="llm_provider">';
		echo '<option value="openai"' . selected( $value, 'openai', false ) . '>OpenAI</option>';
		echo '<option value="anthropic"' . selected( $value, 'anthropic', false ) . '>Anthropic</option>';
		echo '<option value="custom"' . selected( $value, 'custom', false ) . '>Custom API</option>';
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Select your preferred LLM provider.', 'arunai-auto-news-poster' ) . '</p>';
	}

	/**
	 * API Key callback
	 */
	public function api_key_callback() {
		$options = get_option( 'aanp_settings', array() );
		$has_key = ! empty( $options['api_key'] );

		// Never render the stored (encrypted) value — use a placeholder instead
		$placeholder = $has_key ? __( 'API key saved — enter a new value to replace it', 'arunai-auto-news-poster' ) : '';
		echo '<input type="password" name="aanp_settings[api_key]" id="api_key" value="" class="regular-text" placeholder="' . esc_attr( $placeholder ) . '" autocomplete="new-password" />';
		echo '<p class="description">' . esc_html__( 'Enter your API key for the selected LLM provider.', 'arunai-auto-news-poster' ) . '</p>';
	}

	/**
	 * Categories callback
	 */
	public function categories_callback() {
		$options             = get_option( 'aanp_settings', array() );
		$selected_categories = isset( $options['categories'] ) ? $options['categories'] : array();

		$categories = get_categories( array( 'hide_empty' => false ) );

		echo '<div class="aanp-categories">';
		foreach ( $categories as $category ) {
			$checked = in_array( $category->term_id, $selected_categories, true ) ? 'checked' : '';
			echo '<label>';
			echo '<input type="checkbox" name="aanp_settings[categories][]" value="' . esc_attr( $category->term_id ) . '" ' . esc_attr( $checked ) . ' />';
			echo ' ' . esc_html( $category->name );
			echo '</label><br>';
		}
		echo '</div>';
		echo '<p class="description">' . esc_html__( 'Select categories for generated posts.', 'arunai-auto-news-poster' ) . '</p>';
	}

	/**
	 * Word count callback
	 */
	public function word_count_callback() {
		$options = get_option( 'aanp_settings', array() );
		$value   = isset( $options['word_count'] ) ? $options['word_count'] : 'medium';

		echo '<select name="aanp_settings[word_count]" id="word_count">';
		echo '<option value="short"' . selected( $value, 'short', false ) . '>Short (300-400 words)</option>';
		echo '<option value="medium"' . selected( $value, 'medium', false ) . '>Medium (500-600 words)</option>';
		echo '<option value="long"' . selected( $value, 'long', false ) . '>Long (800-1000 words)</option>';
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Select the desired word count for generated posts.', 'arunai-auto-news-poster' ) . '</p>';
	}

	/**
	 * Tone callback
	 */
	public function tone_callback() {
		$options = get_option( 'aanp_settings', array() );
		$value   = isset( $options['tone'] ) ? $options['tone'] : 'neutral';

		echo '<select name="aanp_settings[tone]" id="tone">';
		echo '<option value="neutral"' . selected( $value, 'neutral', false ) . '>Neutral</option>';
		echo '<option value="professional"' . selected( $value, 'professional', false ) . '>Professional</option>';
		echo '<option value="friendly"' . selected( $value, 'friendly', false ) . '>Friendly</option>';
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Select the tone of voice for generated content.', 'arunai-auto-news-poster' ) . '</p>';
	}

	/**
	 * Custom API endpoint callback
	 */
	public function custom_api_endpoint_callback(): void {
		$options = get_option( 'aanp_settings', array() );
		$value   = isset( $options['custom_api_endpoint'] ) ? $options['custom_api_endpoint'] : '';
		echo '<input type="url" name="aanp_settings[custom_api_endpoint]" id="custom_api_endpoint" value="' . esc_attr( $value ) . '" class="regular-text" placeholder="https://my-api.example.com/v1/chat/completions" />';
		echo '<p class="description">' . esc_html__( 'OpenAI-compatible endpoint URL for the Custom API provider. Required when "Custom API" is selected above.', 'arunai-auto-news-poster' ) . '</p>';
	}

	/**
	 * Custom API model callback
	 */
	public function custom_api_model_callback(): void {
		$options = get_option( 'aanp_settings', array() );
		$value   = isset( $options['custom_api_model'] ) ? $options['custom_api_model'] : '';
		echo '<input type="text" name="aanp_settings[custom_api_model]" id="custom_api_model" value="' . esc_attr( $value ) . '" class="regular-text" placeholder="e.g. mistral-7b-instruct" />';
		echo '<p class="description">' . esc_html__( 'Model name to pass in the API request body. Leave blank to use the endpoint default.', 'arunai-auto-news-poster' ) . '</p>';
	}

	/**
	 * RSS Feeds callback
	 */
	public function rss_feeds_callback() {
		$options = get_option( 'aanp_settings', array() );
		$feeds   = isset( $options['rss_feeds'] ) ? $options['rss_feeds'] : array();

		echo '<div id="rss-feeds-container">';
		if ( ! empty( $feeds ) ) {
			foreach ( $feeds as $feed ) {
				echo '<div class="rss-feed-row">';
				echo '<input type="url" name="aanp_settings[rss_feeds][]" value="' . esc_attr( $feed ) . '" class="regular-text" placeholder="https://example.com/feed.xml" />';
				echo '<button type="button" class="button test-feed">' . esc_html__( 'Test', 'arunai-auto-news-poster' ) . '</button>';
				echo '<button type="button" class="button remove-feed">' . esc_html__( 'Remove', 'arunai-auto-news-poster' ) . '</button>';
				echo '<span class="feed-test-result"></span>';
				echo '</div>';
			}
		}
		echo '</div>';
		echo '<button type="button" id="add-feed" class="button">' . esc_html__( 'Add RSS Feed', 'arunai-auto-news-poster' ) . '</button>';
		echo '<p class="description">' . esc_html__( 'Add RSS feed URLs for news sources.', 'arunai-auto-news-poster' ) . '</p>';
	}

	/**
	 * Automation section callback
	 */
	public function automation_section_callback() {
		echo '<p>' . esc_html__( 'Configure automatic post generation and featured image settings.', 'arunai-auto-news-poster' ) . '</p>';
	}

	/**
	 * Schedule callback
	 */
	public function schedule_callback() {
		$options = get_option( 'aanp_settings', array() );
		$current = isset( $options['schedule'] ) ? $options['schedule'] : 'disabled';
		$choices = array(
			'disabled'         => __( 'Manual only', 'arunai-auto-news-poster' ),
			'hourly'           => __( 'Every hour', 'arunai-auto-news-poster' ),
			'aanp_every6hours' => __( 'Every 6 hours', 'arunai-auto-news-poster' ),
			'twicedaily'       => __( 'Twice daily', 'arunai-auto-news-poster' ),
			'daily'            => __( 'Daily', 'arunai-auto-news-poster' ),
		);

		echo '<select name="aanp_settings[schedule]" id="aanp_schedule">';
		foreach ( $choices as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '"' . selected( $current, $value, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';

		$scheduler = new AANP_Scheduler();
		$next_run  = $scheduler->get_next_run();
		if ( $next_run ) {
			echo '<p class="description">';
			/* translators: %s: human-readable time until next scheduled run, e.g. "2 hours" */
			echo esc_html( sprintf( __( 'Next run in %s.', 'arunai-auto-news-poster' ), $next_run ) );
			echo '</p>';
		} else {
			echo '<p class="description">' . esc_html__( 'Posts will only be generated when you click the Generate button.', 'arunai-auto-news-poster' ) . '</p>';
		}
	}

	/**
	 * Featured images callback
	 */
	public function featured_images_callback() {
		$options  = get_option( 'aanp_settings', array() );
		$enabled  = ! empty( $options['featured_images'] );
		$provider = isset( $options['llm_provider'] ) ? $options['llm_provider'] : 'openai';

		echo '<label>';
		echo '<input type="checkbox" name="aanp_settings[featured_images]" value="1"' . checked( $enabled, true, false ) . ' />';
		echo ' ' . esc_html__( 'Auto-generate a featured image via DALL-E for each post', 'arunai-auto-news-poster' );
		echo '</label>';

		if ( 'openai' !== $provider ) {
			echo '<p class="description aanp-warning">';
			echo esc_html__( 'Featured image generation requires the OpenAI provider (uses DALL-E 3).', 'arunai-auto-news-poster' );
			echo '</p>';
		} else {
			echo '<p class="description">';
			echo esc_html__( 'Uses the DALL-E 3 API — each image costs approximately $0.04. Requires an OpenAI key with image permissions.', 'arunai-auto-news-poster' );
			echo '</p>';
		}
	}

	/** Transient key prefix for per-user rate-limiting. User ID is appended at runtime. */
	const RATE_LIMIT_TRANSIENT = 'aanp_cooldown_';

	/** Seconds a user must wait between generation requests. */
	const RATE_LIMIT_SECONDS = 60;

	/**
	 * Return the rate-limit transient key for the current user.
	 */
	private function rate_limit_key(): string {
		return self::RATE_LIMIT_TRANSIENT . get_current_user_id();
	}

	/**
	 * AJAX handler for generating posts
	 */
	public function ajax_generate_posts() {
		// Verify nonce and capability
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'aanp_nonce' ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
			return;
		}

		// Rate limit: prevent rapid repeated submissions (per user)
		$rate_key = $this->rate_limit_key();
		if ( get_transient( $rate_key ) ) {
			wp_send_json_error(
				array(
					'message'      => __( 'Please wait before generating again. Try again in a moment.', 'arunai-auto-news-poster' ),
					'rate_limited' => true,
				)
			);
			return;
		}

		// Set per-user cooldown transient before starting work
		set_transient( $rate_key, 1, self::RATE_LIMIT_SECONDS );

		try {
			// Initialize classes
			$news_fetch   = new AANP_News_Fetch();
			$ai_generator = new AANP_AI_Generator();
			$post_creator = new AANP_Post_Creator();

			// Fetch news articles
			$articles = $news_fetch->fetch_latest_news();

			if ( empty( $articles ) ) {
				wp_send_json_error( 'No articles found' );
				return;
			}

			// Process up to 5 articles per batch.
			$articles = array_slice( $articles, 0, 5 );

			$generated_posts = array();

			foreach ( $articles as $article ) {
				// Generate content using AI
				$generated_content = $ai_generator->generate_content( $article );

				if ( ! $generated_content ) {
					continue;
				}

				// Validate before persisting
				$validation = $post_creator->validate_post_data( $generated_content, $article );
				if ( ! $validation['valid'] ) {
					error_log( 'AANP: Skipping invalid post data: ' . implode( '; ', $validation['errors'] ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					continue;
				}

				// Create WordPress post
				$post_id = $post_creator->create_post( $generated_content, $article );

				if ( $post_id ) {
					$generated_posts[] = array(
						'id'        => $post_id,
						'title'     => $generated_content['title'],
						'edit_link' => get_edit_post_link( $post_id ),
					);
				}
			}

			if ( ! empty( $generated_posts ) ) {
				wp_send_json_success(
					array(
						/* translators: %d: Number of posts generated */
						'message' => sprintf( __( '%d posts generated successfully!', 'arunai-auto-news-poster' ), count( $generated_posts ) ),
						'posts'   => $generated_posts,
					)
				);
			} else {
				wp_send_json_error( 'Failed to generate posts' );
			}
		} catch ( Exception $e ) {
			error_log( 'AANP: Generation exception: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			wp_send_json_error( __( 'An unexpected error occurred. Please try again.', 'arunai-auto-news-poster' ) );
		}
	}

	/**
	 * AJAX handler: test whether a single RSS feed URL is reachable and parseable.
	 */
	public function ajax_test_feed(): void {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'aanp_nonce' ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
			return;
		}

		$feed_url = isset( $_POST['feed_url'] ) ? esc_url_raw( wp_unslash( $_POST['feed_url'] ) ) : '';

		if ( empty( $feed_url ) || ! filter_var( $feed_url, FILTER_VALIDATE_URL ) ) {
			wp_send_json_error( __( 'Invalid feed URL.', 'arunai-auto-news-poster' ) );
			return;
		}

		// Restrict to http/https to prevent SSRF via other schemes (ftp://, file://, etc.)
		$parsed_scheme = wp_parse_url( $feed_url, PHP_URL_SCHEME );
		if ( ! in_array( $parsed_scheme, array( 'http', 'https' ), true ) ) {
			wp_send_json_error( __( 'Only http:// and https:// feed URLs are supported.', 'arunai-auto-news-poster' ) );
			return;
		}

		$news_fetch = new AANP_News_Fetch();
		$is_valid   = $news_fetch->validate_feed_url( $feed_url );

		if ( $is_valid ) {
			wp_send_json_success(
				array(
					'message' => __( 'Feed is reachable and contains valid RSS/Atom content.', 'arunai-auto-news-poster' ),
				)
			);
		} else {
			wp_send_json_error( __( 'Could not reach the feed or it returned invalid content.', 'arunai-auto-news-poster' ) );
		}
	}

	/**
	 * AJAX handler: fetch the list of candidate articles without generating posts.
	 * Returns up to 5 article stubs so the client can drive per-article generation.
	 */
	public function ajax_fetch_articles(): void {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'aanp_nonce' ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
			return;
		}

		$news_fetch = new AANP_News_Fetch();
		$articles   = $news_fetch->fetch_latest_news();

		if ( empty( $articles ) ) {
			wp_send_json_error( __( 'No articles found in the configured RSS feeds.', 'arunai-auto-news-poster' ) );
			return;
		}

		$articles = array_slice( $articles, 0, 5 );

		// Return lightweight stubs (no content) so the payload stays small
		$stubs = array_map(
			function ( $a ) {
				return array(
					'title'         => $a['title'],
					'link'          => $a['link'],
					'description'   => $a['description'],
					'date'          => $a['date'],
					'source_feed'   => $a['source_feed'],
					'source_domain' => $a['source_domain'],
				);
			},
			$articles
		);

		wp_send_json_success( array( 'articles' => $stubs ) );
	}

	/**
	 * AJAX handler: generate and save a single post from one article stub.
	 * Called once per article by the JS progress loop.
	 */
	public function ajax_generate_single(): void {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'aanp_nonce' ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
			return;
		}

		// Rate limit per user — prevents direct API endpoint spam bypassing the UI cooldown
		$rate_key = $this->rate_limit_key();
		if ( get_transient( $rate_key ) ) {
			wp_send_json_error(
				array(
					'message'      => __( 'Please wait before generating again. Try again in a moment.', 'arunai-auto-news-poster' ),
					'rate_limited' => true,
				)
			);
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- array is sanitized field-by-field below.
		$article = isset( $_POST['article'] ) ? wp_unslash( $_POST['article'] ) : null;
		if ( ! is_array( $article ) || empty( $article['link'] ) ) {
			wp_send_json_error( __( 'Invalid article data.', 'arunai-auto-news-poster' ) );
			return;
		}

		// Sanitize article fields
		$article = array(
			'title'         => sanitize_text_field( $article['title'] ),
			'link'          => esc_url_raw( $article['link'] ),
			'description'   => sanitize_textarea_field( $article['description'] ),
			'date'          => sanitize_text_field( $article['date'] ),
			'source_feed'   => esc_url_raw( $article['source_feed'] ),
			'source_domain' => sanitize_text_field( $article['source_domain'] ),
		);

		$ai_generator = new AANP_AI_Generator();
		$post_creator = new AANP_Post_Creator();

		$generated_content = $ai_generator->generate_content( $article );

		if ( ! $generated_content ) {
			wp_send_json_error(
				/* translators: %s: article title */
				sprintf( __( 'AI generation failed for: %s', 'arunai-auto-news-poster' ), $article['title'] )
			);
			return;
		}

		$validation = $post_creator->validate_post_data( $generated_content, $article );
		if ( ! $validation['valid'] ) {
			wp_send_json_error( implode( '; ', $validation['errors'] ) );
			return;
		}

		$post_id = $post_creator->create_post( $generated_content, $article );

		if ( ! $post_id ) {
			// Duplicate or other failure
			wp_send_json_error(
				/* translators: %s: article title */
				sprintf( __( 'Could not create post for: %s (duplicate or error)', 'arunai-auto-news-poster' ), $article['title'] )
			);
			return;
		}

		wp_send_json_success(
			array(
				'id'        => $post_id,
				'title'     => $generated_content['title'],
				'edit_link' => get_edit_post_link( $post_id ),
			)
		);
	}

	/**
	 * Sanitize settings
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		// Validate and sanitize LLM provider
		if ( isset( $input['llm_provider'] ) ) {
			$allowed_providers = array( 'openai', 'anthropic', 'custom' );
			$provider          = sanitize_text_field( $input['llm_provider'] );
			if ( in_array( $provider, $allowed_providers, true ) ) {
				$sanitized['llm_provider'] = $provider;
			} else {
				add_settings_error( 'aanp_settings', 'invalid_provider', __( 'Invalid LLM provider selected.', 'arunai-auto-news-poster' ) );
				$sanitized['llm_provider'] = 'openai'; // Default fallback
			}
		}

		// Sanitize and encrypt API key — keep existing value when field is left blank
		$existing_options = get_option( 'aanp_settings', array() );
		if ( isset( $input['api_key'] ) ) {
			$api_key = sanitize_text_field( $input['api_key'] );
			if ( ! empty( $api_key ) ) {
				if ( strlen( $api_key ) < 10 ) {
					add_settings_error( 'aanp_settings', 'invalid_api_key', __( 'API key appears to be too short.', 'arunai-auto-news-poster' ) );
				}
				$sanitized['api_key'] = $this->encrypt_api_key( $api_key );
			} else {
				// Preserve existing key when no new value was entered
				$sanitized['api_key'] = isset( $existing_options['api_key'] ) ? $existing_options['api_key'] : '';
			}
		}

		// Validate and sanitize categories
		if ( isset( $input['categories'] ) && is_array( $input['categories'] ) ) {
			$sanitized['categories'] = array();
			$valid_categories        = get_categories( array( 'hide_empty' => false ) );
			$valid_cat_ids           = wp_list_pluck( $valid_categories, 'term_id' );

			foreach ( $input['categories'] as $cat_id ) {
				$cat_id = intval( $cat_id );
				if ( in_array( $cat_id, $valid_cat_ids, true ) ) {
					$sanitized['categories'][] = $cat_id;
				}
			}
		}

		// Validate and sanitize word count
		if ( isset( $input['word_count'] ) ) {
			$allowed_counts = array( 'short', 'medium', 'long' );
			$word_count     = sanitize_text_field( $input['word_count'] );
			if ( in_array( $word_count, $allowed_counts, true ) ) {
				$sanitized['word_count'] = $word_count;
			} else {
				$sanitized['word_count'] = 'medium'; // Default fallback
			}
		}

		// Validate and sanitize tone
		if ( isset( $input['tone'] ) ) {
			$allowed_tones = array( 'neutral', 'professional', 'friendly' );
			$tone          = sanitize_text_field( $input['tone'] );
			if ( in_array( $tone, $allowed_tones, true ) ) {
				$sanitized['tone'] = $tone;
			} else {
				$sanitized['tone'] = 'neutral'; // Default fallback
			}
		}

		// Sanitize custom API endpoint
		if ( isset( $input['custom_api_endpoint'] ) ) {
			$endpoint = esc_url_raw( trim( $input['custom_api_endpoint'] ) );
			if ( ! empty( $endpoint ) && filter_var( $endpoint, FILTER_VALIDATE_URL ) ) {
				$sanitized['custom_api_endpoint'] = $endpoint;
			} else {
				$sanitized['custom_api_endpoint'] = '';
				if ( ! empty( $input['custom_api_endpoint'] ) ) {
					add_settings_error( 'aanp_settings', 'invalid_custom_endpoint', __( 'Custom API endpoint must be a valid URL.', 'arunai-auto-news-poster' ) );
				}
			}
		}

		// Sanitize custom API model
		if ( isset( $input['custom_api_model'] ) ) {
			$sanitized['custom_api_model'] = sanitize_text_field( $input['custom_api_model'] );
		}

		// Validate and sanitize RSS feeds
		if ( isset( $input['rss_feeds'] ) && is_array( $input['rss_feeds'] ) ) {
			$sanitized['rss_feeds'] = array();
			$max_feeds              = 20; // Limit number of feeds
			$feed_count             = 0;

			foreach ( $input['rss_feeds'] as $feed ) {
				if ( $feed_count >= $max_feeds ) {
					add_settings_error( 'aanp_settings', 'too_many_feeds', __( 'Maximum 20 RSS feeds allowed.', 'arunai-auto-news-poster' ) );
					break;
				}

				$feed = esc_url_raw( $feed );
				if ( ! empty( $feed ) && filter_var( $feed, FILTER_VALIDATE_URL ) ) {
					// Additional security check for feed URL
					$parsed_url = wp_parse_url( $feed );
					if ( isset( $parsed_url['scheme'] ) && in_array( $parsed_url['scheme'], array( 'http', 'https' ), true ) ) {
						$sanitized['rss_feeds'][] = $feed;
						++$feed_count;
					}
				}
			}

			// Ensure at least one feed exists
			if ( empty( $sanitized['rss_feeds'] ) ) {
				$sanitized['rss_feeds'] = AANP_DEFAULT_FEEDS;
				add_settings_error( 'aanp_settings', 'no_feeds', __( 'At least one RSS feed is required. Default feeds restored.', 'arunai-auto-news-poster' ) );
			}
		}

		// Sanitize schedule setting and update WP-Cron accordingly.
		$new_schedule = isset( $input['schedule'] ) ? sanitize_text_field( $input['schedule'] ) : 'disabled';
		if ( ! in_array( $new_schedule, AANP_Scheduler::ALLOWED_SCHEDULES, true ) ) {
			$new_schedule = 'disabled';
		}
		$sanitized['schedule'] = $new_schedule;
		( new AANP_Scheduler() )->maybe_reschedule( $new_schedule );

		// Sanitize featured images toggle.
		$sanitized['featured_images'] = ! empty( $input['featured_images'] );

		return $sanitized;
	}

	/**
	 * Encrypt API key for secure storage using AES-256-CBC.
	 * Requires the OpenSSL PHP extension. If unavailable the plugin will
	 * show an admin warning and refuse to store the key.
	 */
	private function encrypt_api_key( string $api_key ): string {
		if ( ! function_exists( 'openssl_encrypt' ) ) {
			add_settings_error(
				'aanp_settings',
				'openssl_unavailable',
				__( 'The OpenSSL PHP extension is required to store your API key securely. Please enable OpenSSL on your server.', 'arunai-auto-news-poster' ),
				'error'
			);
			// Return empty rather than storing the key unencrypted
			return '';
		}

		// Derive a 32-byte key from the WordPress auth salt so it is unique per install
		$key       = substr( hash( 'sha256', wp_salt( 'auth' ), true ), 0, 32 );
		$iv        = openssl_random_pseudo_bytes( 16 );
		$encrypted = openssl_encrypt( $api_key, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );

		if ( false === $encrypted ) {
			return '';
		}

		// Prefix "enc2:" identifies the new format and distinguishes it from legacy values.
		return 'enc2:' . base64_encode( $iv . $encrypted ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Decrypt API key.
	 * Supports both the new "enc2:" format and the legacy format from earlier plugin versions.
	 */
	public static function decrypt_api_key( string $encrypted_key ): string {
		if ( empty( $encrypted_key ) ) {
			return '';
		}

		// New format: enc2:<base64(iv . ciphertext)>.
		if ( 0 === strncmp( $encrypted_key, 'enc2:', 5 ) && function_exists( 'openssl_decrypt' ) ) {
			$raw = base64_decode( substr( $encrypted_key, 5 ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			if ( false === $raw || strlen( $raw ) <= 16 ) {
				return '';
			}
			$key        = substr( hash( 'sha256', wp_salt( 'auth' ), true ), 0, 32 );
			$iv         = substr( $raw, 0, 16 );
			$ciphertext = substr( $raw, 16 );
			$decrypted  = openssl_decrypt( $ciphertext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv );
			return false !== $decrypted ? $decrypted : '';
		}

		// Legacy format: base64(iv . ciphertext) using wp_salt('auth') as raw key.
		if ( function_exists( 'openssl_decrypt' ) ) {
			$data = base64_decode( $encrypted_key, true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			if ( false !== $data && strlen( $data ) > 16 ) {
				$key        = wp_salt( 'auth' );
				$iv         = substr( $data, 0, 16 );
				$ciphertext = substr( $data, 16 );
				$decrypted  = openssl_decrypt( $ciphertext, 'AES-256-CBC', $key, 0, $iv );
				if ( false !== $decrypted ) {
					return $decrypted;
				}
			}
		}

		return '';
	}
}
