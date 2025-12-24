<?php
/**
 * OpenAI Provider
 *
 * @package QueryMind
 */

namespace QueryMind\AI\Providers;

use QueryMind\AI\ProviderInterface;
use QueryMind\AI\ProviderException;

/**
 * OpenAI API provider implementation.
 */
class OpenAIProvider implements ProviderInterface {

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
    private string $endpoint = 'https://api.openai.com/v1/chat/completions';

    /**
     * Constructor.
     */
    public function __construct() {
        $this->api_key = get_option( 'querymind_openai_key', '' );
        $this->model = get_option( 'querymind_ai_model', 'gpt-4o-mini' );
    }

    /**
     * Get provider name.
     *
     * @return string
     */
    public function get_name(): string {
        return 'openai';
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
                __( 'OpenAI API key not configured', 'querymind' ),
                'openai',
                'not_configured'
            );
        }

        $model = $options['model'] ?? $this->model;
        $temperature = $options['temperature'] ?? 0.1; // Low for consistent SQL
        $max_tokens = $options['max_tokens'] ?? 1000;

        $body = [
            'model'       => $model,
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' => 'You are a SQL expert. Generate safe, read-only MySQL queries. Always respond with valid JSON.',
                ],
                [
                    'role'    => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => $temperature,
            'max_tokens'  => $max_tokens,
        ];

        // Add JSON response format for compatible models
        if ( in_array( $model, [ 'gpt-4o', 'gpt-4o-mini', 'gpt-4-turbo', 'gpt-4-turbo-preview' ], true ) ) {
            $body['response_format'] = [ 'type' => 'json_object' ];
        }

        $response = wp_remote_post( $this->endpoint, [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode( $body ),
        ] );

        if ( is_wp_error( $response ) ) {
            throw new ProviderException(
                sanitize_text_field( $response->get_error_message() ),
                'openai',
                'request_failed'
            );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status_code !== 200 ) {
            $error_message = isset( $body['error']['message'] )
                ? sanitize_text_field( $body['error']['message'] )
                : esc_html__( 'Unknown OpenAI error', 'querymind' );
            $error_code = isset( $body['error']['code'] )
                ? sanitize_text_field( $body['error']['code'] )
                : (string) intval( $status_code );

            throw new ProviderException(
                $error_message,
                'openai',
                $error_code,
                intval( $status_code )
            );
        }

        if ( ! isset( $body['choices'][0]['message']['content'] ) ) {
            throw new ProviderException(
                __( 'Invalid response from OpenAI', 'querymind' ),
                'openai',
                'invalid_response'
            );
        }

        return $body['choices'][0]['message']['content'];
    }

    /**
     * Estimate token count for text.
     *
     * @param string $text Text to count.
     * @return int
     */
    public function get_token_count( string $text ): int {
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
            'gpt-4o'        => [
                'name'        => 'GPT-4o',
                'description' => 'Most capable model, best for complex queries',
                'cost'        => 'High',
            ],
            'gpt-4o-mini'   => [
                'name'        => 'GPT-4o Mini',
                'description' => 'Fast and efficient, good for most queries',
                'cost'        => 'Low',
            ],
            'gpt-4-turbo'   => [
                'name'        => 'GPT-4 Turbo',
                'description' => 'Powerful with large context window',
                'cost'        => 'High',
            ],
            'gpt-3.5-turbo' => [
                'name'        => 'GPT-3.5 Turbo',
                'description' => 'Legacy model, fast but less capable',
                'cost'        => 'Very Low',
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
