<?php

namespace App\Http\Requests\Api\Purchase;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
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
        $company = company();
        $loggedUser = auth('api')->user();

        $rules = [
            'user_id' => 'required',
            'order_status' => 'required',
            'product_items'    => 'required',
            'order_date'    => 'required',
        ];

        $allPayments = $this->input('all_payments', []);
        if (!is_array($allPayments)) {
            $allPayments = [];
        }

        if ($this->has('all_payments') && count($this->all_payments) > 0) {
            $allPayments = collect($this->all_payments);


            foreach ($allPayments as $key => $payment) {
                if (isset($payment['amount']) && $payment['amount'] > 0) {
                    if (!isset($payment['payment_mode_id']) || $payment['payment_mode_id'] == '') {
                        $rules['paid_payment'] = 'required';
                    }
                }
            }
        }

        if ($this->invoice_number && $this->invoice_number != '') {
            $rules['invoice_number'] = [
                'required',
                Rule::unique('orders', 'invoice_number')->where(function ($query) use ($company) {
                    return $query->where('company_id', $company->id);
                })
            ];
        }

        return $rules;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'paid_payment.required' => 'Payment mode is required when paid payment is greater than zero.',
        ];
    }
}
