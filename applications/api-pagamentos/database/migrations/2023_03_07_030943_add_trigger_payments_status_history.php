<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 
 * Migration para criar a trigger pra salvar o log de status da tabela payments
 * php artisan make:migration 
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       
        
            DB::unprepared('CREATE TRIGGER payments_after_update
            AFTER UPDATE ON payments
            FOR EACH ROW
            BEGIN
                INSERT INTO payments_status_history
                SET payment_id = NEW.id,
                    payment_transfer_id = NEW.transaction_table_id,
                    starkbank_id = NEW.starkbank_id,
                    starkbank_status = NEW.starkbank_status,
                    created_at = CURRENT_TIMESTAMP,
                    updated_at = CURRENT_TIMESTAMP;

            END ');

            DB::unprepared("CREATE TRIGGER payments_after_insert
            AFTER INSERT ON payments
            FOR EACH ROW
            BEGIN
                IF NEW.starkbank_status != '' THEN
                    INSERT INTO payments_status_history
                    SET payment_id = NEW.id,
                        payment_transfer_id = NEW.transaction_table_id,
                        starkbank_id = NEW.starkbank_id,
                        starkbank_status = NEW.starkbank_status,
                        created_at = CURRENT_TIMESTAMP,
                        updated_at = CURRENT_TIMESTAMP;
                END IF;
            END ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared('DROP TRIGGER payments_after_insert');
        DB::unprepared('DROP TRIGGER payments_after_update');
    }
};
