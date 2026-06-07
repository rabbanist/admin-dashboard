<?php

declare(strict_types=1);

namespace Yourvendor\AdminDashboard\Exceptions;

use RuntimeException;

class AdminDashboardException extends \RuntimeException implements \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface
{
    protected int $statusCode;
    protected array $headers = [];

    public function __construct(string $message = '', int $statusCode = 500, ?\Throwable $previous = null, array $headers = [])
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        parent::__construct($message, $statusCode, $previous);
    }

    /**
     * Create an exception for unauthorized access attempts.
     */
    public static function unauthorized(string $message = 'Unauthorized access to admin dashboard.'): static
    {
        return new static($message, 403);
    }

    /**
     * Create an exception for invalid configuration.
     */
    public static function invalidConfiguration(string $key, string $reason = ''): static
    {
        $message = "Invalid admin-dashboard configuration for [{$key}].";

        if ($reason !== '') {
            $message .= " {$reason}";
        }

        return new static($message, 500);
    }

    /**
     * Create an exception for a missing feature dependency.
     */
    public static function featureNotEnabled(string $feature): static
    {
        return new static(
            "The [{$feature}] feature is not enabled. Enable it in config/admin-dashboard.php.",
            400,
        );
    }

    /**
     * Create an exception for upload failures.
     */
    public static function uploadFailed(string $reason = 'Unknown error'): static
    {
        return new static("File upload failed: {$reason}", 422);
    }

    /**
     * Get the HTTP status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get response headers.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
