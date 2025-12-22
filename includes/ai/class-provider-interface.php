<?php
/**
 * AI Provider Interface
 *
 * @package QueryMind
 */

namespace QueryMind\AI;

/**
 * Interface for AI providers.
 */
interface ProviderInterface {

    /**
     * Get provider name.
     *
     * @return string
     */
    public function get_name(): string;

    /**
     * Check if provider is configured.
     *
     * @return bool
     */
    public function is_configured(): bool;

    /**
     * Generate completion from prompt.
     *
     * @param string $prompt The prompt to complete.
     * @param array  $options Optional provider options.
     * @return string
     * @throws ProviderException If completion fails.
     */
    public function complete( string $prompt, array $options = [] ): string;

    /**
     * Estimate token count for text.
     *
     * @param string $text Text to count.
     * @return int
     */
    public function get_token_count( string $text ): int;

    /**
     * Get available models.
     *
     * @return array
     */
    public function get_available_models(): array;
}
