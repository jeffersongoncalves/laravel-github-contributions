<div class="filament-hidden">

![Laravel GitHub Contributions](https://raw.githubusercontent.com/jeffersongoncalves/laravel-github-contributions/master/art/jeffersongoncalves-laravel-github-contributions.png)

</div>

# Laravel GitHub Contributions

[![Tests](https://github.com/jeffersongoncalves/laravel-github-contributions/actions/workflows/run-tests.yml/badge.svg)](https://github.com/jeffersongoncalves/laravel-github-contributions/actions/workflows/run-tests.yml)
[![PHPStan](https://github.com/jeffersongoncalves/laravel-github-contributions/actions/workflows/phpstan.yml/badge.svg)](https://github.com/jeffersongoncalves/laravel-github-contributions/actions/workflows/phpstan.yml)
[![Code Style](https://github.com/jeffersongoncalves/laravel-github-contributions/actions/workflows/fix-php-code-style-issues.yml/badge.svg)](https://github.com/jeffersongoncalves/laravel-github-contributions/actions/workflows/fix-php-code-style-issues.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/jeffersongoncalves/laravel-github-contributions.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-github-contributions)
[![Total Downloads](https://img.shields.io/packagist/dt/jeffersongoncalves/laravel-github-contributions.svg?style=flat-square)](https://packagist.org/packages/jeffersongoncalves/laravel-github-contributions)
[![License](https://img.shields.io/packagist/l/jeffersongoncalves/laravel-github-contributions.svg?style=flat-square)](LICENSE.md)

Fetch the GitHub contribution calendar for any login as a **flat `0-4` heatmap-cell array** — ready to render your own contribution heatmap. The cells are ordered the same way GitHub renders the calendar: column-major (week-by-week, top→bottom), seven cells per week.

> This package returns raw heatmap-cell data. If you instead want ready-made profile SVG cards (stats, top languages, streaks, trophies), use [jeffersongoncalves/laravel-github-stats](https://github.com/jeffersongoncalves/laravel-github-stats).

## Installation

```bash
composer require jeffersongoncalves/laravel-github-contributions
```

Publish the config file (optional):

```bash
php artisan vendor:publish --tag="laravel-github-contributions-config"
```

## Configuration

Add to your `.env`:

```env
GITHUB_TOKEN=ghp_xxxxxxxxxxxxxxxxxxxx
```

Create a [Personal Access Token](https://github.com/settings/tokens/new) with the `read:user` scope.

### Config Options

```php
// config/github-contributions.php
return [
    // Falls back to config('services.github.token') when empty.
    'token' => env('GITHUB_TOKEN'),

    'user_agent' => env('GITHUB_CONTRIBUTIONS_USER_AGENT', 'laravel-github-contributions'),

    'timeout' => (int) env('GITHUB_CONTRIBUTIONS_TIMEOUT', 8),
];
```

## Usage

```php
use JeffersonGoncalves\GitHubContributions\GitHubContributions;

$data = GitHubContributions::fetch('jeffersongoncalves');

// [
//     'cells' => [0, 1, 0, 2, 4, 3, 0, ...], // list<int 0-4>, 7 per week, column-major
//     'total' => 1234,                        // total contributions in the calendar window
// ]
```

Each value in `cells` is a contribution level mapped from GitHub's `contributionLevel` enum:

| GitHub level | Cell |
|:-------------|:----:|
| `NONE` | `0` |
| `FIRST_QUARTILE` | `1` |
| `SECOND_QUARTILE` | `2` |
| `THIRD_QUARTILE` | `3` |
| `FOURTH_QUARTILE` | `4` |

When no token is configured (neither `github-contributions.token` nor `services.github.token`) or the GitHub API request fails, `fetch()` returns `['cells' => [], 'total' => 0]`.

## Testing

```bash
composer test
```

## Static Analysis

```bash
composer analyse
```

## Code Formatting

```bash
composer format
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
