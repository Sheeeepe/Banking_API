<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $table = 'transactions';
    
    // Use true if you have created_at and updated_at
    // But your original query 'ORDER BY created_at DESC' implies you have created_at at least.
    const UPDATED_AT = null;
    
    protected $fillable = [
        'account_id', 
        'type', 
        'amount', 
        'description', 
        'balance_after'
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
