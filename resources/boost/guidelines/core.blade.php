## Laravel GitHub Contributions

### Overview

A Laravel package that fetches the GitHub contribution calendar for a given login
via the GitHub GraphQL API and returns a flat `0-4` heatmap-cell array, ideal for
rendering your own contribution heatmap.

### Key Concepts

- **GitHubContributions**: Static service exposing `fetch(string $login): array` returning `{cells, total}`
- **Column-major cells**: 7 entries per week (top→bottom), missing weekdays default to `0`
- **Level mapping**: GitHub `contributionLevel` enum (NONE/FIRST/SECOND/THIRD/FOURTH_QUARTILE) → `0-4`
- **Token resolution**: `config('github-contributions.token')` with `config('services.github.token')` fallback

### Usage

@verbatim
<code-snippet name="fetch" lang="php">
use JeffersonGoncalves\GitHubContributions\GitHubContributions;

$data = GitHubContributions::fetch('jeffersongoncalves');
// ['cells' => [0, 1, 0, 2, 4, 3, 0, ...], 'total' => 1234]
</code-snippet>
@endverbatim

### Configuration

@verbatim
<code-snippet name="config-keys" lang="php">
// config/github-contributions.php
'token'      => env('GITHUB_TOKEN'), // falls back to config('services.github.token')
'user_agent' => env('GITHUB_CONTRIBUTIONS_USER_AGENT', 'laravel-github-contributions'),
'timeout'    => (int) env('GITHUB_CONTRIBUTIONS_TIMEOUT', 8),
</code-snippet>
@endverbatim

### Conventions

- `fetch()` returns `['cells' => [], 'total' => 0]` when no token is configured or the API call fails
- No HTTP request is made when there is no token
- Distinct from `jeffersongoncalves/laravel-github-stats`, which renders profile SVG cards/streaks
