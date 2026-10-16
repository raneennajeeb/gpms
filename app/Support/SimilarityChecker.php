<?php

namespace App\Support;

use App\Models\ArchiveEntry;
use App\Models\Project;
use Illuminate\Support\Collection;

/**
 * Compares a proposal against the archive and against the other live projects,
 * using TF-IDF vectors and cosine similarity. Pure PHP, no Python, no packages.
 *
 * The four steps, in order:
 *   1. Normalise  — App\Support\ArabicText folds spelling variants, then stop
 *                   words are dropped so "في" and "the" carry no weight.
 *   2. TF          — how often a term appears in one document, divided by that
 *                   document's length, so a long text is not favoured.
 *   3. IDF         — log(N / documents containing the term). A term in every
 *                   document says nothing; a rare one is a strong signal.
 *   4. Cosine      — the angle between two TF-IDF vectors, 0 to 1. It compares
 *                   direction, not size, so document length does not matter.
 */
class SimilarityChecker
{
    /** Words too common in Arabic or English project texts to carry meaning */
    private const STOP_WORDS = [
        'في', 'من', 'الى', 'علي', 'عن', 'مع', 'هذا', 'هذه', 'ذلك', 'التي', 'الذي',
        'كما', 'بين', 'او', 'ثم', 'كل', 'بعد', 'قبل', 'عند', 'حيث', 'لكن', 'قد',
        'يتم', 'تم', 'يكون', 'كان', 'هو', 'هي', 'ان', 'انه', 'ما', 'لا', 'به',
        'the', 'and', 'for', 'with', 'that', 'this', 'from', 'are', 'was', 'has',
        'using', 'use', 'used', 'based', 'system', 'project', 'نظام', 'مشروع',
    ];

    /** How many matches to show the student */
    private const TOP_MATCHES = 5;

    /** @var array<string, float> term => idf */
    private array $idf = [];

    /** @var Collection<int, array{id:int, kind:string, title:string, year:?int, faculty:?string, tokens:array<string,float>}> */
    private Collection $corpus;

    public function __construct(?int $excludeProjectId = null)
    {
        $this->corpus = $this->loadCorpus($excludeProjectId);
        $this->idf = $this->buildIdf();
    }

    /**
     * @return array{score:float, archive_score:float, project_score:float, matches:array<int, array<string, mixed>>, terms:array<int, string>}
     */
    public function check(string $title, string $description, array $keywords = []): array
    {
        $queryTokens = $this->tokenize($title . ' ' . $description . ' ' . implode(' ', $keywords));
        $queryVector = $this->vectorize($queryTokens);

        $matches = $this->corpus
            ->map(function (array $document) use ($queryVector, $queryTokens) {
                $score = $this->cosine($queryVector, $this->vectorize($document['tokens']));

                return [
                    'id' => $document['id'],
                    'kind' => $document['kind'],
                    'title' => $document['title'],
                    'year' => $document['year'],
                    'faculty' => $document['faculty'],
                    'score' => round($score * 100, 1),
                    // The words the two texts share, for highlighting
                    'shared' => array_values(array_slice(
                        array_intersect(array_keys($queryTokens), array_keys($document['tokens'])),
                        0,
                        12
                    )),
                ];
            })
            ->filter(fn (array $match) => $match['score'] > 0)
            ->sortByDesc('score')
            ->take(self::TOP_MATCHES)
            ->values();

        $archiveScore = (float) $matches->where('kind', 'archive')->max('score');
        $projectScore = (float) $matches->where('kind', 'project')->max('score');

        return [
            // The overall figure is the highest of the two, not an average:
            // one near-identical project is what matters, however many exist
            'score' => max($archiveScore, $projectScore),
            'archive_score' => $archiveScore,
            'project_score' => $projectScore,
            'matches' => $matches->all(),
            'terms' => array_keys($queryTokens),
        ];
    }

    /* ---------- Corpus ---------- */

    private function loadCorpus(?int $excludeProjectId): Collection
    {
        $archive = ArchiveEntry::with('faculty')
            ->get()
            ->map(fn (ArchiveEntry $entry) => [
                'id' => $entry->id,
                'kind' => 'archive',
                'title' => $entry->title,
                'year' => $entry->year,
                'faculty' => $entry->faculty?->localizedName(),
                'tokens' => $this->tokenize(
                    $entry->title . ' ' . $entry->idea_summary . ' ' . $entry->description
                ),
            ]);

        $projects = Project::with('semester')
            ->whereNot('status', 'rejected')
            ->when($excludeProjectId, fn ($query) => $query->whereKeyNot($excludeProjectId))
            ->get()
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'kind' => 'project',
                'title' => $project->title,
                'year' => $project->semester?->start_date?->year,
                'faculty' => null,
                'tokens' => $this->tokenize(
                    $project->title . ' ' . $project->description . ' ' . implode(' ', $project->keywords ?? [])
                ),
            ]);

        return $archive->concat($projects)->values();
    }

    /** Step 2: term frequency inside one document */
    private function tokenize(string $text): array
    {
        $words = array_filter(
            explode(' ', ArabicText::normalize($text)),
            fn (string $word) => mb_strlen($word) >= 3 && ! in_array($word, self::STOP_WORDS, true)
        );

        if (! $words) {
            return [];
        }

        $counts = array_count_values($words);
        $total = count($words);

        return array_map(fn (int $count) => $count / $total, $counts);
    }

    /** Step 3: inverse document frequency across the whole corpus */
    private function buildIdf(): array
    {
        $documentCount = max($this->corpus->count(), 1);
        $seen = [];

        foreach ($this->corpus as $document) {
            foreach (array_keys($document['tokens']) as $term) {
                $seen[$term] = ($seen[$term] ?? 0) + 1;
            }
        }

        // +1 on both sides keeps a brand-new term from dividing by zero
        return array_map(
            fn (int $count) => log(($documentCount + 1) / ($count + 1)) + 1,
            $seen
        );
    }

    private function vectorize(array $termFrequencies): array
    {
        $vector = [];

        foreach ($termFrequencies as $term => $frequency) {
            // A term the corpus has never seen gets the highest weight
            $vector[$term] = $frequency * ($this->idf[$term] ?? log($this->corpus->count() + 1) + 1);
        }

        return $vector;
    }

    /** Step 4: cosine of the angle between two vectors */
    private function cosine(array $a, array $b): float
    {
        $dot = 0.0;

        foreach (array_intersect_key($a, $b) as $term => $weight) {
            $dot += $weight * $b[$term];
        }

        if ($dot === 0.0) {
            return 0.0;
        }

        $magnitude = sqrt(array_sum(array_map(fn ($w) => $w ** 2, $a)))
            * sqrt(array_sum(array_map(fn ($w) => $w ** 2, $b)));

        return $magnitude > 0 ? $dot / $magnitude : 0.0;
    }
}
