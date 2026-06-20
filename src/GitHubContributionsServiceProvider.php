<?php

namespace JeffersonGoncalves\GitHubContributions;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class GitHubContributionsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-github-contributions')
            ->hasConfigFile();
    }
}
