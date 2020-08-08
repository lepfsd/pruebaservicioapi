<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserWallet extends Model
{
    //
    protected $fillable = array('user_id', 'type', 'identification', 'telephone', 'amount', 
        'description', 'token', 'status');

    
    
}
