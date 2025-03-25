<?php

namespace App\Trait;

use Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

trait ErrorLog{
    private static $requestId;

    private static function initializeRequestId()
    {
        self::$requestId = Carbon::now()->format('Ymd;H:i:s') . '_' . \Str::random(10);
    }
    // private static $requestId = Carbon::now()->format('Ymd;H:i:s') . '_' . \Str::random(10);


    public function logicError(string $message, string $errorMessage, string $errorFile, string $errorLine)
    {
        self::initializeRequestId();
        $reqid = self::$requestId;
        $message = [
            'Message' => $message,
            'Error' => $errorMessage,
            'File' => $errorFile,
            'Line' => $errorLine,
            'RequestId' => $reqid,
        ];
        
        Log::channel('logic')->critical(json_encode($message, JSON_PRETTY_PRINT));

        return $reqid;
    }

    public function syncError()
    {

    }

    public function emailError()
    {

    }
}
