<?php

namespace App\Http\Controllers\Api\V1\PerformanceReport;

use App\Trait\ResponseApi;
use Carbon\Carbon;
use App\Trait\StoreFile;
use Dom\Document;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\PerformanceReport\PerformanceReport;
use App\Http\Requests\PerformanceReport\StorePerformanceRequest;
use App\Http\Resources\PerformanceReport\PerformanceReportResource;

class PerformanceReportController extends Controller
{
    /**
     * -------TRAIT---------
     * Mandatory:
     * 1. ResponseApi = Response api should use ResponseApi trait template
     * 2. StoreFile = Save file to server storage
     */
    use ResponseApi, StoreFile;

    // View list data Listing Report
    public function index(Request $bp_code)
    {
        $check = Auth::user()->role;
        if ($check == 5 || $check == 6 || $check == 7 || $check == 6 || $check == 8) {
            $bp_code = Auth::user()->bp_code;
        } elseif ($check == 2 || $check == 3 || $check == 4 || $check == 9) {
            $bp_code = $bp_code->bp_code;
        }

        $data_listingreport = PerformanceReport::with('partner')
            ->where('bp_code', $bp_code)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Success Display Listing Report',
            'data' => PerformanceReportResource::collection($data_listingreport),
        ], 200);
    }

    public function indexAll()
    {
        $data_listingreport = PerformanceReport::with('partner')->get();

        return response()->json([
            'success' => true,
            'message' => 'Success Display Listing Report',
            'data' => PerformanceReportResource::collection($data_listingreport),
        ], 200);
    }

    /**
     * Store Performance Report
     * @param \App\Http\Requests\PerformanceReport\StorePerformanceRequest $request
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    public function store(StorePerformanceRequest $request)
    {
        $request->validated();

        if ($request->hasFile('file')) {
            $filePath = $this->saveFile($request->file('file'), 'Performance', 'Documents', 'Performance', 'local');
        } else {
            $filePath = "Upload File Is Mandatory !!!";
        }

        $time = Carbon::now()->timezone("Asia/Jakarta");

        $store = PerformanceReport::updateOrCreate(
            [
                'date' => Carbon::parse($request->date)->format('Y-m-d'),
                'bp_code' => $request->bp_code,
            ],
            [
                'file' => $filePath,
                'upload_at' => $time,
            ]
        );

        return $this->returnResponseApi(
            true,
            'Add Performance Report Successfully',
            new PerformanceReportResource($store),
            201
        );
    }

    /**
     * Download Performance report
     * @param mixed $filename
     * @return mixed|\Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function getFile($filename)
    {
        $file = PerformanceReport::select('file')->where('file', 'like', "%{$filename}%")->first();
        if (!$file) {
            return $this->returnResponseApi(false, 'Performance Report Not Found', '', 404);
        }

        try {
            $filePath = Storage::disk('local')->path($file->file);
        } catch (\Throwable $th) {
            return $this->returnResponseApi(false, 'There is No File', '', 404);
        }

        $fileName = str_replace(' ', '_', Carbon::now()->format('Ymd') . '_' . $file->project_name);

        return response()->download($filePath, $fileName);
    }
}
