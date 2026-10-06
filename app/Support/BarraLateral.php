<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Barra lateral de clientes de cada usuário: a ordem que ele arrastou e as
 * pastas que ele criou. Cada pessoa organiza a sua — fica no próprio
 * usuário (users.sidebar_client_order), não muda nada para a equipe.
 *
 * Formato guardado (JSON), em ordem de exibição:
 *   [ 7, {"pasta": "p_k3x9", "nome": "Varejo", "aberta": true, "clientes": [3, 5]}, 12 ]
 * Número = cliente solto; objeto = pasta com os clientes dela. O formato
 * antigo (só a lista de ids, de antes das pastas) continua valendo.
 */
final class BarraLateral
{
    public const MAX_NOME = 60;

    /**
     * Itens da barra na ordem do usuário. Clientes que ele ainda não
     * posicionou (novos) entram no fim, em ordem alfabética; clientes que
     * saíram (arquivados, excluídos) simplesmente somem.
     *
     * @return array<int, array{tipo: string}>
     */
    public static function montar(Collection $clientes, mixed $layout): array
    {
        $porId = $clientes->keyBy('id');
        $usados = [];
        $itens = [];

        $pegar = function ($id) use ($porId, &$usados) {
            $id = (int) $id;

            if (! $porId->has($id) || isset($usados[$id])) {
                return null;
            }

            $usados[$id] = true;

            return $porId[$id];
        };

        foreach (is_array($layout) ? $layout : [] as $entrada) {
            if (is_array($entrada) && isset($entrada['pasta'])) {
                $dentro = collect($entrada['clientes'] ?? [])->map($pegar)->filter()->values();

                $itens[] = [
                    'tipo' => 'pasta',
                    'id' => (string) $entrada['pasta'],
                    'nome' => (string) ($entrada['nome'] ?? 'Pasta'),
                    'aberta' => (bool) ($entrada['aberta'] ?? true),
                    'clientes' => $dentro,
                ];
            } elseif (is_numeric($entrada) && ($cliente = $pegar($entrada))) {
                $itens[] = ['tipo' => 'cliente', 'cliente' => $cliente];
            }
        }

        foreach ($clientes->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE) as $cliente) {
            if (! isset($usados[$cliente->id])) {
                $itens[] = ['tipo' => 'cliente', 'cliente' => $cliente];
            }
        }

        return $itens;
    }

    /**
     * Limpa o que veio da tela antes de guardar: só números e pastas bem
     * formadas, nomes curtos, nada de pasta dentro de pasta.
     */
    public static function normalizar(array $layout): array
    {
        $limpo = [];

        foreach (array_slice($layout, 0, 1000) as $entrada) {
            if (is_numeric($entrada)) {
                $limpo[] = (int) $entrada;

                continue;
            }

            if (! is_array($entrada)) {
                continue;
            }

            $id = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($entrada['pasta'] ?? ''));
            $nome = trim(preg_replace('/\s+/', ' ', strip_tags((string) ($entrada['nome'] ?? ''))));

            $limpo[] = [
                'pasta' => $id !== '' ? substr($id, 0, 40) : 'p_'.Str::lower(Str::random(8)),
                'nome' => mb_substr($nome !== '' ? $nome : 'Pasta', 0, self::MAX_NOME),
                'aberta' => (bool) ($entrada['aberta'] ?? true),
                'clientes' => array_values(array_map('intval', array_filter((array) ($entrada['clientes'] ?? []), 'is_numeric'))),
            ];
        }

        return $limpo;
    }
}
