<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Reads a student list exported from the academic portal and turns it into
 * accounts in one section. Step 06 reuses this for whole-branch imports.
 *
 * Two passes on purpose: preview() only reads and reports, commit() writes.
 * Nobody creates fifty accounts from a file they have not seen checked.
 */
class StudentImporter
{
    public const COLUMNS = ['university_id', 'name', 'email'];

    /**
     * @return array{rows: array<int, array<string, mixed>>, valid: int, invalid: int}
     */
    public function preview(UploadedFile $file, Section $section): array
    {
        $rows = [];
        $seen = [];

        foreach ($this->read($file) as $number => $raw) {
            $row = [
                'line' => $number,
                'university_id' => trim((string) ($raw['university_id'] ?? '')),
                'name' => trim((string) ($raw['name'] ?? '')),
                'email' => trim((string) ($raw['email'] ?? '')),
            ];

            $row['error'] = $this->errorFor($row, $seen, $section);
            $row['exists'] = $row['error'] === null
                && User::where('university_id', $row['university_id'])->exists();

            if ($row['error'] === null) {
                $seen[] = $row['university_id'];
            }

            $rows[] = $row;
        }

        return [
            'rows' => $rows,
            'valid' => count(array_filter($rows, fn (array $row) => $row['error'] === null)),
            'invalid' => count(array_filter($rows, fn (array $row) => $row['error'] !== null)),
        ];
    }

    /**
     * Writes the valid rows. A university ID that already exists is updated,
     * never duplicated, and keeps its password.
     *
     * @return array{created: int, updated: int, passwords: array<int, array<string, string>>}
     */
    public function commit(array $rows, Section $section): array
    {
        $created = 0;
        $updated = 0;
        $passwords = [];

        DB::transaction(function () use ($rows, $section, &$created, &$updated, &$passwords) {
            foreach ($rows as $row) {
                if (($row['error'] ?? null) !== null) {
                    continue;
                }

                $existing = User::where('university_id', $row['university_id'])->first();

                if ($existing) {
                    $existing->update([
                        'name' => $row['name'],
                        'email' => $row['email'],
                        'section_id' => $section->id,
                        'branch_id' => $section->branch_id,
                        'department_id' => $section->department_id,
                    ]);

                    $updated++;

                    continue;
                }

                // A fresh random password per student, shown once and never stored in clear
                $temporary = Str::password(10, true, true, false);

                $student = User::create([
                    'university_id' => $row['university_id'],
                    'name' => $row['name'],
                    'email' => $row['email'],
                    'password' => Hash::make($temporary),
                    'role' => 'student',
                    'section_id' => $section->id,
                    'branch_id' => $section->branch_id,
                    'department_id' => $section->department_id,
                    'must_change_password' => true,
                ]);

                $passwords[] = [
                    'university_id' => $student->university_id,
                    'name' => $student->name,
                    'password' => $temporary,
                ];

                $created++;
            }

            AuditLog::record('students_imported', $section, [], [
                'section' => $section->number,
                'created' => $created,
                'updated' => $updated,
            ]);
        });

        return ['created' => $created, 'updated' => $updated, 'passwords' => $passwords];
    }

    /** The header row the file must have, also used for the downloadable template */
    public function template(): string
    {
        return $this->csvLine(self::COLUMNS)
            . $this->csvLine(['01500099', 'اسم الطالب', 'student@students.qou.edu']);
    }

    public function passwordsCsv(array $passwords): string
    {
        $out = $this->csvLine(['university_id', 'name', 'temporary_password']);

        foreach ($passwords as $row) {
            $out .= $this->csvLine([$row['university_id'], $row['name'], $row['password']]);
        }

        return $out;
    }

    /* ---------- Internals ---------- */

    private function errorFor(array $row, array $seen, Section $section): ?string
    {
        if ($row['university_id'] === '' || $row['name'] === '' || $row['email'] === '') {
            return __('supervisor.import_error_missing');
        }

        if (! ctype_digit($row['university_id'])) {
            return __('supervisor.import_error_id_not_numeric');
        }

        if (in_array($row['university_id'], $seen, true)) {
            return __('supervisor.import_error_duplicate_in_file');
        }

        if (! filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
            return __('supervisor.import_error_email');
        }

        // Someone who is not a student, or a student in another section,
        // must not be pulled into this section by a file
        $existing = User::where('university_id', $row['university_id'])->first();

        if ($existing && $existing->role !== 'student') {
            return __('supervisor.import_error_not_student');
        }

        if ($existing && $existing->section_id !== null && $existing->section_id !== $section->id) {
            return __('supervisor.import_error_other_section');
        }

        return null;
    }

    /** @return array<int, array<string, string>> keyed by the line number shown to the user */
    private function read(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return [];
        }

        // Excel writes a BOM in front of UTF-8 CSV; it would corrupt the first header
        $first = fgets($handle);
        rewind($handle);

        if (str_starts_with((string) $first, "\xEF\xBB\xBF")) {
            fseek($handle, 3);
        }

        $header = fgetcsv($handle);

        if ($header === false) {
            fclose($handle);

            return [];
        }

        $header = array_map(fn ($cell) => Str::of((string) $cell)->trim()->lower()->replace(' ', '_')->value(), $header);

        $rows = [];
        $line = 1;

        while (($cells = fgetcsv($handle)) !== false) {
            $line++;

            if ($cells === [null] || $cells === []) {
                continue; // blank line
            }

            $rows[$line] = array_combine(
                $header,
                array_pad(array_slice($cells, 0, count($header)), count($header), '')
            );
        }

        fclose($handle);

        return $rows;
    }

    private function csvLine(array $cells): string
    {
        return implode(',', array_map(
            fn ($cell) => '"' . str_replace('"', '""', (string) $cell) . '"',
            $cells
        )) . "\r\n";
    }
}
