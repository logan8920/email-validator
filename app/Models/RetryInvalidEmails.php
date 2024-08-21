<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RetryInvalidEmails extends Model
{
    use HasFactory;
    protected $table = 'table_retry_batch_proccess_id';
    protected $fillable = [
    	'batch_proccess_id',
    	'file_name',
		'total_jobs',
		'job_completed',
		'status',
		'created_at',
		'updated_at',
    ];
}
