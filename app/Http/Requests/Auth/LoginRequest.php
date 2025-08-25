<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use App\Application\Dto\Auth\LoginDto; 

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:6'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }


    public function toDto(): LoginDto
    {
        return new LoginDto(
            email: (string) $this->input('email'),
            password: (string) $this->input('password'),
            remember: (bool) $this->boolean('remember')
        );
    }
}
