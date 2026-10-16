<?php

namespace App\Support;

class Grade
{
    // Minimum score for each letter, highest first (university grading scale)
    private const MIN_SCORES = [
        'A' => 90,
        'B' => 80,
        'C' => 70,
        'D' => 60,
        'E' => 50,
    ];

    private const KEYS = [
        'A' => 'excellent',
        'B' => 'very_good',
        'C' => 'good',
        'D' => 'average',
        'E' => 'pass',
        'F' => 'fail',
    ];

    public static function letter(float $score): string
    {
        foreach (self::MIN_SCORES as $letter => $min) {
            if ($score >= $min) {
                return $letter;
            }
        }

        return 'F';
    }

    public static function key(float $score): string
    {
        return self::KEYS[self::letter($score)];
    }

    public static function label(string $key, ?string $locale = null): string
    {
        return __('grades.' . $key, [], $locale);
    }
}
