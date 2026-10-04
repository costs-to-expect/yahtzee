<?php

declare(strict_types=1);

namespace App\Support;

/**
 * What GameStatBuilder made of a game: the rows to store, or the reason the game can't be counted.
 */
final class GameStatResult
{
    /**
     * @param list<array<string, mixed>> $rows One for each player, the columns of the game_stat table, empty when skipped
     * @param string|null $reason One of the GameStatBuilder reasons, null when the game is counted
     */
    private function __construct(
        public readonly array $rows,
        public readonly ?string $reason,
    ) {
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    public static function counted(array $rows): self
    {
        return new self($rows, null);
    }

    public static function skipped(string $reason): self
    {
        return new self([], $reason);
    }

    public function isCounted(): bool
    {
        return $this->reason === null;
    }
}
