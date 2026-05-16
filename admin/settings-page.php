<?php
/**
 * Admin Settings Page Template
 *
 * @package AANP_Plugin
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$aanp_options      = get_option( 'aanp_settings', array() );
$aanp_post_creator = new AANP_Post_Creator();
$aanp_stats        = $aanp_post_creator->get_stats();
$aanp_recent_posts = $aanp_post_creator->get_recent_posts( 10 );

$aanp_schedule   = isset( $aanp_options['schedule'] ) ? $aanp_options['schedule'] : 'disabled';
$aanp_provider   = isset( $aanp_options['llm_provider'] ) ? $aanp_options['llm_provider'] : 'openai';
$aanp_word_count = isset( $aanp_options['word_count'] ) ? $aanp_options['word_count'] : 'medium';
$aanp_tone       = isset( $aanp_options['tone'] ) ? $aanp_options['tone'] : 'neutral';
$aanp_images     = ! empty( $aanp_options['featured_images'] );
$aanp_feeds      = isset( $aanp_options['rss_feeds'] ) ? (array) $aanp_options['rss_feeds'] : AANP_DEFAULT_FEEDS;
$aanp_categories = isset( $aanp_options['categories'] ) ? (array) $aanp_options['categories'] : array();
?>

<div class="aanp-page">
	<div class="wrap">
		<?php settings_errors(); ?>
	</div>

	<!-- ── Hero ───────────────────────────────────────────────────────────── -->
	<div class="aanp-hero">
		<div class="aanp-hero__brand">
			<div class="aanp-hero__icon">AI</div>
			<div>
				<div class="aanp-hero__title">ArunAI – Auto News Poster</div>
				<div class="aanp-hero__subtitle">
					<?php esc_html_e( 'AI-powered news blogging for WordPress · by', 'arunai-auto-news-poster' ); ?>
					<a href="https://github.com/arunrajiah" target="_blank" rel="noopener" style="color:#7dd3fc;font-weight:600;text-decoration:none;">arunrajiah</a>
				</div>
			</div>
		</div>
		<div class="aanp-hero__meta">
			<span class="aanp-hero__badge">v<?php echo esc_html( AANP_VERSION ); ?></span>
			<a href="https://github.com/sponsors/arunrajiah" target="_blank" rel="noopener" class="aanp-sponsor-btn">
				<span>&#9829;</span>
				<?php esc_html_e( 'Sponsor', 'arunai-auto-news-poster' ); ?>
			</a>
		</div>
	</div>

	<!-- ── Stats ──────────────────────────────────────────────────────────── -->
	<div class="aanp-stats">
		<div class="aanp-stat aanp-stat--total">
			<div class="aanp-stat__icon dashicons dashicons-admin-post"></div>
			<div class="aanp-stat__body">
				<div class="aanp-stat__value"><?php echo esc_html( $aanp_stats['total'] ); ?></div>
				<div class="aanp-stat__label"><?php esc_html_e( 'Total Posts', 'arunai-auto-news-poster' ); ?></div>
			</div>
		</div>
		<div class="aanp-stat aanp-stat--today">
			<div class="aanp-stat__icon dashicons dashicons-calendar-alt"></div>
			<div class="aanp-stat__body">
				<div class="aanp-stat__value"><?php echo esc_html( $aanp_stats['today'] ); ?></div>
				<div class="aanp-stat__label"><?php esc_html_e( 'Today', 'arunai-auto-news-poster' ); ?></div>
			</div>
		</div>
		<div class="aanp-stat aanp-stat--week">
			<div class="aanp-stat__icon dashicons dashicons-chart-bar"></div>
			<div class="aanp-stat__body">
				<div class="aanp-stat__value"><?php echo esc_html( $aanp_stats['week'] ); ?></div>
				<div class="aanp-stat__label"><?php esc_html_e( 'This Week', 'arunai-auto-news-poster' ); ?></div>
			</div>
		</div>
		<div class="aanp-stat aanp-stat--month">
			<div class="aanp-stat__icon dashicons dashicons-chart-line"></div>
			<div class="aanp-stat__body">
				<div class="aanp-stat__value"><?php echo esc_html( $aanp_stats['month'] ); ?></div>
				<div class="aanp-stat__label"><?php esc_html_e( 'This Month', 'arunai-auto-news-poster' ); ?></div>
			</div>
		</div>
	</div>

	<!-- ── Tabs nav ───────────────────────────────────────────────────────── -->
	<div class="aanp-tabs-nav" role="tablist">
		<button class="aanp-tab-btn is-active" data-tab="dashboard" role="tab" aria-selected="true" type="button">
			<span class="dashicons dashicons-dashboard"></span>
			<?php esc_html_e( 'Dashboard', 'arunai-auto-news-poster' ); ?>
		</button>
		<button class="aanp-tab-btn" data-tab="settings" role="tab" aria-selected="false" type="button">
			<span class="dashicons dashicons-admin-settings"></span>
			<?php esc_html_e( 'Settings', 'arunai-auto-news-poster' ); ?>
		</button>
		<button class="aanp-tab-btn" data-tab="feeds" role="tab" aria-selected="false" type="button">
			<span class="dashicons dashicons-rss"></span>
			<?php esc_html_e( 'RSS Feeds', 'arunai-auto-news-poster' ); ?>
		</button>
	</div>

	<!-- ════════════════════════════════════════════════════════════════════
	     TAB: Dashboard
	     ════════════════════════════════════════════════════════════════════ -->
	<div class="aanp-tab-pane is-active" id="aanp-tab-dashboard">

		<!-- Generate card -->
		<div class="aanp-card aanp-generate-card">
			<div class="aanp-card__header">
				<h2 class="aanp-card__title">
					<span class="dashicons dashicons-update"></span>
					<?php esc_html_e( 'Generate Posts', 'arunai-auto-news-poster' ); ?>
				</h2>
				<?php
				$aanp_next = wp_next_scheduled( AANP_Scheduler::CRON_HOOK );
				if ( $aanp_next && 'disabled' !== $aanp_schedule ) :
					?>
					<span style="font-size:12px;color:var(--aanp-text-muted);font-weight:500;">
						<?php
						/* translators: %s: human-readable time, e.g. "in 2 hours" */
						printf(
							esc_html__( 'Next run in %s', 'arunai-auto-news-poster' ),
							esc_html( human_time_diff( time(), $aanp_next ) )
						);
						?>
					</span>
				<?php endif; ?>
			</div>
			<div class="aanp-card__body">
				<p class="aanp-generate-desc">
					<?php esc_html_e( 'Fetch the latest headlines from your RSS feeds and let AI write unique, publication-ready blog posts — automatically.', 'arunai-auto-news-poster' ); ?>
				</p>
				<div class="aanp-generate-actions">
					<button type="button" id="aanp-generate-posts" class="aanp-btn-primary">
						<span class="dashicons dashicons-update"></span>
						<?php esc_html_e( 'Generate Posts Now', 'arunai-auto-news-poster' ); ?>
					</button>
					<?php if ( 'disabled' === $aanp_schedule ) : ?>
					<span style="font-size:12px;color:var(--aanp-text-muted);">
						<?php esc_html_e( 'Tip: enable auto-scheduling in the Settings tab.', 'arunai-auto-news-poster' ); ?>
					</span>
					<?php endif; ?>
				</div>

				<div id="aanp-generation-status">
					<div class="aanp-progress-wrap">
						<div class="aanp-progress-bar"></div>
					</div>
					<p id="aanp-status-text"></p>
				</div>

				<div id="aanp-generation-results">
					<h3 style="font-size:14px;font-weight:700;margin:0 0 12px;color:var(--aanp-text);">
						<?php esc_html_e( 'Results', 'arunai-auto-news-poster' ); ?>
					</h3>
					<ul id="aanp-results-list"></ul>
				</div>
			</div>
		</div>

		<!-- Recent posts -->
		<div class="aanp-card">
			<div class="aanp-card__header">
				<h2 class="aanp-card__title">
					<span class="dashicons dashicons-list-view"></span>
					<?php esc_html_e( 'Recent Generated Posts', 'arunai-auto-news-poster' ); ?>
				</h2>
				<a href="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>" class="aanp-btn-secondary" style="padding:5px 12px;font-size:12px;">
					<?php esc_html_e( 'View all posts →', 'arunai-auto-news-poster' ); ?>
				</a>
			</div>
			<?php if ( ! empty( $aanp_recent_posts ) ) : ?>
			<table class="aanp-posts-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Title', 'arunai-auto-news-poster' ); ?></th>
						<th><?php esc_html_e( 'Status', 'arunai-auto-news-poster' ); ?></th>
						<th><?php esc_html_e( 'Generated', 'arunai-auto-news-poster' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $aanp_recent_posts as $aanp_post ) : ?>
					<tr>
						<td>
							<span class="aanp-post-title"><?php echo esc_html( $aanp_post['title'] ); ?></span>
							<a class="aanp-post-source" href="<?php echo esc_url( $aanp_post['source_url'] ); ?>" target="_blank" rel="noopener">
								↗ <?php esc_html_e( 'Source', 'arunai-auto-news-poster' ); ?>
							</a>
						</td>
						<td>
							<span class="aanp-badge aanp-badge--<?php echo esc_attr( $aanp_post['status'] ); ?>">
								<?php echo esc_html( ucfirst( $aanp_post['status'] ) ); ?>
							</span>
						</td>
						<td>
							<span class="aanp-post-time">
								<?php
								echo esc_html(
									/* translators: %s: human-readable time difference, e.g. "5 minutes" */
									sprintf( __( '%s ago', 'arunai-auto-news-poster' ), human_time_diff( strtotime( $aanp_post['generated_at'] ), time() ) )
								);
								?>
							</span>
						</td>
						<td>
							<a href="<?php echo esc_url( $aanp_post['edit_link'] ); ?>" class="aanp-btn-edit">
								<span class="dashicons dashicons-edit" style="font-size:13px;width:13px;height:13px;margin-top:1px;"></span>
								<?php esc_html_e( 'Edit', 'arunai-auto-news-poster' ); ?>
							</a>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php else : ?>
			<div class="aanp-empty">
				<span class="dashicons dashicons-admin-post"></span>
				<p><?php esc_html_e( 'No posts generated yet. Click "Generate Posts Now" to get started.', 'arunai-auto-news-poster' ); ?></p>
			</div>
			<?php endif; ?>
		</div>

	</div><!-- /dashboard tab -->

	<!-- ════════════════════════════════════════════════════════════════════
	     TAB: Settings
	     ════════════════════════════════════════════════════════════════════ -->
	<div class="aanp-tab-pane" id="aanp-tab-settings">
		<form method="post" action="options.php">
			<?php settings_fields( 'aanp_settings_group' ); ?>

			<div class="aanp-settings-grid">

				<!-- AI Provider -->
				<div class="aanp-card">
					<div class="aanp-card__header">
						<h2 class="aanp-card__title">
							<span class="dashicons dashicons-admin-network"></span>
							<?php esc_html_e( 'AI Provider', 'arunai-auto-news-poster' ); ?>
						</h2>
					</div>
					<div class="aanp-card__body">
						<div class="aanp-field">
							<label><?php esc_html_e( 'Provider', 'arunai-auto-news-poster' ); ?></label>
							<select name="aanp_settings[llm_provider]">
								<option value="openai"    <?php selected( $aanp_provider, 'openai' ); ?>><?php esc_html_e( 'OpenAI (GPT-4o / GPT-4)', 'arunai-auto-news-poster' ); ?></option>
								<option value="anthropic" <?php selected( $aanp_provider, 'anthropic' ); ?>><?php esc_html_e( 'Anthropic (Claude)', 'arunai-auto-news-poster' ); ?></option>
								<option value="custom"    <?php selected( $aanp_provider, 'custom' ); ?>><?php esc_html_e( 'Custom (OpenAI-compatible)', 'arunai-auto-news-poster' ); ?></option>
							</select>
						</div>
						<div class="aanp-field">
							<label><?php esc_html_e( 'API Key', 'arunai-auto-news-poster' ); ?></label>
							<input type="password"
								name="aanp_settings[api_key]"
								value="<?php echo esc_attr( isset( $aanp_options['api_key'] ) ? $aanp_options['api_key'] : '' ); ?>"
								autocomplete="new-password"
								placeholder="sk-…" />
							<p class="aanp-field-hint">
								<?php
								printf(
									/* translators: %s: link to OpenAI API keys page */
									esc_html__( 'Stored encrypted. Get your key from %s.', 'arunai-auto-news-poster' ),
									'<a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener">platform.openai.com</a>'
								);
								?>
							</p>
						</div>
						<?php if ( 'custom' === $aanp_provider ) : ?>
						<div class="aanp-field" id="aanp-custom-endpoint-wrap">
							<label><?php esc_html_e( 'Endpoint URL', 'arunai-auto-news-poster' ); ?></label>
							<input type="url"
								name="aanp_settings[custom_endpoint]"
								value="<?php echo esc_attr( isset( $aanp_options['custom_endpoint'] ) ? $aanp_options['custom_endpoint'] : '' ); ?>"
								placeholder="https://api.example.com/v1" />
						</div>
						<?php endif; ?>
					</div>
				</div>

				<!-- Content Settings -->
				<div class="aanp-card">
					<div class="aanp-card__header">
						<h2 class="aanp-card__title">
							<span class="dashicons dashicons-editor-paragraph"></span>
							<?php esc_html_e( 'Content', 'arunai-auto-news-poster' ); ?>
						</h2>
					</div>
					<div class="aanp-card__body">
						<div class="aanp-field">
							<label><?php esc_html_e( 'Word Count', 'arunai-auto-news-poster' ); ?></label>
							<select name="aanp_settings[word_count]">
								<option value="short"  <?php selected( $aanp_word_count, 'short' ); ?>><?php esc_html_e( 'Short (~300 words)', 'arunai-auto-news-poster' ); ?></option>
								<option value="medium" <?php selected( $aanp_word_count, 'medium' ); ?>><?php esc_html_e( 'Medium (~600 words)', 'arunai-auto-news-poster' ); ?></option>
								<option value="long"   <?php selected( $aanp_word_count, 'long' ); ?>><?php esc_html_e( 'Long (~1000 words)', 'arunai-auto-news-poster' ); ?></option>
							</select>
						</div>
						<div class="aanp-field">
							<label><?php esc_html_e( 'Writing Tone', 'arunai-auto-news-poster' ); ?></label>
							<select name="aanp_settings[tone]">
								<option value="neutral"       <?php selected( $aanp_tone, 'neutral' ); ?>><?php esc_html_e( 'Neutral', 'arunai-auto-news-poster' ); ?></option>
								<option value="professional"  <?php selected( $aanp_tone, 'professional' ); ?>><?php esc_html_e( 'Professional', 'arunai-auto-news-poster' ); ?></option>
								<option value="conversational"<?php selected( $aanp_tone, 'conversational' ); ?>><?php esc_html_e( 'Conversational', 'arunai-auto-news-poster' ); ?></option>
								<option value="analytical"    <?php selected( $aanp_tone, 'analytical' ); ?>><?php esc_html_e( 'Analytical', 'arunai-auto-news-poster' ); ?></option>
							</select>
						</div>
						<div class="aanp-field">
							<label><?php esc_html_e( 'Post Categories', 'arunai-auto-news-poster' ); ?></label>
							<div class="aanp-categories">
								<?php
								$aanp_all_cats = get_categories( array( 'hide_empty' => false ) );
								if ( ! empty( $aanp_all_cats ) ) :
									foreach ( $aanp_all_cats as $aanp_cat ) :
										?>
										<label>
											<input type="checkbox"
												name="aanp_settings[categories][]"
												value="<?php echo esc_attr( $aanp_cat->term_id ); ?>"
												<?php checked( in_array( (string) $aanp_cat->term_id, array_map( 'strval', $aanp_categories ), true ) ); ?> />
											<?php echo esc_html( $aanp_cat->name ); ?>
										</label>
									<?php
									endforeach;
								else :
									echo '<p style="padding:6px;color:var(--aanp-text-muted);font-size:13px;">' . esc_html__( 'No categories found.', 'arunai-auto-news-poster' ) . '</p>';
								endif;
								?>
							</div>
							<p class="aanp-field-hint"><?php esc_html_e( 'Leave empty to use the default Uncategorized.', 'arunai-auto-news-poster' ); ?></p>
						</div>
					</div>
				</div>

				<!-- Scheduling — full width -->
				<div class="aanp-card" style="grid-column:1/-1;">
					<div class="aanp-card__header">
						<h2 class="aanp-card__title">
							<span class="dashicons dashicons-clock"></span>
							<?php esc_html_e( 'Auto-Scheduling', 'arunai-auto-news-poster' ); ?>
						</h2>
					</div>
					<div class="aanp-card__body">
						<p class="aanp-field-hint" style="margin:0 0 16px;">
							<?php esc_html_e( 'Run post generation automatically via WP-Cron. "Disabled" means manual-only.', 'arunai-auto-news-poster' ); ?>
						</p>
						<div class="aanp-schedule-grid">
							<?php
							$aanp_schedules = array(
								'disabled'     => array( '⛔', __( 'Disabled', 'arunai-auto-news-poster' ) ),
								'hourly'       => array( '⚡', __( 'Hourly', 'arunai-auto-news-poster' ) ),
								'aanp_6hourly' => array( '🕐', __( 'Every 6h', 'arunai-auto-news-poster' ) ),
								'twicedaily'   => array( '📅', __( 'Twice Daily', 'arunai-auto-news-poster' ) ),
								'daily'        => array( '☀️', __( 'Daily', 'arunai-auto-news-poster' ) ),
							);
							foreach ( $aanp_schedules as $aanp_val => $aanp_sched ) :
								?>
								<div class="aanp-schedule-option">
									<input type="radio"
										name="aanp_settings[schedule]"
										id="aanp_schedule_<?php echo esc_attr( $aanp_val ); ?>"
										value="<?php echo esc_attr( $aanp_val ); ?>"
										<?php checked( $aanp_schedule, $aanp_val ); ?> />
									<label for="aanp_schedule_<?php echo esc_attr( $aanp_val ); ?>">
										<span class="sched-icon"><?php echo $aanp_sched[0]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
										<?php echo esc_html( $aanp_sched[1] ); ?>
									</label>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
				</div>

				<!-- Featured Images — full width -->
				<div class="aanp-card" style="grid-column:1/-1;">
					<div class="aanp-card__header">
						<h2 class="aanp-card__title">
							<span class="dashicons dashicons-format-image"></span>
							<?php esc_html_e( 'Featured Images', 'arunai-auto-news-poster' ); ?>
						</h2>
						<span style="font-size:12px;color:var(--aanp-text-muted);">
							<?php esc_html_e( 'OpenAI DALL-E 3 · ~$0.08 / image', 'arunai-auto-news-poster' ); ?>
						</span>
					</div>
					<div class="aanp-card__body">
						<div class="aanp-toggle-row">
							<label class="aanp-toggle">
								<input type="checkbox" name="aanp_settings[featured_images]" value="1" <?php checked( $aanp_images ); ?> />
								<span class="aanp-toggle__slider"></span>
							</label>
							<div class="aanp-toggle-content">
								<span class="aanp-toggle-label"><?php esc_html_e( 'Generate DALL-E 3 featured images', 'arunai-auto-news-poster' ); ?></span>
								<span class="aanp-toggle-desc">
									<?php esc_html_e( 'Automatically generate and attach an AI-created editorial image (1792×1024 px) to every post. Requires an OpenAI API key with image access.', 'arunai-auto-news-poster' ); ?>
								</span>
							</div>
						</div>
					</div>
					<!-- Sticky save bar inside the card -->
					<div class="aanp-save-bar">
						<span class="aanp-save-bar__hint">
							<?php esc_html_e( 'Settings are saved per WordPress site.', 'arunai-auto-news-poster' ); ?>
						</span>
						<?php submit_button( __( 'Save Settings', 'arunai-auto-news-poster' ), 'aanp-btn-primary', 'submit', false ); ?>
					</div>
				</div>

			</div><!-- /.aanp-settings-grid -->
		</form>
	</div><!-- /settings tab -->

	<!-- ════════════════════════════════════════════════════════════════════
	     TAB: RSS Feeds
	     ════════════════════════════════════════════════════════════════════ -->
	<div class="aanp-tab-pane" id="aanp-tab-feeds">
		<form method="post" action="options.php">
			<?php settings_fields( 'aanp_settings_group' ); ?>
			<!-- carry other settings along as hidden fields -->
			<input type="hidden" name="aanp_settings[llm_provider]"    value="<?php echo esc_attr( $aanp_provider ); ?>" />
			<input type="hidden" name="aanp_settings[api_key]"         value="<?php echo esc_attr( isset( $aanp_options['api_key'] ) ? $aanp_options['api_key'] : '' ); ?>" />
			<input type="hidden" name="aanp_settings[word_count]"      value="<?php echo esc_attr( $aanp_word_count ); ?>" />
			<input type="hidden" name="aanp_settings[tone]"            value="<?php echo esc_attr( $aanp_tone ); ?>" />
			<input type="hidden" name="aanp_settings[schedule]"        value="<?php echo esc_attr( $aanp_schedule ); ?>" />
			<input type="hidden" name="aanp_settings[featured_images]" value="<?php echo esc_attr( $aanp_images ? '1' : '0' ); ?>" />

			<div class="aanp-card">
				<div class="aanp-card__header">
					<h2 class="aanp-card__title">
						<span class="dashicons dashicons-rss"></span>
						<?php esc_html_e( 'RSS Feed Sources', 'arunai-auto-news-poster' ); ?>
					</h2>
					<span style="font-size:12px;color:var(--aanp-text-muted);"><?php echo esc_html( count( $aanp_feeds ) ); ?> <?php esc_html_e( 'feeds configured', 'arunai-auto-news-poster' ); ?></span>
				</div>
				<div class="aanp-card__body">
					<p class="aanp-field-hint" style="margin:0 0 16px;">
						<?php esc_html_e( 'Add any valid RSS/Atom feed URL. Posts are fetched from all feeds during each generation run and cached for 30 minutes.', 'arunai-auto-news-poster' ); ?>
					</p>

					<div id="rss-feeds-container">
						<?php foreach ( $aanp_feeds as $aanp_feed_url ) : ?>
						<div class="rss-feed-row">
							<input type="url"
								name="aanp_settings[rss_feeds][]"
								value="<?php echo esc_url( $aanp_feed_url ); ?>"
								placeholder="https://example.com/feed.xml" />
							<button type="button" class="test-feed"><?php esc_html_e( 'Test', 'arunai-auto-news-poster' ); ?></button>
							<button type="button" class="remove-feed">✕</button>
							<span class="feed-test-result"></span>
						</div>
						<?php endforeach; ?>
					</div>
					<button type="button" id="add-feed">
						<span class="dashicons dashicons-plus-alt2" style="font-size:15px;width:15px;height:15px;margin-top:2px;"></span>
						<?php esc_html_e( 'Add Feed', 'arunai-auto-news-poster' ); ?>
					</button>

					<div class="aanp-save-bar" style="margin-top:24px;">
						<span class="aanp-save-bar__hint">
							<?php esc_html_e( 'Feeds are validated before saving.', 'arunai-auto-news-poster' ); ?>
						</span>
						<?php submit_button( __( 'Save Feeds', 'arunai-auto-news-poster' ), 'aanp-btn-primary', 'submit', false ); ?>
					</div>
				</div>
			</div>
		</form>
	</div><!-- /feeds tab -->

</div><!-- /.aanp-page -->
