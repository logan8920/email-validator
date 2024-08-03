<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
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
Route::get('/update-progress/{batch}',[ExcelBulkUploadController::class,'updateProgress'])->name('update.progress');

Route::post('/fetch-job-id',[ExcelBulkUploadController::class, 'fetch_job_id'])->name('fetch.job.id');	

Route::prefix('/cmd')->group(function() {

	Route::get('queue-clear', function() {
		Artisan::call("queue:clear");
		echo "success";
	});

	Route::get('optimize', function() {
		Artisan::call("optimize");
		echo "success";
	});

	Route::get('migrate', function() {
		Artisan::call("migrate");
		echo "success";
	});

	Route::get('migrate-fresh-seed', function() {
		Artisan::call("migrate:fresh --seed");
		echo "success";
	});

	Route::get('migrate', function() {
		Artisan::call("migrate");
		echo "success";
	});

	Route::get('migrate-rollback', function() {
		Artisan::call("migrate:rollback");
		echo "success";
	});

	Route::get('queue-work', function() {
		Artisan::call("queue:work");
		echo "success";
	});

	Route::get('queue-work-paraller', function() {
		set_time_limit(0);
		Artisan::call("queue:work --queue=default --sleep=3 --tries=3");
		Artisan::call("optimize");
		echo "success";
	});



	Route::get('queue-work-exec', function() {
		set_time_limit(0);
	    // Define the command to run
	    $cmd = 'php ' . base_path('artisan') . ' queue:work --queue=default --sleep=3 --tries=3';
	    
	    // Execute the command and capture the output
	    exec($cmd, $output, $returnVar);
	    
	    // Check if the command was successful
	    if ($returnVar !== 0) {
	        return response()->json([
	            'status' => 'error',
	            'message' => 'There was an error running the queue worker.',
	            'output' => $output
	        ], 500);
	    }

	    return response()->json([
	        'status' => 'success',
	        'message' => 'Queue worker started successfully.',
	        'output' => $output
	    ]);
	});

});


Route::get('/job-stop/{job}', [ExcelBulkUploadController::class, 'job_stop'])->name('job.stop');
Route::post('/upload-paraller',[ExcelBulkUploadController::class,'handleUploadParaller'])->name('upload.paraller');
