<?php

namespace App\Service\Subcontractor;

use App\Trait\ErrorLog;
use Log;
use Carbon\Carbon;
use App\Trait\ResponseApi;
use Illuminate\Support\Facades\DB;
use App\Models\Subcontractor\SubcontItem;
use App\Models\Subcontractor\SubcontStock;
use Illuminate\Http\Exceptions\HttpResponseException;

class SubcontImportStockItem
{
    /**
     * -------TRAIT---------
     * Mandatory:
     * 1. ResponseApi = Response api should use ResponseApi trait template
     * 2. ErrorLog = Make internal log and return RequestId if the logic error was critical
     */
    use ResponseApi, ErrorLog;
    public function __construct(protected SubcontCreateStock $subcontCreateStock){}

    public function importStockItem(
        string $bpCode,
        string $partNumber,
        int $freshIncomingItems,
        int $freshProcessItems,
        int $freshNgItems,
        int $replatingIncomingItems,
        int $replatingProcessItems,
        int $replatingNgItems,
    ) {
        // Query to get sub_item_id form table subcont_item
        $getItem = SubcontItem::where('bp_code', $bpCode)
            ->where('item_code', $partNumber)
            ->value('sub_item_id');
        if (!$getItem) {
            return $this->returnResponseApi(false, 'Subcont Item Not Found', 404);
        }
        // Check stock record availability
        $this->subcontCreateStock->createAndCheckStock($partNumber, $getItem);

        // Query to get stock record form table subcont_stock
        $getStock = SubcontStock::where('sub_item_id', $getItem)
            ->where('item_code', $partNumber)
            ->first();
        if (!$getStock) {
            return $this->returnResponseApi(false, 'Subcont Stock Not Found', 404);
        }

        // Update/import stock
        try {
            $getStock->update([
                'incoming_fresh_stock' => $freshIncomingItems,
                'process_fresh_stock' => $freshProcessItems,
                'ng_fresh_stock' => $freshNgItems,
                'incoming_replating_stock' => $replatingIncomingItems,
                'process_replating_stock' => $replatingProcessItems,
                'ng_replating_stock' => $replatingNgItems,
            ]);
        } catch (\Throwable $th) {
            $requestId = $this->logicError('Error Import Item Subcont', $th->getMessage(), $th->getFile(), $th->getLine());
            return $this->returnResponseApi(false, "Internal error while input stock (Request_id:$requestId)", null, 500);
        }

        return $this->returnResponseApi(true, 'Import Subcont Item Successful', null, 201);
    }
}
