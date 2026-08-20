<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\ProjectNote;

class ProjectNoteController extends Controller
{
    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'content' => 'nullable|string',
        ]);

        $note = ProjectNote::updateOrCreate(
            ['project_id' => $project->id, 'user_id' => auth()->id()],
            ['content' => $data['content']]
        );

        return response()->json(['status' => 'success', 'note' => $note]);
    }
}
