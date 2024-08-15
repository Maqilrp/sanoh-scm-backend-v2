<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DN_HeaderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'no_dn' => $this->no_dn,
            'po_no' => $this->po_no,
            'plan_delivery_date' => $this->plan_delivery_date,
            'status_desc' => $this->status_desc,
        ];
    }
}