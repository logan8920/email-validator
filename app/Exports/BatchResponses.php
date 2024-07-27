<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use App\Models\EmailResponse;

class BatchResponses implements FromArray
{
	public $batch;

	public function __construct($batch)
	{
		$this->batch = $batch;
	}
    /**
    * @return \Illuminate\Support\Collection
    */
    public function array(): array
    {
    	$email = EmailResponse::where('batch_id',$this->batch->id)->get()->toArray();
    	$data = [];

    	if (count($email)) :

    		$headers = array_keys($email[0]);
    	$data[] = $headers;
    	

    	for ($i=0; $i < count($email); $i++) { 
    		$data[] = array_map(function($value) {
    			return $value;
    		}, $email[$i]);

    	}
		
	    endif;

	    //dd($data);
        return $data;
    }
}
