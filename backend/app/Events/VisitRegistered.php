<?php

namespace App\Events;

use App\Models\Visit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VisitRegistered
{
    use Dispatchable, SerializesModels;

    public function __construct(public Visit $visit) {}
}
