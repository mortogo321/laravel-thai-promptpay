<?php

use Illuminate\Support\Facades\Route;
use Mortogo321\LaravelThaiPromptPay\Http\Controllers\PromptPayController;

Route::prefix('promptpay')->name('promptpay.')->group(function () {
    Route::post('generate', [PromptPayController::class, 'generate'])->name('generate');
    Route::post('payload', [PromptPayController::class, 'payload'])->name('payload');
    Route::post('download', [PromptPayController::class, 'download'])->name('download');
});
