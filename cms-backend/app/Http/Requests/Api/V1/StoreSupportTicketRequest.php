<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route is behind auth:sanctum; any authenticated customer may open a ticket.
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'body'    => ['required', 'string', 'max:5000'],
            'game'    => ['required', Rule::in(['valorant', 'fortnite', 'legends'])],
        ];
    }
}
