<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class DeployController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);

        $output = "";

        try {
            $output .= "=== Git Pull ===\n";
            // Em ambiente local isso pode falhar (depende do git no Windows), mas vai funcionar na VPS
            $output .= shell_exec('git pull origin main 2>&1') ?? "Git pull finalizado sem output.\n";

            $output .= "\n=== Migrate ===\n";
            Artisan::call('migrate', ['--force' => true]);
            $output .= Artisan::output();

            $output .= "\n=== Clear Cache ===\n";
            Artisan::call('view:clear');
            $output .= Artisan::output();
            Artisan::call('cache:clear');
            $output .= Artisan::output();

            Log::info("Deploy executado via botão", ['output' => $output]);

            return back()->with('status', 'Deploy executado com sucesso! (Git pull, migrations e cache limpo).');
        } catch (\Exception $e) {
            Log::error("Erro no deploy: " . $e->getMessage());
            return back()->with('status', 'Erro ao executar deploy: ' . $e->getMessage());
        }
    }
}
