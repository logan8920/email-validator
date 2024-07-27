<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExcelBulkUploadController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', [ExcelBulkUploadController::class,'index'])->name('index');


Route::post('/upload',[ExcelBulkUploadController::class,'handleUpload'])->name('upload');
Route::get('/check',[ExcelBulkUploadController::class,'check'])->name('check');	
Route::get('/download-batch/{batch}',[ExcelBulkUploadController::class,'downloadBatch'])->name('download.batch');	