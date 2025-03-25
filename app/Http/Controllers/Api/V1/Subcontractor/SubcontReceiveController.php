<?php

namespace App\Http\Controllers\Api\V1\Subcontractor;

use Carbon\Carbon;
use App\Trait\ErrorLog;
use App\Trait\ResponseApi;
use App\Trait\AuthorizationRole;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Subcontractor\SubcontTransaction;
use App\Service\Subcontractor\SubcontCreateTransaction;
use App\Service\Subcontractor\SubcontUpdateTransaction;
use App\Http\Resources\Subcontractor\SubcontReviewDetailResource;
use App\Http\Resources\Subcontractor\SubcontReviewHeaderResource;
use App\Http\Requests\Subcontractor\SubcontReviewTransactionRequest;

class SubcontReceiveController extends Controller
{
    /**
     * -------TRAIT---------
     * Mandatory:
     * 1. ResponseApi = Response api should use ResponseApi trait template
     * 2. AuthorizationRole = for checking permissible user role
     * 3. ErrorLog = Make internal log and return RequestId if the logic error was critical
     */
    use AuthorizationRole, ErrorLog, ResponseApi;

    public function __construct(
        protected SubcontCreateTransaction $subcontCreateTransaction,
        protected SubcontUpdateTransaction $subcontUpdateTransaction,
    ) {
    }

    /**
     * Get header subcont review data
     * @param string $bpCode
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function reviewHeader(string $bpCode)
    {
        if ($this->permissibleRole('4', '9')) {
            $bpCode;
        } else {
            return $this->returnResponseApi(false, 'User Unauthorized', null, 401);
        }

        $data = SubcontTransaction::select('delivery_note', 'status', 'transaction_date', 'transaction_time', 'response')
            ->whereIn('transaction_type', ['Outgoing', 'outgoing'])
            ->whereNull('response')
            ->whereHas('subItem', function ($query) use ($bpCode) {
                $query->where('bp_code', $bpCode);
            })
            ->distinct()
            ->get();
        if (empty($data)) {
            return $this->returnResponseApi(true, "There is no Subcont Review", [], 200);
        }

        return $this->returnResponseApi(
            true,
            'Display List Subcont Review Header Successfully',
            SubcontReviewHeaderResource::collection($data),
            200
        );
    }

    /**
     * Get detail subcont review data
     * @param string $noDn
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function reviewDetail(string $noDn)
    {
        if ($this->permissibleRole('4', '9')) {
            $noDn;
        } else {
            return $this->returnResponseApi(false, 'User Unauthorized', null, 401);
        }

        // Query get detail
        $getDetail = SubcontTransaction::with('subItem')
            ->where('delivery_note', $noDn)
            ->whereIn('transaction_type', ['Outgoing', 'outgoing'])
            ->get();

        // query get and format date & time
        $firstRecord = $getDetail->first();
        $formatDate = Carbon::parse($firstRecord->transaction_date)->format('Y-m-d');
        $formatTime = Carbon::parse($firstRecord->transaction_time)->format('h:i');
        $getDateTime = "$formatDate $formatTime";

        return $this->returnResponseApi(
            true,
            'Display List Subcont Review Detail Successfully',
            [
                'dn_number' => $noDn,
                'date_time' => $getDateTime,
                'status' => $getDetail->first()->status,
                'status_confirm' => false,
                'detail' => SubcontReviewDetailResource::collection($getDetail),
            ],
            200
        );
    }

    /**
     * Update subcont review data
     * @param \App\Http\Requests\Subcontractor\SubcontReviewTransactionRequest $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function reviewUpdate(SubcontReviewTransactionRequest $request)
    {
        try {
            // Validated request
            $validated = $request->validated();

            foreach ($validated['data'] as $data) {
                DB::transaction(function () use ($data) {
                    // Update record transaction after review
                    $this->subcontUpdateTransaction->updateTransaction(
                        $data['sub_transaction_id'],
                        $data['actual_qty_ok'],
                        $data['actual_qty_ng'],
                    );

                    // Create transaction diffrence review
                    $this->subcontCreateTransaction->createSubcontTransactionReview(
                        $data['sub_transaction_id'],
                        $data['sub_item_id'],
                        $data['actual_qty_ok'],
                        $data['actual_qty_ng'],
                    );
                });
            }

        } catch (\Throwable $th) {
            $requestId = $this->logicError('Error Updating Subcont Review', $th->getMessage(), $th->getFile(), $th->getLine());
            return $this->returnResponseApi(false, "Internal Error while Updating Subcont Review (Request_id:$requestId)", null, 500);
        }

        return $this->returnResponseApi(true, 'System Transaction Review Diffrence Successfuly', null, 200);
    }
}
