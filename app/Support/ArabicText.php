<?php

namespace App\Support;

class ArabicText
{
    /** Harakat, tanween, shadda and the dagger alif */
    private const DIACRITICS = '/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}]/u';

    /** Letters people type in more than one way */
    private const LETTERS = [
        'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا',
        'ى' => 'ي', 'ئ' => 'ي',
        'ة' => 'ه',
        'ؤ' => 'و',
    ];

    /**
     * Fold a string into the shape we store in archive_entries.search_text,
     * so "مُؤَسَّسَة" and "مؤسسه" both match the same row.
     */
    public static function normalize(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        $text = preg_replace(self::DIACRITICS, '', $text);
        $text = str_replace('ـ', '', $text); // tatweel
        $text = strtr($text, self::LETTERS);

        // Punctuation, Arabic or Latin, is a word break
        $text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text);

        return trim(mb_strtolower($text));
    }

    /**
     * MySQL's FULLTEXT ignores words shorter than ft_min_word_len (4 by default),
     * which rules out plenty of real Arabic words, so those fall back to LIKE.
     */
    public static function needsLikeFallback(string $normalized): bool
    {
        foreach (explode(' ', $normalized) as $word) {
            if ($word !== '' && mb_strlen($word) < 4) {
                return true;
            }
        }

        return false;
    }
}
