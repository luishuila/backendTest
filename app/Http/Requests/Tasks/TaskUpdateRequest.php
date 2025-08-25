<?php

namespace App\Http\Requests\Tasks;

use App\Domain\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class TaskUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'       => ['required','string','max:150','min:3'],
            'description' => ['nullable','string','max:1000','min:2'],
            'status'      => ['required', new Enum(TaskStatus::class)],
        ];
    }
}
