<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name' => [
                'required',
                'string',
                'min:5',
                'max:255'
            ],
            'username' => [
                'required',
                'string',
                'min:5',
                'max:64',
                'unique:users,username'
            ], // Usuário deve ser único
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users,email'
            ], // E-mail deve ser único
            'password' => [
                'required',
                Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols(),
                //'string',
                //'min:8',
                //'confirmed',
                //'regex:/[a-z]/',      // letra minúscula
                //'regex:/[A-Z]/',      // letra maiúscula
                //'regex:/[0-9]/',      // número
                //'regex:/[@$!%*#?&]/', // caractere especial
            ], // Senha obrigatória na criação
            'userGroups' => 'sometimes|array',
            'userGroups.*' => 'string|exists:mongodb.groups,_id'
        ];
    }
    protected function prepareForValidation(): void
    {
        // Certifique-se de que userGroups é um array, mesmo que venha como null ou string vazia
        if (isset($this->userGroups) && is_string($this->userGroups)) {
            $this->merge([
                'userGroups' => json_decode($this->userGroups, true) ?? [],
            ]);
        }
        if (!isset($this->userGroups)) {
            $this->merge([
                'userGroups' => [],
            ]);
        }
    }
}
