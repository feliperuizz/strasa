<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\Mime\MimeTypes;

/**
 * Anexos antigos guardaram o mime que o navegador mandou, que muitas vezes vem
 * genérico ("application/octet-stream") ou ambíguo ("application/mp4"). Esse
 * valor agora decide se o arquivo abre no player ou vira download, então vale
 * recalcular pela extensão do nome original — só quando o que está lá não é
 * um tipo de mídia reconhecido.
 */
return new class extends Migration
{
    public function up(): void
    {
        $mimes = new MimeTypes();

        DB::table('task_attachments')
            ->orderBy('id')
            ->chunkById(200, function ($anexos) use ($mimes) {
                foreach ($anexos as $anexo) {
                    $atual = (string) $anexo->mime_type;

                    // Já é mídia identificada ou PDF: não mexe.
                    if ($atual === 'application/pdf' || Str::startsWith($atual, ['video/', 'audio/', 'image/'])) {
                        continue;
                    }

                    $extensao = strtolower(pathinfo((string) $anexo->original_name, PATHINFO_EXTENSION));
                    if ($extensao === '') {
                        continue;
                    }

                    $novo = null;
                    foreach ($mimes->getMimeTypes($extensao) as $candidato) {
                        if (Str::startsWith($candidato, ['video/', 'audio/', 'image/'])) {
                            $novo = $candidato;
                            break;
                        }
                    }

                    if ($novo === null || $novo === $atual) {
                        continue;
                    }

                    DB::table('task_attachments')
                        ->where('id', $anexo->id)
                        ->update([
                            'mime_type' => $novo,
                            'is_image' => Str::startsWith($novo, 'image/'),
                        ]);
                }
            });
    }

    public function down(): void
    {
        // Sem volta: o valor anterior era justamente o que estava errado.
    }
};
