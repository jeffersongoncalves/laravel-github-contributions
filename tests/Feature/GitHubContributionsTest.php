<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use JeffersonGoncalves\GitHubContributions\GitHubContributions;

function fakeCalendarResponse(array $weeks = [], int $total = 0): array
{
    return [
        'data' => [
            'user' => [
                'contributionsCollection' => [
                    'contributionCalendar' => [
                        'totalContributions' => $total,
                        'weeks' => $weeks,
                    ],
                ],
            ],
        ],
    ];
}

it('maps contribution levels to 0-4 cells', function () {
    Http::fake([
        'api.github.com/graphql' => Http::response(fakeCalendarResponse([
            [
                'contributionDays' => [
                    ['contributionCount' => 0, 'contributionLevel' => 'NONE', 'weekday' => 0],
                    ['contributionCount' => 1, 'contributionLevel' => 'FIRST_QUARTILE', 'weekday' => 1],
                    ['contributionCount' => 4, 'contributionLevel' => 'SECOND_QUARTILE', 'weekday' => 2],
                    ['contributionCount' => 8, 'contributionLevel' => 'THIRD_QUARTILE', 'weekday' => 3],
                    ['contributionCount' => 15, 'contributionLevel' => 'FOURTH_QUARTILE', 'weekday' => 4],
                ],
            ],
        ], total: 28)),
    ]);

    $result = GitHubContributions::fetch('testuser');

    // weekday 5 and 6 are absent, so they default to 0 (column-major, 7 per week)
    expect($result['cells'])->toBe([0, 1, 2, 3, 4, 0, 0]);
});

it('returns the correct total', function () {
    Http::fake([
        'api.github.com/graphql' => Http::response(fakeCalendarResponse([
            [
                'contributionDays' => [
                    ['contributionCount' => 3, 'contributionLevel' => 'FIRST_QUARTILE', 'weekday' => 0],
                ],
            ],
        ], total: 580)),
    ]);

    $result = GitHubContributions::fetch('testuser');

    expect($result['total'])->toBe(580);
});

it('returns empty cells and zero total when no token is configured', function () {
    config()->set('github-contributions.token', null);
    config()->set('services.github.token', null);

    Http::fake();

    $result = GitHubContributions::fetch('testuser');

    expect($result)->toBe(['cells' => [], 'total' => 0]);
    Http::assertNothingSent();
});

it('returns empty cells and zero total on a failed response', function () {
    Http::fake([
        'api.github.com/graphql' => Http::response('Server Error', 500),
    ]);

    $result = GitHubContributions::fetch('testuser');

    expect($result)->toBe(['cells' => [], 'total' => 0]);
});

it('returns empty cells and zero total when the connection fails', function () {
    Http::fake(function () {
        throw new ConnectionException('cURL error 28: Connection timed out');
    });

    $result = GitHubContributions::fetch('testuser');

    expect($result)->toBe(['cells' => [], 'total' => 0]);
});

it('returns an empty calendar for an invalid login (data.user is null)', function () {
    Http::fake([
        'api.github.com/graphql' => Http::response([
            'data' => ['user' => null],
        ]),
    ]);

    $result = GitHubContributions::fetch('does-not-exist');

    expect($result)->toBe(['cells' => [], 'total' => 0]);
});

it('sends the bearer token and the login graphql variable', function () {
    config()->set('github-contributions.token', 'secret-token');

    Http::fake([
        'api.github.com/graphql' => Http::response(fakeCalendarResponse(total: 1)),
    ]);

    GitHubContributions::fetch('octocat');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.github.com/graphql'
            && $request->hasHeader('Authorization', 'Bearer secret-token')
            && $request['variables']['login'] === 'octocat';
    });
});

it('caches the response and only hits the api once', function () {
    config()->set('github-contributions.cache.ttl', 3600);
    Cache::flush();

    Http::fake([
        'api.github.com/graphql' => Http::response(fakeCalendarResponse([
            [
                'contributionDays' => [
                    ['contributionCount' => 3, 'contributionLevel' => 'FIRST_QUARTILE', 'weekday' => 0],
                ],
            ],
        ], total: 42)),
    ]);

    $first = GitHubContributions::fetch('cached-user');
    $second = GitHubContributions::fetch('cached-user');

    expect($first)->toBe($second)
        ->and($first['total'])->toBe(42);

    Http::assertSentCount(1);
});
