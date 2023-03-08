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
            $table->string('tp_transaction', 100)
                                        ->nullable($value = false); 
            $table->string('transaction_table_name', 20)
                                        ->nullable($value = false); 
            $table->integer('transaction_table_id')
                                        ->nullable($value = false); 
            $table->bigInteger('starkbank_id')
                                        ->nullable($value = true); 
            $table->string('starkbank_status')
                                        ->nullable($value = true); 
            $table->bigInteger('users_id')
                                        ->nullable($value = true); 
            $table->timestamp('created_at', $precision = 0)
                                        ->nullable($value = false)
                                        ->useCurrent();
            $table->timestamp('updated_at', $precision = 0)
                                        ->nullable($value = false)
                                        ->useCurrent();
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
