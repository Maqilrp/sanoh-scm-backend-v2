<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ForecastController;
use App\Http\Controllers\TestMailController;
use App\Http\Controllers\Api\PrintController;
use App\Http\Controllers\Api\HistoryController;
use App\Http\Controllers\Api\PartnerController;
use App\Http\Controllers\Api\SubcontController;
use App\Http\Controllers\SynchronizeController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DN_DetailController;
use App\Http\Controllers\Api\DN_HeaderController;
use App\Http\Controllers\Api\PO_DetailController;
use App\Http\Controllers\Api\PO_HeaderController;
use App\Http\Controllers\Api\ListingReportController;
use App\Http\Controllers\SynchronizeManualController;

// Route Default
// Route::get("", function () {
//     return view("index");
// });

// Route Login
Route::post('/login', [AuthController::class, 'login']);

// route view print
Route::get('/dnhview/{no_dn}', [PrintController::class, 'dnHeaderView']);
Route::get('/lbview/{no_dn}', [PrintController::class, 'labelView']);
Route::get('/pohview/{po_no}', [PrintController::class, 'poHeaderView']);

// Route sync
Route::get('/mail', [TestMailController::class,'mail']);
Route::get('/sync', [SynchronizeController::class, 'sync']);
Route::get('/copyBusinessPartner', [SynchronizeController::class, 'copyBusinessPartner']);
Route::get('/copyPoHeader', [SynchronizeController::class, 'copyPoHeader']);
Route::get('/copyPoDetail', [SynchronizeController::class, 'copyPoDetail']);
Route::get('/copyDnHeader', [SynchronizeController::class, 'copyDnHeader']);
Route::get('/copyDnDetail', [SynchronizeController::class, 'copyDnDetail']);

//Route Supplier
Route::middleware(['auth:sanctum','userRole:1']) ->group(function () {
    /**
     * New Route
     */
    // Dashboard
    Route::get('supplier/dashboard', [DashboardController::class, 'index']);

    // route view history
    Route::get('supplier/po/history/{bp_code}', [HistoryController::class, 'poHeaderHistory']);
    Route::get('supplier/dn/history/{bp_code}', [HistoryController::class, 'dnHeaderHistory']);

    // Route for show list PO Header
    Route::get('supplier/po/index/{sp_code}',[PO_HeaderController::class, "index"]);

    // Route for show list PO Detail
    Route::get('supplier/po/detail/{po_no}',[PO_DetailController::class, "index"]);
    // Route for update list PO Header
    Route::put('supplier/po/update/{po_no}',[PO_HeaderController::class, "update"]);


    // Route for show list DN Header
    Route::get('supplier/dn/index',[DN_HeaderController::class, "index"]);

    // Route for show list DN Detail
    Route::get('supplier/dn/detail/{no_dn}',[DN_DetailController::class, "index"]);
    // Route for edit list DN Detail
    Route::get('supplier/dn/edit/{dn_detail_no}',[DN_DetailController::class, "edit"]);
    // Route for update list DN Detail
    Route::put('supplier/dn/update',[DN_DetailController::class, "update"]);

    // route view print
    Route::get('supplier/po/print/{po_no}', [PrintController::class, 'poHeaderView']);
    Route::get('supplier/dn/print/{no_dn}', [PrintController::class, 'dnHeaderView']);
    Route::get('supplier/label/print/{no_dn}', [PrintController::class, 'labelView']);

    // Route for show list of Listing Report
    Route::get('supplier/performance-report/index/{bp_code}',[ListingReportController::class, "index"]);
    Route::get('supplier/performance-report/file/{filename}', [ListingReportController::class, 'getFile']);

    // Route for forcast
    Route::get('supplier/forecast/index',[ForecastController::class,'indexSupplier']);
    Route::get('supplier/forecast/file/{filename}', [ForecastController::class,"getFile"]);


    //Logout route
    Route::post('supplier/logout', [AuthController::class, 'logout']);
});


//Route Warehouse
Route::middleware(['auth:sanctum','userRole:2']) ->group(function () {
    /**
     * New route
     */
    // Route sync data
    Route::get('warehouse/sync', [SynchronizeManualController::class, 'syncManual']);

    // Route get partner data
    Route::get('warehouse/partner/index', [PartnerController::class, 'index']);

    // Route for show list DN Header
    // Specific Partner
    Route::get('warehouse/dn/index/{sp_code}',[DN_HeaderController::class, "indexWarehouse"]);

    // Route for show list DN Detail
    Route::get('warehouse/dn/detail/{no_dn}',[DN_DetailController::class, "index"]);
    Route::get('warehouse/dn/print/{no_dn}', [PrintController::class, 'dnHeaderView']);

    Route::get('warehouse/dn/history/{bp_code}', [HistoryController::class, 'dnHeaderHistory']);

    //Logout route
    Route::post('warehouse/logout', [AuthController::class, 'logout']);
});

