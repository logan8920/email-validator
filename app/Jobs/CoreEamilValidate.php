<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Artisan;
use App\Models\{EmailResponse,batch_process_id};
use Illuminate\Support\Facades\Log;

class CoreEamilValidate implements ShouldQueue
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
        $batch_process = batch_process_id::whereId($this->batch_id)->first();

        try {
            
            $result = $this->validateEmails($this->data['emails']);


            foreach ($result as $key => &$value) {
                $value['mx_record'] = 'sadsa';
                $value['batch_id'] = $this->batch_id;
                $value['created_at'] = now();
                $value['updated_at'] = now();
            }

           
            //dd($body['data']);
            EmailResponse::insert($result);
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

           /* Artisan::call('queue:clear');
            $this->batch_id->update([
                'status' => '2',
                'updated_at' => date('Y-m-d H:i:s')
            ]);*/
        }
        
    }


   /* private function verifyEmailSMTP($email) {
        $domain = substr(strrchr($email, "@"), 1);
        $mxRecords = $this->getMxRecords($domain);
        if (empty($mxRecords)) {
            return [false, "No MX records found."];
        }
        if ( $mxRecords[0] ?? false) {
        foreach ($mxRecords as $mxRecord) {
              = fsockopen($mxRecords[0], 25, $errno, $errstr, 2);
            if ($smtpConnect) {
                fgets($smtpConnect, 515);
                fputs($smtpConnect, "HELO " . $domain . "\r\n");
                fgets($smtpConnect, 515);
                fputs($smtpConnect, "MAIL FROM: <test@example.com>\r\n");
                fgets($smtpConnect, 515);
                fputs($smtpConnect, "RCPT TO: <$email>\r\n");
                $response = fgets($smtpConnect, 515);
                fputs($smtpConnect, "QUIT\r\n");
                fclose($smtpConnect);

                if (strpos($response, '250') !== false) {
                    return [true, "SMTP check passed."];
                } else {
                    return [false, "SMTP check failed with message: $response"];
                }
            }
        }

        return [false, "All MX records failed SMTP check."];
    }*/

    private function verifyEmailSMTP($email)
    {
        $domain = substr(strrchr($email, "@"), 1);
        $mxRecords = $this->getMxRecords($domain);

        if (empty($mxRecords)) {
            return [false, "No MX records found."];
        }

        try {
            $smtpConnect = fsockopen($mxRecords[0], 25, $errno, $errstr, 2);
            if ($smtpConnect) {
                fgets($smtpConnect, 515);
                fputs($smtpConnect, "HELO " . $domain . "\r\n");
                fgets($smtpConnect, 515);
                fputs($smtpConnect, "MAIL FROM: <test@example.com>\r\n");
                fgets($smtpConnect, 515);
                fputs($smtpConnect, "RCPT TO: <$email>\r\n");
                $response = fgets($smtpConnect, 515);
                fputs($smtpConnect, "QUIT\r\n");
                fclose($smtpConnect);

                if (strpos($response, '250') !== false) {
                    return [true, "SMTP check passed."];
                } else {
                    return [false, "SMTP check failed with message: $response"];
                }
            } else {
                throw new \Exception("SMTP connection failed: $errstr ($errno)");
            }
        } catch (\Exception $e) {
            Log::error('SMTP connection failed', ['email' => $email, 'error' => $e->getMessage()]);
            return [false, "SMTP check failed: " . $e->getMessage()];
        }

        return [false, "All MX records failed SMTP check."];
    }


    private function checkEmail($email) {
        $startTime = microtime(true);

        if (!$this->isValidEmailSyntax($email)) {
            return [
                "user" => explode('@', $email)[0] ?? 'Not Found',
                'domain' => explode('@', $email)[1] ?? 'Not Found',
                "time" => microtime(true) - $startTime,
                "email" => $email,
                "status" => "invalid",
                "reason" => "Invalid email syntax.",
                "disposable" => false,
                "role" => false,
                "free_email" => false,
                "valid_format" => "invalid"
            ];
        }

        list($isValid, $smtpMessage) = $this->verifyEmailSMTP($email);
        $elapsedTime = microtime(true) - $startTime;

        return [
            "user" => explode('@', $email)[0] ?? 'Not Found',
            'domain' => explode('@', $email)[1] ?? 'Not Found',
            "time" => $elapsedTime,
            "email" => $email,
            "status" => $isValid ? "valid" : "invalid",
            "reason" => $smtpMessage,
            "disposable" => false,
            "role" => false,
            "free_email" => false,
            "valid_format" => $isValid ? "valid" : "invalid"
           /* "disposable" => isDisposableEmail($email),*/
            /*"role" => isRoleBasedEmail($email),*/
            /*"free_email" => isFreeEmail($email)*/
        ];
    }

    private function isValidEmailSyntax($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    private function getMxRecords($domain) {
        $mxRecords = [];
        if (dns_get_mx($domain, $mxRecords)) {
            return $mxRecords;
        }
        return [];
    }


    public function validateEmails(array $emails): ?array
    {
        $result = [];
        try {
            //dd($emails);
            foreach ($emails as $email) {
                $result[] = $this->checkEmail($email);
            }
            return $result;
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());  
        }
    }
}
