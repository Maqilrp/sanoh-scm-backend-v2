<?php

namespace App\Http\Controllers\Api\V1\DeliveryNote;

use App\Http\Resources\DeliveryNote\DnDetailListResource;
use Carbon\Carbon;
use App\Trait\ResponseApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\DeliveryNote\DnDetail;
use App\Models\DeliveryNote\DnHeader;
use App\Http\Resources\DeliveryNote\DnDetailResource;
use App\Service\DeliveryNote\DeliveryNoteUpdateTransaction;
use App\Http\Requests\DeliveryNote\UpdateDeliveryNoteRequest;

class DnDetailController extends Controller
{
    /**
     * -------TRAIT---------
     * Mandatory:
     * 1. ResponseApi = Response api should use ResponseApi trait template
     */
    use ResponseApi;

    public function __construct(
        protected DeliveryNoteUpdateTransaction $deliveryNoteUpdateTransaction,
    ) {
    }

    /**
     * Get list of detail DN based on no_dn
     * @param mixed $no_dn
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getListDetailDnUser($no_dn)
    {
        $dnDetailData = DnDetail::with('dnOutstanding')
            ->where('no_dn', $no_dn)
            ->orderBy('plan_delivery_date', direction: 'asc')
            ->orderBy('dn_line', 'asc')
            ->get();
        if ($dnDetailData->isEmpty()) {
            return $this->returnResponseApi(false, 'DN detail not found', null, 404);
        }

        $dnHeader = $dnDetailData->first()->dnHeader;
        if (!$dnHeader) {
            return $this->returnResponseApi(false, 'DN Header not found', null, 404);
        }

        $date = Carbon::parse($dnHeader->plan_delivery_date)->format('Y-m-d');
        $time = Carbon::parse($dnHeader->plan_delivery_time)->format('H:i');
        $dateTime = "$date $time";

        // Logic to get value confirm_at based on wave/sequence
        $confirmation = [];
        $uniqueTimestamp = [];
        foreach ($dnDetailData as $dnDetail) {
            if ($dnDetail->dnOutstanding) {
                $groupedWave = $dnDetail->dnOutstanding->groupBy('wave');
                foreach ($groupedWave as $wave => $group) {
                    $firstItem = $group->first();

                    $timestamp = "$firstItem->add_outstanding_date $firstItem->add_outstanding_time";

                    // Prevent duplication timestamp
                    if (!in_array($timestamp, $uniqueTimestamp)) {
                        $uniqueTimestamp[] = $timestamp;
                        $confirmation['confirm_' . ($wave + 1) . '_at'] = $timestamp;
                    }
                }
            }
        }
        return $this->returnResponseApi(
            true,
            'Display List DN Detail Successfully',
            new DnDetailListResource(
                $dnHeader,
                $dnDetailData,
                $dateTime,
                $confirmation
            ),
            200
        );
    }

    //test
    public function indexAll()
    {
        // Fetch PO details based on the provided po_no
        $data_podetail = DnDetail::with('dnHeader')->get();

        if ($data_podetail->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'PO Detail Not Found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Display List PO Detail Successfully',
            'data' => DnDetailResource::collection($data_podetail),
        ], 200);
    }

    // Show edit form DNDetail
    public function edit($dnDetailNo)
    {
        $data = DnDetail::with('dnOutstanding')->findOrFail($dnDetailNo);

        return new DnDetailResource($data);
    }

    // Update data to database
    public function update(UpdateDeliveryNoteRequest $request)
    {
        try {
            $result = $this->deliveryNoteUpdateTransaction->updateQuantity($request->validated());
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'error' => $th->getMessage() . ' (On line ' . $th->getLine() . ')',
            ], 500);
        }

        return $result;
    }
}
