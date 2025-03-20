<?php

use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\DeliveryNote\DnDetailController;
use App\Http\Controllers\Api\V1\DeliveryNote\DnHeaderController;
use App\Http\Controllers\Api\V1\Email\EmailNotificationSupplierController;
use App\Http\Controllers\Api\V1\Forecast\ForecastController;
use App\Http\Controllers\Api\V1\HistoryController;
use App\Http\Controllers\Api\V1\PerformanceReport\PerformanceReportController;
use App\Http\Controllers\Api\V1\PrintController;
use App\Http\Controllers\Api\V1\PurchaseOrder\PoDetailController;
use App\Http\Controllers\Api\V1\PurchaseOrder\PoHeaderController;
use App\Http\Controllers\Api\V1\Subcontractor\SubcontController;
use App\Http\Controllers\Api\V1\Subcontractor\SubcontReceiveController;
use App\Http\Controllers\Api\V1\Syncronization\SyncController;
use App\Http\Controllers\Api\V1\Syncronization\SyncManualController;
use App\Http\Controllers\Api\V1\User\AuthController;
use App\Http\Controllers\Api\V1\User\BusinessPartnerController;
use App\Http\Controllers\Api\V1\User\UserController;
use Illuminate\Support\Facades\Route;

// Route Login
Route::post('/login', [AuthController::class, 'login']);

// move email
Route::get('/move', [UserController::class, 'moveEmail']);

// route view print
Route::get('/pohview/{po_no}', [PrintController::class, 'poHeaderView']);

// print dn
Route::get('/dnhview/{no_dn}', [PrintController::class, 'dnHeaderView']);
Route::get('/dnhviewconf/{no_dn}', [PrintController::class, 'dnHeaderViewQtyConfirm']);
Route::get('/dnhviewout/{outstanding}/{no_dn}', [PrintController::class, 'dnHeaderViewOutstanding']);

// print label
Route::get('/lbview/{no_dn}', [PrintController::class, 'labelAllView']);
Route::get('/dnout/{outstanding}/{no_dn}', [PrintController::class, 'labelOutstanding']);
Route::get('/dnqty/{no_dn}', [PrintController::class, 'labelQtyConfirm']);

// Route sync
Route::get('/mail-notification', [EmailNotificationSupplierController::class, 'mail']);
Route::get('/sync', [SyncController::class, 'sync']);
Route::get('/copyBusinessPartner', [SyncController::class, 'copyBusinessPartner']);
Route::get('/copyPoHeader', [SyncController::class, 'copyPoHeader']);
Route::get('/copyPoDetail', [SyncController::class, 'copyPoDetail']);
Route::get('/copyDnHeader', [SyncController::class, 'copyDnHeader']);
Route::get('/copyDnDetail', [SyncController::class, 'copyDnDetail']);

// Route Super Admin
Route::middleware(['auth:sanctum', 'userRole:1'])->prefix('super-admin')->group(function () {

    // Route for show list of user
    Route::get('partner/list', [BusinessPartnerController::class, 'GetBusinessPartner']);

    // Route for show online user
    Route::get('dashboard', [DashboardController::class, 'dashboard']);

    // Route for detail active user
    Route::get('user/online', [DashboardController::class, 'detailActiveUser']);

    // Route for logout current useer active token
    Route::post('user/logout', [DashboardController::class, 'logoutByTokenId']);

    // Route for monthly login data
    Route::get('user/monthly', [DashboardController::class, 'monthlyLoginData']);

    /**
     * Route for Business Partner
     */
    // Route for get business partner
    Route::get('organization/email/{bp_code}', [BusinessPartnerController::class,'getBussinessPartnerEmail']);
    // Route for update business partner
    Route::put('organization/email/update/{bp_code}', [BusinessPartnerController::class,'updateBusinessPartnerEmail']);

    /**
     * Route For User
     */
    // Route for get record data
    Route::get('user/index', [UserController::class, 'getUser']);
    // Route for get email user
    Route::get('user/email/{bp_code}', [UserController::class, 'getBusinessPartnerEmail']);
    // Route for create user
    Route::post('user/store', [UserController::class, 'createUser']);
    // Route for edit user form
    Route::get('user/edit/{user}', [UserController::class, 'getUserDetail']);
    // Route for update user data
    Route::put('user/update/{user}', [UserController::class, 'updateUser']);
    // Route for update status user
    Route::put('user/update/status/{user}', [UserController::class, 'updateStatus']);
    // Route for delete user
    Route::delete('user/delete/{user}', [UserController::class, 'deleteUser']);

    //Logout route
    Route::post('logout', [AuthController::class, 'logout']);
});

