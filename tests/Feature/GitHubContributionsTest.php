<?php

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
