<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Str;

class UpdateGroupRequest extends FormRequest
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

    protected function prepareForValidation(): void
    {
        // Verifica se o campo 'name' foi enviado na requisição
        if ($this->name) {
            $this->merge([
                // Pega o valor de 'name', converte para maiúsculas e o substitui na requisição
                'name' => Str::upper($this->name),

                // Se quisesse fazer o mesmo para a descrição, seria assim:
                // 'description' => Str::upper($this->description),
            ]);
        }
    }
    public function rules(): array
    {
        // this->route('group') pega o modelo 'group' injetado na rota.
        $group = $this->route('group');

        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                // A lógica para ignorar o grupo atual funciona perfeitamente aqui.
                Rule::unique('groups', 'name')->ignore($group->_id, '_id')
            ],
            'description' => [
                'required',
                'string',
                'min:10',
                'max:255',
            ],
            'user_ids' => 'sometimes|array',
            'user_ids.*' => 'string|exists:mongodb.users,_id'
        ];
    }
}
