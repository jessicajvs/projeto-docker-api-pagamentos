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
        Schema::create('payments_transfer', function (Blueprint $table) {
            $table->id();

            $table->decimal('amount', $precision = 8, $scale = 2)
                                        ->nullable($value = false); 
            $table->string('name', 250)
                                        ->nullable($value = false); 
            $table->enum('tp_pessoa', ['PF', 'PJ'])
                                        ->nullable($value = false); 
            $table->string('tax_id', 14)
                                        ->nullable($value = false);
            $table->enum('tp_transfer', ['Pix', 'TED'])
                                        ->nullable($value = false);  
            $table->string('bank_code', 8)
                                        ->nullable($value = false); 
            $table->string('branch_code', 10)
                                        ->nullable($value = false); 
            $table->string('account_number', 15)
                                        ->nullable($value = false); 
            $table->enum('account_type', ['checking', 'savings', 'salary'] )
                                        ->nullable($value = false)
                                        ->default("checking"); //"checking" is the default.
            $table->date('scheduled', $precision = 0)
                                        ->nullable($value = false)
                                        ->useCurrent();
            $table->timestamp('created_at', $precision = 0)
                                        ->nullable($value = false)
                                        ->useCurrent();
            $table->timestamp('updated_at', $precision = 0)
                                        ->nullable($value = false)
                                        ->useCurrent();


            //$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments_transfer');
    }
};
