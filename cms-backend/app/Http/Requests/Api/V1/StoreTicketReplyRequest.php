<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body'         => ['required', 'string', 'max:5000'],
            'close_ticket' => ['sometimes', 'boolean'],
        ];
    }
}