//Route Purchasing
Route::middleware(['auth:sanctum','userRole:3']) ->group(function () {
    // Route sync data
    Route::get('purchasing/sync', [SynchronizeManualController::class, 'syncManual']);

    // Route get partner
    Route::get('purchasing/partner/index', [PartnerController::class, 'index']);
    // Route for show list PO Header
    // Specific Partner
    Route::get('purchasing/po/index/{sp_code}',[PO_HeaderController::class, "index"]);

    // Route for show list PO Detail
    Route::get('purchasing/po/detail/{po_no}',[PO_DetailController::class, "index"]);
    Route::get('purchasing/po/history/{bp_code}', [HistoryController::class, 'poHeaderHistory']);

    // Route for show list of Listing Report
    Route::get('purchasing/performance-report/index/{bp_code}',[ListingReportController::class, "index"])->name('index');
    Route::get('purchasing/performance-report/file/{filename}', [ListingReportController::class, 'getFile']);
    // Route for store Listing Report
    Route::post('purchasing/performance-report/store',[ListingReportController::class, "store"]);

    Route::get('purchasing/po/print/{po_no}', [PrintController::class, 'poHeaderView']);

    // Route for Forecast
    Route::get('/purchasing/forecast/index/{bp_code}', [ForecastController::class,"indexPurchasing"]);
    Route::post('/purchasing/forecast/store', [ForecastController::class,"store"]);
    Route::delete('/purchasing/forecast/delete/{forecast}', [ForecastController::class,"destroy"]);
    Route::get('purchasing/forecast/file/{filename}', [ForecastController::class,"getFile"]);

    //Logout route
    Route::post('purchasing/logout', [AuthController::class, 'logout']);
});
//Route Super Admin
Route::middleware(['auth:sanctum','userRole:4']) ->group(function () {


    Route::get('admin/partner/index', [PartnerController::class, 'index']);

    // Route for show list of user
    Route::get('admin/index', [UserController::class, 'index']);

    // Route for create user
    Route::post('admin/create',[UserController::class, "store"]);

    // Route for edit user form
    Route::get('admin/edit/{user}',[UserController::class, "edit"]);

    // Route for update  user data
    Route::put('admin/update/{user}',[UserController::class, "update"]);
    Route::put('admin/update/status/{user}',[UserController::class, "updateStatus"]);

    //Logout route
    Route::post('admin/logout', [AuthController::class, 'logout']);
});

// Route subcont
Route::middleware(['auth:sanctum','userRole:5']) ->group(function () {
    /**
     * New Route
     */
    // Dashboard
    Route::get('subcont/dashboard', [DashboardController::class, 'index']);

    // route view history
    Route::get('subcont/po/history/{bp_code}', [HistoryController::class, 'poHeaderHistory']);
    Route::get('subcont/dn/history/{bp_code}', [HistoryController::class, 'dnHeaderHistory']);

    // Route for show list PO Header
    Route::get('subcont/po/index/{sp_code}',[PO_HeaderController::class, "index"]);

    // Route for show list PO Detail
    Route::get('subcont/po/detail/{po_no}',[PO_DetailController::class, "index"]);
    // Route for update list PO Header
    Route::put('subcont/po/update/{po_no}',[PO_HeaderController::class, "update"]);


    // Route for show list DN Header
    Route::get('subcont/dn/index',[DN_HeaderController::class, "index"]);

    // Route for show list DN Detail
    Route::get('subcont/dn/detail/{no_dn}',[DN_DetailController::class, "index"]);
    // Route for edit list DN Detail
    Route::get('subcont/dn/edit/{dn_detail_no}',[DN_DetailController::class, "edit"]);
    // Route for update list DN Detail
    Route::put('subcont/dn/update',[DN_DetailController::class, "update"]);

    // route view print
    Route::get('subcont/po/print/{po_no}', [PrintController::class, 'poHeaderView']);
    Route::get('subcont/dn/print/{no_dn}', [PrintController::class, 'dnHeaderView']);
    Route::get('subcont/label/print/{no_dn}', [PrintController::class, 'labelView']);

    // Route for show list of Listing Report
    Route::get('subcont/performance-report/index/{bp_code}',[ListingReportController::class, "index"]);
    Route::get('subcont/performance-report/file/{filename}', [ListingReportController::class, 'getFile']);

    // Route subcont
    Route::get('subcont/item/index', [SubcontController::class,'indexItem']);
    Route::get('subcont/trasaction/index', [SubcontController::class,'indexTrans']);
    Route::post('subcont/item/store', [SubcontController::class,'item']);
    Route::post('subcont/transaction/store', [SubcontController::class,'transaction']);

    // Route for forcast
    Route::get('subcont/forecast/index',[ForecastController::class,'indexSupplier']);
    Route::get('subcont/forecast/file/{filename}', [ForecastController::class,"getFile"]);

    //Logout route
    Route::post('subcont/logout', [AuthController::class, 'logout']);
});

// route testing
// Route for show list of user
// Route::get('/index',[UserController::class, "index"])->name('index');
Route::get('/listingreporttest/file/{filename}', [ListingReportController::class, 'getFile']);
Route::get('/forecasttest/file/{filename}', [ForecastController::class,"getFile"]);
/* Route::middleware('api')->group(function () {
    Route::get('/poheader', [PO_HeaderController::class, 'index']);
    Route::post('/poheader', [PO_HeaderController::class, 'store']);
    Route::get('/poheader/{po_no}', [PO_HeaderController::class, 'show']);
    Route::put('/poheader/{po_no}', [PO_HeaderController::class, 'update']);
    Route::patch('/poheader/edit/{po_no}', [PO_HeaderController::class, 'edit'])->name('po_header.edit');
}); */
