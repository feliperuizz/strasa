<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Task;

class MyTasksController extends Controller
{
    public function index(Request $request)
    {
        $tasks = Task::where('company_id', $request->user()->company_id)
            ->whereHas('assignees', fn ($q) => $q->where('users.id', $request->user()->id))
            // Tarefa concluída sai da lista: aqui é o que a pessoa AINDA tem
            // para fazer. Vale tanto para o botão verde quanto para o arraste
            // até a coluna de concluído — os dois marcam is_published.
            ->where('is_published', false)
            ->with(['project.client', 'column', 'tags'])
            ->orderBy('publish_date', 'asc')
            ->get();

        return view('my-tasks.index', compact('tasks'));
    }
}
