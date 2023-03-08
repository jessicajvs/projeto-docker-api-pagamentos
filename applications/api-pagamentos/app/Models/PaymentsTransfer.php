<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Classe responsável pela manipulação dos dados da tabela payments_transfer do db
 * 
 * @author Jéssica Vachelli - https://github.com/jessicajvs
 */
class PaymentsTransfer extends Model {
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payments_transfer';

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
    protected $fillable = ['amount',
                            'name',
                            'tp_pessoa',
                            'tax_id',
                            'tp_transfer',
                            'bank_code',
                            'branch_code',
                            'account_number',
                            'account_type',
                            'scheduled'];

    /**
     * Método responsável por validar os dados de acordo com o DB
     * 
     * @param \Request com os dados da requisição contendo os dados serem validados.
     * 
     * @return array $content
     *      [
     *       "code" => $state, (202 - sucesso ou 501 - erro nos parâmetros criados)
     *       "message" => $message,
     *       "data" => $data - dados formatados e validados
     *      ]
     */
    private function validate(Request $request){
        $data['amount'] = $request->input('amount');
        $data['name'] = $request->input('name');
        $data['tp_pessoa'] = strtoupper($request->input('tp_pessoa'));
        $data['tax_id'] = $request->input('tax_id');
        $data['tp_transfer'] = strtoupper($request->input('tp_transfer'));
        $data['bank_code'] = $request->input('bank_code');
        $data['branch_code'] = $request->input('branch_code');
        $data['account_number'] = $request->input('account_number');
        $data['account_type'] = strtolower($request->input('account_type'));
        $data['scheduled'] = $request->input('scheduled');

        $state=202;
        $message_error = [];
        $message = [];
        
        /*
        * amount REQUIRED 
        * A positive integer that represents the amount in cents to be transferred. Example: amount=100 (R$1.00)
        */
        if(empty($data['amount'])){
            $state=501;
            array_push($message_error, [
                "param" => "amount",
                "message" => "Parâmetro obrigatório."
            ]);
        }else{
            if(!is_numeric($data['amount'])){
                $state=501;
                array_push($message_error, [
                    "param" => "amount",
                    "message" => "Parâmetro deve ser númerico."
                ]);
            }else
            if($data['amount'] <= 0){
                $state=501;
                array_push($message_error, [
                    "param" => "amount",
                    "message" => "Parâmetro deve ser maior que zero."
                ]);
            }else
            if(strlen($data['amount']) > 8){
                $state=501;
                array_push($message_error, [
                    "param" => "amount",
                    "message" => "Parâmetro deve conter menos de 9 caracteres."
                ]);
            }
        }

        /*
        * name REQUIRED
        * Receiver full name. Example: "Joana da Silva"
        */
        if(empty($data['name'])){
            $state=501;
            array_push($message_error, [
                "param" => "name",
                "message" => "Parâmetro obrigatório."
            ]);
        }else{
            if(strlen($data['name']) <= 3){
                $state=501;
                array_push($message_error, [
                    "param" => "name",
                    "message" => "Parâmetro inválido. Preencha com o nome completo do recebedor"
                ]);
            }else
            if(strlen($data['name']) > 250){
                $state=501;
                array_push($message_error, [
                    "param" => "name",
                    "message" => "Parâmetro deve conter menos de 250 caracteres."
                ]);
            }
        }

        /*
        * tp_pessoa REQUIRED
        * Define quantos caracteres terá o taxId
        * PF - CPF (11 digits formatted or unformatted) 
        * PJ - CNPJ (14 digits formatted or unformatted)
        */
        if(empty($data['tp_pessoa'])){
            $state=501;
            array_push($message_error, [
                "param" => "tp_pessoa",
                "message" => "Parâmetro obrigatório."
            ]);
        }else{
            if($data['tp_pessoa'] != "PJ" && $data['tp_pessoa'] != "PF"){
                $state=501;
                array_push($message_error, [
                    "param" => "tp_pessoa",
                    "message" => "Parâmetro inválido. Preencha com o tipo de pessoa (PF ou PJ - 2 caracteres): PF (Pessoa Física) ou PJ (Pessoa Jurídica)"
                ]);
            }
        }

        /*
        * taxId REQUIRED
        * Receiver CPF (11 digits formatted or unformatted) or CNPJ (14 digits formatted or unformatted). Example: 012.345.678-90
        */
        if(empty($data['tax_id'])){
            $state=501;
            array_push($message_error, [
                "param" => "tax_id",
                "message" => "Parâmetro obrigatório."
            ]);
        }else{
            $data['tax_id'] = str_replace(".", "", $data['tax_id']);
            $data['tax_id'] = str_replace("-", "", $data['tax_id']);
            if(!is_numeric($data['tax_id'])){
                $state=501;
                array_push($message_error, [
                    "param" => "tax_id",
                    "message" => "Valor do Parâmetro é inválido. Formato: Só números ou 000.000.000-00"
                ]);
            }else
            if($data['tp_pessoa'] == "PJ" && strlen($data['tax_id']) != 14){
                $state=501;
                array_push($message_error, [
                    "param" => "tp_pessoa",
                    "message" => "Parâmetro inválido. Preencha com CPF pu CNPJ de acordo com o parâmetro tp_pessoa."
                ]);
            }else
            if($data['tp_pessoa'] == "PF" && strlen($data['tax_id']) != 11){
                $state=501;
                array_push($message_error, [
                    "param" => "tp_pessoa",
                    "message" => "Parâmetro inválido. Preencha com CPF pu CNPJ de acordo com o parâmetro tp_pessoa."
                ]);
            }else
            if(strlen($data['tax_id']) != 14 && strlen($data['tax_id']) != 11){
                $state=501;
                array_push($message_error, [
                    "param" => "tp_pessoa",
                    "message" => "Parâmetro inválido. Preencha com CPF pu CNPJ de acordo com o parâmetro tp_pessoa."
                ]);
            }
        }

        /*
        * tp_transfer REQUIRED
        * this parameter specifies wether this will be a Pix or a TED transfer. 
        */
        if(empty($data['tp_transfer'])){
            $state=501;
            array_push($message_error, [
                "param" => "tp_transfer",
                "message" => "Parâmetro obrigatório."
            ]);
        }else{
            if($data['tp_transfer'] != "PIX" && $data['tp_transfer'] != "TED"){
                $state=501;
                array_push($message_error, [
                    "param" => "tp_transfer",
                    "message" => "Parâmetro inválido. Preencha com PIX pu TED de acordo com o tipo de transferência desejada.."
                ]);
            }
        }

        /*
        * bankCode REQUIRED
        * Besides informing the receiver bank, this parameter specifies wether this will be a Pix or a TED transfer. 
        * If you wish to send a Pix, pass the bank ISPB (8 digits). Example: 20018183 = StarkBank 
        * If you wish to send a TED, pass the usual bank code (1 to 3 digits). Example: 341 = Itaú
        */
        if(empty($data['bank_code'])){
            $state=501;
            array_push($message_error, [
                "param" => "bank_code",
                "message" => "Parâmetro obrigatório."
            ]);
        }else{
            if($data['tp_transfer'] == "PIX" && strlen($data['bank_code']) != 8){
                $state=501;
                array_push($message_error, [
                    "param" => "bank_code",
                    "message" => "Parâmetro inválido. Preencha com código do banco ISPB (8 digitos). Exemplo: 20018183"
                ]);
            }else
            if($data['tp_transfer'] == "TED" && strlen($data['bank_code']) > 3){
                $state=501;
                array_push($message_error, [
                    "param" => "bank_code",
                    "message" => "Parâmetro inválido. Preencha com código do banco de 1 a 3 digitos. Exemplo: 341 = Itaú"
                ]);
            }else
            if($data['tp_transfer'] == "TED" && strlen($data['bank_code']) == 2){
                $data['bank_code'] = "0" . $data['bank_code'];
            }else
            if($data['tp_transfer'] == "TED" && strlen($data['bank_code']) == 1){
                $data['bank_code'] = "00" . $data['bank_code'];
            }
        }

        /*
        * branchCode REQUIRED
        * Receiver bank account branch. Use "-" in case there is a validation digit. Example: 1234-5
        */
        if(empty($data['branch_code'])){
            $state=501;
            array_push($message_error, [
                "param" => "branch_code",
                "message" => "Parâmetro obrigatório."
            ]);
        }else 
        if(strlen($data['branch_code']) <= 3 || strlen($data['branch_code']) > 10){
            $state=501;
            array_push($message_error, [
                "param" => "branch_code",
                "message" => "Parâmetro inválido."
            ]);
        }

        /*
        * accountNumber REQUIRED
        * Receiver bank account number. Use "-" before the validation digit. Example: 876543-2
        */
        if(empty($data['account_number'])){
            $state=501;
            array_push($message_error, [
                "param" => "account_number",
                "message" => "Parâmetro obrigatório."
            ]);
        }else 
        if(strlen($data['account_number']) <= 3 || strlen($data['account_number']) > 15){
            $state=501;
            array_push($message_error, [
                "param" => "account_number",
                "message" => "Parâmetro inválido."
            ]);
        }

        /*
        * accountType OPTIONAL
        * Receiver bank account type. Options are "checking", "savings" and "salary". "checking" is the default. This parameter only has effect on Pix Transfers.
        */
        if(!empty($data['account_type'])){
            if($data['tp_transfer'] == "PIX"){
                if($data['account_type'] != "checking" && $data['account_type'] != "savings" && $data['account_type'] != "salary") {
                    $state=501;
                    array_push($message_error, [
                        "param" => "account_number",
                        "message" => "Parâmetro inválido. Options are 'checking', 'savings' and 'salary'. 'checking' is the default.."
                    ]);
                }
            }else{
                $data['account_type'] = "";
            }
        }else{
            if($data['tp_transfer'] == "PIX"){
                $data['account_type'] = "checking";
            }
        }

        /*
        * scheduled OPTIONAL
        * Schedule the transfer for a specific date. Today is the default. Schedules for today will be accepted until 16:00 (BRT) and will be pushed to next business day afterwards. Example: "2020-08-14"
        */
        if(!empty($data['scheduled'])){
            $str_scheduled = strtotime($data['scheduled']);
            $dia_scheduled = date('d', $str_scheduled);
            $mes_scheduled = date('m', $str_scheduled);
            $ano_scheduled = date('Y', $str_scheduled);
            $data_scheduled = date('Y-m-d', $str_scheduled);;

            //checkdate verifica se a data é de um dia válido no calendário
            if(!checkdate($mes_scheduled, $dia_scheduled, $ano_scheduled)){
                $state=501;
                array_push($message_error, [
                    "param" => "scheduled",
                    "message" => "Parâmetro inválido. Formato: Y-m-d. Example: '2020-08-14'"
                 ]);
            }else{
                //Verifica se a data é de um dia útil 
                $now = new \DateTime('now', new \DateTimeZone($_ENV['APP_DB_TIMEZONE']));
                $data['scheduled'] = $ano_scheduled . "-" . $mes_scheduled. "-" . $dia_scheduled;
            }
        }else{
            $now = new \DateTime('now', new \DateTimeZone($_ENV['APP_DB_TIMEZONE']));
            if($now->format('H') >= 16){
                //+1 weekdays -> Soma 1 dia útil
                $data['scheduled'] = $now->modify('+1 weekdays')->format('Y-m-d');
            }else{
                $data['scheduled'] = $now->format('Y-m-d');
            }
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
     * Método responsável por criar um novo registro na tabela payment_transfer
     * 
     * 
     * @param \Request com os dados da requisição contendo os dados serem validados.
     * 
     * @return array $content
     *      [
     *       "code" => $state, (202 - sucesso ou 501 - erro nos parâmetros criados)
     *       "message" => $message,
     *       "data" => $data - dados formatados e validados
     *      ]
     */
    public function createCustom(Request $request){
        $content = $this->validate($request);
        if($content["code"] != 202){
            return $content;
        }
        $state=202;
        $message = ['Everything went right.'];
        
        $data = $content['data'];
        $id=0;
        
        try{
            $paymentTransfer = PaymentsTransfer::create([
                'amount' => $data['amount'],
                'name' => $data['name'],
                'tp_pessoa' => $data['tp_pessoa'],
                'tax_id' => $data['tax_id'],
                'tp_transfer' => $data['tp_transfer'],
                'bank_code' => $data['bank_code'],
                'branch_code' => $data['branch_code'],
                'account_number' => $data['account_number'],
                'account_type' => $data['account_type'],
                'scheduled' => $data['scheduled']
            ]);
            
            $id = $paymentTransfer->id;
            
        }catch(\Illuminate\Database\QueryException $e){ 
            $state=501;
            $message = ['Erro inesperado. Entre em contato com o administrador do sistema. Error: 01 - create payment transfer. ', $e];
        }

        $content=[
            "code" => $state,
            "message" => $message,
            "data" => $data,
            "id" => $id
        ];
        

        return $content;
    }
}