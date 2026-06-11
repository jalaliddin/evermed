<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantUserEmail extends Model
{
    protected $connection = 'mysql'; // always central DB, not overridden by tenancy

    protected $fillable = ['tenant_id', 'email'];
}
