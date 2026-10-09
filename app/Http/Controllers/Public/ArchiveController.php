<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use Illuminate\View\View;

class ArchiveController extends Controller
{
    public function faculty(Faculty $faculty): View
    {
        $entries = $faculty->archiveEntries()
            ->withCardData()
            ->latest('defense_date')
            ->paginate(12);

        return view('public.archive.faculty', compact('faculty', 'entries'));
    }
}