// Route Admin Purchasing
Route::middleware(['auth:sanctum', 'userRole:2'])->prefix('admin-purchasing')->group(function () {
    // Route sync data
    Route::get('sync', [SyncManualController::class, 'syncManual']);

    // Route get partner list
    Route::get('partner/list', [BusinessPartnerController::class, 'GetBusinessPartner']);

    /**
     *  Route for Purchase Order
     *
     * @param  $sp_code  / supplier_code is bp_code
     */
    // Routefor get record po with specific user
    Route::get('po/index/{bp_code}', [PoHeaderController::class, 'getListPoUser']);
    // Route for show PO Detail list
    Route::get('po/detail/{po_no}', [PoDetailController::class, 'getListDetailPo']);
    // Route for print PO
    Route::get('po/print/{po_no}', [PrintController::class, 'poHeaderView']);
    // Route for show PO history list
    Route::get('po/history/{bp_code}', [HistoryController::class, 'poHeaderHistory']);

    /**
     * Route for Performance Report
     */
    // Route for show list of performance report
    Route::get('performance-report/index/{bp_code}', [PerformanceReportController::class, 'index'])->name('index');
    // Route for download performance report
    Route::get('performance-report/file/{filename}', [PerformanceReportController::class, 'getFile']);
    // Route for store Listing Report
    Route::post('performance-report/store', [PerformanceReportController::class, 'store']);

    /**
     * Route for Forecast
     */
    // Route for get record forecast with spesific user
    Route::get('forecast/index/{bp_code}', [ForecastController::class, 'indexPurchasing']);
    // Route for store forecast file
    Route::post('forecast/store', [ForecastController::class, 'store']);
    // Route for download forecast file
    Route::get('forecast/file/{filename}', [ForecastController::class, 'getFile']);
    // Route for delete forecast file
    Route::delete('forecast/delete/{forecast}', [ForecastController::class, 'destroy']);

    //Logout route
    Route::post('logout', [AuthController::class, 'logout']);
});

// Route Admin Warehouse
Route::middleware(['auth:sanctum', 'userRole:3'])->prefix('admin-warehouse')->group(function () {
    // Route sync data
    Route::get('sync', [SyncManualController::class, 'syncManual']);

    // Route get partner data
    Route::get('partner/list', [BusinessPartnerController::class, 'GetBusinessPartner']);

    /**
     * Route for Delivery Note
     */
    // Route for get record DN with specific user
    Route::get('dn/index/{sp_code}', [DnHeaderController::class, 'getListDnHeaderSelected']);
    // Route for show list DN Detail
    Route::get('dn/detail/{no_dn}', [DnDetailController::class, 'getListDetailDnUser']);
    // Route for print DN file
    Route::get('dn/print/{no_dn}', [PrintController::class, 'dnHeaderView']);
    Route::get('dn/print/qty-confirm/{no_dn}', [PrintController::class, 'dnHeaderViewQtyConfirm']);
    Route::get('dn/print/outstanding/{outstanding}/{no_dn}', [PrintController::class, 'dnHeaderViewOutstanding']);
    // Route fo prin DN label / kanban
    Route::get('dn-label/print/{no_dn}', [PrintController::class, 'labelAllView']);
    Route::get('dn-label/print/outstanding/{outstanding}/{no_dn}', [PrintController::class, 'labelOutstanding']);
    Route::get('dn-label/print/qty-confirm/{no_dn}', [PrintController::class, 'labelQtyConfirm']);
    // Route fo get DN history
    Route::get('dn/history/{bp_code}', [HistoryController::class, 'dnHeaderHistory']);

    //Logout route
    Route::post('logout', [AuthController::class, 'logout']);
});

