<?php

namespace App\Http\Requests\Subcontractor;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class SubcontImportStockItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->role == 4 || 9;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'data' => 'required|array',
            'data.*.bp_code' => 'required|string',
            'data.*.part_number' => 'required|string',
            'data.*.fresh_unprocess_incoming_items' => 'required|integer|min:0',
            'data.*.fresh_ready_delivery_items' => 'required|integer|min:0',
            'data.*.fresh_ng_items' => 'required|integer|min:0',
            'data.*.replating_unprocess_incoming_items' => 'required|integer|min:0',
            'data.*.replating_ready_delivery_items' => 'required|integer|min:0',
            'data.*.replating_ng_items' => 'required|integer|min:0',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Bp code
            'data.*.bp_code.required' => 'The bp_code field is required.',
            'data.*.bp_code.string' => 'The bp_code field must be a string.',

            // Part number
            'data.*.part_number.required' => 'The part_number field is required.',
            'data.*.part_number.string' => 'The part_number field must be a string.',

            // Fresh unprocess incoming
            'data.*.fresh_unprocess_incoming_items.required' => 'The fresh_unprocess_incoming_items field is required.',
            'data.*.fresh_unprocess_incoming_items.integer' => 'The fresh_unprocess_incoming_items field must be an integer.',
            'data.*.fresh_unprocess_incoming_items.min' => 'The fresh_unprocess_incoming_items field must be at least 0.',

            // Fresh ready delivery
            'data.*.fresh_ready_delivery_items.required' => 'The fresh_ready_delivery_items field is required.',
            'data.*.fresh_ready_delivery_items.integer' => 'The fresh_ready_delivery_items field must be an integer.',
            'data.*.fresh_ready_delivery_items.min' => 'The fresh_ready_delivery_items field must be at least 0.',

            // Fresh NG
            'data.*.fresh_ng_items.required' => 'The fresh_ng_items field is required.',
            'data.*.fresh_ng_items.integer' => 'The fresh_ng_items field must be an integer.',
            'data.*.fresh_ng_items.min' => 'The fresh_ng_items field must be at least 0.',

            // Replating unprocess incoming
            'data.*.replating_unprocess_incoming_items.required' => 'The replating_unprocess_incoming_items field is required.',
            'data.*.replating_unprocess_incoming_items.integer' => 'The replating_unprocess_incoming_items field must be an integer.',
            'data.*.replating_unprocess_incoming_items.min' => 'The replating_unprocess_incoming_items field must be at least 0.',

            // Replating Ready delivery
            'data.*.replating_ready_delivery_items.required' => 'The replating_ready_delivery_items field is required.',
            'data.*.replating_ready_delivery_items.integer' => 'The replating_ready_delivery_items field must be an integer.',
            'data.*.replating_ready_delivery_items.min' => 'The replating_ready_delivery_items field must be at least 0.',

            // Replating NG
            'data.*.replating_ng_items.required' => 'The replating_ng_items field is required.',
            'data.*.replating_ng_items.integer' => 'The replating_ng_items field must be an integer.',
            'data.*.replating_ng_items.min' => 'The replating_ng_items field must be at least 0.',
        ];
    }

    // Failed validation response
    protected function failedValidation($validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
