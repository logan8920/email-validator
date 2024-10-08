<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class batch_process_id extends Model
{
    use HasFactory;
    protected $fillable = [

    	'file_name',
		'total_jobs',
		'job_completed',
		'status',
		'created_at',
		'updated_at',
    ];

    public function email_response()
    {
    	return $this->hasMany(EmailResponse::class,"batch_id",'id');
    }

    public function retry_batch()
    {
    	return $this->hasOne(RetryInvalidEmails::class,"batch_proccess_id",'id');
    }
}