// Route Admin Subcont
Route::middleware(['auth:sanctum', 'userRole:4'])->prefix('admin-subcont')->group(function () {
    // Route for get partner list
    Route::get('partner/list', [BusinessPartnerController::class, 'GetBusinessPartner']);
    // Route for dashboard admin
    Route::get('dashboard/performance-subcont/{bp_code}', [DashboardController::class, 'adminSubcontGraphic']);

    /**
     * Route for Subcontractor
     */
    // Route for get list item Erp
    Route::get('item/list/item', [SubcontController::class, 'getListItemErp']);
    // Route for get list item user
    Route::get('item/list/{bp_code}', [SubcontController::class, 'getListItem']);
    // Route for admin get all list item user based on bp_code
    Route::get('item/all-list/{bp_code}', [SubcontController::class, 'adminGetAllItem']);
    // Route for get index subcont item (include stock)
    Route::get('item/index/{bp_code}', [SubcontController::class, 'getStock']);
    // Route for store subcont item
    Route::post('item/store', [SubcontController::class, 'createItem']);
    // Route for import stock subcont item
    Route::post('item/stock/initial', [SubcontController::class, 'importStockItems']);
    // Route for update subcont item
    Route::patch('item/update', [SubcontController::class, 'updateItem']);
    // Route for delete subcont item
    Route::delete('item/delete', [SubcontController::class, 'deleteItem']);
    // Route for review subcont transaction header
    Route::get('transaction-review/header/{bp_code}', [SubcontReceiveController::class, 'reviewHeader']);
    // Route for review subcont transaction detail
    Route::get('transaction-review/detail/{no_dn}', [SubcontReceiveController::class, 'reviewDetail']);
    // Route for update review subcont transaction
    Route::patch('transaction-review/update', [SubcontReceiveController::class, 'reviewUpdate']);
    // Route for get index subcont transaction
    Route::get('transaction/index/{bp_code}/{start_date}/{end_date}', [SubcontController::class, 'getListTrans']);

    /**
     * Route for Delivery Note
     */
    // Route for get record DN with specific user
    Route::get('dn/index/{sp_code}', [DnHeaderController::class, 'getListDnHeaderSelected']);
    // Route for show list DN Detail
    Route::get('dn/detail/{no_dn}', [DnDetailController::class, 'getListDetailDnUser']);
    // Route for print DN file
    Route::get('dn/print/{no_dn}', [PrintController::class, 'dnHeaderView']);
    Route::get('dn/print/qty-confirm/{no_dn}', [PrintController::class, 'dnHeaderViewQtyConfirm']);
    Route::get('dn/print/outstanding/{outstanding}/{no_dn}', [PrintController::class, 'dnHeaderViewOutstanding']);
    // Route fo prin DN label / kanban
    Route::get('dn-label/print/{no_dn}', [PrintController::class, 'labelAllView']);
    Route::get('dn-label/print/outstanding/{outstanding}/{no_dn}', [PrintController::class, 'labelOutstanding']);
    Route::get('dn-label/print/qty-confirm/{no_dn}', [PrintController::class, 'labelQtyConfirm']);
    // Route fo get DN history
    Route::get('dn/history/{bp_code}', [HistoryController::class, 'dnHeaderHistory']);

    //Logout route
    Route::post('logout', [AuthController::class, 'logout']);
});

