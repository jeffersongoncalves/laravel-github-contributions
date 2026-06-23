<?php

namespace JeffersonGoncalves\GitHubContributions;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubContributions
{
    /**
     * Fetch the contribution calendar from the GitHub GraphQL API.
     *
     * Returns a flat list of contribution levels (0-4) ordered the same way
     * GitHub renders the calendar: column-major (week-by-week, top→bottom).
     * The result is ideal for rendering a contribution heatmap.
     *
     * @return array{cells: list<int>, total: int}
     */
    public static function fetch(string $login): array
    {
        $token = config('github-contributions.token') ?: config('services.github.token');

        if (! $token) {
            Log::warning('GitHub contributions: no API token configured; returning an empty calendar.', [
                'login' => $login,
            ]);

            return ['cells' => [], 'total' => 0];
        }

        $ttl = (int) config('github-contributions.cache.ttl', 3600);

        if ($ttl <= 0) {
            return self::request($login, $token);
        }

        $key = "github-contributions:{$login}";

        /** @var array{cells: list<int>, total: int}|null $cached */
        $cached = Cache::get($key);

        if ($cached !== null) {
            return $cached;
        }

        $result = self::request($login, $token);

        // Only cache a successful, non-empty calendar. Caching the empty failure
        // sentinel would poison the cache for the whole TTL window.
        if ($result['cells'] !== []) {
            Cache::put($key, $result, $ttl);
        }

        return $result;
    }

    /**
     * Perform the live GraphQL request and map the response to heatmap cells.
     *
     * Honours the package's "graceful failure" contract: any connection
     * problem (timeout, DNS failure, refused connection) or unsuccessful
     * response yields an empty calendar instead of throwing.
     *
     * @return array{cells: list<int>, total: int}
     */
    private static function request(string $login, string $token): array
    {
        $query = <<<'GQL'
        query($login: String!) {
          user(login: $login) {
            contributionsCollection {
              contributionCalendar {
                totalContributions
                weeks {
                  contributionDays {
                    contributionCount
                    contributionLevel
                    weekday
                  }
                }
              }
            }
          }
        }
        GQL;

        try {
            $response = Http::timeout((int) config('github-contributions.timeout', 8))
                ->withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'User-Agent' => config('github-contributions.user_agent', 'laravel-github-contributions'),
                ])
                ->post('https://api.github.com/graphql', [
                    'query' => $query,
                    'variables' => ['login' => $login],
                ]);
        } catch (ConnectionException) {
            return ['cells' => [], 'total' => 0];
        }

        if (! $response->successful()) {
            Log::warning('GitHub contributions: request failed.', [
                'login' => $login,
                'status' => $response->status(),
                'rate_limit_remaining' => $response->header('X-RateLimit-Remaining'),
                'retry_after' => $response->header('Retry-After'),
            ]);

            return ['cells' => [], 'total' => 0];
        }

        // A GraphQL endpoint can return HTTP 200 while still reporting errors in
        // the body (e.g. rate limiting, bad credentials). Surface them instead
        // of silently returning an empty calendar.
        $errors = $response->json('errors');

        if (! empty($errors)) {
            Log::warning('GitHub contributions: GraphQL returned errors.', [
                'login' => $login,
                'errors' => $errors,
                'rate_limit_remaining' => $response->header('X-RateLimit-Remaining'),
                'retry_after' => $response->header('Retry-After'),
            ]);

            return ['cells' => [], 'total' => 0];
        }

        $calendar = $response->json('data.user.contributionsCollection.contributionCalendar');

        if (! $calendar) {
            return ['cells' => [], 'total' => 0];
        }

        $cells = [];

        foreach ($calendar['weeks'] ?? [] as $week) {
            $byDay = array_fill(0, 7, 0);

            foreach ($week['contributionDays'] ?? [] as $day) {
                $byDay[$day['weekday']] = self::levelFromEnum($day['contributionLevel'] ?? 'NONE');
            }

            foreach ($byDay as $level) {
                $cells[] = $level;
            }
        }

        return [
            'cells' => $cells,
            'total' => (int) ($calendar['totalContributions'] ?? 0),
        ];
    }

    private static function levelFromEnum(string $enum): int
    {
        return match ($enum) {
            'FIRST_QUARTILE' => 1,
            'SECOND_QUARTILE' => 2,
            'THIRD_QUARTILE' => 3,
            'FOURTH_QUARTILE' => 4,
            default => 0,
        };
    }
}
