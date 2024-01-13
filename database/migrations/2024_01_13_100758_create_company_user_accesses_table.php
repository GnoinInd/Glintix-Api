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
        Schema::create('company_user_accesses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('emp_id');
            $table->string('emp_code')->nullable();
            $table->string('email');
            $table->string('username');
            $table->string('password');
            $table->string('dbName');
            $table->string('company_code');
            $table->enum('role',['admin','subadmin'])->default('admin');
            $table->enum('status',['active','inactive'])->default('active');
            $table->boolean('read');
            $table->boolean('create');
            $table->boolean('edit');
            $table->boolean('delete');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('company_user_accesses');
    }
};
