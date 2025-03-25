<?php

namespace App\Service\Subcontractor;

use App\Models\Subcontractor\SubcontItem;
use App\Models\Subcontractor\SubcontStock;
use App\Models\Subcontractor\SubcontTransaction;
use App\Trait\AuthorizationRole;
use App\Trait\ErrorLog;
use App\Trait\ResponseApi;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Log;

class SubcontCreateTransaction
{
    /**
     * -------TRAIT---------
     * Mandatory:
     * 1. ResponseApi = Response api should use ResponseApi trait template
     * 2. AuthorizationRole = for checking permissible user role
     * 3. ErrorLog = Make internal log and return RequestId if the logic error was critical
     */
    use AuthorizationRole, ErrorLog, ResponseApi;

    /**
     * Create new transaction
     *
     * @return bool|mixed|\Illuminate\Http\JsonResponse
     */
    public function createTransactionSubcont(array $data)
    {
        if ($this->permissibleRole('6', '8')) {
            $bpCode = Auth::user()->bp_code;
        } elseif ($this->permissibleRole('9')) {
            $bpCode = $data['bp_code'];
        }

        // Start foreach loop subcont transaction
        foreach ($data['data'] as $dataTransaction) {
            // Check item status
            $itemStatus = $this->checkItemStatus($bpCode, $dataTransaction['item_code']);
            if ($itemStatus == false) {
                return $this->returnResponseApi(
                    false,
                    'Item is Inactive and Cannot be Used.',
                    null,
                    404
                );
            }

            $subItemId = SubcontItem::where('item_code', $dataTransaction['item_code'])
                ->where('bp_code', $bpCode)
                ->value('sub_item_id');
            if (! $subItemId) {
                return $this->returnResponseApi(
                    false,
                    'Item Not Found.',
                    null,
                    404
                );
            }

            // Generate delivery note (Only for process transaction)
            try {
                if (empty($dataTransaction['delivery_note'])) {
                    $dateLatestProcess = Carbon::now()->format('Ymd');
                    $today = Carbon::now()->format('dmy');
                    $user = substr(Auth::user()->bp_code, strpos(Auth::user()->bp_code, 'SLS') + 3, 5);
                    $getLatestProcess = SubcontTransaction::where('delivery_note', 'like', "$user$today-%")
                        ->where('transaction_type', 'Process')
                        ->where('transaction_date', $dateLatestProcess)
                        ->count();
                    $deliveryNote = "$user$today-".($getLatestProcess + 1);
                    $dataTransaction['delivery_note'] = $deliveryNote;
                }
            } catch (\Throwable $th) {
                $requestId = $this->logicError('Error Generate Delivery Note', $th->getMessage(), $th->getFile(), $th->getLine());

                return $this->returnResponseApi(false, "Internal error while generate delivery note (Request_id:$requestId)", null, 500);
            }

            // Start transaction
            DB::transaction(function () use ($dataTransaction, $subItemId) {
                SubcontTransaction::create([
                    'delivery_note' => $dataTransaction['delivery_note'],
                    'sub_item_id' => $subItemId,
                    'transaction_type' => $dataTransaction['transaction_type'],
                    'actual_transaction_date' => $dataTransaction['actual_transaction_date'],
                    'actual_transaction_time' => $dataTransaction['actual_transaction_time'],
                    'transaction_date' => Carbon::now()->format('Y-m-d'),
                    'transaction_time' => Carbon::now()->format('H:i:s'),
                    'item_code' => $dataTransaction['item_code'],
                    'status' => $dataTransaction['status'],
                    'qty_ok' => $dataTransaction['qty_ok'],
                    'qty_ng' => $dataTransaction['qty_ng'],
                ]);

                // Check subcont item stock record availability
                $isStockRecordAvailable = $this->checkStockRecordAvailability($dataTransaction['item_code'], $subItemId);

                // Get subcont item stock
                $stock = SubcontStock::with('subItem')
                    ->where('sub_item_id', $subItemId)
                    ->where('item_code', $dataTransaction['item_code'])
                    ->first();
                if (! $stock) {
                    return $this->returnResponseApi(false, 'Stock Not Found', null, 404);
                }

                // Calculate stock
                if ($isStockRecordAvailable == true && $stock) {
                    switch ($dataTransaction['status']) {
                        case 'Fresh':
                            $this->calculatingFreshStock(
                                $dataTransaction['transaction_type'],
                                $dataTransaction['qty_ok'],
                                $dataTransaction['qty_ng'],
                                $stock
                            );
                            break;

                        case 'Replating':
                            $this->calculatingReplatingStock(
                                $dataTransaction['transaction_type'],
                                $dataTransaction['qty_ok'],
                                $dataTransaction['qty_ng'],
                                $stock
                            );
                            break;

                        default:
                            return $this->returnResponseApi(false, 'Transaction Status must be Fresh or Replating', 403);
                    }
                } else {
                    return $this->returnResponseApi(false, 'Error processing check stock record availability', null, 500);
                }
            });
        }

        return true;
    }

