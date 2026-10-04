<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * What the stats page says about what GameStats worked out: a card for each record, in the order they are shown, with
 * the words for its value and for who holds it and when, and the extra numbers a player's card needs. It decides
 * nothing about the stats, GameStats does that.
 */
final class StatsPage
{
    /** A record with more holders than this says how many more there are */
    public const MAX_HOLDERS = 3;

    /**
     * The records in the order they are shown. A gold card is for something good, a quiet one for something that
     * isn't, none of them are a warning.
     *
     * @var array<string, array{title: string, icon: string, gold: bool, one: string, many: string, none: string}>
     */
    private const RECORDS = [
        'highest_score' => ['title' => 'Highest score', 'icon' => 'trophy', 'gold' => true, 'one' => 'point', 'many' => 'points', 'none' => 'No finished games yet'],
        'most_wins' => ['title' => 'Most wins', 'icon' => 'crown', 'gold' => true, 'one' => 'win', 'many' => 'wins', 'none' => 'Nobody has won a game yet'],
        'most_consecutive_wins' => ['title' => 'Most consecutive wins', 'icon' => 'star', 'gold' => true, 'one' => 'win in a row', 'many' => 'wins in a row', 'none' => 'Nobody has won a game yet'],
        'lowest_score' => ['title' => 'Lowest score', 'icon' => 'flag', 'gold' => false, 'one' => 'point', 'many' => 'points', 'none' => 'No finished games yet'],
        'most_consecutive_losses' => ['title' => 'Most consecutive losses', 'icon' => 'flag', 'gold' => false, 'one' => 'loss in a row', 'many' => 'losses in a row', 'none' => 'Nobody has lost a game yet'],
        'most_yahtzees_in_a_game' => ['title' => 'Most Yahtzees in a game', 'icon' => 'sparkles', 'gold' => true, 'one' => 'Yahtzee', 'many' => 'Yahtzees', 'none' => 'No Yahtzees yet'],
        'most_consecutive_games_with_a_yahtzee' => ['title' => 'Most consecutive games with a Yahtzee', 'icon' => 'sparkles', 'gold' => true, 'one' => 'game in a row', 'many' => 'games in a row', 'none' => 'No Yahtzees yet'],
    ];

    /**
     * A card for each record, a record nobody holds says so. A card's holders are the first few, `more` is how many
     * were left off. A holder's `detail` is when, a day or the days of a streak, and its `game_id` is the game
     * to open (the last game of a streak), both null for a total such as the wins.
     *
     * @param array<string, array{value: int, holders: list<array<string, mixed>>}|null> $records The records of GameStats
     * @return list<array{
     *     key: string, title: string, icon: string, gold: bool, value: int|null, unit: string|null, none: string,
     *     holders: list<array{player_id: string, name: string, detail: string|null, game_id: string|null}>, more: int
     * }>
     */
    public static function records(array $records, ?CarbonInterface $now = null): array
    {
        $cards = [];

        foreach (self::RECORDS as $key => $definition) {
            $record = $records[$key] ?? null;
            $holders = $record['holders'] ?? [];
            $value = $record['value'] ?? null;

            $cards[] = [
                'key' => $key,
                'title' => $definition['title'],
                'icon' => $definition['icon'],
                'gold' => $definition['gold'],
                'value' => $value,
                'unit' => $value === null ? null : ($value === 1 ? $definition['one'] : $definition['many']),
                'none' => $definition['none'],
                'holders' => array_map(
                    static fn (array $holder): array => [
                        'player_id' => $holder['player_id'],
                        'name' => $holder['player_name'],
                        'detail' => self::when($holder['from'] ?? null, $holder['to'] ?? null, $now),
                        'game_id' => $holder['game_id'] ?? null,
                    ],
                    array_slice($holders, 0, self::MAX_HOLDERS)
                ),
                'more' => max(0, count($holders) - self::MAX_HOLDERS),
            ];
        }

        return $cards;
    }

    /**
     * The players of GameStats with what a player's card adds: the share of the games they won, when someone won or
     * lost (a game on their own is neither), and their average, as a whole number when it is one.
     *
     * @param list<array<string, mixed>> $players The `players` of GameStats
     * @return list<array<string, mixed>>
     */
    public static function players(array $players): array
    {
        return array_map(static function (array $player): array {
            $decided = $player['wins'] + $player['losses'];

            return $player + [
                'win_rate' => $decided > 0 ? (int) round(100 * $player['wins'] / $decided) : null,
                'average' => rtrim(rtrim(number_format((float) $player['average_score'], 1, '.', ''), '0'), '.'),
            ];
        }, $players);
    }

    /**
     * Saturday, or Saturday to Tuesday for the days of a streak, null for a record that is not about a day
     */
    private static function when(?string $from, ?string $to, ?CarbonInterface $now): ?string
    {
        if ($from === null || $to === null) {
            return null;
        }

        $start = GameBoard::when(CarbonImmutable::parse($from, 'UTC'), $now);
        $end = GameBoard::when(CarbonImmutable::parse($to, 'UTC'), $now);

        return $start === $end ? $start : $start . ' to ' . $end;
    }
}