// Route Supplier Marketing
Route::middleware(['auth:sanctum', 'userRole:5'])->prefix('supplier-marketing')->group(function () {
    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index']);

    // Graph PO And DN Yearly Data
    Route::get('yearData', [DashboardController::class, 'getYearlyData']);

    // Route For Calender Events
    Route::get('event', [DashboardController::class, 'calenderEvents']);

    /**
     *  Route for Purchase Order
     */
    // Route for show list PO Header
    Route::get('po/index', [PoHeaderController::class, 'getListPoUser']);
    // Route for show list PO Detail
    Route::get('po/detail/{po_no}', [PoDetailController::class, 'getListDetailPo']);
    // Route for update list PO Header
    Route::put('po/update/{po_no}', [PoHeaderController::class, 'updateResponse']);
    // route view PO history
    Route::get('po/history', [HistoryController::class, 'poHeaderHistory']);
    // route view print PO file
    Route::get('po/print/{po_no}', [PrintController::class, 'poHeaderView']);

    /**
     *  Route for Delivery Note
     */
    // Route for show list DN Header
    Route::get('dn/index', [DnHeaderController::class, 'getListDnUser']);
    // Route for show list DN Detail
    Route::get('dn/detail/{no_dn}', [DnDetailController::class, 'getListDetailDnUser']);
    // Route for edit list DN Detail
    Route::get('dn/edit/{dn_detail_no}', [DnDetailController::class, 'edit']);
    // Route for update list DN Detail
    Route::put('dn/update', [DnDetailController::class, 'update']);
    // route view DN history
    Route::get('dn/history', [HistoryController::class, 'dnHeaderHistory']);
    // route view print DN file
    Route::get('dn/print/{no_dn}', [PrintController::class, 'dnHeaderView']);
    Route::get('dn/print/qty-confirm/{no_dn}', [PrintController::class, 'dnHeaderViewQtyConfirm']);
    Route::get('dn/print/outstanding/{outstanding}/{no_dn}', [PrintController::class, 'dnHeaderViewOutstanding']);
    // route view print DN label/ kanban
    Route::get('dn-label/print/{no_dn}', [PrintController::class, 'labelAllView']);
    Route::get('dn-label/print/outstanding/{outstanding}/{no_dn}', [PrintController::class, 'labelOutstanding']);
    Route::get('dn-label/print/qty-confirm/{no_dn}', [PrintController::class, 'labelQtyConfirm']);

    /**
     *  Route for Performance Repot
     */
    // Route for show list Perfromance Report
    Route::get('performance-report/index', [PerformanceReportController::class, 'index']);
    // Route for download Performance Report
    Route::get('performance-report/file/{filename}', [PerformanceReportController::class, 'getFile']);

    /**
     * Route for Forecast
     */
    // Route for show list Forecast
    Route::get('forecast/index', [ForecastController::class, 'indexSupplier']);
    // Route for download Forecast
    Route::get('forecast/file/{filename}', [ForecastController::class, 'getFile']);

    //Logout route
    Route::post('logout', [AuthController::class, 'logout']);
});

