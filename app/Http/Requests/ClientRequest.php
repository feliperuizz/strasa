<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Autorização fina é feita via Policy no controller.
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'segment' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image'],
            'color' => ['nullable', 'string', 'max:20'],
            'bg_type' => ['nullable', 'string', 'in:default,color,gradient'],
            'bg_color' => ['nullable', 'string', 'max:20'],
            'bg_gradient' => ['nullable', 'string', 'max:500'],
        ];
    }
}
