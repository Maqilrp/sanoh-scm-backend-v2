<?php

namespace App\Http\Controllers\Api\V1\Subcontractor;

use App\Service\Subcontractor\SubcontCreateStock;
use Auth;
use App\Trait\ResponseApi;
use Illuminate\Http\Request;
use App\Trait\AuthorizationRole;
use App\Models\Subcontractor\SubcontItem;
use App\Models\Subcontractor\SubcontItemErp;
use App\Service\Subcontractor\SubcontGetItem;
use App\Models\Subcontractor\SubcontTransaction;
use App\Service\Subcontractor\SubcontCreateItem;
use App\Service\Subcontractor\SubcontDeleteItem;
use App\Service\Subcontractor\SubcontUpdateItem;
use App\Service\Subcontractor\SubcontGetListItem;
use App\Service\Subcontractor\SubcontGetListItemErp;
use App\Service\Subcontractor\SubcontGetTransaction;
use App\Service\Subcontractor\SubcontImportStockItem;
use App\Http\Requests\Subcontractor\SubcontItemRequest;
use App\Service\Subcontractor\SubcontCreateTransaction;
use App\Http\Resources\Subcontractor\SubcontItemResource;
use App\Http\Requests\Subcontractor\SubcontItemUpdateRequest;
use App\Http\Resources\Subcontractor\SubcontListItemResource;
use App\Http\Requests\Subcontractor\SubcontTransactionRequest;
use App\Http\Resources\Subcontractor\SubcontAllListItemResource;
use App\Http\Resources\Subcontractor\SubcontListItemErpResource;
use App\Http\Resources\Subcontractor\SubcontTransactionResource;
use App\Http\Requests\Subcontractor\SubcontImportStockItemRequest;

class SubcontController
{
    /**
     * -------TRAIT---------
     * Mandatory:
     * 1. ResponseApi = Response api should use ResponseApi trait template
     * 2. AuthorizationRole = for checking permissible user role
     */
    use AuthorizationRole, ResponseApi;

    public function __construct(
        protected SubcontGetItem $subcontGetItem,
        protected SubcontGetTransaction $subcontGetTransaction,
        protected SubcontCreateItem $subcontCreateItem,
        protected SubcontCreateTransaction $subcontCreateTransaction,
        protected SubcontGetListItem $subcontGetListItem,
        protected SubcontGetListItemErp $subcontGetListItemErp,
        protected SubcontUpdateItem $subcontUpdateItem,
        protected SubcontDeleteItem $subcontDeleteItem,
        protected SubcontImportStockItem $subcontImportStockItem,
        protected SubcontCreateStock $subcontCreateStock,
    ) {
    }

