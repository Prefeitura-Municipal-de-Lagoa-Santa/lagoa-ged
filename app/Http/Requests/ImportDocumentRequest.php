<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportDocumentRequest extends FormRequest
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
            'csv_file' => 'required|file|mimes:csv,txt|max:20480', // Max 20MB (20480 KB)
            'read_group_ids' => 'nullable|array',
            'read_group_ids.*' => 'string', // IDs do MongoDB são strings, não esqueça
            'write_group_ids' => 'nullable|array',
            'write_group_ids.*' => 'string',
            'deny_group_ids' => 'nullable|array',
            'deny_group_ids.*' => 'string',
        ];
    }
    public function messages()
    {
        return [
            'csv_file.required' => 'Por favor, selecione um arquivo CSV para importar.',
            'csv_file.file' => 'O campo de arquivo CSV deve ser um arquivo válido.',
            'csv_file.mimes' => 'O arquivo deve ser do tipo CSV ou TXT.',
            'csv_file.max' => 'O tamanho máximo do arquivo CSV é de 10 MB.',
            'read_group_ids.array' => 'Os grupos de leitura devem ser selecionados como um array.',
            'write_group_ids.array' => 'Os grupos de escrita devem ser selecionados como um array.',
            'deny_group_ids.array' => 'Os grupos bloqueados devem ser selecionados como um array.',
            // Você pode adicionar mensagens personalizadas para outras regras também.
        ];
    }
}
