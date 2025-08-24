<?php

namespace App\Http\Requests\Tasks;

use App\Domain\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class TaskStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'       => ['required','string','max:150'],
            'description' => ['nullable','string','max:1000'],
            'status'      => ['sometimes', new Enum(TaskStatus::class)],
        ];
    }
}