    /**
     * Get list item from ERP
     *
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getListItemErp()
    {
        $data = SubcontItemErp::select('item', 'description', 'old_item')->get();
        if ($data->isEmpty()) {
            return $this->returnResponseApi(true, 'Subcont Item Data Not Found', [], 200);
        }

        return $this->returnResponseApi(
            true,
            'Display List Subcont Item Successfully',
            SubcontListItemErpResource::collection($data),
            200
        );
    }

    /**
     * Get list item user
     *
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getListItem($bpCode = null)
    {
        if ($this->permissibleRole('6', '8')) {
            $bpCode = Auth::user()->bp_code;
        } elseif ($this->permissibleRole('4', '9')) {
            $bpCode;
        }

        $data = SubcontItem::select('item_code', 'item_name', 'item_old_name')
            ->where('bp_code', $bpCode)
            ->where('status', '1')
            ->orderBy('item_name', 'asc')
            ->get();
        if ($data->isEmpty()) {
            return $this->returnResponseApi(true, 'Subcont Item Data Not Found', [], 200);
        }

        return $this->returnResponseApi(
            true,
            'Display List Subcont Item Successfully',
            SubcontListItemResource::collection($data),
            200
        );
    }

    /**
     * Get list item for admin subcont (feat: manage-items)
     * @param mixed $bpCode
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getListItemAdmin($bpCode = null)
    {
        $data = SubcontItem::select('sub_item_id', 'item_code', 'item_name', 'item_old_name', 'status')
            ->where('bp_code', $bpCode)
            ->orderBy('item_name', 'asc')
            ->get();
        if ($data->isEmpty()) {
            return $this->returnResponseApi(true, 'Subcont Item Data Not Found', [], 200);
        }

        return $this->returnResponseApi(
            true,
            'Display List Subcont Item Successfully',
            SubcontAllListItemResource::collection($data),
            200
        );
    }

    /**
     * Get subcont stock based on BP Code
     * @param mixed $bpCode
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getStock($bpCode = null)
    {
        if ($this->permissibleRole('6', '8')) {
            $bpCode = Auth::user()->bp_code;
        } elseif ($this->permissibleRole('4', '9')) {
            $bpCode;
        }

        $data = SubcontItem::with('subStock')
            ->where('bp_code', $bpCode)
            ->orderBy('item_code', 'asc')
            ->get();
        if ($data->isEmpty()) {
            return $this->returnResponseApi(true, 'Subcont Item Data Not Found', [], 200);
        }

        return $this->returnResponseApi(
            true,
            'Display List Subcont Item Successfully',
            SubcontItemResource::collection($data),
            200
        );
    }

    /**
     * Get subcont transaction based on bp_code, start_date, and end_date.
     * start and end date is the range of subcont transaction you want return
     * @param \Illuminate\Http\Request $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function getListTrans(Request $request)
    {
        // Declare variable
        $bpCode = $request->bp_code ?? null;
        $startDate = $request->start_date ?? null;
        $endDate = $request->end_date ?? null;

        if ($this->permissibleRole('6', '8')) {
            $bpCode = Auth::user()->bp_code;
        } elseif ($this->permissibleRole('4', '9')) {
            $bpCode;
        }

        $data = SubcontTransaction::whereHas('subItem', function ($q) use ($bpCode) {
            $q->where('bp_code', $bpCode);
        })
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('transaction_time', 'desc')
            ->get();
        if ($data->isEmpty()) {
            return $this->returnResponseApi(true, 'Subcont Transaction Data Not Found', [], 200);
        }

        return $this->returnResponseApi(
            true,
            'Display List Subcont Transaction Successfully',
            SubcontTransactionResource::collection($data),
            200
        );
    }

    /**
     * Create new item
     *
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function createItem(SubcontItemRequest $request)
    {
        $request->validated();

        foreach ($request['data'] as $d) {
            $item = SubcontItemErp::select('description', 'old_item')
                ->where('item', $d['part_number'])
                ->first();
            if (! $item) {
                return $this->returnResponseApi(false, 'Item Not Found', [], 404);
            }

            $create = SubcontItem::create([
                'bp_code' => $d['bp_code'],
                'item_code' => $d['part_number'],
                'item_name' => $item['description'] ?? null,
                'item_old_name' => $item['old_item'] ?? null,
                'status' => '1',
            ]);

            // Check stock record availability
            $this->subcontCreateStock->createAndCheckStock($d['part_number'], $create->sub_item_id);
        }

        return $this->returnResponseApi(true, 'Data Successfull Stored', [], 200);
    }

    /**
     * Create new transaction
     *
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function createTransaction(SubcontTransactionRequest $request)
    {
        $result = $this->subcontCreateTransaction->createTransactionSubcont($request->validated());

        // Return response
        if ($result == true) {
            return response()->json([
                'status' => true,
                'message' => 'Data Successfully Stored',
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Request data format error',
            ], 422);
        }

    }

    /**
     * Summary of updateItem
     *
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function updateItem(SubcontItemUpdateRequest $request)
    {
        try {
            $result = $this->subcontUpdateItem->updateItem($request->validated());
        } catch (\Throwable $ex) {
            return response()->json([
                'error' => $ex->getMessage(),
            ], 500);
        }

        return $result;
    }

    public function deleteItem(SubcontItemUpdateRequest $request)
    {
        try {
            $result = $this->subcontDeleteItem->deleteItem($request->validated());
        } catch (\Throwable $ex) {
            return response()->json([
                'error' => $ex->getMessage(),
            ], 500);
        }

        return $result;
    }

    public function importStockItems(SubcontImportStockItemRequest $request)
    {
        // validated request data
        $validateData = $request->validated();

        foreach ($validateData['data'] as $data) {
            $this->subcontImportStockItem->importStockItem(
                $data['bp_code'],
                $data['part_number'],
                $data['fresh_unprocess_incoming_items'],
                $data['fresh_ready_delivery_items'],
                $data['fresh_ng_items'],
                $data['replating_unprocess_incoming_items'],
                $data['replating_ready_delivery_items'],
                $data['replating_ng_items'],
            );
        }

        // Response
        return response()->json([
            'status' => true,
            'message' => 'Import Stock Items Successfully',
        ], 200);

    }
}
