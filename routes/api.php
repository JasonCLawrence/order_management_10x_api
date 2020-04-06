<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::namespace('\App\Http\Controllers\Api\V1')->prefix('v1')->group(function () {


	Route::namespace("Auth")->prefix("auth")->group(function () {

		//login
		Route::post("login", "LoginController@login")->name('login');
		Route::get("refresh-token", "LoginController@refresh");
		Route::get("logout", "LoginController@logout");

		//password

		Route::put("change_password", "PasswordController@change")->middleware("auth:api");
		Route::post("pre_forgot", "PasswordController@forgot");
		Route::put("post_forgot", "PasswordController@forgotPost");
	});

	//user

	Route::namespace("User")->prefix("user")->middleware(['auth:api'])->group(function () {

		Route::get("me", "UserController@me");
		Route::put("me/update_location", "UserController@updateLocation");
		Route::post("", "UserController@create");
		Route::get("roles", "UserController@roles");
		Route::get("drivers/location", "UserController@driverLocation");
		Route::get("all", "UserController@all");
		Route::get("", "UserController@query");
		Route::get("{id}", "UserController@findById");
		Route::put("{id}", "UserController@updateUser");
		Route::put("{id}/roles", "UserController@updateRoles");
		Route::put("{id}/password", "UserController@changePassword");
		Route::put("{id}/email", "UserController@updateEmail");
		Route::post("{id}/delete", "UserController@delete");
	});

	Route::namespace("Customer")->prefix("customer")->middleware(['auth:api'])->group(function () {

		Route::post("", "CustomerController@create");
		Route::get("all", "CustomerController@all");
		Route::get("", "CustomerController@query");
		Route::put("{id}", "CustomerController@update");
		Route::get("{id}", "CustomerController@show");
		Route::delete("{id}", "CustomerController@delete");
	});

	Route::namespace("Fleet")->prefix("fleet")->middleware(['auth:api'])->group(function () {

		Route::post("", "FleetController@create");
		Route::put("{id}", "FleetController@update");
		Route::get("{id}", "FleetController@show");
		Route::get("", "FleetController@all");
		Route::delete("{id}", "FleetController@delete");
	});

	Route::namespace("Warehouse")->prefix("warehouse")->middleware(['auth:api'])->group(function () {

		Route::post("", "WarehouseController@create");
		Route::get("all", "WarehouseController@all");
		Route::put("{id}", "WarehouseController@update");
		Route::get("{id}", "WarehouseController@show");
		Route::get("", "WarehouseController@query");
		Route::delete("{id}", "WarehouseController@delete");
	});


	Route::namespace("Order")->prefix("order")->middleware(['auth:api'])->group(function () {

		Route::post("", "OrderController@create");
		Route::put("{id}", "OrderController@update");
		Route::get("my_orders", "OrderController@myorders");
		Route::get("{id}", "OrderController@show");
		Route::get("", "OrderController@all");
		Route::delete("{id}", "OrderController@delete");

		Route::post("invoice_upload", "OrderController@invoice_upload");

		// bulk ops
		Route::post("bulk_set_driver", "OrderController@bulk_set_driver");
		Route::post("bulk_set_warehouse", "OrderController@bulk_set_warehouse");
		Route::post("bulk_set_incomplete", "OrderController@bulk_set_incomplete");
		Route::post("bulk_delete", "OrderController@bulk_delete");

		Route::post("{id}/note", "OrderNoteController@create");
		Route::delete("{id}/note/{note_id}", "OrderNoteController@delete");
		Route::post("{id}/complete", "OrderController@complete");
		Route::post("{id}/incomplete", "OrderController@incomplete");
		Route::post("{id}/warehouse_signature", "OrderController@warehouse_signature");
		Route::post("bulk_warehouse_signature", "OrderController@bulk_warehouse_signature");


		//Route::post("{id}/checklist", "OrderChecklistController@create");
		Route::put("{id}/checklist/{check_id}", "OrderChecklistController@update");
		Route::delete("{id}/checklist/{check_id}", "OrderChecklistController@delete");
		Route::get("{id}/checklist", "OrderChecklistController@getAll");
		Route::post("{id}/checklist", "OrderChecklistController@update");

		Route::put("{id}/task/{task_id}/check", "OrderChecklistController@check");
		Route::put("{id}/task/{task_id}/uncheck", "OrderChecklistController@uncheck");
		Route::delete("{id}/task/{task_id}", "OrderChecklistController@delete");

		Route::get("{id}/invoice", "OrderInvoiceController@getAll");
		Route::post("{id}/invoice", "OrderInvoiceController@update");
		Route::put("{id}/invoice/item/{invoice_item_id}/quantity", "OrderInvoiceController@updateQuantity");
		Route::get("{id}/download", "OrderInvoiceController@download");
		Route::post("{id}/email_client", "OrderInvoiceController@emailToClient");


		Route::post("{id}/attachment", "OrderAttachmentController@create");
		Route::delete("{id}/attachment/{attachment_id}", "OrderAttachmentController@delete");

		Route::get('{id}/summary', 'OrderSummaryController@downloadSummary');
		Route::get('{id}/order_complete_email', 'OrderController@order_complete_email');
	});

	Route::namespace("Company")->prefix("company")->middleware(['auth:api'])->group(function () {

		Route::get("", "CompanyController@show");
		Route::put("", "CompanyController@update");
	});


	Route::namespace("Dashboard")->prefix("dashboard")->middleware(['auth:api'])->group(function () {

		Route::get("", "DashboardController");
	});

	Route::namespace("Report")->prefix("report")->middleware(['auth:api'])->group(function () {

		Route::get("details", "ReportController@details");
		Route::get("headers", "ReportController@headers");
	});

	Route::namespace("AuditLog")->prefix("auditlogs")->middleware(['auth:api'])->group(function () {
		Route::get("", "AuditLogController@all");
		Route::delete("{id}", "AuditLogController@delete");
	});
});
