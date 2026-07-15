<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'full_name')) {
                $table->string('full_name')->nullable()->after('id');
            }

            if (!Schema::hasColumn('users', 'phone_number')) {
                $table->string('phone_number')->nullable()->unique()->after('email');
            }

            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('user')->after('password');
            }

            if (!Schema::hasColumn('users', 'interest_ids')) {
                $table->json('interest_ids')->nullable()->after('role');
            }
        });

        if (Schema::hasColumn('users', 'name')) {
            DB::table('users')
                ->whereNull('full_name')
                ->update(['full_name' => DB::raw('name')]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'phone_number')) {
                $table->dropUnique('users_phone_number_unique');
            }

            foreach (['full_name', 'phone_number', 'role', 'interest_ids'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
