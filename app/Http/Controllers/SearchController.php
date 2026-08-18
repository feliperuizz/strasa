<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function tasks(Request $request)
    {
        $query = $request->input('q');

        if (! $query) {
            return response()->json([]);
        }

        $tasks = Task::with(['project.client'])
            ->where('company_id', $request->user()->company_id)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhereHas('project', function ($q) use ($query) {
                      $q->where('name', 'like', "%{$query}%");
                  });
            })
            ->latest()
            ->take(10)
            ->get()
            ->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'project' => $task->project->name,
                    'client' => $task->project->client->name,
                    'edit_url' => route('tasks.edit', $task),
                ];
            });

        return response()->json($tasks);
    }
}