    /**
     * Summary of createSubcontTransactionDifference
     */
    public function createSubcontTransactionReview(string $subTransactionId, int $subItemId, int $actualQtyOk, int $actualQtyNg)
    {
        if ($this->permissibleRole('4', '9')) {
        } else {
            return $this->returnResponseApi(false, 'User Forbidden', null, 403);
        }

        DB::transaction(function () use ($subTransactionId, $subItemId, $actualQtyOk, $actualQtyNg) {
            // Get transaction record
            $transaction = SubcontTransaction::where('sub_transaction_id', $subTransactionId)->first();

            // Declare variable
            $dnNo = $transaction->delivery_note;
            $itemCode = $transaction->item_code;
            $transactionStatus = $transaction->status;
            $transactionType = 'Process';
            $diffrenceQtyOk = $transaction->qty_ok - $actualQtyOk;
            $diffrenceQtyNg = $transaction->qty_ng - $actualQtyNg;

            // Create the transaction
            if ($diffrenceQtyOk + $diffrenceQtyNg != 0) {
                SubcontTransaction::create([
                    'delivery_note' => "System-$dnNo",
                    'sub_item_id' => $subItemId,
                    'transaction_type' => $transactionType,
                    'transaction_date' => Carbon::now()->format('Y-m-d'),
                    'transaction_time' => Carbon::now()->format('H:i:s'),
                    'item_code' => $itemCode,
                    'status' => $transactionStatus,
                    'qty_ok' => $diffrenceQtyOk,
                    'qty_ng' => $diffrenceQtyNg,
                    'response' => "System Review-$dnNo",
                ]);

                // Get stock
                $stock = SubcontStock::with('subItem')
                    ->where('sub_item_id', $subItemId)
                    ->where('item_code', $itemCode)
                    ->first();

                // Calculate
                switch ($transactionStatus) {
                    case 'Fresh':
                        $this->calculatingFreshStock(
                            $transactionType,
                            $diffrenceQtyOk,
                            $diffrenceQtyNg,
                            $stock,
                            true,
                        );
                        break;

                    case 'Replating':
                        $this->calculatingReplatingStock(
                            $transactionType,
                            $diffrenceQtyOk,
                            $diffrenceQtyNg,
                            $stock,
                            true,
                        );
                        break;

                    default:
                        return $this->returnResponseApi(false, 'Transaction Status must be Fresh or Replating', 403);
                }
            }
        });
    }

    /**
     * Calculating stock items when status is fresh
     *
     * @param  bool  $system  optional parameter for system review subcont
     *
     * @throws \Exception
     */
    private function calculatingFreshStock(string $transactionType, int $qtyOk, int $qtyNg, SubcontStock $stock, bool $system = false, string $transactionStatus = 'Fresh')
    {
        if ($transactionStatus != 'Fresh') {
            return $this->returnResponseApi(false, 'Forbidden Method !, this method only used for transaction status "Fresh"');
        }

        switch ($transactionType) {
            case 'Incoming':
                // qty_ok
                $stock->increment('incoming_fresh_stock', $qtyOk);

                // qty_ng
                if (! empty($qtyNg)) {
                    $stock->increment('ng_fresh_stock', $qtyNg);
                }
                break;

            case 'Process':
                switch ($system) {
                    case true:
                        // Qty Ok
                        $stock->increment('process_fresh_stock', $qtyOk);
                        //Qty Ng
                        $stock->increment('ng_fresh_stock', $qtyNg);
                        break;

                    case false:
                        // qty_ok
                        if ($stock->incoming_fresh_stock < $qtyOk) {
                            return $this->returnResponseApi(
                                false,
                                'Insufficient stock.',
                                "The remaining unprocess fresh stock (Item: {$stock->subItem->item_name}) is currently \"{$stock->incoming_fresh_stock}\".",
                                422
                            );
                        } else {
                            $stock->decrement('incoming_fresh_stock', $qtyOk);
                            $stock->increment('process_fresh_stock', $qtyOk);
                        }

                        // qty_ng
                        if ($stock->incoming_fresh_stock < $qtyNg) {
                            return $this->returnResponseApi(
                                false,
                                'Insufficient stock.',
                                "The remaining unprocess fresh stock (Item: {$stock->subItem->item_name}) is currently \"{$stock->incoming_fresh_stock}\".",
                                422
                            );
                        } else {
                            $stock->decrement('incoming_fresh_stock', $qtyNg);
                            $stock->increment('ng_fresh_stock', $qtyNg);
                        }
                        break;

                    default:
                        throw new Exception('Error for System: Only accept "true or False" value', 500);
                }
                break;

            case 'Outgoing':
                // qty_ok
                if ($stock->process_fresh_stock < $qtyOk) {
                    return $this->returnResponseApi(
                        false,
                        'Insufficient stock.',
                        "The remaining ready fresh stock (Item: {$stock->subItem->item_name}) is currently \"{$stock->process_fresh_stock}\".",
                        422
                    );
                } else {
                    $stock->decrement('process_fresh_stock', $qtyOk);
                }

                // qty_ng
                if ($stock->ng_fresh_stock < $qtyNg) {
                    return $this->returnResponseApi(
                        false,
                        'Insufficient stock.',
                        "The remaining NG fresh stock (Item: {$stock->subItem->item_name}) is currently \"{$stock->ng_fresh_stock}\".",
                        422
                    );
                } else {
                    $stock->decrement('ng_fresh_stock', $qtyNg);
                }
                break;

            default:
                return $this->returnResponseApi(false, 'Request Transaction Type Invalid. Value must be Incoming, Process, Outgoing for Calculating Stock Fresh', null, 403);
        }
    }

