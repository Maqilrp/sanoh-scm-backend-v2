<?php

namespace Database\Seeders;

use App\Models\DN_Detail;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DN_DetailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // create dummy data
        for ($i = 0; $i <= 10; $i++) {
            // code...
            DN_Detail::create([
                'dn_detail_no' => rand(),
                'no_dn' => 'E8f8qjvZN6',
                'dn_line' => rand(1, 3),
                'order_origin' => rand(60, 80),
                'plan_delivery_date' => Carbon::now(),
                'plan_delivery_time' => Carbon::now(),
                'actual_receipt_date' => Carbon::now(),
                'actual_receipt_time' => Carbon::now(),
                'no_order' => Str::random(10),
                'order_set' => rand(0, 1),
                'order_line' => rand(0, 1),
                'order_seq' => rand(0, 1),
                'part_no' => Str::random(10),
                'supplier_item_no' => Str::random(10),
                'item_desc_a' => Str::random(10),
                'item_desc_b' => Str::random(10),
                'lot_number' => Str::random(10),
                'dn_qty' => rand(1, 20),
                'receipt_qty' => rand(1, 20),
                'dn_unit' => rand(1, 20),
                'dn_snp' => rand(1, 20),
                'reference' => Str::random(10),
                'status_desc' => 'inprocess',
                // 'qty_confirm' => rand(200, 400),
            ]);
        }
    }
}
