<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BatchUpdateDocumentPermissionsRequest extends FormRequest
{
    public function authorize()
    {
        // Ajuste conforme sua lógica de permissão
        return $this->user() !== null;
    }

    public function rules()
    {
        return [
            'document_ids' => ['required', 'array', 'min:1'],
            'document_ids.*' => ['string'],
            'read_group_ids' => ['array'],
            'read_group_ids.*' => ['string'],
            'write_group_ids' => ['array'],
            'write_group_ids.*' => ['string'],
            'preview' => ['boolean'],
        ];
    }
}
