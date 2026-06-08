<?php

namespace App\Http\Requests\Api\V1;

use App\Models\SupportTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupportTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Customers can only close their own ticket from the storefront. The
            // 'new'/'open' transitions are staff-driven, but the rule accepts the
            // full set so the same endpoint can serve a reopen later.
            'status' => ['required', Rule::in([
                SupportTicket::STATUS_NEW,
                SupportTicket::STATUS_OPEN,
                SupportTicket::STATUS_CLOSED,
            ])],
        ];
    }
}
