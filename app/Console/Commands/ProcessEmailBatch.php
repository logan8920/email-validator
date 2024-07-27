<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\EmailBatchValidator;

class ProcessEmailBatch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:process-batch {batch_id} {emails*}';
    

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process a batch of emails for validation';


    public function __construct()
    {
        parent::__construct();
    }


    /**
     * Execute the console command.
     */
    public function handle()
    {
        $emails = $this->argument('emails');
        $batch_id = $this->argument('batch_id');
        //dd($emails);
        // Dispatch the job to validate emails
        EmailBatchValidator::dispatch(['emails' => $emails,'batch_id' => $batch_id]);

        $this->info('Batch processed successfully!');
    }
}