// Route Supplier Subcont Marketing
Route::middleware(['auth:sanctum', 'userRole:6'])->prefix('supplier-subcont-marketing')->group(function () {
    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index']);

    // Graph PO And DN Yearly Data
    Route::get('yearData', [DashboardController::class, 'getYearlyData']);

    // Route For Calender Events
    Route::get('event', [DashboardController::class, 'calenderEvents']);

    /**
     *  Route for Purchase Order
     */

    // Route for show list PO Header
    Route::get('po/index', [PoHeaderController::class, 'getListPoUser']);
    // Route for show list PO Detail
    Route::get('po/detail/{po_no}', [PoDetailController::class, 'getListDetailPo']);
    // Route for update list PO Header
    Route::put('po/update/{po_no}', [PoHeaderController::class, 'updateResponse']);
    // Route for PO history
    Route::get('po/history', [HistoryController::class, 'poHeaderHistory']);
    // Route for print PO
    Route::get('po/print/{po_no}', [PrintController::class, 'poHeaderView']);

    /**
     *  Route for Delivery Note
     */
    // Route for show list DN Header
    Route::get('dn/index', [DnHeaderController::class, 'getListDnUser']);
    // Route for show list DN Detail
    Route::get('dn/detail/{no_dn}', [DnDetailController::class, 'getListDetailDnUser']);
    // Route for edit list DN Detail
    Route::get('dn/edit/{dn_detail_no}', [DnDetailController::class, 'edit']);
    // Route for update list DN Detail
    Route::put('dn/update', [DnDetailController::class, 'update']);
    // Route for print DN
    Route::get('dn/print/{no_dn}', [PrintController::class, 'dnHeaderView']);
    Route::get('dn/print/qty-confirm/{no_dn}', [PrintController::class, 'dnHeaderViewQtyConfirm']);
    Route::get('dn/print/outstanding/{outstanding}/{no_dn}', [PrintController::class, 'dnHeaderViewOutstanding']);
    // Route for print DN label / kanban
    Route::get('dn-label/print/{no_dn}', [PrintController::class, 'labelAllView']);
    Route::get('dn-label/print/outstanding/{outstanding}/{no_dn}', [PrintController::class, 'labelOutstanding']);
    Route::get('dn-label/print/qty-confirm/{no_dn}', [PrintController::class, 'labelQtyConfirm']);
    // route view DN history
    Route::get('dn/history', [HistoryController::class, 'dnHeaderHistory']);

    /**
     *  Route for Performance Report
     */
    // Route for show list of Performance Report
    Route::get('performance-report/index', [PerformanceReportController::class, 'index']);
    // Route for download Performance Report
    Route::get('performance-report/file/{filename}', [PerformanceReportController::class, 'getFile']);

    /**
     *  Route for Subcontractor
     */
    // Route for get list subcont item
    Route::get('item/list', [SubcontController::class, 'getListItem']);
    // Route for get index subcont item (include stock)
    Route::get('item/index/{param?}', [SubcontController::class, 'getStock']);
    // Route for get index subcont transaction
    Route::get('transaction/index', [SubcontController::class, 'getListTrans']);
    // Route for store subcont transaction
    Route::post('transaction/store', [SubcontController::class, 'createTransaction']);

    /**
     *  Route for Forcast
     */
    // Route for get list Forecast
    Route::get('forecast/index', [ForecastController::class, 'indexSupplier']);
    // Route for download Forecast
    Route::get('forecast/file/{filename}', [ForecastController::class, 'getFile']);

    //Logout route
    Route::post('logout', [AuthController::class, 'logout']);
});

// Route Supplier Warehouse
Route::middleware(['auth:sanctum', 'userRole:7'])->prefix('supplier-warehouse')->group(function () {

    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index']);

    // Graph PO And DN Yearly Data
    Route::get('yearData', [DashboardController::class, 'getYearlyData']);

    // Route For Calender Events
    Route::get('event', [DashboardController::class, 'calenderEvents']);

    /**
     * Route for Delivery Note
     */
    // Route for get record DN with specific user
    Route::get('dn/index', [DnHeaderController::class, 'getListDnUser']);
    // Route for show list DN Detail
    Route::get('dn/detail/{no_dn}', [DnDetailController::class, 'getListDetailDnUser']);
    // Route for update list DN Detail
    Route::put('dn/update', [DnDetailController::class, 'update']);
    // Route for edit list DN Detail
    Route::get('dn/edit/{dn_detail_no}', [DnDetailController::class, 'edit']);
    // Route for print DN file
    Route::get('dn/print/{no_dn}', [PrintController::class, 'dnHeaderView']);
    Route::get('dn/print/qty-confirm/{no_dn}', [PrintController::class, 'dnHeaderViewQtyConfirm']);
    Route::get('dn/print/outstanding/{outstanding}/{no_dn}', [PrintController::class, 'dnHeaderViewOutstanding']);
    // Route fo prin DN label / kanban
    Route::get('dn-label/print/{no_dn}', [PrintController::class, 'labelAllView']);
    Route::get('dn-label/print/outstanding/{outstanding}/{no_dn}', [PrintController::class, 'labelOutstanding']);
    Route::get('dn-label/print/qty-confirm/{no_dn}', [PrintController::class, 'labelQtyConfirm']);
    // Route fo get DN history
    Route::get('dn/history', [HistoryController::class, 'dnHeaderHistory']);

    //Logout route
    Route::post('logout', [AuthController::class, 'logout']);
});

