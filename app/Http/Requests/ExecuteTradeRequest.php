<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExecuteTradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'to_account_id' => ['nullable', 'integer', 'exists:accounts,id', 'different:from_account_id'],
            'from_currency' => ['required', 'string', 'size:3'],
            'to_currency' => ['required', 'string', 'size:3', 'different:from_currency'],
            'from_amount' => ['required', 'numeric', 'min:0.00000001'],
            'side' => ['required', 'in:BUY,SELL'],
            'client_order_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
