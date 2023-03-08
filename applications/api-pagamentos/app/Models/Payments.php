<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Classe responsável pela manipulação dos dados da tabela payments do db
 * @author Jéssica Vachelli - https://github.com/jessicajvs
 */
class Payments extends Model {
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payments';

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
    protected $fillable = ['tp_transaction',
                            'transaction_table_name',
                            'transaction_table_id',
                            'starkbank_id',
                            'starkbank_status',
                            'users_id',
                            'created_at',
                            'updated_at'
                            ];

    /**
     * Método responsável por validar os dados de acordo com o DB
     * 
     * @param string $transaction Tipo da Transação (Ex.: transfer)
     * @param string $table_transaction Nome da Tabela da Transação (Ex.: payment_transfer)
     * @param string $transaction_table_id Id da chave primária da Tabela da Transação (Ex.: payment_transfer.id)
     * 
     * @return array $content
     *      [
     *       "code" => $state, (202 - sucesso ou 501 - erro nos parâmetros criados)
     *       "message" => $message,
     *       "data" => $data - dados formatados e validados
     *      ]
     */
    private function validate($transaction, $table_transaction, $transaction_table_id){
        $data['tp_transaction'] = $transaction;
        $data['transaction_table_name'] = $table_transaction;
        $data['transaction_table_id'] = $transaction_table_id;

        $state=202;
        $message_error = [];
        $message = [];

        /*
        * tp_transaction REQUIRED
        * tp_transaction OPTIONS ['transfer']
        */
        if(empty($data['tp_transaction'])){
            $state=501;
            array_push($message_error, [
                "param" => "tp_transaction",
                "message" => "Parâmetro obrigatório."
            ]);
        }else{
            /*
            * transaction_table_name REQUIRED
            * transaction_table_name OPTIONS de acordo com a tp_transaction
            */
            if(empty($data['transaction_table_name'])){
                $state=501;
                array_push($message_error, [
                    "param" => "transaction_table_name",
                    "message" => "Parâmetro obrigatório."
                ]);
            }else{  
                //Verifica se existe a tabela no DB
                $results = DB::select("SHOW TABLES LIKE '".$data['transaction_table_name']."' ");
                if(empty($results)){
                    $state=501;
                    array_push($message_error, [
                        "param" => "transaction_table_name",
                        "message" => "Erro interno. Contacte o administrador do sistema. Erro-Payments-validade-Data: " . $data['transaction_table_name']
                    ]);
                }
                
            }
        }

        //id da tabela da transação (Ex.: payment_transfer)
        if(empty($data['transaction_table_id'])){
            $state=501;
            array_push($message_error, [
                "param" => "transaction_table_id",
                "message" => "Erro interno. Contacte o administrador do sistema. Erro-Payments-validade-Data: " . $data['transaction_table_id']
            ]);
        }


        if(!empty($message_error)){
            $message = ["errors" => $message_error];
        }

        $content=[
            "code" => $state,
            "message" => $message,
            "data" => $data
        ];
        
        return $content;
    }

    /**
     * Método responsável por criar um novo registro na tabela payment
     * 
     * @see StarkBank\Transfer
     * 
     * @param string $usernameUserLogado Username do usuário logado na API
     * @param string $transaction Tipo da Transação (Ex.: transfer)
     * @param string $table_transaction Nome da Tabela da Transação (Ex.: payment_transfer)
     * @param string $transaction_table_id Id da chave primária da Tabela da Transação (Ex.: payment_transfer.id)
     * 
     * @return array $content
     *      [
     *       "code" => $state, (202 - sucesso ou 501 - erro nos parâmetros criados)
     *       "message" => $message,
     *       "data" => $data - dados formatados e validados
     *      ]
     */
    public function createCustom($usernameUserLogado, $transaction, $table_transaction, $transaction_table_id){
        $content = $this->validate($transaction, $table_transaction, $transaction_table_id);
        if($content["code"] != 202){
            return $content;
        }
        $state=202;
        $message = ['Everything went right.'];
        
        $data = $content['data'];

        $user = DB::table('users')
                        ->where('username', $usernameUserLogado)
                        ->where('active', 1)
                        ->first();
                        
        $payment = "";
        try{
            $payment = Payments::create([
                'tp_transaction' => $data['tp_transaction'],
                'transaction_table_name' => $data['transaction_table_name'],
                'transaction_table_id' => $data['transaction_table_id'],
                'users_id' => $user->id
            ]);
        }catch(\Illuminate\Database\QueryException $e){ 
            $state=501;
            $message = ['Erro inesperado. Entre em contato com o administrador do sistema. Error: 01 - create payment. ', $e];
        }

        if(empty($payment->id)){
            $state=501;
            $message = ['Erro inesperado. Entre em contato com o administrador do sistema. Error: 01 - create payment. id is empty'];
        }

        $content=[
            "code" => $state,
            "message" => $message,
            "data" => $payment
        ];
        

        return $content;
    }

}