<?php

namespace App\Http\Requests\Subcontractor;

use App\Models\Subcontractor\SubcontItem;
use App\Models\Users\BusinessPartner;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class SubcontItemRequest extends FormRequest
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
            'data.*.bp_code' => 'required|string|max:50',
            'data.*.part_number' => 'required|string|max:50',
            'data.*.part_name' => 'string|max:255',
            'data.*.old_part_name' => 'string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            // bp_code
            'data.*.bp_code.required' => 'The bp_code is required.',
            'data.*.bp_code.string' => 'The bp_code must be a valid string.',
            'data.*.bp_code.max' => 'The bp_code cannot be longer than 50 characters.',

            // part_number
            'data.*.part_number.required' => 'The part number is required.',
            'data.*.part_number.string' => 'The part number must be a valid string.',
            'data.*.part_number.max' => 'The part number cannot be longer than 50 characters.',

            // part_name
            'data.*.part_name.required' => 'The part name is required.',
            'data.*.part_name.string' => 'The part name must be a valid string.',
            'data.*.part_name.max' => 'The part name cannot be longer than 255 characters.',

            // old_part_name
            'data.*.old_part_name.required' => 'The  old part name is required.',
            'data.*.old_part_name.string' => 'The  old part name must be a valid string.',
            'data.*.old_part_name.max' => 'The  old part name cannot be longer than 255 characters.',
        ];
    }

    // Check if part_number already exist
    // Call the withValidation method (method injection from formRequest.php. it's core of framework method)
    protected function withValidator()
    {
        $this->duplicateCheck();
        $this->checkBpCode();
    }

    // Check bp_code
    private function checkBpCode()
    {
        $getRequest = $this->input('data');
        $errorMessage = [];

        foreach ($getRequest as $key => $item) {
            $checkBpCode = BusinessPartner::where('bp_code', $item['bp_code'])
                ->exists();

            if ($checkBpCode == false) {
                $errorMessage[] = [
                    "item.$key" => "This Partner Code \"{$item['bp_code']}\" Doesn't Already Exist",
                ];
            }
        }

        if (! empty($errorMessage)) {
            throw new HttpResponseException(
                response()->json([
                    'status' => false,
                    'message' => 'Partner Code Doesn\'t Exists.',
                    'errors' => $errorMessage,
                ], 422)
            );
        }
    }

    // Duplicate logic
    private function duplicateCheck()
    {
        $getRequest = $this->input('data');
        $errorMessage = [];

        foreach ($getRequest as $key => $item) {
            $checkItem = SubcontItem::where('bp_code', $item['bp_code'])
                ->where('item_code', $item['part_number'])
                ->exists();

            if ($checkItem == true) {
                $errorMessage[] = [
                    "item.$key" => "This {$item['part_number']} Part Number Already Exist",
                ];
            }
        }

        if (! empty($errorMessage)) {
            throw new HttpResponseException(
                response()->json([
                    'status' => false,
                    'message' => 'Duplicate Part Number.',
                    'errors' => $errorMessage,
                ], 400)
            );
        }
    }
}
