<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

/**
 * Classe responsável pela manipulação dos dados da tabela payments_status_history do db
 * @author Jéssica Vachelli - https://github.com/jessicajvs
 */
class PaymentsStatusHistory extends Model {
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payments_status_history';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The storage format of the model's date columns.
     *
     * @var string
     */
    protected $dateFormat = 'U';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['payment_id',
                            'payment_transfer_id',
                            'starkbank_id',
                            'starkbank_status',
                            'created_at',
                            'updated_at'
                            ];

    private function getByUser(){

    }

}