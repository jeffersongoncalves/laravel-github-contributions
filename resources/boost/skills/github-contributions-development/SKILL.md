---
name: github-contributions-development
description: Development guide for the Laravel GitHub Contributions package - fetches the GitHub contribution calendar for a login as a flat 0-4 heatmap-cell array
---

## When to use this skill

- Changing how the GitHub contribution calendar is fetched or shaped
- Adjusting the `contributionLevel` enum → `0-4` mapping
- Adding configuration (token source, user agent, timeout)
- Writing tests for GitHub GraphQL API interactions
- Understanding the column-major cell ordering

## Setup

### Requirements

- PHP 8.2+
- Laravel 11, 12 or 13
- GitHub Personal Access Token (scope: `read:user`)
- spatie/laravel-package-tools ^1.14.0

### Installation

```bash
composer require jeffersongoncalves/laravel-github-contributions
```

### Environment Variables

```env
GITHUB_TOKEN=ghp_your_personal_access_token
GITHUB_CONTRIBUTIONS_USER_AGENT=laravel-github-contributions
GITHUB_CONTRIBUTIONS_TIMEOUT=8
```

### Publish Config

```bash
php artisan vendor:publish --tag=laravel-github-contributions-config
```

## Architecture

### Namespace Structure

```
JeffersonGoncalves\GitHubContributions\
    GitHubContributionsServiceProvider  # Registers the config file
    GitHubContributions                 # Static fetch(string $login): array
```

### Service Provider Registration

The provider only wires the config file via spatie/laravel-package-tools:

```php
public function configurePackage(Package $package): void
{
    $package
        ->name('laravel-github-contributions')
        ->hasConfigFile();
}
```

`shortName()` strips the `laravel-` prefix, so the published config and config key
are both `github-contributions` (`config/github-contributions.php`).

## Features

### Fetching the Contribution Calendar

```php
use JeffersonGoncalves\GitHubContributions\GitHubContributions;

$data = GitHubContributions::fetch('jeffersongoncalves');
// ['cells' => list<int 0-4>, 'total' => int]
```

- `cells`: contribution levels ordered column-major (week-by-week, top→bottom),
  always 7 entries per week — missing weekdays default to `0`.
- `total`: `contributionCalendar.totalContributions`.

### Token Resolution

```php
$token = config('github-contributions.token') ?: config('services.github.token');
```

`GITHUB_TOKEN` is the primary source; `services.github.token` is the fallback so
the package plays nicely with apps that already configure the GitHub service.

### Level Mapping

```php
// GitHub contributionLevel enum -> int
'FIRST_QUARTILE'  => 1,
'SECOND_QUARTILE' => 2,
'THIRD_QUARTILE'  => 3,
'FOURTH_QUARTILE' => 4,
// NONE / anything else => 0
```

### Graceful Failure

`fetch()` returns `['cells' => [], 'total' => 0]` when:

- no token is configured (no HTTP request is made),
- the response is not successful,
- the calendar payload is missing.

## Configuration

```php
// config/github-contributions.php
return [
    'token' => env('GITHUB_TOKEN'),
    'user_agent' => env('GITHUB_CONTRIBUTIONS_USER_AGENT', 'laravel-github-contributions'),
    'timeout' => (int) env('GITHUB_CONTRIBUTIONS_TIMEOUT', 8),
];
```

## Testing Patterns

### Mocking the GraphQL Endpoint

```php
use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\GitHubContributions\GitHubContributions;

it('maps contribution levels to cells', function () {
    Http::fake([
        'api.github.com/graphql' => Http::response([
            'data' => ['user' => ['contributionsCollection' => ['contributionCalendar' => [
                'totalContributions' => 28,
                'weeks' => [[
                    'contributionDays' => [
                        ['contributionCount' => 0, 'contributionLevel' => 'NONE', 'weekday' => 0],
                        ['contributionCount' => 1, 'contributionLevel' => 'FIRST_QUARTILE', 'weekday' => 1],
                    ],
                ]],
            ]]]],
        ]),
    ]);

    expect(GitHubContributions::fetch('testuser')['cells'])
        ->toBe([0, 1, 0, 0, 0, 0, 0]);
});
```

### Testing the No-Token Path

```php
it('returns empty when no token is configured', function () {
    config()->set('github-contributions.token', null);
    config()->set('services.github.token', null);

    Http::fake();

    expect(GitHubContributions::fetch('testuser'))->toBe(['cells' => [], 'total' => 0]);
    Http::assertNothingSent();
});
```

### Dev Commands

```bash
# Run tests
vendor/bin/pest

# Run static analysis
vendor/bin/phpstan analyse

# Format code
vendor/bin/pint
```
