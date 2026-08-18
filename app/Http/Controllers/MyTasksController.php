<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;

class MyTasksController extends Controller
{
    public function index(Request $request)
    {
        $tasks = Task::where('company_id', $request->user()->company_id)
            ->where('assignee_id', $request->user()->id)
            ->with(['project.client', 'column', 'tags'])
            ->orderBy('publish_date', 'asc')
            ->get();

        return view('my-tasks.index', compact('tasks'));
    }
}
