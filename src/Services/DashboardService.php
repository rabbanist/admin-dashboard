<?php

declare(strict_types=1);

namespace Yourvendor\AdminDashboard\Services;

use Yourvendor\AdminDashboard\Contracts\DashboardServiceInterface;

class DashboardService implements DashboardServiceInterface
{
    /**
     * @param  array<string, mixed>  $config  The resolved admin-dashboard config array.
     */
    public function __construct(
        protected readonly array $config,
    ) {}

    /**
     * {@inheritDoc}
     */
    public function title(): string
    {
        return $this->config['title'] ?? 'Admin Dashboard';
    }

    /**
     * {@inheritDoc}
     */
    public function featureEnabled(string $feature): bool
    {
        return (bool) ($this->config['features'][$feature] ?? false);
    }

    /**
     * {@inheritDoc}
     */
    public function perPage(): int
    {
        return (int) ($this->config['pagination']['per_page'] ?? 25);
    }

    /**
     * {@inheritDoc}
     */
    public function maxPerPage(): int
    {
        return (int) ($this->config['pagination']['max_per_page'] ?? 100);
    }

    /**
     * {@inheritDoc}
     */
    public function uploadDisk(): string
    {
        return $this->config['uploads']['disk'] ?? 'public';
    }

    /**
     * {@inheritDoc}
     */
    public function uploadPath(): string
    {
        return $this->config['uploads']['path'] ?? 'admin-uploads';
    }

    /**
     * {@inheritDoc}
     */
    public function config(): array
    {
        return $this->config;
    }
}
