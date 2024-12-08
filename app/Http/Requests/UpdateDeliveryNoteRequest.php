<?php

namespace App\Http\Requests;

use App\Models\DeliveryNote\DN_Detail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateDeliveryNoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->role == 5 || 6 || 7;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'no_dn' => 'required|string',
            'updates' => 'required|array',
            'updates.*.dn_detail_no' => 'required|integer|exists:dn_detail,dn_detail_no',
            'updates.*.qty_confirm' => 'required|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'no_dn.required' => 'Delivery note number is required',
            'no_dn.string' => 'Delivery note number must be a string',
            'updates.required' => 'Updates data is required',
            'updates.array' => 'Updates must be an array',
            'updates.*.dn_detail_no.required' => 'Delivery note detail number is required',
            'updates.*.dn_detail_no.integer' => 'Delivery note detail number must be an integer',
            'updates.*.dn_detail_no.exists' => 'Delivery note detail number does not exist',
            'updates.*.qty_confirm.required' => 'Quantity confirmation is required',
            'updates.*.qty_confirm.integer' => 'Quantity confirmation must be an integer',
            'updates.*.qty_confirm.min' => 'Quantity confirmation must be at least 0',
        ];
    }

    // Failed validation response
    protected function failedValidation($validator)
    {
        throw new HttpResponseException(
            response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422)
        );
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->checkQuantity($validator);
        });
    }

    // Check qty_confirm
    protected function checkQuantity($validator){
        $getRequestUpdate = $this->input('updates');

        // dd($getRequestUpdate);
        foreach ($getRequestUpdate as $i) {
            $getData = DN_Detail::select('dn_qty','receipt_qty','dn_snp')
                    ->where('no_dn', $this->no_dn)
                    ->first();

            // if $getData not null
            if ($getData) {
                $currentReceipt = $getData->receipt_qty ?? 0;
                $dnQty = $getData->dn_qty;
                $dnSnp = $getData->dn_snp;

                // Check qty_confirm must be multiple of dn_snp
                if (($i['qty_confirm'] % $dnSnp) != 0) {
                    $validator->errors()->add(
                        "updates.*.qty_confirm",
                        "Qty Confirm must be multiple of Qty Label"
                    );
                }

                // Check qty_confirm can't exceed qty_requested
                if (($i['qty_confirm'] + $currentReceipt) > $dnQty) {
                    $validator->errors()->add(
                        "updates.{$i['dn_detail_no']}.qty_confirm}",
                        "Qty Confirm exceeds Qty Requested for DN: {$i['dn_detail_no']}"
                    );
                }
            } else {
                throw new \Exception("Error when Checking qty_confirm Request", 500);
            }
        }
    }
}
