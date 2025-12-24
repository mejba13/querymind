<?php
/**
 * AI Router
 *
 * Routes AI requests to appropriate providers with fallback support.
 *
 * @package QueryMind
 */

namespace QueryMind\AI;

use QueryMind\AI\Providers\OpenAIProvider;
use QueryMind\AI\Providers\AnthropicProvider;

/**
 * AI Router class.
 *
 * Manages AI providers and routes requests with fallback support.
 */
class AIRouter {

    /**
     * Registered providers.
     *
     * @var array<string, ProviderInterface>
     */
    private array $providers = [];

    /**
     * Default provider.
     *
     * @var string
     */
    private string $default_provider;

    /**
     * Prompt builder instance.
     *
     * @var PromptBuilder
     */
    private PromptBuilder $prompt_builder;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->register_providers();
        $this->default_provider = get_option( 'querymind_ai_provider', 'openai' );
        $this->prompt_builder = new PromptBuilder();
    }

    /**
     * Register available providers.
     */
    private function register_providers(): void {
        $this->providers = [
            'openai'    => new OpenAIProvider(),
            'anthropic' => new AnthropicProvider(),
        ];
    }

    /**
     * Generate SQL from natural language question.
     *
     * @param string $question User's question.
     * @param array  $schema   Database schema.
     * @param array  $options  Additional options.
     * @return SQLResult
     * @throws ProviderException If all providers fail.
     */
    public function generate_sql( string $question, array $schema, array $options = [] ): SQLResult {
        $provider_name = $options['provider'] ?? $this->default_provider;

        // Build the prompt
        $prompt = $this->prompt_builder->build( $question, $schema, $options );

        // Try primary provider
        try {
            $result = $this->call_provider( $provider_name, $prompt, $options );
            return $this->parse_result( $result, $provider_name );
        } catch ( ProviderException $e ) {
            // Log the error
            $this->log_error( $e, $provider_name );

            // Try fallback if primary fails
            if ( ! $e->is_auth_error() ) {
                return $this->try_fallback( $prompt, $provider_name, $options );
            }

            throw $e;
        }
    }

    /**
     * Call a specific provider.
     *
     * @param string $provider_name Provider name.
     * @param string $prompt        Prompt to send.
     * @param array  $options       Provider options.
     * @return string
     * @throws ProviderException If provider fails.
     */
    private function call_provider( string $provider_name, string $prompt, array $options = [] ): string {
        $safe_provider_name = sanitize_text_field( $provider_name );

        if ( ! isset( $this->providers[ $provider_name ] ) ) {
            throw new ProviderException(
                sprintf(
                    /* translators: %s: provider name */
                    esc_html__( 'Unknown AI provider: %s', 'querymind' ),
                    esc_html( $safe_provider_name )
                ),
                $safe_provider_name,
                'unknown_provider'
            );
        }

        $provider = $this->providers[ $provider_name ];

        if ( ! $provider->is_configured() ) {
            throw new ProviderException(
                sprintf(
                    /* translators: %s: provider name */
                    esc_html__( '%s is not configured. Please add your API key in settings.', 'querymind' ),
                    esc_html( ucfirst( $safe_provider_name ) )
                ),
                $safe_provider_name,
                'not_configured'
            );
        }

        return $provider->complete( $prompt, $options );
    }

    /**
     * Try fallback providers.
     *
     * @param string $prompt           Prompt to send.
     * @param string $failed_provider  Provider that failed.
     * @param array  $options          Provider options.
     * @return SQLResult
     * @throws ProviderException If all providers fail.
     */
    private function try_fallback( string $prompt, string $failed_provider, array $options = [] ): SQLResult {
        $fallback_order = [ 'openai', 'anthropic' ];

        foreach ( $fallback_order as $provider_name ) {
            if ( $provider_name === $failed_provider ) {
                continue;
            }

            if ( ! $this->providers[ $provider_name ]->is_configured() ) {
                continue;
            }

            try {
                $result = $this->call_provider( $provider_name, $prompt, $options );
                return $this->parse_result( $result, $provider_name );
            } catch ( ProviderException $e ) {
                $this->log_error( $e, $provider_name );
                continue;
            }
        }

        throw new ProviderException(
            __( 'All AI providers failed. Please try again later.', 'querymind' ),
            'all',
            'all_failed'
        );
    }

    /**
     * Parse AI response into SQLResult.
     *
     * @param string $response      AI response.
     * @param string $provider_name Provider used.
     * @return SQLResult
     * @throws ProviderException If response is invalid.
     */
    private function parse_result( string $response, string $provider_name ): SQLResult {
        // Clean the response - remove markdown code blocks if present
        $response = preg_replace( '/```json\s*/', '', $response );
        $response = preg_replace( '/```\s*$/', '', $response );
        $response = trim( $response );

        $data = json_decode( $response, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            // Try to extract SQL directly if JSON parsing fails
            if ( preg_match( '/SELECT\s+.+/is', $response, $matches ) ) {
                return new SQLResult(
                    sql: trim( $matches[0] ),
                    explanation: __( 'Query extracted from response', 'querymind' ),
                    columns: [],
                    chart_type: 'table',
                    provider: $provider_name
                );
            }

            throw new ProviderException(
                __( 'Invalid JSON response from AI provider', 'querymind' ),
                $provider_name,
                'invalid_json'
            );
        }

        if ( empty( $data['sql'] ) ) {
            throw new ProviderException(
                __( 'No SQL query in AI response', 'querymind' ),
                $provider_name,
                'no_sql'
            );
        }

        return new SQLResult(
            sql: $data['sql'],
            explanation: $data['explanation'] ?? '',
            columns: $data['columns'] ?? [],
            chart_type: $data['chartType'] ?? 'table',
            provider: $provider_name
        );
    }

    /**
     * Log provider error.
     *
     * @param ProviderException $e             Exception.
     * @param string            $provider_name Provider name.
     */
    private function log_error( ProviderException $e, string $provider_name ): void {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( sprintf(
                'QueryMind AI Error [%s]: %s (Code: %s)',
                $provider_name,
                $e->getMessage(),
                $e->get_provider_code()
            ) );
        }
    }

    /**
     * Get available providers.
     *
     * @return array
     */
    public function get_available_providers(): array {
        $available = [];

        foreach ( $this->providers as $name => $provider ) {
            $available[ $name ] = [
                'name'       => ucfirst( $name ),
                'configured' => $provider->is_configured(),
                'models'     => $provider->get_available_models(),
            ];
        }

        return $available;
    }

    /**
     * Get a specific provider.
     *
     * @param string $name Provider name.
     * @return ProviderInterface|null
     */
    public function get_provider( string $name ): ?ProviderInterface {
        return $this->providers[ $name ] ?? null;
    }

    /**
     * Check if any provider is configured.
     *
     * @return bool
     */
    public function has_configured_provider(): bool {
        foreach ( $this->providers as $provider ) {
            if ( $provider->is_configured() ) {
                return true;
            }
        }
        return false;
    }
}

