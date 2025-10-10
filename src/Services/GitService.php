<?php

declare(strict_types=1);

namespace LaravelPlus\LaravelUpdater\Services;

use LaravelPlus\LaravelUpdater\Contracts\VersionControlInterface;
use RuntimeException;

final readonly class GitService
{
    public function __construct(
        private VersionControlInterface $versionControl
    ) {}

    /**
     * Add a remote if it doesn't exist.
     *
     * @param  string  $name  The remote name
     * @param  string  $url  The remote URL
     * @return bool True if remote was added or already exists
     */
    public function addRemoteIfNotExists(string $name, string $url): bool
    {
        if ($this->versionControl->hasRemote($name)) {
            return true;
        }

        $this->versionControl->run(['remote', 'add', $name, $url]);

        return true;
    }

    /**
     * Fetch from a remote.
     *
     * @param  string  $remote  The remote name
     * @return string The fetch output
     */
    public function fetch(string $remote): string
    {
        return $this->versionControl->run(['fetch', $remote]);
    }

    /**
     * Get the current branch name.
     *
     * @return string The current branch name
     */
    public function getCurrentBranch(): string
    {
        return mb_trim($this->versionControl->run(['rev-parse', '--abbrev-ref', 'HEAD']));
    }

    /**
     * Check if a branch exists.
     *
     * @param  string  $branch  The branch name
     * @return bool True if the branch exists
     */
    public function branchExists(string $branch): bool
    {
        try {
            $this->versionControl->run(['rev-parse', '--verify', $branch], throw: false);

            return true;
        } catch (RuntimeException) {
            return false;
        }
    }

    /**
     * Create and checkout a new branch.
     *
     * @param  string  $branch  The branch name
     * @return string The checkout output
     */
    public function createAndCheckoutBranch(string $branch): string
    {
        return $this->versionControl->run(['checkout', '-b', $branch]);
    }

    /**
     * Checkout an existing branch.
     *
     * @param  string  $branch  The branch name
     * @return string The checkout output
     */
    public function checkoutBranch(string $branch): string
    {
        return $this->versionControl->run(['checkout', $branch]);
    }

    /**
     * Ensure a branch exists and is checked out.
     *
     * @param  string  $branch  The branch name
     * @return string The checkout output
     */
    public function ensureBranchExists(string $branch): string
    {
        if ($this->branchExists($branch)) {
            return $this->checkoutBranch($branch);
        }

        return $this->createAndCheckoutBranch($branch);
    }

    /**
     * Merge a branch into the current branch.
     *
     * @param  string  $branch  The branch to merge
     * @param  bool  $allowUnrelatedHistories  Whether to allow unrelated histories
     * @return string The merge output
     */
    public function merge(string $branch, bool $allowUnrelatedHistories = false): string
    {
        $args = ['merge', $branch];

        if ($allowUnrelatedHistories) {
            $args[] = '--allow-unrelated-histories';
        }

        return $this->versionControl->run($args);
    }

    /**
     * Rebase the current branch onto another branch.
     *
     * @param  string  $branch  The branch to rebase onto
     * @return string The rebase output
     */
    public function rebase(string $branch): string
    {
        return $this->versionControl->run(['rebase', $branch]);
    }

    /**
     * Check if there are uncommitted changes.
     *
     * @return bool True if there are uncommitted changes
     */
    public function hasUncommittedChanges(): bool
    {
        $output = $this->versionControl->run(['status', '--porcelain'], throw: false);

        return ! in_array(mb_trim($output), ['', '0'], true);
    }

    /**
     * Commit changes with a message.
     *
     * @param  string  $message  The commit message
     * @return string The commit output
     */
    public function commit(string $message): string
    {
        return $this->versionControl->run(['commit', '-m', $message]);
    }

    /**
     * Push changes to a remote.
     *
     * @param  string  $remote  The remote name
     * @param  string  $branch  The branch name
     * @return string The push output
     */
    public function push(string $remote, string $branch): string
    {
        return $this->versionControl->run(['push', $remote, $branch]);
    }

    /**
     * Get the latest commit hash.
     *
     * @return string The commit hash
     */
    public function getLatestCommitHash(): string
    {
        return mb_trim($this->versionControl->run(['rev-parse', 'HEAD']));
    }

    /**
     * Get the latest commit hash from a remote branch.
     *
     * @param  string  $remote  The remote name
     * @param  string  $branch  The branch name
     * @return string The commit hash
     */
    public function getRemoteCommitHash(string $remote, string $branch): string
    {
        return mb_trim($this->versionControl->run(['rev-parse', "$remote/$branch"]));
    }

    /**
     * Check if local and remote branches are in sync.
     *
     * @param  string  $remote  The remote name
     * @param  string  $branch  The branch name
     * @return bool True if branches are in sync
     */
    public function isUpToDate(string $remote, string $branch): bool
    {
        $localHash = $this->getLatestCommitHash();
        $remoteHash = $this->getRemoteCommitHash($remote, $branch);

        return $localHash === $remoteHash;
    }
}
