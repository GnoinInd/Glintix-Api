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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->decimal('amount', 10, 2);
            $table->timestamp('payment_date');
            $table->string('payment_method');
            $table->string('transaction_id');
            $table->enum('payment_status', ['pending', 'completed', 'failed'])->default('pending');
            $table->string('company_code')->unique();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('expiry_date')->nullable();
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
};
