<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Artisan;
use App\Imports\EmailUploadExcel;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\{Email,batch_process_id,EmailResponse};
use App\Jobs\{EmailBatchValidator,MultiCurlsHandler,CoreEamilValidate};
use DB;
use Illuminate\Support\Facades\Log;
use App\Exports\BatchResponses;

class ExcelBulkUploadController extends Controller
{


    public function index()
    {
        $batch_ids = batch_process_id::orderBy('id','desc')->get();
        return view('welcome',compact('batch_ids'));
    }
    /**
     * Function to get Excel Data in Array
     */
    public function handleUploads(Request $request)
    {
        set_time_limit(0);
        // Validate the uploaded file
        $validator = Validator::make(
            $request->all(),
            [
                'excel' => ['required', 'file', 'max:2042', 'mimes:xlsx,xls']
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'validationError' => $validator->errors(),
            ], 200);
        }

        try {
            // Process the uploaded file
            $fileName = $request->file('excel')->getClientOriginalName();
            $data = Excel::toArray(new EmailUploadExcel, $request->file('excel'));
            $heading = array_shift($data[0]);
            $batchId = batch_process_id::create([
                'file_name' => $fileName,
                'job_completed' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => NULL
            ]);


            // Prepare the emails for insertion
            $insertedEmails = [];
            foreach ($data[0] as $value) {
                $insertedEmails[] = [
                    'email' => $value[0],
                    'created_at' => now()
                ];
            }

            // Insert emails into the database
            // DB::beginTransaction();
            // Email::insert($insertedEmails);
            // DB::commit();

            // Prepare the emails for validation
            $emails['emails'] = [];
            foreach ($data[0] as $value) {
                if (!in_array($value[0], $emails['emails'])) {
                    $emails['emails'][] = $value[0];
                }
            }

            // Dispatch the job to validate emails
            $chunks = array_chunk($emails['emails'], 20);

            $batchId->update([
                'total_jobs' => count($chunks)
            ]);
            foreach ($chunks as $chunk) {
                //dispatch(new MultiCurlsHandler(['batch_id' => $batchId->id, 'emails' => $chunk]));
                dispatch(new EmailBatchValidator(['batch_id' => $batchId->id, 'emails' => $chunk]));
                //EmailBatchValidator::dispatch(['batch_id' => $batchId,'emails' => $chunk]);
                //Artisan::call('email:process-batch', ['batch_id' => $batchId,'emails' => $chunk]);
            }


            // Return response to the user immediately
            return redirect()->back()->with('success','Data Imported Successfully and Job is running in the background!');
            /*return response()->json([
                'success' => $fileName . ' Data Imported Successfully and Job is running in the background!',
                'reloadReq' => false
            ], 200);*/
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 200);
        }
    }


    public function downloadBatch(batch_process_id $batch)
    {
        return Excel::download(new BatchResponses($batch), 'batch'.$batch->id.'.'.(rand(1111,99999)).'.xlsx');
    }


    public function updateProgress(batch_process_id $batch)
    {
        try {
           $widths = round(($batch->job_completed / $batch->total_jobs) * 100);
           $width = ($widths ? $widths : 1).'%';
           $status = $batch->status == '1' ? 'Completed' : 'Pending';
           $file_name = $batch->file_name;
           $success = true;
           return response()->json(compact(
            'width',
            'status',
            'file_name',
            'success'
           ),200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ],200);  
        }
    }

    public function fetch_job_id(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'excel' => ['required', 'file', 'max:20420', 'mimes:xlsx,xls']
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'validationError' => $validator->errors(),
            ], 200);
        }

        $fileName = $request->file('excel')->getClientOriginalName();

        try {

            $batch_process_id = batch_process_id::create([
                'file_name' => $fileName,
                'job_completed' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => NULL
            ]);

           return response()->json([
                'job_id' => $batch_process_id->id,
                'success' => true
            ],200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ],200);  
        }
    }

    /**
     * Function to stop the job
     * @param $job
     * @return JSON
     **/
    public function job_stop(batch_process_id $job)
    {
        try {

            $job->update(['status' => '2']);

            return response()->json([
                'job_id' => $job,
                'success' => true
            ],200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ],200);  
        }
    }


    /**
     * Function to get Excel Data in Array
     */
    public function handleUploadParaller(Request $request)
    {
        set_time_limit(0);
       $rawData = file_get_contents('php://input');

        // Decode the JSON data
        $data = json_decode($rawData, true);
               // dd($data);
        // Validate the uploaded file
        $validator = Validator::make(
            $data,
            [
                'emails.*' => 'required|email',
                'jobId' => 'required|numeric'
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'validationError' => $validator->errors(),
            ], 200);
        }

        try {
            // Process the uploaded file
            /*$fileName = $request->file('excel')->getClientOriginalName();
            $data = Excel::toArray(new EmailUploadExcel, $request->file('excel'));
            $heading = array_shift($data[0]);
            $batchId = batch_process_id::create([
                'file_name' => $fileName,
                'job_completed' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => NULL
            ]);*/


            // Prepare the emails for insertion
            /*$insertedEmails = [];
            foreach ($data[0] as $value) {
                $insertedEmails[] = [
                    'email' => $value[0],
                    'created_at' => now()
                ];
            }*/

            // Insert emails into the database
            // DB::beginTransaction();
            // Email::insert($insertedEmails);
            // DB::commit();

            // Prepare the emails for validation
            /*$emails['emails'] = [];
            foreach ($data[0] as $value) {
                if (!in_array($value[0], $emails['emails'])) {
                    $emails['emails'][] = $value[0];
                }
            }

            // Dispatch the job to validate emails
            $chunks = array_chunk($emails['emails'], 50);

            $batchId->update([
                'total_jobs' => count($chunks)
            ]);*/
          /*  foreach ($chunks as $chunk) {*/
                EmailBatchValidator::dispatch(['batch_id' => $data['jobId'],'emails' => $data['emails']]);
                //Artisan::call('email:process-batch', ['batch_id' => $batchId,'emails' => $chunk]);
            /*}*/


            // Return response to the user immediately
           /* return redirect()->back()->with('success','Job id '.$data['jobId'].' Successfully and Job is running in the background!');*/
            return response()->json([
                'success' => 'Job id '.$data['jobId'].' Successfully and Job is running in the background!',
                'reloadReq' => false
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 200);
        }
    }

    /**
     * Function to get Excel Data in Array
     */
    public function handleUpload(Request $request)
    {
        //set_time_limit(0);
        // Validate the uploaded file
        $validator = Validator::make(
            $request->all(),
            [
                'excel' => ['required', 'file', 'max:2042', 'mimes:xlsx,xls']
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'validationError' => $validator->errors(),
            ], 200);
        }

        try {
            // Process the uploaded file
            $fileName = $request->file('excel')->getClientOriginalName();
            $data = Excel::toArray(new EmailUploadExcel, $request->file('excel'));
            $heading = array_shift($data[0]);
            $batchId = batch_process_id::create([
                'file_name' => $fileName,
                'job_completed' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => NULL
            ]);


            // Prepare the emails for insertion
            $insertedEmails = [];
            foreach ($data[0] as $value) {
                $insertedEmails[] = [
                    'email' => $value[0],
                    'created_at' => now()
                ];
            }

            // Insert emails into the database
            // DB::beginTransaction();
            // Email::insert($insertedEmails);
            // DB::commit();

            // Prepare the emails for validation
            $emails['emails'] = [];
            foreach ($data[0] as $value) {
                if (!in_array($value[0], $emails['emails'])) {
                    $emails['emails'][] = $value[0];
                }
            }

            // Dispatch the job to validate emails
            //$chunks = array_chunk($emails['emails'], 10);

            /*$batchId->update([
                'total_jobs' => count($chunks)
            ]);*/
          /*  foreach ($chunks as $chunk) {*/
                 dispatch(new MultiCurlsHandler(['batch_id' => $batchId->id, 'emails' => $emails['emails']]));
                //EmailBatchValidator::dispatch(['batch_id' => $batchId,'emails' => $chunk]);
                //Artisan::call('email:process-batch', ['batch_id' => $batchId,'emails' => $chunk]);
            /*}*/


            // Return response to the user immediately
            return redirect()->back()->with('success','Data Imported Successfully and Job is running in the background!');
            /*return response()->json([
                'success' => $fileName . ' Data Imported Successfully and Job is running in the background!',
                'reloadReq' => false
            ], 200);*/
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 200);
        }
    }



    /**
     * Function to get Excel Data in Array
     */
    public function handleUploadss(Request $request)
    {
        set_time_limit(0);
        // Validate the uploaded file
        $validator = Validator::make(
            $request->all(),
            [
                'excel' => ['required', 'file', 'max:2042', 'mimes:xlsx,xls']
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'validationError' => $validator->errors(),
            ], 200);
        }

        try {
            // Process the uploaded file
            $fileName = $request->file('excel')->getClientOriginalName();
            $data = Excel::toArray(new EmailUploadExcel, $request->file('excel'));
            $heading = array_shift($data[0]);
            $batchId = batch_process_id::create([
                'file_name' => $fileName,
                'job_completed' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => NULL
            ]);


            // Prepare the emails for insertion
            $insertedEmails = [];
            foreach ($data[0] as $value) {
                $insertedEmails[] = [
                    'email' => $value[0],
                    'created_at' => now()
                ];
            }

            // Insert emails into the database
            // DB::beginTransaction();
            // Email::insert($insertedEmails);
            // DB::commit();

            // Prepare the emails for validation
            $emails['emails'] = [];
            foreach ($data[0] as $value) {
                if (!in_array($value[0], $emails['emails'])) {
                    $emails['emails'][] = $value[0];
                }
            }

            // Dispatch the job to validate emails
            $chunks = array_chunk($emails['emails'], 20);

            $batchId->update([
                'total_jobs' => count($chunks)
            ]);
            foreach ($chunks as $chunk) {
                //dispatch(new MultiCurlsHandler(['batch_id' => $batchId->id, 'emails' => $chunk]));
                dispatch(new CoreEamilValidate(['batch_id' => $batchId->id, 'emails' => $chunk]));
                //EmailBatchValidator::dispatch(['batch_id' => $batchId,'emails' => $chunk]);
                //Artisan::call('email:process-batch', ['batch_id' => $batchId,'emails' => $chunk]);
            }


            // Return response to the user immediately
            return redirect()->back()->with('success','Data Imported Successfully and Job is running in the background!');
            /*return response()->json([
                'success' => $fileName . ' Data Imported Successfully and Job is running in the background!',
                'reloadReq' => false
            ], 200);*/
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 200);
        }
    }
}
