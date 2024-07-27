<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class batch_process_id extends Model
{
    use HasFactory;
    protected $fillable = [

    	'job_ids',
    	'created_at',
    	'updated_at'
    ];
}
