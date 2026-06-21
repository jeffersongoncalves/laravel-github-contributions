<?php

return [
    /*
    |--------------------------------------------------------------------------
    | GitHub Personal Access Token
    |--------------------------------------------------------------------------
    |
    | Token used to authenticate against the GitHub GraphQL API. Falls back to
    | config('services.github.token') when this value is empty.
    |
    | Create at: https://github.com/settings/tokens/new
    | Required scopes: read:user
    |
    */
    'token' => env('GITHUB_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | User Agent
    |--------------------------------------------------------------------------
    |
    | The User-Agent header sent with every request to the GitHub API.
    |
    */
    'user_agent' => env('GITHUB_CONTRIBUTIONS_USER_AGENT', 'laravel-github-contributions'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout (in seconds)
    |--------------------------------------------------------------------------
    |
    | How long to wait for the GitHub GraphQL API before giving up.
    |
    */
    'timeout' => (int) env('GITHUB_CONTRIBUTIONS_TIMEOUT', 8),

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | The GitHub GraphQL API is rate limited to 5000 requests per hour. To
    | avoid a live call on every fetch(), responses are cached for "ttl"
    | seconds, keyed by login. Set "ttl" to 0 to disable caching entirely.
    |
    */
    'cache' => [
        'ttl' => (int) env('GITHUB_CONTRIBUTIONS_CACHE_TTL', 3600),
    ],
];
