<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'file' => 'sometimes|file|image|required|mimes:doc,pdf,docx,ppt,csv,txt,xlx,xls|max:256',
        ];
    }

    public function messages()
    {
        return [
            'file.mimes' => 'El archivo anexado no coincide con los formatos permitidos'
        ];
    }
}
