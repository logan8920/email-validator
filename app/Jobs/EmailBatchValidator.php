<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\{EmailResponse,batch_process_id};
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Artisan;

class EmailBatchValidator implements ShouldQueue 
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $data;
    public $jobId;
    public $batch_id;

    /**
     * Create a new job instance.
     */
    public function __construct(array $data)
    {
        $this->data = ["emails" => $data['emails']];
        $this->batch_id = $data['batch_id'];
        $this->jobId = Uuid::uuid4()->toString();  // Generate a unique ID for this job
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        Log::info('Job started', ['jobId' => $this->jobId, 'time' => now(),'email' => count($this->data['emails'])]);
        $batch_process = batch_process_id::whereId($this->batch_id->id)->first();
        /* $jobids = $batch_process->job_ids ? json_decode($batch_process->job_ids) : [];
        $jobids[] = $this->jobId;
        $batch_process->update(['job_ids'=>json_encode($jobids)]);*/
        $client = new Client();
        $url = 'http://38.242.135.82:5000/check-emails';
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
                $value['batch_id'] = $this->batch_id->id;
                $value['created_at'] = now();
                $value['updated_at'] = now();
            }
            //dd($body['data']);
            EmailResponse::insert($body['data']);
            Log::info('Job completed successfully', ['jobId' => $this->jobId, 'time' => now()]);

            $batch_process->update([
                'job_completed' => ($batch_process->job_completed + 1)
            ]);

           
           // dd([$batch_process->total_jobs,$batch_process->job_completed]);
            if ($batch_process->total_jobs === $batch_process->job_completed) :
                //dd('inside');
                $batch_process->update([
                    'status' => '1'
                ]);
            endif;
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                $error = $e->getResponse()->getBody()->getContents();
                Log::error('Job failed', ['jobId' => $this->jobId, 'status' => $e->getResponse()->getStatusCode(), 'error' => $error, 'time' => now()]);
            } else {
                Log::error('Job failed', ['jobId' => $this->jobId, 'status' => 500, 'error' => 'Request failed', 'time' => now()]);
            }

            Artisan::call('queue:clear');
            $this->batch_id->update([
                'status' => '2',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
}
