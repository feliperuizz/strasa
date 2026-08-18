<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validação de criação/edição de tarefa (post).
 * Regras de tenant (coluna/responsável pertencem à empresa) são reforçadas
 * pelo CompanyScope global ao resolver os IDs no controller.
 */
class TaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // No update (rota com {task}) o slideover não tem seletor de coluna, então
        // column_id é opcional e a coluna atual é mantida. No store (criação) se não for enviado,
        // o controller cuidará de colocar na primeira coluna disponível.
        
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'column_id' => ['nullable', 'integer', 'exists:columns,id'],
            'assignee_id' => ['nullable', 'integer', 'exists:users,id'],
            'content_type' => ['nullable', Rule::in(array_keys(Task::CONTENT_TYPES))],
            'publish_date' => ['nullable', 'date'],
            'publish_time' => ['nullable', 'date_format:H:i'],
            'description' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
        ];
    }
}
