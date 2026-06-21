<?php

namespace JeffersonGoncalves\GitHubContributions\Tests;

use JeffersonGoncalves\GitHubContributions\GitHubContributionsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            GitHubContributionsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('github-contributions.token', 'fake-token');
        $app['config']->set('github-contributions.user_agent', 'laravel-github-contributions');
        $app['config']->set('github-contributions.timeout', 8);
        $app['config']->set('github-contributions.cache.ttl', 0);
        $app['config']->set('services.github.token', null);
    }
}