// Route Supplier Subcont
Route::middleware(['auth:sanctum', 'userRole:8'])->prefix('supplier-subcont')->group(function () {

    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index']);

    // Graph PO And DN Yearly Data
    Route::get('yearData', [DashboardController::class, 'getYearlyData']);

    // Route For Calender Events
    Route::get('event', [DashboardController::class, 'calenderEvents']);

    /**
     *  Route for Subcontractor
     */
    // Route for get list subcont item
    Route::get('item/list', [SubcontController::class, 'getListItem']);
    // Route for get index subcont item (include stock)
    Route::get('item/index/{param?}', [SubcontController::class, 'getStock']);
    // Route for get index subcont transaction
    Route::get('transaction/index', [SubcontController::class, 'getListTrans']);
    // Route for store subcont transaction
    Route::post('transaction/store', [SubcontController::class, 'createTransaction']);

    /**
     *  Route for Delivery Note
     */
    // Route for show list DN Header
    Route::get('dn/index', [DnHeaderController::class, 'getListDnUser']);
    // Route for show list DN Detail
    Route::get('dn/detail/{no_dn}', [DnDetailController::class, 'getListDetailDnUser']);
    // Route for edit list DN Detail
    Route::get('dn/edit/{dn_detail_no}', [DnDetailController::class, 'edit']);
    // Route for update list DN Detail
    Route::put('dn/update', [DnDetailController::class, 'update']);
    // route view DN history
    Route::get('dn/history', [HistoryController::class, 'dnHeaderHistory']);
    // route view print DN file
    Route::get('dn/print/{no_dn}', [PrintController::class, 'dnHeaderView']);
    Route::get('dn/print/qty-confirm/{no_dn}', [PrintController::class, 'dnHeaderViewQtyConfirm']);
    Route::get('dn/print/outstanding/{outstanding}/{no_dn}', [PrintController::class, 'dnHeaderViewOutstanding']);
    // route view print DN label/ kanban
    Route::get('dn-label/print/{no_dn}', [PrintController::class, 'labelAllView']);
    Route::get('dn-label/print/outstanding/{outstanding}/{no_dn}', [PrintController::class, 'labelOutstanding']);
    Route::get('dn-label/print/qty-confirm/{no_dn}', [PrintController::class, 'labelQtyConfirm']);

    //Logout route
    Route::post('logout', [AuthController::class, 'logout']);
});

