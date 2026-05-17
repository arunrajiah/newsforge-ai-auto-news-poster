<?php
/**
 * AI Generator Class
 *
 * @package AI_Auto_News_Poster
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AANP_AI_Generator {

	private $api_key;
	private $provider;
	private $word_count;
	private $tone;

	/**
	 * Truncate a string for safe inclusion in error logs.
	 */
	private function sanitize_for_log( string $text, int $max_length = 200 ): string {
		return mb_substr( wp_strip_all_tags( $text ), 0, $max_length );
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		$options = get_option( 'aanp_settings', array() );
		// Decrypt API key for use
		$encrypted_key    = isset( $options['api_key'] ) ? $options['api_key'] : '';
		$this->api_key    = AANP_Admin_Settings::decrypt_api_key( $encrypted_key );
		$this->provider   = isset( $options['llm_provider'] ) ? $options['llm_provider'] : 'openai';
		$this->word_count = isset( $options['word_count'] ) ? $options['word_count'] : 'medium';
		$this->tone       = isset( $options['tone'] ) ? $options['tone'] : 'neutral';
	}

	/**
	 * Generate content from news article
	 *
	 * @param array $article Article data
	 * @return array|false Generated content or false on failure
	 */
	public function generate_content( array $article ) {
		if ( empty( $this->api_key ) ) {
			error_log( 'AANP: API key not configured' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return false;
		}

		$prompt = $this->build_prompt( $article );

		switch ( $this->provider ) {
			case 'openai':
				return $this->generate_with_openai( $prompt, $article );
			case 'anthropic':
				return $this->generate_with_anthropic( $prompt, $article );
			case 'custom':
				return $this->generate_with_custom_api( $prompt, $article );
			default:
				error_log( 'AANP: Unknown LLM provider: ' . $this->provider ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				return false;
		}
	}

	/**
	 * Build prompt for AI generation
	 *
	 * @param array $article Article data
	 * @return string Generated prompt
	 */
	private function build_prompt( array $article ): string {
		$word_counts = array(
			'short'  => '300-400',
			'medium' => '500-600',
			'long'   => '800-1000',
		);

		$word_range = isset( $word_counts[ $this->word_count ] ) ? $word_counts[ $this->word_count ] : '500-600';

		$tone_descriptions = array(
			'neutral'      => 'neutral and informative',
			'professional' => 'professional and authoritative',
			'friendly'     => 'friendly and conversational',
		);

		$tone_desc = isset( $tone_descriptions[ $this->tone ] ) ? $tone_descriptions[ $this->tone ] : 'neutral and informative';

		$prompt  = 'You are a professional content writer. Your task is to rewrite the following news article into a unique, engaging blog post.

';
		$prompt .= 'ORIGINAL ARTICLE:
';
		$prompt .= "Title: {$article['title']}
";
		$prompt .= "Summary: {$article['description']}
";
		$prompt .= "Source: {$article['source_domain']}

";

		$prompt .= 'REQUIREMENTS:
';
		$prompt .= "- Write a {$word_range} word blog post
";
		$prompt .= "- Use a {$tone_desc} tone
";
		$prompt .= '- Create an engaging, SEO-friendly title
';
		$prompt .= '- Include a compelling introduction
';
		$prompt .= '- Provide detailed analysis and context
';
		$prompt .= '- Add a thoughtful conclusion
';
		$prompt .= '- Do NOT copy text directly from the original
';
		$prompt .= '- Make the content unique and valuable
';
		$prompt .= '- Use proper paragraph structure

';

		$prompt .= 'Please provide your response in the following JSON format:
';
		$prompt .= '{
';
		$prompt .= '  "title": "Your generated title here",
';
		$prompt .= '  "content": "Your full blog post content here"
';
		$prompt .= '}';

		return $prompt;
	}

	/**
	 * Generate content using OpenAI
	 *
	 * @param string $prompt AI prompt
	 * @param array  $article Original article
	 * @return array|false Generated content
	 */
	private function generate_with_openai( string $prompt, array $article ) {
		$url = 'https://api.openai.com/v1/chat/completions';

		$data = array(
			'model'       => 'gpt-3.5-turbo',
			'messages'    => array(
				array(
					'role'    => 'user',
					'content' => $prompt,
				),
			),
			'max_tokens'  => 2000,
			'temperature' => 0.7,
		);

		$headers = array(
			'Authorization' => 'Bearer ' . $this->api_key,
			'Content-Type'  => 'application/json',
		);

		$response = wp_remote_post(
			$url,
			array(
				'headers' => $headers,
				'body'    => wp_json_encode( $data ),
				'timeout' => 60,
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'AANP: OpenAI API error: ' . $response->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return false;
		}

		$body   = wp_remote_retrieve_body( $response );
		$result = json_decode( $body, true );

		if ( ! isset( $result['choices'][0]['message']['content'] ) ) {
			error_log( 'AANP: Invalid OpenAI response: ' . $this->sanitize_for_log( $body ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return false;
		}

		return $this->parse_ai_response( $result['choices'][0]['message']['content'], $article );
	}

	/**
	 * Generate content using Anthropic
	 *
	 * @param string $prompt AI prompt
	 * @param array  $article Original article
	 * @return array|false Generated content
	 */
	private function generate_with_anthropic( string $prompt, array $article ) {
		$url = 'https://api.anthropic.com/v1/messages';

		$data = array(
			'model'      => 'claude-3-5-sonnet-20241022',
			'max_tokens' => 2000,
			'messages'   => array(
				array(
					'role'    => 'user',
					'content' => $prompt,
				),
			),
		);

		$headers = array(
			'x-api-key'         => $this->api_key,
			'Content-Type'      => 'application/json',
			'anthropic-version' => '2023-06-01',
		);

		$response = wp_remote_post(
			$url,
			array(
				'headers' => $headers,
				'body'    => wp_json_encode( $data ),
				'timeout' => 60,
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'AANP: Anthropic API error: ' . $response->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return false;
		}

		$body   = wp_remote_retrieve_body( $response );
		$result = json_decode( $body, true );

		if ( ! isset( $result['content'][0]['text'] ) ) {
			error_log( 'AANP: Invalid Anthropic response: ' . $this->sanitize_for_log( $body ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return false;
		}

		return $this->parse_ai_response( $result['content'][0]['text'], $article );
	}

	/**
	 * Generate content using a user-configured custom API endpoint.
	 * The endpoint must accept an OpenAI-compatible chat completions request
	 * and return an OpenAI-compatible response (choices[0].message.content).
	 *
	 * @param string $prompt AI prompt
	 * @param array  $article Original article
	 * @return array|false Generated content
	 */
	private function generate_with_custom_api( string $prompt, array $article ) {
		$options  = get_option( 'aanp_settings', array() );
		$endpoint = isset( $options['custom_api_endpoint'] ) ? trim( $options['custom_api_endpoint'] ) : '';

		if ( empty( $endpoint ) || ! filter_var( $endpoint, FILTER_VALIDATE_URL ) ) {
			error_log( 'AANP: Custom API endpoint not configured or invalid' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return false;
		}

		$model = ! empty( $options['custom_api_model'] ) ? $options['custom_api_model'] : 'default';

		$data = array(
			'model'       => $model,
			'messages'    => array(
				array(
					'role'    => 'user',
					'content' => $prompt,
				),
			),
			'max_tokens'  => 2000,
			'temperature' => 0.7,
		);

		$headers = array(
			'Content-Type' => 'application/json',
		);

		if ( ! empty( $this->api_key ) ) {
			$headers['Authorization'] = 'Bearer ' . $this->api_key;
		}

		$response = wp_remote_post(
			$endpoint,
			array(
				'headers' => $headers,
				'body'    => wp_json_encode( $data ),
				'timeout' => 60,
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'AANP: Custom API error: ' . $response->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return false;
		}

		$body   = wp_remote_retrieve_body( $response );
		$result = json_decode( $body, true );

		if ( ! isset( $result['choices'][0]['message']['content'] ) ) {
			error_log( 'AANP: Invalid custom API response: ' . $this->sanitize_for_log( $body ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return false;
		}

		return $this->parse_ai_response( $result['choices'][0]['message']['content'], $article );
	}

	/**
	 * Parse AI response
	 *
	 * @param string $response AI response
	 * @param array  $article Original article
	 * @return array|false Parsed content
	 */
	private function parse_ai_response( string $response, array $article ) {
		// Try to parse JSON response
		$json_data = json_decode( $response, true );

		if ( $json_data && isset( $json_data['title'] ) && isset( $json_data['content'] ) ) {
			return array(
				'title'         => sanitize_text_field( $json_data['title'] ),
				'content'       => wp_kses_post( $json_data['content'] ),
				'source_url'    => $article['link'],
				'source_domain' => $article['source_domain'],
			);
		}

		// If JSON parsing fails, try to extract title and content manually
		$lines   = explode(
			'
',
			$response
		);
		$title   = '';
		$content = '';

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( empty( $line ) ) {
				continue;
			}

			// Look for title patterns
			if ( preg_match( '/^(title|headline):\s*(.+)$/i', $line, $matches ) ) {
				$title = $matches[2];
			} elseif ( empty( $title ) && strlen( $line ) < 100 && ! empty( $line ) ) {
				// First short line might be the title
				$title = $line;
			} else {
				// Everything else is content
				$content .= $line . '

';
			}
		}

		// Fallback if parsing fails
		if ( empty( $title ) || empty( $content ) ) {
			return array(
				'title'         => 'Breaking: ' . $article['title'],
				'content'       => $this->generate_fallback_content( $article ),
				'source_url'    => $article['link'],
				'source_domain' => $article['source_domain'],
			);
		}

		return array(
			'title'         => sanitize_text_field( $title ),
			'content'       => wp_kses_post( trim( $content ) ),
			'source_url'    => $article['link'],
			'source_domain' => $article['source_domain'],
		);
	}

	/**
	 * Generate fallback content when AI fails
	 *
	 * @param array $article Original article
	 * @return string Fallback content
	 */
	private function generate_fallback_content( array $article ): string {
		$content  = '<p>In recent news, ' . esc_html( $article['title'] ) . ' has been making headlines.</p>

';
		$content .= '<p>' . wp_kses_post( $article['description'] ) . '</p>

';
		$content .= '<p>This developing story continues to unfold, and we will provide updates as more information becomes available.</p>

';
		$content .= '<p>For more details, you can read the original article at <a href="' . esc_url( $article['link'] ) . '" target="_blank" rel="noopener">' . esc_html( $article['source_domain'] ) . '</a>.</p>';

		return $content;
	}

	/**
	 * Test API connection
	 *
	 * @return array Test result
	 */
	public function test_api_connection(): array {
		if ( empty( $this->api_key ) ) {
			return array(
				'status'  => 'error',
				'message' => 'API key not configured',
			);
		}

		$test_article = array(
			'title'         => 'Test Article',
			'description'   => 'This is a test article for API connection.',
			'link'          => 'https://example.com',
			'source_domain' => 'example.com',
		);

		$result = $this->generate_content( $test_article );

		if ( $result ) {
			return array(
				'status'  => 'success',
				'message' => 'API connection successful',
			);
		} else {
			return array(
				'status'  => 'error',
				'message' => 'API connection failed',
			);
		}
	}
}
