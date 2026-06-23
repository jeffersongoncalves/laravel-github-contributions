<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use JeffersonGoncalves\GitHubContributions\GitHubContributions;

it('orders cells column-major across multiple weeks', function () {
    Http::fake([
        'api.github.com/graphql' => Http::response(fakeCalendarResponse([
            [
                'contributionDays' => [
                    ['contributionCount' => 1, 'contributionLevel' => 'FIRST_QUARTILE', 'weekday' => 0],
                    ['contributionCount' => 9, 'contributionLevel' => 'SECOND_QUARTILE', 'weekday' => 6],
                ],
            ],
            [
                'contributionDays' => [
                    ['contributionCount' => 5, 'contributionLevel' => 'THIRD_QUARTILE', 'weekday' => 1],
                ],
            ],
        ], total: 15)),
    ]);

    $result = GitHubContributions::fetch('testuser');

    // Week 1 first (7 cells), then week 2 (7 cells): column-major.
    expect($result['cells'])->toBe([1, 0, 0, 0, 0, 0, 2, 0, 3, 0, 0, 0, 0, 0]);
});

it('falls back to services.github.token when the package token is empty', function () {
    config()->set('github-contributions.token', null);
    config()->set('services.github.token', 'services-token');

    Http::fake([
        'api.github.com/graphql' => Http::response(fakeCalendarResponse(total: 1)),
    ]);

    GitHubContributions::fetch('octocat');

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer services-token'));
});

it('sends the configured User-Agent header', function () {
    config()->set('github-contributions.user_agent', 'my-custom-agent');

    Http::fake([
        'api.github.com/graphql' => Http::response(fakeCalendarResponse(total: 1)),
    ]);

    GitHubContributions::fetch('octocat');

    Http::assertSent(fn ($request) => $request->hasHeader('User-Agent', 'my-custom-agent'));
});

it('does not cache a failed response (no cache poisoning)', function () {
    config()->set('github-contributions.cache.ttl', 3600);
    Cache::flush();

    Http::fakeSequence('api.github.com/*')
        ->push('Server Error', 500)
        ->push(fakeCalendarResponse([
            [
                'contributionDays' => [
                    ['contributionCount' => 3, 'contributionLevel' => 'FIRST_QUARTILE', 'weekday' => 0],
                ],
            ],
        ], total: 42));

    $first = GitHubContributions::fetch('poison-user');
    expect($first)->toBe(['cells' => [], 'total' => 0]);

    $second = GitHubContributions::fetch('poison-user');
    expect($second['total'])->toBe(42)
        ->and($second['cells'])->not->toBe([]);

    Http::assertSentCount(2);
});

it('returns an empty calendar and logs when the GraphQL body contains errors', function () {
    Log::spy();

    Http::fake([
        'api.github.com/graphql' => Http::response([
            'errors' => [['message' => 'API rate limit exceeded']],
        ], 200),
    ]);

    $result = GitHubContributions::fetch('testuser');

    expect($result)->toBe(['cells' => [], 'total' => 0]);

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn (string $message) => str_contains($message, 'GraphQL returned errors'));
});

it('logs a warning when no token is configured', function () {
    config()->set('github-contributions.token', null);
    config()->set('services.github.token', null);

    Log::spy();
    Http::fake();

    GitHubContributions::fetch('testuser');

    Log::shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn (string $message) => str_contains($message, 'no API token configured'));
});
