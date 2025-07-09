<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        $user = $this->route('group');
        return [
            'full_name' => [
                'required',
                'string',
                'min:5',
                'max:255',
                // A lógica para ignorar o grupo atual funciona perfeitamente aqui.
                //Rule::unique('users', 'name')->ignore($user->_id, '_id')
            ],
            'username' => [
                'required',
                'string',
                'min:5',
                'max:64',
                Rule::unique('users','username')->ignore($this->id, 'id'),
            ],
             'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users','email')->ignore($this->id, 'id'),
            ],
            'userGroups' => 'sometimes|array',
            'userGroups.*' => 'string|exists:mongodb.groups,_id'
        ];
    }
}