// Route Super User
Route::middleware(['auth:sanctum', 'userRole:9'])->prefix('super-user')->group(function () {
    // Route sync data
    Route::get('sync', [SyncManualController::class, 'syncManual']);

    // Route get partner list
    Route::get('partner/list', [BusinessPartnerController::class, 'GetBusinessPartner']);

    // Route For Calender Events
    Route::get('event', [DashboardController::class, 'calenderEvents']);

    /**
     *  Route for Purchase Order

     */
    // Routefor get record po with specific user
    Route::get('po/index/{bp_code}', [PoHeaderController::class, 'getListPoUser']);
    // Route for show PO Detail list
    Route::get('po/detail/{po_no}', [PoDetailController::class, 'getListDetailPo']);
    // Route for update list PO Header
    Route::put('po/update/{po_no}', [PoHeaderController::class, 'updateResponse']);
    // Route for print PO
    Route::get('po/print/{po_no}', [PrintController::class, 'poHeaderView']);
    // Route for show PO history list
    Route::get('po/history/{bp_code}', [HistoryController::class, 'poHeaderHistory']);

    /**
     *  Route for Delivery Note
     */
    // Route for show list DN Header
    Route::get('dn/index/{sp_code}', [DnHeaderController::class, 'getListDnHeaderSelected']);
    // Route for show list DN Detail
    Route::get('dn/detail/{no_dn}', [DnDetailController::class, 'getListDetailDnUser']);
    // Route for edit list DN Detail
    Route::get('dn/edit/{dn_detail_no}', [DnDetailController::class, 'edit']);
    // Route for update list DN Detail
    Route::put('dn/update', [DnDetailController::class, 'update']);
    // route view DN history
    Route::get('dn/history/{bp_code}', [HistoryController::class, 'dnHeaderHistory']);
    // route view print DN file
    Route::get('dn/print/{no_dn}', [PrintController::class, 'dnHeaderView']);
    // route view print DN label/ kanban
    Route::get('dn-label/print/{no_dn}', [PrintController::class, 'labelAllView']);
    Route::get('dn-label/print/outstanding/{outstanding}/{no_dn}', [PrintController::class, 'labelOutstanding']);
    Route::get('dn-label/print/qty-confirm/{no_dn}', [PrintController::class, 'labelQtyConfirm']);

    /**
     * Route for Performance Report
     */
    // Route for show list of performance report
    Route::get('performance-report/index/{bp_code}', [PerformanceReportController::class, 'index'])->name('index');
    // Route for download performance report
    Route::get('performance-report/file/{filename}', [PerformanceReportController::class, 'getFile']);
    // Route for store Listing Report
    Route::post('performance-report/store', [PerformanceReportController::class, 'store']);

    /**
     * Route for Forecast
     */
    // Route for get record forecast with spesific user
    Route::get('forecast/index/{bp_code}', [ForecastController::class, 'indexPurchasing']);
    // Route for store forecast file
    Route::post('forecast/store', [ForecastController::class, 'store']);
    // Route for download forecast file
    Route::get('forecast/file/{filename}', [ForecastController::class, 'getFile']);
    // Route for delete forecast file
    Route::delete('forecast/delete/{forecast}', [ForecastController::class, 'destroy']);

    /**
     * Route for Subcontractor
     */
    // Route for get list item Erp
    Route::get('item/list/item', [SubcontController::class, 'getListItemErp']);
    // Route for get list item user
    Route::get('item/list/{bp_code}', [SubcontController::class, 'getListItem']);
    // Route for admin get all list item user based on bp_code
    Route::get('item/all-list/{bp_code}', [SubcontController::class, 'adminGetAllItem']);
    // Route for get index subcont item (include stock)
    Route::get('item/index/{bp_code}', [SubcontController::class, 'getStock']);
    // Route for store subcont item
    Route::post('item/store', [SubcontController::class, 'createItem']);
    // Route for import stock subcont item
    Route::post('item/stock/initial', [SubcontController::class, 'importStockItems']);
    // Route for update subcont item
    Route::patch('item/update', [SubcontController::class, 'updateItem']);
    // Route for delete subcont item
    Route::delete('item/delete', [SubcontController::class, 'deleteItem']);
    // Route for get index subcont transaction
    Route::get('transaction/index', [SubcontController::class, 'getListTrans']);
    // Route for review subcont transaction header
    Route::get('transaction-review/header/{bp_code}', [SubcontReceiveController::class, 'reviewHeader']);
    // Route for review subcont transaction detail
    Route::get('transaction-review/detail/{no_dn}', [SubcontReceiveController::class, 'reviewDetail']);
    // Route for update review subcont transaction
    Route::patch('transaction-review/update', [SubcontReceiveController::class, 'reviewUpdate']);
    // Route for get index subcont transaction
    Route::get('transaction/index/{bp_code}/{start_date}/{end_date}', [SubcontController::class, 'getListTrans']);
    // Route for store subcont transaction
    Route::post('transaction/store', [SubcontController::class, 'createTransaction']);

    //Logout route
    Route::post('logout', [AuthController::class, 'logout']);
});

// route testing
Route::get('/listingreporttest/file/{filename}', [PerformanceReportController::class, 'getFile']);
Route::get('/forecasttest/file/{filename}', [ForecastController::class, 'getFile']);
