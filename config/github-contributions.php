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
];