    /**
     * Calculating stock items when status is replating
     *
     * @param  bool  $system  optional parameter for system review subcont
     */
    public function calculatingReplatingStock(string $transactionType, int $qtyOk, int $qtyNg, SubcontStock $stock, bool $system = false, string $transactionStatus = 'Replating')
    {
        if ($transactionStatus != 'Replating') {
            return $this->returnResponseApi(false, 'Forbidden Method !, this method only used for transaction status "Replating"');
        }

        switch ($transactionType) {
            case 'Incoming':
                // qty_ok
                $stock->increment('incoming_replating_stock', $qtyOk);

                // qty_ng
                if (! empty($qtyNg)) {
                    $stock->increment('ng_replating_stock', $qtyNg);
                }
                break;

            case 'Process':
                switch ($system) {
                    case true:
                        // Qty Ok
                        $stock->increment('process_replating_stock', $qtyOk);

                        //Qty Ng
                        $stock->increment('ng_replating_stock', $qtyNg);
                        break;

                    case false:
                        // qty_ok
                        if ($stock->incoming_replating_stock < $qtyOk) {
                            return $this->returnResponseApi(
                                false,
                                'Insufficient stock.',
                                "The remaining unprocess replating stock (Item: {$stock->subItem->item_name}) is currently \"{$stock->incoming_replating_stock}\".",
                                422
                            );
                        } else {
                            $stock->decrement('incoming_replating_stock', $qtyOk);
                            $stock->increment('process_replating_stock', $qtyOk);
                        }

                        // qty_ng
                        if ($stock->incoming_replating_stock < $qtyNg) {
                            return $this->returnResponseApi(
                                false,
                                'Insufficient stock.',
                                "The remaining unprocess replating stock (Item: {$stock->subItem->item_name}) is currently \"{$stock->incoming_replating_stock}\".",
                                422
                            );
                        } else {
                            $stock->decrement('incoming_replating_stock', $qtyNg);
                            $stock->increment('ng_replating_stock', $qtyNg);
                        }
                        break;

                    default:
                        return $this->returnResponseApi(false, 'Error for System: Only accept "true or False" value', null, 500);
                }
                break;

            case 'Outgoing':
                // qty_ok
                if ($stock->process_replating_stock < $qtyOk) {
                    return $this->returnResponseApi(
                        false,
                        'Insufficient stock.',
                        "The remaining ready replating stock (Item: {$stock->subItem->item_name}) is currently \"{$stock->process_replating_stock}\".",
                        422
                    );
                } else {
                    $stock->decrement('process_replating_stock', $qtyOk);
                }

                // qty_ng
                if ($stock->ng_replating_stock < $qtyNg) {
                    return $this->returnResponseApi(
                        false,
                        'Insufficient stock.',
                        "The remaining NG replating stock (Item: {$stock->subItem->item_name}) is currently \"{$stock->ng_replating_stock}\".",
                        422
                    );
                } else {
                    $stock->decrement('ng_replating_stock', $qtyNg);
                }
                break;

            default:
                return $this->returnResponseApi(false, 'Request Transaction Type Invalid. Value must be Incoming, Process, Outgoing for Calculating Stock Replating', null, 403);
        }
    }

    /**
     * Check the subcont item stock record
     *
     * @param  mixed  $itemCode
     * @param  mixed  $subItemId
     */
    private function checkStockRecordAvailability($itemCode, $subItemId): bool
    {
        $checkAvaibility = SubcontStock::where('sub_item_id', $subItemId)
            ->where('item_code', $itemCode)
            ->exists();
        if (! $checkAvaibility) {
            SubcontStock::create([
                'sub_item_id' => $subItemId,
                'item_code' => $itemCode,
                'incoming_fresh_stock' => 0,
                'incoming_replating_stock' => 0,
                'process_fresh_stock' => 0,
                'process_replating_stock' => 0,
                'ng_fresh_stock' => 0,
                'ng_replating_stock' => 0,
            ]);
        }

        return true;
    }

    /**
     * Check subcont item status (active: 1 and inactive: 2)
     *
     * @return bool
     */
    private function checkItemStatus(string $bpCode, string $partNumber)
    {
        $getStatus = SubcontItem::where('bp_code', $bpCode)
            ->where('item_code', $partNumber)
            ->value('status');

        if ($getStatus == 1) {
            return true;
        } else {
            return false;
        }
    }
}
