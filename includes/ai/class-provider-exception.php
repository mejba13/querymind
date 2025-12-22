<?php
/**
 * AI Provider Exception
 *
 * @package QueryMind
 */

namespace QueryMind\AI;

/**
 * Exception for AI provider errors.
 */
class ProviderException extends \Exception {

    /**
     * Provider name.
     *
     * @var string
     */
    private string $provider;

    /**
     * Error code from provider.
     *
     * @var string
     */
    private string $provider_code;

    /**
     * Constructor.
     *
     * @param string          $message       Error message.
     * @param string          $provider      Provider name.
     * @param string          $provider_code Provider error code.
     * @param int             $code          Exception code.
     * @param \Throwable|null $previous      Previous exception.
     */
    public function __construct(
        string $message,
        string $provider = '',
        string $provider_code = '',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct( $message, $code, $previous );
        $this->provider = $provider;
        $this->provider_code = $provider_code;
    }

    /**
     * Get provider name.
     *
     * @return string
     */
    public function get_provider(): string {
        return $this->provider;
    }

    /**
     * Get provider error code.
     *
     * @return string
     */
    public function get_provider_code(): string {
        return $this->provider_code;
    }

    /**
     * Check if error is rate limit related.
     *
     * @return bool
     */
    public function is_rate_limit(): bool {
        return in_array( $this->provider_code, [ 'rate_limit_exceeded', '429', 'overloaded' ], true );
    }

    /**
     * Check if error is authentication related.
     *
     * @return bool
     */
    public function is_auth_error(): bool {
        return in_array( $this->provider_code, [ 'invalid_api_key', '401', 'unauthorized' ], true );
    }
}
