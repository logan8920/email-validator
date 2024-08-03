<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Artisan;
use App\Models\{EmailResponse,batch_process_id};
use Illuminate\Support\Facades\Log;

class MultiCurlsHandler implements ShouldQueue
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
        $this->jobId = Uuid::uuid4()->toString();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        Log::info('Job started', ['jobId' => $this->jobId, 'time' => now(), 'email' => count($this->data['emails'])]);
        $batch_process = batch_process_id::whereId($this->batch_id)->first();

        // Initialize cURL multi handle
        $mh = curl_multi_init();
        $curlHandles = [];

        // URL for the request
        //$url = 'http://38.242.135.82:5000/check-emails';
        //$url = 'http://192.168.10.175:5000/check-emails';
        $url = 'http://192.168.10.175:5000/check-emails';

        // Split the data into chunks if necessary
        $chunks = array_chunk($this->data['emails'], 20);
       /* $chunks = [
            array_splice($this->data['emails'], 0,15),
            array_splice($this->data['emails'], 0,10),
            array_splice($this->data['emails'], 0,25),
            array_splice($this->data['emails'], 0,30)
        ];*/
        // print_r($chunks);
        // die();
        // Create and add multiple cURL handles
        $loop_count = 1;
        foreach ($chunks as $i => $chunk) {
            $url = 'http://38.242.135.82:500'.($loop_count++).'/check-emails';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['emails' => $chunk]));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'Content-Type: application/json',
            ]);

            $curlHandles[$i] = $ch;
            curl_multi_add_handle($mh, $ch);
            Log::info('Request Endpoint', ['url' => $url, 'time' => now()]);

            if ($loop_count  > 10) {
                $loop_count = 1;
            }
        }

        // Execute all cURL handles
        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);

        // Collect responses and handle errors
        foreach ($curlHandles as $ch) {
            $response = curl_multi_getcontent($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if ($statusCode == 200) {
                $body = json_decode($response, true);

                foreach ($body['data'] as $key => &$value) {
                    $value['mx_record'] = json_encode($value['mx_record']);
                }

                foreach ($body['data'] as $key => &$value) {
                    $value['batch_id'] = $this->batch_id;
                    $value['created_at'] = now();
                    $value['updated_at'] = now();
                }

                EmailResponse::insert($body['data']);
            } else {
                Log::error('Request failed', ['jobId' => $this->jobId, 'status' => $statusCode, 'error' => curl_error($ch), 'time' => now()]);
            }

            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }

        // Close the multi handle
        curl_multi_close($mh);

        // Update batch process
        $batch_process->update([
            'job_completed' => ($batch_process->job_completed + count($chunks))
        ]);

        if ($batch_process->total_jobs === $batch_process->job_completed) {
            $batch_process->update([
                'status' => '1'
            ]);
        }

        Log::info('Job completed successfully', ['jobId' => $this->jobId, 'time' => now()]);
    }

}
