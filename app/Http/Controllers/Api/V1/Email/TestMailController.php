<?php

namespace App\Http\Controllers\Api\V1\Email;

use App\Http\Controllers\Controller;
use App\Mail\PoResponseSupplier;
use App\Models\PurchaseOrder\PoHeader;
use App\Models\Users\User;
use Illuminate\Support\Facades\Mail;

class TestMailController extends Controller
{
    //
    public function mail()
    {
        // Get data User
        $user = User::where('role', 1)->get(['bp_code', 'email']);

        // Get PO open based of bp_code
        foreach ($user as $data) {

            $po_header = PoHeader::with('user')
                ->where('supplier_code', $data->bp_code)
                ->whereIn('po_status', ['Sent', 'Open'])
                ->get();

            // Store/format the return value of po_header into collection map function
            $collection = $po_header->map(function ($data) {
                $user = $data->user;

                return [
                    'bp_code' => $user ? $user->bp_code : 'User Data Not Found',
                    'email' => $user ? $user->email : 'Data Email Data Not Found',
                    'po_no' => $data ? $data->po_no : 'PO Data Not Found',
                ];
            });

            \Log::info('Generated Collection:', $collection->toArray());
            dd($collection);

            Mail::to($data->email)->send(new PoResponseSupplier(po_header: $collection));
        }

        return response()->json(['data' => 'berhasil']);
    }
}
