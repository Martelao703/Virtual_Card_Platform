<?php

use App\Http\Controllers\auth\AuthController;
use App\Http\Controllers\AuthUserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VcardController;
use App\Http\Controllers\DefaultCategoryController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\TransactionController;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('auth/login', [AuthController::class, 'login']);
// Registration
Route::post('vcards', [VcardController::class, 'store']);

Route::get('authUsers', [AuthUserController::class, 'index']);

Route::middleware('auth:api')->group(function () {
    //Auth Users
    Route::post('auth/logout',  [AuthController::class, 'logout']);
    Route::get('authUsers/me', [AuthUserController::class, 'show_me']);
    Route::get('authUsers/{authUser}', [AuthUserController::class, 'show']);


    //Users (admins)
    Route::get('users/me', [UserController::class, 'show_me']);
    Route::patch('users/{user}/password', [UserController::class, 'update_password']);
    Route::apiResource('users', UserController::class);

    //Vcards
    Route::get('vcards/me', [VcardController::class, 'show_me']);
    Route::patch('vcards/{vcard}/password', [VcardController::class, 'update_password']);
    Route::patch('vcards/{vcard}/confirmation_code', [VcardController::class, 'update_confirmation_code']);
    Route::get('/vcards/{vcard}/categories', [VcardController::class, 'show_categories']);
    Route::get('/vcards/{vcard}/transactions', [VcardController::class, 'show_transactions']);
    Route::get('/vcards/{vcard}/extraInfo', [VcardController::class, 'show_extra_info']);
    Route::delete('/vcards/{vcard}', [VcardController::class, 'destroy']);
    Route::apiResource('vcards', VcardController::class)->except(['store', 'destroy']);

    //Transactions
    Route::apiResource('transactions', TransactionController::class);

    //Categories
    Route::apiResource('categories', CategoryController::class);
    Route::get('categories/{category}/transactions', [CategoryController::class, 'getTransactions']);

    //Default Categories
    Route::apiResource('defaultCategories', DefaultCategoryController::class);

    //Vcards Statistics
    Route::get('statistics/{vcard}/spentByCategory', [StatisticsController::class, 'getAmountSpentByCategory']);
    Route::get('statistics/{vcard}/getAmountSpentByMonth', [StatisticsController::class, 'getAmountSpentByMonth']);
    Route::get('statistics/{vcard}/lastTransactions', [StatisticsController::class, 'lastTransactions']);

    //Admin:Global statistics
    Route::get('statistics/currentVcards', [StatisticsController::class, 'currentActiveVcards']);
    Route::get('statistics/sumOfVcardBalences', [StatisticsController::class, 'sumOfCurrentVcardBalances']);
    Route::get('statistics/totalTransactionsByPaymentType', [StatisticsController::class, 'totalTransactionsByPaymentType']);
    route::get('statistics/sumOfTransactionsByDate', [StatisticsController::class, 'sumOfTransactionsByDate']);
});


//----------------------Rotas do Projeto De TAES-----------------------

Route::post('vcardsTAES/transactions', [TransactionController::class, 'storeTransactionTAES']); //Make Transactions

Route::post('vcardsTAES/phone_numbers', [VcardController::class, 'validNumbers']);

Route::post('vcardsTAES', [VcardController::class, 'storeTAES']);
Route::put('vcardsTAES/{vcard}', [VcardController::class, 'updateTAES']); //Piggy Bank ADD or WITHDRAW
Route::get('vcardsTAES/{vcard}', [VcardController::class, 'getTAES']);
Route::post('vcardsTAES/login', [VcardController::class, 'loginTAES']);
Route::post('vcardsTAES/loginPin', [VcardController::class, 'loginPinTAES']);
Route::post('vcardsTAES/{vcard}', [VcardController::class, 'updateSaldo']);
Route::delete('vcardsTAES/{vcard}', [VcardController::class, 'destroyTAES']);


Route::get('vcardsTAES/{vcard}/transactions', [TransactionController::class, 'getTransactionsTAES']); //Get Transactions of specific VCard
