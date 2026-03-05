<?php

namespace App\Http\Requests\Charities;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCharityTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'charity_type_id' => ['required', 'exists:charity_types,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'payer_name' => ['required', 'string', 'max:255'],
            'payer_phone' => ['nullable', 'string', 'max:50'],
            'payer_email' => ['nullable', 'email', 'max:255'],
            'payment_method' => ['required', 'in:cash,transfer,qris'],
            'charity_payment_id' => ['nullable', 'required_if:payment_method,transfer,qris', 'exists:charity_payments,id'],
            'is_package' => ['nullable', 'boolean'],
            'use_same_package_amount' => ['exclude_unless:is_package,1,true', 'nullable', 'boolean'],
            'is_input_family_members' => ['exclude_unless:is_package,1,true', 'nullable', 'boolean'],
            'package_amount_each' => ['exclude_unless:is_package,1,true', 'nullable', 'required_if:use_same_package_amount,1,true', 'numeric', 'gt:0'],
            'representative_total_money' => ['exclude_unless:is_package,1,true', 'nullable', 'numeric', 'gt:0'],
            'representative_total_rice' => ['exclude_unless:is_package,1,true', 'nullable', 'numeric', 'gt:0'],
            'package_members_count' => ['exclude_unless:is_package,1,true', 'required_if:is_package,1,true', 'integer', 'min:1'],
            'package_payers' => ['exclude_unless:is_package,1,true', 'nullable', 'array'],
            'package_payers.*.payer_name' => ['exclude_unless:is_package,1,true', 'required_if:is_input_family_members,1,true', 'string', 'max:255'],
            'package_payers.*.payer_phone' => ['exclude_unless:is_package,1,true', 'nullable', 'string', 'max:50'],
            'package_payers.*.payer_email' => ['exclude_unless:is_package,1,true', 'nullable', 'email', 'max:255'],
            'package_payers.*.is_money' => ['exclude_unless:is_package,1,true', 'nullable', 'boolean'],
            'package_payers.*.is_rice' => ['exclude_unless:is_package,1,true', 'nullable', 'boolean'],
            'package_payers.*.multiplier_count' => ['exclude_unless:is_package,1,true', 'nullable', 'integer', 'min:1'],
            'package_payers.*.total_money' => ['exclude_unless:is_package,1,true', 'nullable', 'numeric', 'gt:0'],
            'package_payers.*.total_rice' => ['exclude_unless:is_package,1,true', 'nullable', 'numeric', 'gt:0'],
            'package_payers.*.notes' => ['exclude_unless:is_package,1,true', 'nullable', 'string', 'max:1000'],
            'status' => ['nullable', 'string', 'max:50'],
            'received_by' => ['nullable', 'exists:users,id'],
            'amount_money' => ['nullable', 'numeric', 'gt:0'],
            'amount_rice' => ['nullable', 'numeric', 'gt:0'],
            'total_money' => ['nullable', 'numeric', 'gt:0'],
            'total_rice' => ['nullable', 'numeric', 'gt:0'],
            'multiplier_count' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'detail' => ['nullable', 'array'],
            'detail.is_rice' => ['nullable', 'boolean'],
            'detail.is_money' => ['nullable', 'boolean'],
        ];
    }
}
