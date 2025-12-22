<?php
/**
 * Sanitizer Utility
 *
 * Sanitizes user input to prevent prompt injection and other attacks.
 *
 * @package QueryMind
 */

namespace QueryMind\Utils;

/**
 * Sanitizer class.
 */
class Sanitizer {

    /**
     * Dangerous patterns that could be prompt injection.
     */
    private const INJECTION_PATTERNS = [
        '/ignore\s+(previous|above|all)\s+instructions/i',
        '/disregard\s+(previous|above|all)/i',
        '/forget\s+(everything|all|previous)/i',
        '/new\s+instructions?:/i',
        '/system\s*:/i',
        '/assistant\s*:/i',
        '/\bDROP\b/i',
        '/\bDELETE\b/i',
        '/\bTRUNCATE\b/i',
        '/\bUPDATE\b.*\bSET\b/i',
        '/\bINSERT\b.*\bINTO\b/i',
        '/;\s*(DROP|DELETE|TRUNCATE|UPDATE|INSERT)/i',
        '/execute\s+immediately/i',
        '/override\s+safety/i',
    ];

    /**
     * Maximum question length.
     */
    private const MAX_LENGTH = 1000;

    /**
     * Sanitize user question.
     *
     * @param string $question User's question.
     * @return SanitizeResult
     */
    public function sanitize_question( string $question ): SanitizeResult {
        $warnings = [];

        // Check length
        if ( strlen( $question ) > self::MAX_LENGTH ) {
            $question = substr( $question, 0, self::MAX_LENGTH );
            $warnings[] = sprintf(
                /* translators: %d: max length */
                __( 'Question truncated to %d characters', 'querymind' ),
                self::MAX_LENGTH
            );
        }

        // Check for empty question
        if ( empty( trim( $question ) ) ) {
            return new SanitizeResult(
                success: false,
                question: '',
                error: __( 'Question cannot be empty', 'querymind' )
            );
        }

        // Check for injection patterns
        foreach ( self::INJECTION_PATTERNS as $pattern ) {
            if ( preg_match( $pattern, $question ) ) {
                return new SanitizeResult(
                    success: false,
                    question: '',
                    error: __( 'Potentially malicious input detected', 'querymind' ),
                    blocked: true
                );
            }
        }

        // Remove any HTML/script tags
        $question = wp_strip_all_tags( $question );

        // Normalize whitespace
        $question = preg_replace( '/\s+/', ' ', trim( $question ) );

        // Remove control characters
        $question = preg_replace( '/[\x00-\x1F\x7F]/u', '', $question );

        return new SanitizeResult(
            success: true,
            question: $question,
            warnings: $warnings
        );
    }

    /**
     * Sanitize SQL for display (not execution).
     *
     * @param string $sql SQL query.
     * @return string
     */
    public function sanitize_sql_for_display( string $sql ): string {
        return esc_html( $sql );
    }

    /**
     * Mask sensitive data patterns in results.
     *
     * @param array $data Result data.
     * @return array
     */
    public function mask_sensitive_data( array $data ): array {
        $sensitive_patterns = [
            // Credit card numbers
            '/\b\d{4}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{4}\b/' => '****-****-****-****',
            // SSN
            '/\b\d{3}-\d{2}-\d{4}\b/' => '***-**-****',
            // Email (partial mask)
            '/([a-zA-Z0-9._%+-]+)@([a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/' => function ( $matches ) {
                $local = $matches[1];
                $domain = $matches[2];
                if ( strlen( $local ) > 2 ) {
                    $local = substr( $local, 0, 2 ) . '***';
                }
                return $local . '@' . $domain;
            },
        ];

        $masked_data = [];

        foreach ( $data as $row ) {
            $masked_row = [];
            foreach ( $row as $key => $value ) {
                if ( is_string( $value ) ) {
                    foreach ( $sensitive_patterns as $pattern => $replacement ) {
                        if ( is_callable( $replacement ) ) {
                            $value = preg_replace_callback( $pattern, $replacement, $value );
                        } else {
                            $value = preg_replace( $pattern, $replacement, $value );
                        }
                    }
                }
                $masked_row[ $key ] = $value;
            }
            $masked_data[] = $masked_row;
        }

        return $masked_data;
    }
}

/**
 * Sanitize result object.
 */
class SanitizeResult {

    /**
     * Whether sanitization was successful.
     *
     * @var bool
     */
    public bool $success;

    /**
     * Sanitized question.
     *
     * @var string
     */
    public string $question;

    /**
     * Error message if failed.
     *
     * @var string
     */
    public string $error;

    /**
     * Warnings during sanitization.
     *
     * @var array
     */
    public array $warnings;

    /**
     * Whether input was blocked.
     *
     * @var bool
     */
    public bool $blocked;

    /**
     * Constructor.
     *
     * @param bool   $success  Whether successful.
     * @param string $question Sanitized question.
     * @param string $error    Error message.
     * @param array  $warnings Warnings.
     * @param bool   $blocked  Whether blocked.
     */
    public function __construct(
        bool $success,
        string $question,
        string $error = '',
        array $warnings = [],
        bool $blocked = false
    ) {
        $this->success = $success;
        $this->question = $question;
        $this->error = $error;
        $this->warnings = $warnings;
        $this->blocked = $blocked;
    }
}