/**
 * SQL Result object.
 */
class SQLResult {

    /**
     * Generated SQL query.
     *
     * @var string
     */
    public string $sql;

    /**
     * Explanation of the query.
     *
     * @var string
     */
    public string $explanation;

    /**
     * Expected columns.
     *
     * @var array
     */
    public array $columns;

    /**
     * Suggested chart type.
     *
     * @var string
     */
    public string $chart_type;

    /**
     * Provider used.
     *
     * @var string
     */
    public string $provider;

    /**
     * Constructor.
     *
     * @param string $sql         SQL query.
     * @param string $explanation Query explanation.
     * @param array  $columns     Expected columns.
     * @param string $chart_type  Suggested chart type.
     * @param string $provider    Provider used.
     */
    public function __construct(
        string $sql,
        string $explanation = '',
        array $columns = [],
        string $chart_type = 'table',
        string $provider = ''
    ) {
        $this->sql = $sql;
        $this->explanation = $explanation;
        $this->columns = $columns;
        $this->chart_type = $chart_type;
        $this->provider = $provider;
    }

    /**
     * Convert to array.
     *
     * @return array
     */
    public function to_array(): array {
        return [
            'sql'         => $this->sql,
            'explanation' => $this->explanation,
            'columns'     => $this->columns,
            'chartType'   => $this->chart_type,
            'provider'    => $this->provider,
        ];
    }
}
