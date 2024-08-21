<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\{EmailResponse,RetryInvalidEmails as RetryInvalidEmailModals};
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Artisan;
use DB;

class RetryInvalidEmails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public $data;
    public $jobId;
    public $batch_id;
    public $url;
    public $batch_proccess_id;

    /**
     * Create a new job instance.
     */
    public function __construct(array $data)
    {
        $this->data = ["emails" => $data['emails']];
        $this->batch_id = $data['batch_id'];
        $this->jobId = Uuid::uuid4()->toString();  // Generate a unique ID for this job
        $this->url = $data['url'];
        $this->batch_proccess_id = $data['batch_proccess_id'];
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        Log::info('Job started', ['RjobId' => $this->jobId, 'time' => now(),'email' => count($this->data['emails'])]);
        $batch_process = RetryInvalidEmailModals::whereId($this->batch_id)->first();
        /* $jobids = $batch_process->job_ids ? json_decode($batch_process->job_ids) : [];
        $jobids[] = $this->jobId;
        $batch_process->update(['job_ids'=>json_encode($jobids)]);*/
        $client = new Client();
        $url = $this->url;
        //$url = 'http://192.168.10.175:5000/check-emails';

        // dd($this->data);
        try {
            $response = $client->post($url, [
                'body' => json_encode($this->data),  // Sending data as JSON
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
            ]);

            $statusCode = $response->getStatusCode();
            $body = json_decode($response->getBody(), true);
            foreach ($body['data'] as $key => &$value) {
                $value['mx_record'] = json_encode($value['mx_record']);
            }

            foreach ($body['data'] as $key => &$value) {
                $value['batch_id'] = $this->batch_proccess_id;
                //$value['created_at'] = now();
                $value['updated_at'] = now();
                $res = EmailResponse::where(['email' => $value['email'], 'batch_id' => $this->batch_proccess_id])->first();

                if ($res) {
                    // If the record exists, increment the retry count and update the record
                    $res->increment('retry');
                    $res->update($value);
                // echo "string";
                // print_r($res->toArray());
                } else {
                    // If the record doesn't exist, you might want to insert it or handle this case appropriately
                    Log::warning('EmailResponse record not found', ['email' => $value['email'], 'batch_id' => $this->batch_id]);
                    // You could insert a new record if that's appropriate
                    //EmailResponse::create($value);
                }
            }

            //dd($body['data']);
            Log::info('Job completed successfully', ['RjobId' => $this->jobId, 'time' => now()]);

            $batch_process->increment('job_completed');
            $batch_process->update(['updated_at' => NULL]);
            $batch_process = RetryInvalidEmailModals::whereId($this->batch_id)->first();
            //print_r($batch_process);
           // dd([$batch_process->total_jobs,$batch_process->job_completed]);
            echo "batch_process->total_jobs = ".$batch_process->total_jobs.' | '.'batch_process->job_completed'.$batch_process->job_completed;
            if ($batch_process->total_jobs == $batch_process->job_completed) :
                //dd('inside');
                echo "inside";

                DB::table('table_retry_batch_proccess_id')->where('id',$batch_process->id)->update(['status'=>'1','updated_at' => now()]);
                /*$batch_process->update([
                    'status' => '1'
                ]);*/
            endif;
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $error = $e->getResponse()->getBody()->getContents();
                Log::error('Job failed', ['RjobId' => $this->jobId, 'status' => $e->getResponse()->getStatusCode(), 'error' => $error, 'time' => now()]);
            } else {
                Log::error('Job failed', ['RjobId' => $this->jobId, 'status' => 500, 'error' => 'Request failed', 'time' => now()]);
            }

            //Artisan::call('queue:clear');
            $batch_process->update([
                'status' => '2',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
}
