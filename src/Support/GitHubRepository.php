<?php

declare(strict_types=1);

namespace LaravelPlus\LaravelUpdater\Support;

final class GitHubRepository
{
    /**
     * Parse a GitHub remote URL into its owner and repository components.
     *
     * Supports HTTPS, SSH and scp-style remotes, with or without a `.git`
     * suffix, for example:
     *
     * - https://github.com/laravel/vue-starter-kit.git
     * - git@github.com:laravel/vue-starter-kit.git
     * - ssh://git@github.com/laravel/vue-starter-kit
     *
     * @return array{owner: string, repo: string}|null
     */
    public static function parse(?string $remoteUrl): ?array
    {
        $url = mb_trim((string) $remoteUrl);

        if ($url === '') {
            return null;
        }

        $url = mb_rtrim($url, '/');
        $url = preg_replace('/\.git$/i', '', $url) ?? $url;

        if (preg_match('#^(?:(?:https?|ssh)://)?(?:git@)?github\.com[:/]([^/]+)/([^/]+)$#i', $url, $matches) !== 1) {
            return null;
        }

        return [
            'owner' => $matches[1],
            'repo' => $matches[2],
        ];
    }

    /**
     * Build a GitHub compare URL for creating a pull request manually.
     *
     * @param  array{owner: string, repo: string}  $repository
     */
    public static function compareUrl(array $repository, string $base, string $head): string
    {
        return sprintf(
            'https://github.com/%s/%s/compare/%s...%s',
            $repository['owner'],
            $repository['repo'],
            rawurlencode($base),
            rawurlencode($head)
        );
    }
}
