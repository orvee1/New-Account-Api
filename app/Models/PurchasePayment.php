<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchasePayment extends Model
{
    protected $fillable = [
        'purchase_bill_id','cash_amount','bank_amount','credit_amount'
    ];
}
