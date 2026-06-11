<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantUserEmail extends Model
{
    protected $fillable = ['tenant_id', 'email'];
}
