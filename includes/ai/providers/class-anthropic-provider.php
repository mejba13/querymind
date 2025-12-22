<?php
/**
 * Anthropic Provider
 *
 * @package QueryMind
 */

namespace QueryMind\AI\Providers;

use QueryMind\AI\ProviderInterface;
use QueryMind\AI\ProviderException;

/**
 * Anthropic Claude API provider implementation.
 */
class AnthropicProvider implements ProviderInterface {

    /**
     * API key.
     *
     * @var string
     */
    private string $api_key;

    /**
     * Model to use.
     *
     * @var string
     */
    private string $model;

    /**
     * API endpoint.
     *
     * @var string
     */
    private string $endpoint = 'https://api.anthropic.com/v1/messages';

    /**
     * API version.
     *
     * @var string
     */
    private string $api_version = '2023-06-01';

    /**
     * Constructor.
     */
    public function __construct() {
        $this->api_key = get_option( 'querymind_anthropic_key', '' );
        $this->model = 'claude-3-5-sonnet-20241022';
    }

    /**
     * Get provider name.
     *
     * @return string
     */
    public function get_name(): string {
        return 'anthropic';
    }

    /**
     * Check if provider is configured.
     *
     * @return bool
     */
    public function is_configured(): bool {
        return ! empty( $this->api_key );
    }

    /**
     * Generate completion from prompt.
     *
     * @param string $prompt  The prompt to complete.
     * @param array  $options Optional provider options.
     * @return string
     * @throws ProviderException If completion fails.
     */
    public function complete( string $prompt, array $options = [] ): string {
        if ( ! $this->is_configured() ) {
            throw new ProviderException(
                __( 'Anthropic API key not configured', 'querymind' ),
                'anthropic',
                'not_configured'
            );
        }

        $model = $options['model'] ?? $this->model;
        $max_tokens = $options['max_tokens'] ?? 1000;

        $system_prompt = 'You are a SQL expert. Generate safe, read-only MySQL queries. Always respond with valid JSON containing: {"sql": "YOUR SQL QUERY", "explanation": "Brief explanation", "columns": ["column1", "column2"], "chartType": "table|bar|line|pie|none"}';

        $body = [
            'model'      => $model,
            'max_tokens' => $max_tokens,
            'system'     => $system_prompt,
            'messages'   => [
                [
                    'role'    => 'user',
                    'content' => $prompt,
                ],
            ],
        ];

        $response = wp_remote_post( $this->endpoint, [
            'timeout' => 30,
            'headers' => [
                'x-api-key'         => $this->api_key,
                'anthropic-version' => $this->api_version,
                'Content-Type'      => 'application/json',
            ],
            'body'    => wp_json_encode( $body ),
        ] );

        if ( is_wp_error( $response ) ) {
            throw new ProviderException(
                $response->get_error_message(),
                'anthropic',
                'request_failed'
            );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status_code !== 200 ) {
            $error_message = $body['error']['message'] ?? __( 'Unknown Anthropic error', 'querymind' );
            $error_type = $body['error']['type'] ?? (string) $status_code;

            throw new ProviderException(
                $error_message,
                'anthropic',
                $error_type,
                $status_code
            );
        }

        if ( ! isset( $body['content'][0]['text'] ) ) {
            throw new ProviderException(
                __( 'Invalid response from Anthropic', 'querymind' ),
                'anthropic',
                'invalid_response'
            );
        }

        return $body['content'][0]['text'];
    }

    /**
     * Estimate token count for text.
     *
     * @param string $text Text to count.
     * @return int
     */
    public function get_token_count( string $text ): int {
        // Claude uses a similar tokenization to GPT
        // Approximate: 1 token ≈ 4 characters for English text
        return (int) ceil( strlen( $text ) / 4 );
    }

    /**
     * Get available models.
     *
     * @return array
     */
    public function get_available_models(): array {
        return [
            'claude-3-5-sonnet-20241022' => [
                'name'        => 'Claude 3.5 Sonnet',
                'description' => 'Best balance of intelligence and speed',
                'cost'        => 'Medium',
            ],
            'claude-3-opus-20240229'     => [
                'name'        => 'Claude 3 Opus',
                'description' => 'Most powerful Claude model',
                'cost'        => 'High',
            ],
            'claude-3-haiku-20240307'    => [
                'name'        => 'Claude 3 Haiku',
                'description' => 'Fastest and most compact',
                'cost'        => 'Low',
            ],
        ];
    }

    /**
     * Set API key.
     *
     * @param string $api_key API key.
     */
    public function set_api_key( string $api_key ): void {
        $this->api_key = $api_key;
    }

    /**
     * Set model.
     *
     * @param string $model Model name.
     */
    public function set_model( string $model ): void {
        $this->model = $model;
    }
}
