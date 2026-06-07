<?php

declare(strict_types=1);

namespace Rabbanist\AdminDashboard\Console\Commands;

use Illuminate\Console\Command;

class PublishCommand extends Command
{
    protected $signature = 'admin-dashboard:publish
                            {--tag= : The asset group to publish (config, views, migrations, assets)}
                            {--force : Overwrite existing published files}';

    protected $description = 'Publish Admin Dashboard assets selectively';

    /**
     * Valid publish tags.
     *
     * @var array<string, string>
     */
    protected array $tags = [
        'config'     => 'admin-dashboard-config',
        'views'      => 'admin-dashboard-views',
        'migrations' => 'admin-dashboard-migrations',
        'assets'     => 'admin-dashboard-assets',
    ];

    public function handle(): int
    {
        $requestedTag = $this->option('tag');

        if ($requestedTag && ! isset($this->tags[$requestedTag])) {
            $this->components->error("Unknown tag: {$requestedTag}");
            $this->components->bulletList(array_keys($this->tags));

            return self::FAILURE;
        }

        $tagsToPublish = $requestedTag
            ? [$requestedTag => $this->tags[$requestedTag]]
            : $this->tags;

        foreach ($tagsToPublish as $label => $tag) {
            $this->callSilently('vendor:publish', [
                '--tag'   => $tag,
                '--force' => $this->option('force'),
            ]);
            $this->components->task("Published: {$label}");
        }

        $this->newLine();
        $this->components->info('Done.');

        return self::SUCCESS;
    }
}
