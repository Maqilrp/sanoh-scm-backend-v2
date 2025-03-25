<?php

namespace App\Service\Subcontractor;

use App\Trait\ErrorLog;
use App\Trait\ResponseApi;
use App\Models\Subcontractor\SubcontStock;

class SubcontCreateStock
{
    /**
     * -------TRAIT---------
     * Mandatory:
     * 1. ResponseApi = Response api should use ResponseApi trait template
     * 2. ErrorLog = Make internal log and return RequestId if the logic error was critical
     */
    use ResponseApi, ErrorLog;

    /**
     * Check the item stock record
     *
     * @param  mixed  $item_code
     * @param  mixed  $subItemId
     */
    public function createAndCheckStock($item_code, $subItemId): bool
    {
        try {
            // Check if data stock exists
            $checkAvaibility = SubcontStock::where('sub_item_id', $subItemId)
                ->where('item_code', $item_code)
                ->exists();
            if (! $checkAvaibility) {
                SubcontStock::create([
                    'sub_item_id' => $subItemId,
                    'item_code' => $item_code,
                    'incoming_fresh_stock' => 0,
                    'incoming_replating_stock' => 0,
                    'process_fresh_stock' => 0,
                    'process_replating_stock' => 0,
                    'ng_fresh_stock' => 0,
                    'ng_replating_stock' => 0,
                ]);
            }
        } catch (\Throwable $th) {
            $requestId = $this->logicError('Error Create And Check Subcont Stock', $th->getMessage(), $th->getFile(), $th->getLine());
            return $this->returnResponseApi(false, "Internal error while checking stock item (Request_id:$requestId)", null, 500);
        }

        return true;
    }
}
