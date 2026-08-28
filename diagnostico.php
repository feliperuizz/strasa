<?php
/**
 * Diagnóstico de performance do STRASA.
 *
 * Somente leitura — não altera nada. Rode na raiz da aplicação:
 *
 *     php diagnostico.php
 *
 * Mede latência do banco, conta linhas, executa as telas mais pesadas
 * contando queries e mostra as mais lentas, além de checar OPcache e os
 * drivers configurados.
 */

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Http\Kernel::class)->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

function titulo(string $t): void
{
    echo "\n".str_repeat('=', 68)."\n  $t\n".str_repeat('=', 68)."\n";
}

/* ===================================================================== */
titulo('1. AMBIENTE');

printf("  PHP                : %s\n", PHP_VERSION);
printf("  Laravel            : %s\n", app()->version());
printf("  APP_ENV / DEBUG    : %s / %s\n", config('app.env'), config('app.debug') ? 'ON (!)' : 'off');
printf("  Config em cache    : %s\n", app()->configurationIsCached() ? 'sim' : 'NAO (!)');
printf("  Rotas em cache     : %s\n", app()->routesAreCached() ? 'sim' : 'NAO (!)');
printf("  Sessao             : %s\n", config('session.driver'));
printf("  Cache              : %s\n", config('cache.default'));
printf("  Fila               : %s\n", config('queue.default'));
printf("  Disco padrao       : %s\n", config('filesystems.default'));

$op = function_exists('opcache_get_status') ? @opcache_get_status(false) : null;
if (! $op || empty($op['opcache_enabled'])) {
    echo "  OPcache            : DESLIGADO (!!) — costuma custar 3x no tempo de resposta\n";
} else {
    printf("  OPcache            : ligado | memoria usada %.0f MB de %.0f MB | hit rate %.1f%%\n",
        $op['memory_usage']['used_memory'] / 1048576,
        ($op['memory_usage']['used_memory'] + $op['memory_usage']['free_memory']) / 1048576,
        $op['opcache_statistics']['opcache_hit_rate'] ?? 0);

    if (! empty($op['opcache_statistics']['num_cached_keys']) && ! empty($op['opcache_statistics']['max_cached_keys'])
        && $op['opcache_statistics']['num_cached_keys'] >= $op['opcache_statistics']['max_cached_keys'] * 0.95) {
        echo "  !! OPcache quase cheio: aumente opcache.max_accelerated_files\n";
    }
}

/* ===================================================================== */
titulo('2. LATENCIA DO BANCO');

$t = microtime(true);
for ($i = 0; $i < 50; $i++) { DB::select('SELECT 1'); }
$pingMedio = (microtime(true) - $t) / 50 * 1000;
printf("  Ida e volta media  : %.2f ms por query\n", $pingMedio);

if ($pingMedio > 3) {
    echo "  !! Acima de 3ms por query: cada request que faz 50 queries perde "
        .sprintf('%.0f', $pingMedio * 50)." ms so em latencia.\n";
}

/* ===================================================================== */
titulo('3. VOLUME DE DADOS');

$tabelas = ['users', 'clients', 'projects', 'columns', 'tasks', 'task_comments',
    'task_attachments', 'task_items', 'task_activities', 'task_user', 'task_tag',
    'payments', 'sessions', 'cache', 'jobs', 'failed_jobs', 'notifications',
    'task_approvals', 'client_portals'];

foreach ($tabelas as $tabela) {
    try {
        printf("  %-20s %8d linhas\n", $tabela, DB::table($tabela)->count());
    } catch (\Throwable $e) {
        printf("  %-20s (nao existe)\n", $tabela);
    }
}

/* ===================================================================== */
titulo('4. TELAS: TEMPO E NUMERO DE QUERIES');

$req = Request::create('/', 'GET');
$app->instance('request', $req);

$user = App\Models\User::orderBy('id')->first();
if (! $user) { exit("\n  Sem usuarios no banco; nao da para medir as telas.\n"); }

Auth::loginUsingId($user->id);
$req->setUserResolver(fn () => Auth::user());
View::share('errors', new Illuminate\Support\ViewErrorBag());

printf("  Medindo como: %s\n\n", $user->name);

$todasQueries = [];

