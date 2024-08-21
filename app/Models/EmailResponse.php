<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailResponse extends Model
{
    use HasFactory;
    protected $fillable = [
'batch_id',
'email',
'status',
'user',
'time',
'domain',
'disposable',
'role',
'free_email',
'valid_format',
'reason',
'mx_domain',
'mx_record',
'retry',
'created_at',
'updated_at',

    ];
}
