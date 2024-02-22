<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        DB::statement('ALTER TABLE company_user_accesses MODIFY COLUMN status ENUM("active","inactive") DEFAULT "inactive"');

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE company_user_accesses MODIFY COLUMN status ENUM("active","inactive") DEFAULT "active"');
    }
};
