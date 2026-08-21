<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ColumnController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinancialController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskAttachmentController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas públicas (visitante)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    // Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    // Route::post('register', [RegisteredUserController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('convite/{token}', [InvitationController::class, 'show'])->name('invitations.accept');
    Route::post('convite/{token}', [InvitationController::class, 'store']);
});

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')->name('logout');

// Route::view (e não Closure) para que `php artisan route:cache` funcione:
// rotas com Closure não podem ser serializadas.
Route::view('/', 'welcome');
Route::view('/privacy', 'privacy')->name('privacy');
Route::view('/terms', 'terms')->name('terms');

/*
|--------------------------------------------------------------------------
| Aplicação (autenticado + multi-tenant)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'tenant'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('my-tasks', [\App\Http\Controllers\MyTasksController::class, 'index'])->name('my-tasks');
    Route::get('search/tasks', [\App\Http\Controllers\SearchController::class, 'tasks'])->name('search.tasks');

    Route::get('profile', [\App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [\App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
    Route::post('profile/sidebar-order', [\App\Http\Controllers\ProfileController::class, 'updateSidebarOrder'])->name('profile.sidebar-order');
    Route::post('push-subscriptions', [\App\Http\Controllers\ProfileController::class, 'subscribeToPush'])->name('push.subscribe');

    /* Clientes ----------------------------------------------------------- */
    Route::resource('clients', ClientController::class);
    Route::patch('clients/{client}/archive', [ClientController::class, 'archive'])->name('clients.archive');

    /* Projetos (criação aninhada ao cliente; demais ações "shallow") ------ */
    Route::get('clients/{client}/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('clients/{client}/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::patch('projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    Route::post('projects/{project}/favorite', [ProjectController::class, 'toggleFavorite'])->name('projects.favorite');
    Route::patch('projects/{project}/status', [ProjectController::class, 'updateStatus'])->name('projects.status');
    Route::post('projects/{project}/notes', [\App\Http\Controllers\ProjectNoteController::class, 'store'])->name('projects.notes.store');

    /* Quadro Kanban e Lista ----------------------------------------------- */
    Route::get('projects/{project}/board', [BoardController::class, 'show'])->name('projects.board');
    Route::get('projects/{project}/list', [BoardController::class, 'list'])->name('projects.list');

    /* Colunas do quadro -------------------------------------------------- */
    Route::post('projects/{project}/columns', [ColumnController::class, 'store'])->name('columns.store');
    Route::post('projects/{project}/columns/reorder', [ColumnController::class, 'reorder'])->name('columns.reorder');
    Route::patch('columns/{column}', [ColumnController::class, 'update'])->name('columns.update');
    Route::delete('columns/{column}', [ColumnController::class, 'destroy'])->name('columns.destroy');

    /* Tarefas / Posts ---------------------------------------------------- */
    Route::get('projects/{project}/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
    Route::post('projects/{project}/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
    Route::get('tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
    Route::patch('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::post('tasks/{task}/move', [TaskController::class, 'move'])->name('tasks.move');
    Route::post('tasks/{task}/complete', [TaskController::class, 'complete'])->name('tasks.complete');

    /* Itens de Checklist ------------------------------------------------- */
    Route::post('tasks/{task}/items', [\App\Http\Controllers\TaskItemController::class, 'store'])->name('items.store');
    Route::patch('items/{item}', [\App\Http\Controllers\TaskItemController::class, 'update'])->name('items.update');
    Route::delete('items/{item}', [\App\Http\Controllers\TaskItemController::class, 'destroy'])->name('items.destroy');

    /* Comentários -------------------------------------------------------- */
    Route::post('tasks/{task}/comments', [TaskCommentController::class, 'store'])->name('comments.store');
    Route::patch('comments/{comment}', [TaskCommentController::class, 'update'])->name('comments.update');
    Route::delete('comments/{comment}', [TaskCommentController::class, 'destroy'])->name('comments.destroy');

    /* Anexos (S3/R2) e Pastas -------------------------------------------- */
    Route::post('tasks/{task}/folders', [\App\Http\Controllers\TaskFolderController::class, 'store'])->name('folders.store');
    Route::patch('folders/{folder}', [\App\Http\Controllers\TaskFolderController::class, 'update'])->name('folders.update');
    Route::delete('folders/{folder}', [\App\Http\Controllers\TaskFolderController::class, 'destroy'])->name('folders.destroy');

    Route::post('tasks/{task}/attachments', [TaskAttachmentController::class, 'store'])->name('attachments.store');
    Route::delete('attachments/{attachment}', [TaskAttachmentController::class, 'destroy'])->name('attachments.destroy');
    Route::get('attachments/{attachment}/download', [TaskAttachmentController::class, 'download'])->name('attachments.download');
    Route::get('attachments/{attachment}/show', [TaskAttachmentController::class, 'show'])->name('attachments.show');

    /* Calendário --------------------------------------------------------- */
    Route::get('clients/{client}/calendar', [CalendarController::class, 'client'])->name('clients.calendar');
    Route::get('clients/{client}/calendar/events', [CalendarController::class, 'events'])->name('clients.calendar.events');
    Route::get('projects/{project}/calendar', [CalendarController::class, 'project'])->name('projects.calendar');

    /* Time / convites ---------------------------------------------------- */
    Route::get('team', [TeamController::class, 'index'])->name('team.index');
    Route::post('team/invitations', [TeamController::class, 'store'])->name('team.invitations.store');
    Route::delete('team/invitations/{invitation}', [TeamController::class, 'destroy'])->name('team.invitations.destroy');
    Route::patch('team/members/{user}', [TeamController::class, 'updateMember'])->name('team.members.update');
    Route::delete('team/members/{user}', [TeamController::class, 'removeMember'])->name('team.members.destroy');

    /* Financeiro (Apenas Admins) ----------------------------------------- */
    Route::get('financial', [FinancialController::class, 'index'])->name('financial.index');
    Route::post('financial', [FinancialController::class, 'store'])->name('financial.store');
    Route::patch('financial/{payment}', [FinancialController::class, 'update'])->name('financial.update');
    Route::post('financial/{payment}/mark-paid', [FinancialController::class, 'markPaid'])->name('financial.mark-paid');
    Route::delete('financial/{payment}', [FinancialController::class, 'destroy'])->name('financial.destroy');
});
