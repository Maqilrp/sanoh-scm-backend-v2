<?php

namespace App\Jobs\Syncronization;

use App\Models\PurchaseOrder\PoDetail;
use App\Models\PurchaseOrder\PoDetailErp;
use App\Models\PurchaseOrder\PoHeader;
use App\Models\PurchaseOrder\PoHeaderErp;
use App\Service\Syncronization\SyncDeleteData;
use App\Trait\ErrorLog;
use Carbon\Carbon;
use Dompdf\Image\Cache;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;

class SyncPurchaseOrderJob implements ShouldQueue
{
    use ErrorLog, InteractsWithQueue, Queueable;

    public function handle(SyncDeleteData $syncDeleteData): void
    {
        try {
            // Initialize variable
            $actualYear = Carbon::now()->year;
            $actualPeriod = Carbon::now()->month;
            $threeMontBefore = Carbon::now()->subMonths(3)->month; // Change subMonths value if you want to sync within range 3 month (Only Running at 00:00 - 00:10)
            $oneMonthBefore = Carbon::now()->subMonths(1)->month; // Change subMonths value if you want to sync within range 1 month (Running every ten minute)

            // Check condiition for query Synchronization PO
            if (Carbon::now()->format('h:i') >= '00:00' && Carbon::now()->format('h:i') <= '00:10') {
                // Get Purchase Order from range 3 month ago till now
                $sqlsrvDataPoHeader = PoHeaderErp::whereBetween('po_period', [$threeMontBefore, $actualPeriod])
                    ->where('po_year', $actualYear)
                    ->get();
                \Log::info("Running Sync PO 00:00 ");
            } else {
                // Get Purchase Order from range 1 month ago till now on this year
                $sqlsrvDataPoHeader = PoHeaderErp::whereBetween('po_period', [$oneMonthBefore, $actualPeriod])
                    ->where('po_year', $actualYear)
                    ->get();
            }

            $poNo = $sqlsrvDataPoHeader->pluck('po_no')->toArray();
            \Cache::put('syncronization_data_po_number', $poNo);

            // Get Purchase Order Detail
            $poDetailData = collect();
            foreach (array_chunk($poNo, 2000) as $chunk) {
                $poDetailresult = PoDetailErp::whereIn('po_no', $chunk)->get();
                $poDetailData = $poDetailData->merge($poDetailresult);
            }

            // poheader
            foreach ($sqlsrvDataPoHeader as $data) {
                PoHeader::updateOrCreate(
                    [
                        'po_no' => $data->po_no,
                        'supplier_code' => $data->supplier_code,
                    ],
                    [
                        'supplier_name' => $data->supplier_name,
                        'po_date' => $data->po_date,
                        'po_year' => $data->po_year,
                        'po_period' => $data->po_period,
                        'po_status' => $data->po_status,
                        'reference_1' => $data->reference_1,
                        'reference_2' => $data->reference_2,
                        'attn_name' => $data->attn_name,
                        'po_currency' => $data->po_currency,
                        'po_type_desc' => $data->po_type_desc,
                        'pr_no' => $data->pr_no,
                        'planned_receipt_date' => $data->planned_receipt_date,
                        'payment_term' => $data->payment_term,
                        'po_origin' => $data->po_origin,
                        'po_revision_no' => $data->po_revision_no,
                        'po_revision_date' => $data->po_revision_date,
                    ]
                );
            }

            // Po Detail
            foreach ($poDetailData as $data) {
                PoDetail::updateOrCreate(
                    [
                        'po_no' => $data['po_no'],
                        'po_line' => $data['po_line'],
                    ],
                    [
                        'po_sequence' => $data['po_sequence'],
                        'item_code' => $data['item_code'],
                        'code_item_type' => $data['code_item_type'],
                        'bp_part_no' => $data['bp_part_no'],
                        'bp_part_name' => $data['bp_part_name'],
                        'item_desc_a' => $data['item_desc_a'],
                        'item_desc_b' => $data['item_desc_b'],
                        'planned_receipt_date' => $data['planned_receipt_date'],
                        'po_qty' => $data['po_qty'],
                        'receipt_qty' => $data['receipt_qty'],
                        'invoice_qty' => $data['invoice_qty'],
                        'purchase_unit' => $data['purchase_unit'],
                        'price' => $data['price'],
                        'amount' => $data['amount'],
                    ]
                );
            }

            // Delete PO
            $syncDeleteData->deletePo();
        } catch (\Throwable $th) {
            $this->syncError(
                'Sync Purchase Order Failed',
                $th->getMessage(),
                $th->getFile(),
                $th->getLine(),
                $this->job->getJobId()
            );
        }
    }
}
