<?php

declare(strict_types=1);

namespace Rabbanist\AdminDashboard\Contracts;

interface DashboardServiceInterface
{
    /**
     * Get the configured dashboard title.
     */
    public function title(): string;

    /**
     * Determine whether a given feature flag is enabled.
     */
    public function featureEnabled(string $feature): bool;

    /**
     * Get the configured pagination size.
     */
    public function perPage(): int;

    /**
     * Get the maximum allowed pagination size.
     */
    public function maxPerPage(): int;

    /**
     * Get the configured upload disk name.
     */
    public function uploadDisk(): string;

    /**
     * Get the configured upload path prefix.
     */
    public function uploadPath(): string;

    /**
     * Retrieve the full configuration array.
     */
    public function config(): array;
}
