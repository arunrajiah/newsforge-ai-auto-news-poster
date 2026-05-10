<?php
/**
 * Admin Settings Page Template
 *
 * @package AI_Auto_News_Poster
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$aanp_options      = get_option( 'aanp_settings', array() );
$aanp_post_creator = new AANP_Post_Creator();
$aanp_stats        = $aanp_post_creator->get_stats();
$aanp_recent_posts = $aanp_post_creator->get_recent_posts( 5 );
?>

<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php settings_errors(); ?>

	<!-- Author / Sponsor Strip -->
	<div class="aanp-author-strip">
		<div class="aanp-author-strip__logo">
			<span class="aanp-author-strip__logo-mark">AI</span>
			<span class="aanp-author-strip__logo-name">ArunAI</span>
		</div>
		<div class="aanp-author-strip__body">
			<p class="aanp-author-strip__credit">
				<?php esc_html_e( 'ArunAI – Auto News Poster is a free WordPress plugin developed and maintained by', 'arunai-auto-news-poster' ); ?>
				<a href="https://github.com/arunrajiah" target="_blank" rel="noopener">arunrajiah</a>.
			</p>
			<p class="aanp-author-strip__sponsor">
				<?php esc_html_e( 'If you find it useful, please consider', 'arunai-auto-news-poster' ); ?>
				<a href="https://github.com/sponsors/arunrajiah" target="_blank" rel="noopener" class="aanp-sponsor-btn">
					<span class="aanp-sponsor-btn__heart">&#9829;</span>
					<?php esc_html_e( 'becoming a sponsor on GitHub', 'arunai-auto-news-poster' ); ?>
				</a>
				<?php esc_html_e( '— it helps keep the project alive and growing.', 'arunai-auto-news-poster' ); ?>
			</p>
		</div>
	</div>

	<!-- Statistics Dashboard -->
	<div class="aanp-dashboard">
		<h2><?php esc_html_e( 'Statistics', 'arunai-auto-news-poster' ); ?></h2>
		<div class="aanp-stat-grid">
			<div class="aanp-stat-box aanp-stat-total">
				<h3><?php echo esc_html( $aanp_stats['total'] ); ?></h3>
				<p><?php esc_html_e( 'Total Posts', 'arunai-auto-news-poster' ); ?></p>
			</div>
			<div class="aanp-stat-box aanp-stat-today">
				<h3><?php echo esc_html( $aanp_stats['today'] ); ?></h3>
				<p><?php esc_html_e( 'Today', 'arunai-auto-news-poster' ); ?></p>
			</div>
			<div class="aanp-stat-box aanp-stat-week">
				<h3><?php echo esc_html( $aanp_stats['week'] ); ?></h3>
				<p><?php esc_html_e( 'This Week', 'arunai-auto-news-poster' ); ?></p>
			</div>
			<div class="aanp-stat-box aanp-stat-month">
				<h3><?php echo esc_html( $aanp_stats['month'] ); ?></h3>
				<p><?php esc_html_e( 'This Month', 'arunai-auto-news-poster' ); ?></p>
			</div>
		</div>
	</div>

	<!-- Generate Posts Section -->
	<div class="aanp-generate-section">
		<h2><?php esc_html_e( 'Generate Posts', 'arunai-auto-news-poster' ); ?></h2>
		<p>
			<?php esc_html_e( 'Click the button below to fetch the latest news and generate blog posts automatically.', 'arunai-auto-news-poster' ); ?>
		</p>

		<div class="aanp-generate-controls">
			<button type="button" id="aanp-generate-posts" class="button button-primary button-large">
				<span class="dashicons dashicons-update aanp-btn-icon"></span>
				<?php esc_html_e( 'Generate Posts', 'arunai-auto-news-poster' ); ?>
			</button>

			<div id="aanp-generation-status">
				<div class="aanp-progress">
					<div class="aanp-progress-bar"></div>
				</div>
				<p id="aanp-status-text"></p>
			</div>
		</div>

		<div id="aanp-generation-results">
			<h3><?php esc_html_e( 'Generated Posts', 'arunai-auto-news-poster' ); ?></h3>
			<div id="aanp-results-list"></div>
		</div>
	</div>

	<!-- Recent Posts -->
	<?php if ( ! empty( $aanp_recent_posts ) ) : ?>
	<div class="aanp-recent-posts">
		<h2><?php esc_html_e( 'Recent Generated Posts', 'arunai-auto-news-poster' ); ?></h2>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Title', 'arunai-auto-news-poster' ); ?></th>
					<th><?php esc_html_e( 'Status', 'arunai-auto-news-poster' ); ?></th>
					<th><?php esc_html_e( 'Generated', 'arunai-auto-news-poster' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'arunai-auto-news-poster' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $aanp_recent_posts as $aanp_generated_post ) : ?>
				<tr>
					<td>
						<strong><?php echo esc_html( $aanp_generated_post['title'] ); ?></strong>
						<br>
						<small>
							<a href="<?php echo esc_url( $aanp_generated_post['source_url'] ); ?>" target="_blank" rel="noopener">
								<?php esc_html_e( 'Source', 'arunai-auto-news-poster' ); ?>
							</a>
						</small>
					</td>
					<td>
						<span class="post-status <?php echo esc_attr( $aanp_generated_post['status'] ); ?>">
							<?php echo esc_html( ucfirst( $aanp_generated_post['status'] ) ); ?>
						</span>
					</td>
					<td>
						<?php
						/* translators: human-readable time difference, e.g. "5 minutes ago" */
						echo esc_html(
							human_time_diff( strtotime( $aanp_generated_post['generated_at'] ), time() )
							. ' '
							. __( 'ago', 'arunai-auto-news-poster' )
						);
						?>
					</td>
					<td>
						<a href="<?php echo esc_url( $aanp_generated_post['edit_link'] ); ?>" class="button button-small">
							<?php esc_html_e( 'Edit', 'arunai-auto-news-poster' ); ?>
						</a>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>

	<!-- Settings Form -->
	<form method="post" action="options.php">
		<?php
		settings_fields( 'aanp_settings_group' );
		do_settings_sections( 'arunai-auto-news-poster' );
		?>

		<?php submit_button(); ?>
	</form>
</div>
