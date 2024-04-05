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
        Schema::create('project_masters', function (Blueprint $table) {
            $table->id();
            $table->string('branch');
            $table->string('department');
            $table->string('proj_name');
            $table->string('proj_title');
            $table->string('proj_code');
            $table->string('methodology');
            $table->string('version');
            $table->string('description');
            $table->date('start_date');
            $table->date('target_date');
            $table->string('due_date');
            $table->string('duration');
            $table->string('priority');
            $table->string('risk');
            $table->string('company_code');
            $table->string('resource_id')->nullable();
            $table->string('resource_name')->nullable();
            $table->string('location')->nullable();
            $table->string('serial_no')->nullable();
            $table->string('memory_size')->nullable();
            $table->string('model')->nullable();
            $table->string('comments')->nullable();
            $table->string('type_of_resource')->nullable();
            $table->string('quantity')->nullable();
            $table->string('storage_capacity')->nullable();
            $table->string('assumption')->nullable();
            $table->string('resource_description')->nullable();
            $table->string('mac_address')->nullable();
            $table->string('subnet_mask')->nullable();
            $table->string('dns')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('gateway')->nullable();
            $table->string('soft_name')->nullable();
            $table->string('soft_version')->nullable();
            $table->string('year_of_licence')->nullable();
            $table->string('soft_serial_no')->nullable();
            $table->enum('soft_licence',['yes','no'])->nullable();
            $table->string('title')->nullable();
            $table->string('soft_quantity')->nullable();
            $table->string('soft_description')->nullable();
            $table->string('role')->nullable();
            $table->string('no_of_roles')->nullable();
            $table->string('human_resource_description')->nullable();
            $table->string('cost_type')->nullable();
            $table->string('cost_resource_name')->nullable();
            $table->string('cost_quantity')->nullable();
            $table->string('cost')->nullable();
            $table->string('total_cost')->nullable();
            $table->string('cost_description')->nullable();
            $table->string('client_id')->nullable();
            $table->string('client_role')->nullable();
            $table->string('client_name')->nullable();
            $table->string('client_website')->nullable();
            $table->string('client_domain')->nullable();
            $table->enum('client_insurance',['yes','no'])->nullable();
            $table->string('document_type')->nullable();
            $table->string('file')->nullable();
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
        Schema::dropIfExists('project_masters');
    }
};