function medir(string $nome, callable $fn, array &$todasQueries): void
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    $inicio = microtime(true);
    try {
        $saida = $fn();
        $html = is_string($saida) ? $saida : (method_exists($saida, 'render') ? $saida->render() : '');
        $erro = null;
    } catch (\Throwable $e) {
        $html = '';
        $erro = get_class($e).': '.$e->getMessage();
    }
    $ms = (microtime(true) - $inicio) * 1000;

    $log = DB::getQueryLog();
    DB::disableQueryLog();

    foreach ($log as $q) { $todasQueries[] = $q + ['tela' => $nome]; }

    if ($erro) {
        printf("  %-26s ERRO: %s\n", $nome, substr($erro, 0, 90));
        return;
    }

    $tempoSql = array_sum(array_column($log, 'time'));
    printf("  %-26s %7.0f ms | %4d queries | %6.0f ms em SQL | %s KB de HTML\n",
        $nome, $ms, count($log), $tempoSql, number_format(strlen($html) / 1024));

    if (count($log) > 60) {
        echo "      !! muitas queries — provavel N+1\n";
    }
}

// Middleware que alimenta o layout.
medir('middleware tenant', function () use ($req) {
    (new App\Http\Middleware\ShareTenantData())->handle($req, fn ($r) => new Illuminate\Http\Response());
    return '';
}, $todasQueries);

// Dashboard.
medir('dashboard', function () use ($req) {
    return (new App\Http\Controllers\DashboardController())($req);
}, $todasQueries);

// Quadro do projeto com mais tarefas.
$projetoId = DB::table('tasks')->select('project_id', DB::raw('COUNT(*) as total'))
    ->groupBy('project_id')->orderByDesc('total')->value('project_id');

if ($projetoId) {
    $projeto = App\Models\Project::find($projetoId);
    $qtd = App\Models\Task::where('project_id', $projetoId)->count();

    medir("board ({$qtd} cards)", function () use ($req, $projeto) {
        return (new App\Http\Controllers\BoardController())->show($req, $projeto);
    }, $todasQueries);
}

// Minhas tarefas.
medir('minhas tarefas', function () use ($req) {
    return (new App\Http\Controllers\MyTasksController())->index($req);
}, $todasQueries);

// Aprovações.
medir('aprovacoes', function () use ($req) {
    return (new App\Http\Controllers\ApprovalsController(app(App\Services\ApprovalService::class)))->index($req);
}, $todasQueries);

/* ===================================================================== */
titulo('5. QUERIES MAIS LENTAS');

usort($todasQueries, fn ($a, $b) => $b['time'] <=> $a['time']);

foreach (array_slice($todasQueries, 0, 12) as $i => $q) {
    printf("\n  #%d  %.1f ms  [%s]\n      %s\n",
        $i + 1, $q['time'], $q['tela'],
        substr(preg_replace('/\s+/', ' ', $q['query']), 0, 150));
}

/* ===================================================================== */
titulo('6. QUERIES REPETIDAS (sintoma de N+1)');

$assinaturas = [];
foreach ($todasQueries as $q) {
    $chave = preg_replace('/\s+/', ' ', $q['query']);
    $assinaturas[$chave] = ($assinaturas[$chave] ?? 0) + 1;
}
arsort($assinaturas);

$achou = false;
foreach (array_slice($assinaturas, 0, 8, true) as $sql => $vezes) {
    if ($vezes < 5) { continue; }
    $achou = true;
    printf("\n  %dx  %s\n", $vezes, substr($sql, 0, 140));
}
if (! $achou) { echo "\n  Nenhuma query repetida mais de 4 vezes. Sem N+1 obvio.\n"; }

/* ===================================================================== */
titulo('7. INDICES DAS TABELAS QUENTES');

foreach (['tasks', 'task_approvals', 'task_comments', 'task_attachments', 'sessions', 'cache'] as $tabela) {
    try {
        $idx = collect(DB::select("SHOW INDEX FROM `{$tabela}`"))
            ->groupBy('Key_name')
            ->map(fn ($linhas) => implode(',', array_column($linhas->toArray(), 'Column_name')));

        echo "\n  {$tabela}:\n";
        foreach ($idx as $nome => $colunas) { printf("    %-38s (%s)\n", $nome, $colunas); }
    } catch (\Throwable $e) {
        echo "\n  {$tabela}: nao foi possivel ler\n";
    }
}

echo "\n".str_repeat('=', 68)."\n  Fim do diagnostico.\n".str_repeat('=', 68)."\n";
