<?php
/**
 * Scheduler Class
 *
 * @package AI_Auto_News_Poster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AANP_Scheduler {

	/**
	 * WP-Cron hook name used for scheduled generation.
	 */
	const CRON_HOOK = 'aanp_scheduled_generation';

	/**
	 * Schedules that may be stored in settings.
	 *
	 * @var string[]
	 */
	const ALLOWED_SCHEDULES = array( 'disabled', 'hourly', 'aanp_every6hours', 'twicedaily', 'daily' );

	/**
	 * Register the custom "every 6 hours" cron schedule.
	 *
	 * Hooked to `cron_schedules`.
	 *
	 * @param array $schedules Existing schedules passed by WordPress.
	 * @return array Schedules with the custom entry appended.
	 */
	public function register_cron_schedules( array $schedules ): array {
		$schedules['aanp_every6hours'] = array(
			'interval' => 21600,
			'display'  => __( 'Every 6 Hours', 'arunai-auto-news-poster' ),
		);

		return $schedules;
	}

	/**
	 * Reschedule the cron event when the user saves a new schedule setting.
	 *
	 * If $new_schedule is 'disabled', any existing event is cleared and nothing
	 * new is scheduled. Otherwise the existing event (if any) is cleared and a
	 * fresh event is scheduled immediately.
	 *
	 * @param string $new_schedule One of ALLOWED_SCHEDULES.
	 */
	public function maybe_reschedule( string $new_schedule ): void {
		$existing = wp_get_scheduled_event( self::CRON_HOOK );

		// If the schedule hasn't changed, do nothing.
		if ( $existing && $existing->schedule === $new_schedule ) {
			return;
		}

		wp_clear_scheduled_hook( self::CRON_HOOK );

		if ( 'disabled' !== $new_schedule ) {
			wp_schedule_event( time(), $new_schedule, self::CRON_HOOK );
		}
	}

	/**
	 * Clear any scheduled generation event (called on plugin deactivation).
	 */
	public function unschedule(): void {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	/**
	 * Execute scheduled post generation.
	 *
	 * Instantiates the required helper classes, fetches articles, and generates
	 * posts up to the configured batch limit. If the `featured_images` option is
	 * enabled a featured image is also generated and attached to each new post.
	 */
	public static function run(): void {
		$news_fetch   = new AANP_News_Fetch();
		$ai_generator = new AANP_AI_Generator();
		$post_creator = new AANP_Post_Creator();
		$image_gen    = new AANP_Image_Generator();

		$settings        = get_option( 'aanp_settings', array() );
		$featured_images = ! empty( $settings['featured_images'] );

		$articles = $news_fetch->fetch_latest_news();

		if ( empty( $articles ) ) {
			return;
		}

		$articles = array_slice( $articles, 0, 5 );

		foreach ( $articles as $article ) {
			try {
				$generated = $ai_generator->generate_content( $article );

				if ( ! $generated ) {
					continue;
				}

				$validation = $post_creator->validate_post_data( $generated, $article );
				if ( ! $validation['valid'] ) {
					error_log( 'AANP Scheduler: Skipping invalid post data: ' . implode( '; ', $validation['errors'] ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					continue;
				}

				$post_id = $post_creator->create_post( $generated, $article );

				if ( $post_id && $featured_images ) {
					$image_gen->generate_and_attach( $post_id, $generated['title'] );
				}
			} catch ( Exception $e ) {
				error_log( 'AANP Scheduler error: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
		}
	}

	/**
	 * Return a human-readable string describing when the next run will occur.
	 *
	 * @return string|null Human time diff string, or null when nothing is scheduled.
	 */
	public function get_next_run(): ?string {
		$next = wp_next_scheduled( self::CRON_HOOK );

		if ( ! $next ) {
			return null;
		}

		return human_time_diff( time(), $next );
	}

	/**
	 * Return the recurrence key for the currently scheduled event.
	 *
	 * @return string Schedule slug (e.g. 'daily') or 'disabled' when not scheduled.
	 */
	public function get_current_schedule(): string {
		$event = wp_get_scheduled_event( self::CRON_HOOK );

		if ( ! $event ) {
			return 'disabled';
		}

		return $event->schedule;
	}
}
