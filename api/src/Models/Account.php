<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $table = 'accounts';
    
    // Disable timestamps if your table doesn't have created_at/updated_at
    public $timestamps = false;
    
    protected $fillable = ['owner_name', 'currency'];

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
