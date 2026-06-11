<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update any existing roles that are no longer valid to 'receptionist'
        DB::table('users')
            ->whereNotIn('role', ['admin', 'receptionist'])
            ->update(['role' => 'receptionist']);

        // Alter the enum column to only allow admin and receptionist
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','receptionist') NOT NULL DEFAULT 'receptionist'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin','doctor','nurse','cashier','receptionist') NOT NULL DEFAULT 'receptionist'");
    }
};
