<?php

namespace App\Service\Subcontractor;

use App\Trait\ResponseApi;
use App\Trait\AuthorizationRole;
use App\Models\Subcontractor\SubcontTransaction;

class SubcontUpdateTransaction
{
    /**
     * -------TRAIT---------
     * Mandatory:
     * 1. ResponseApi = Response api should use ResponseApi trait template
     * 2. AuthorizationRole = for checking permissible user role
     */
    use AuthorizationRole, ResponseApi;

    public function updateTransaction(int $subTransactionId, int $actualQtyOk, int $actualQtyNg)
    {
        if ($this->permissibleRole('4', '9')) {
        } else {
            return $this->returnResponseApi(false, 'User Unauthorized', null, 401);
        }

        try {
            $findRecord = SubcontTransaction::find($subTransactionId);
            if (! $findRecord) {
                return $this->returnResponseApi(false, 'Subcont Transaction Not Found', null, 404);
            }

            $findRecord->update([
                'actual_qty_ok_receive' => $actualQtyOk,
                'actual_qty_ng_receive' => $actualQtyNg,
                'response' => 'Receipt',
            ]);

            return $this->returnResponseApi(true, 'Subcont Transaction Update Successfully', null, 200);
        } catch (\Throwable $th) {
            return $this->returnResponseApi(false, 'Request data format error', null, 422);
        }
    }
}
