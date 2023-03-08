<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Starkbank\TransferController as StarkbankTransfer;
use App\Http\Controllers\Starkbank\WebhookController as WebhookStarkbank;

use App\Models\Payments;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


/**
 * Classe PaymentsController responsável pelos endpoints da API de pagamento
 * @author Jéssica Vachelli - https://github.com/jessicajvs
 */
class PaymentsController extends Controller
{
    /** 
    * Array contendo os tipos de transações aceitas pela api-pagamentos e seu respectivo controller e model
    *
    * Opções:
    *       Tipo da transação da API v2 da Starkbank - Controller / Model:
    *       - transfer - TransferController / PaymentsTransfer
    *
    */
    const CLASSES_TRANSACTION = [
                            'transfer' => [
                                            'controller' => '\App\Http\Controllers\Starkbank\TransferController',
                                            'model' => '\App\Models\PaymentsTransfer',
                                            'table' =>  "payments_transfer"
                            ],
                            /*
                             * Ex. para usar um novo método de pagamento:
                             *   'boleto' => [
                             *        'controller' => '\App\Http\Controllers\Starkbank\BoletoController',
                             *        'model' => '\App\Models\PaymentsBoleto'
                             *    ],
                             */   
                        ] ; 
                        



    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(){}

    /**
     * Método responsável por controlar os métodos necessários para criar um novo payment na API v2 da Starkbank
     * 
     * 1. Validar se tipo da transação ($request->input('transaction')) é válido para esta API. De acordo com a váriavel global $classesTransaction[]
     * 2. Salva no DB os dados da transação especifica de acordo com o tipo de transação (Cada tipo de transação deve possuir uma tabela - $classesTransaction['table'])
     * 3. Salva no DB a nova transação na tabela payments
     * 
     * Ex.:
     * - Transferência Bancária 
     *         $transaction = transfer 
     *         controllers = PaymentTransferController e StarkbankTransfer
     *            
     * 
     * @param  Request  $request
     * @return \Response
     */
    public function create(Request $request)
    {   
        $transaction = $request->input('transaction');

        //********* Conferir se o tipo de transação $transaction é válido por essa api //*****
            if(!isset(self::CLASSES_TRANSACTION[$transaction]) || !isset(self::CLASSES_TRANSACTION[$transaction]['table']) 
                || !isset(self::CLASSES_TRANSACTION[$transaction]['model']) || !isset(self::CLASSES_TRANSACTION[$transaction]['controller'])
                || empty(self::CLASSES_TRANSACTION[$transaction]) || empty(self::CLASSES_TRANSACTION[$transaction]['table']) 
                            || empty(self::CLASSES_TRANSACTION[$transaction]['model']) || empty(self::CLASSES_TRANSACTION[$transaction]['controller'])){
                $content = [
                    "code" => 501,
                    "message" => [
                        [
                        "param" => "scheduled",
                        "message" => "Parâmetro transaction está incorreto ou o sistema ainda não suporta esse tipo de transação. Tipo de transação: `$transaction`"
                    ]],
                ];
                return response(json_encode($content), $content['code'])
                                        ->header('Content-Type', 'JSON');
            }
        //*********************************************************************************

        $tableTransaction = self::CLASSES_TRANSACTION[$transaction]['table'];
        $model = new (self::CLASSES_TRANSACTION[$transaction]['model'])();
        $starkbank = new (self::CLASSES_TRANSACTION[$transaction]['controller'])();
        
        //Iniciando a conexão aqui para segura o commit para caso algum método dê errado ele dá o rollback no fim deste método 
        DB::beginTransaction();

        // Salva no DB os dados da transação na TABELA ESPECIFICA de acordo com o tipo de transação *****
        // Cada tipo de transação deve possuir uma tabela
            $content = $model->createCustom($request);
            $idPaymentTransactionDB = (isset($content['id']) ? $content['id'] : "");
            if ($content['code'] != 202) {
                return response($content, $content['code'])
                    ->header('Content-Type', 'JSON');
            }
            if (empty($idPaymentTransactionDB)) { //Verificar se gerou o ID corretamente da transação no DB
                $content = [
                    "code" => 501,
                    "message" => ['Erro inesperado. Entre em contato com o administrador do sistema. Error: Controller.create 1']
                ];
                return response($content, $content['code'])
                    ->header('Content-Type', 'JSON');
            }
        //*************************************************************************************

        // Salva no DB a nova transação na TABELA PAYMENTS **********************************
            $modelPayment = new Payments();
            $contentModelPayment = $modelPayment->createCustom($request->getUser(), $transaction, $tableTransaction, $idPaymentTransactionDB);
            if ($contentModelPayment['code'] != 202) {
                return response($contentModelPayment, $contentModelPayment['code'])
                    ->header('Content-Type', 'JSON');
            }
        //***********************************************************************************

        // Envia a transação para a API v2 da starkbank, atualiza o status no DB ************
            $contentStarkbank = $starkbank->create($content['data']);
            $starkbankId =  (isset($contentStarkbank['id_starkbank']) ? $contentStarkbank['id_starkbank'] : "");
            $starkbankStatus =(isset($contentStarkbank['status_starkbank']) ? $contentStarkbank['status_starkbank'] : "");
        //***********************************************************************************

        // Se a transação foi salva com êxito na API da Starkbank atualiza o status no DB e dá commit,  ************
        // se não dá rollback em tudo.
            if (!empty($starkbankId)) {
                $obj_payments = $contentModelPayment['data'];
                $obj_payments->starkbank_id = $starkbankId;
                $obj_payments->starkbank_status = $starkbankStatus;
                $obj_payments->save();

                DB::commit();
            } else {
                DB::rollBack();
            }
        //********************************************************************************************************


        // Cria um webhook caso não exista     ******************************************************************
        $webhookStarkbank = new WebhookStarkbank();
        $content['webhook_create'] = $webhookStarkbank->create($transaction);
        //********************************************************************************************************


        return response($content, $content['code'])
            ->header('Content-Type', 'JSON');
    }


    /**
     * Atualiza o status da transação de acordo com o status na Starkbank
     * Endpoint do Webhook da Starkbank refreshTransactions.
     * 
     * Histórico de alteração de status está sendo salvo na table payments_status_history através de trigger.
     * 
     * @link https://starkbank.com/docs/api#webhook
     * 
     * @param mixed $request 
     * @return \Response
     */
    public function refreshStatusTransactions(Request $request)
    {
        $content=[
            "code" => 202,
            "message" => "Sucesso"
        ];

        if (!isset($request->event)) {
            $content = [
                "code" => 501,
                "message" => "Parâmetro Inválido."
            ];
            return response(json_encode($content), $content['code'])
                ->header('Content-Type', 'JSON');
        }
        $data = $request->event;
        $transactionSubscription = (isset($data->subscription) ? $data->subscription : "");
        $starkbankTransactionId = (isset($data->log->id) ? $data->log->id : "");

    
        if ($transactionSubscription == "transfer"){
            $transactionInfo = $starkbankTransactionId->transfer;
        } elseif ($transactionSubscription == "boleto"){
            $transactionInfo = $starkbankTransactionId->boleto;
        } elseif ($transactionSubscription == "boleto-payment"){
            $transactionInfo = $starkbankTransactionId->payment;
        } elseif ($transactionSubscription == "utility-payment"){
            $transactionInfo = $starkbankTransactionId->payment;
        }
        $transactionNewStatus = $transactionInfo->status;

        // Atualiza o status da transação ******************************************************
        $objPayments = Payments::where('starkbank_id', $starkbankTransactionId)->first();
        $objPayments->starkbank_status = $transactionNewStatus;
        $objPayments->save();
        //**************************************************************************************

        // Se o tipo de transação for transferência bancária e o novo status dela retornado pela API da starkbank for de sucesso faz upload no s3 do comprovante PDF
        if($objPayments->tp_transaction == 'transfer' &&  $transactionNewStatus == 'success'){
            $starkbankTransfer = new StarkbankTransfer();
            //Fazer upload do comprovante da transferencia no s3 
            $content['s3_upload'] = $starkbankTransfer->uploadTransferPDF($starkbankTransactionId);
        }
        //**************************************************************************************

        // Enviar a alteração de status para URL configurada no ENV*****************************
        $url_enviar_status = $_ENV['APP_URL_WEBHOOK'];
        $postData = http_build_query(array('chave' => 'valor'));
        $ch       = curl_init();
        curl_setopt($ch, CURLOPT_URL,"$url_enviar_status/?".$starkbankTransactionId);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = curl_exec($ch);
        curl_close ($ch);
        //**************************************************************************************

        return response(json_encode($content), $content['code'])
                ->header('Content-Type', 'JSON');
    }

    /**
     * Método responsável por buscar transações de acordo com os parâmetros passados na requisição
     * 
     * Possível parâmetros de pesquisa: array $listParamsPermitidos
     * 
     * @param string param_1 - nome do parâmetro 1 (obrigatório)
     * @param string param_1_value - valor do parâmetro 1 (obrigatório)
     * @param string param_2 - nome do parâmetro 2 (opcional)
     * @param string param_2_value- valor do parâmetro 2 (opcional)
     * @param string operador - [Opções: 'and' ou 'or'] (opcional)
     * @return \Response
     */
    public function getBy(Request $request){
        $listParamsPermitidos = [
            'tp_transaction',
            'starkbank_id',
            'starkbank_status',
            'created_at',
            'updated_at'
        ];


        $dataRequest['param_1'] = $request->input('param_1');
        $dataRequest['param_1_value'] = $request->input('param_1_value');
        $dataRequest['param_2'] = $request->input('param_2');
        $dataRequest['param_2_value'] = $request->input('param_2_value');
        $dataRequest['operador'] = strtoupper($request->input('operador'));

        $data = array();


        //Verifica se o param1 está vazio e se é permitido na pesquisa - param1 é obrigatório
        if(empty($dataRequest['param_1']) || empty($dataRequest['param_1_value']) || !in_array($dataRequest['param_1'], $listParamsPermitidos)){
                    $content = [
                        "code" => 501,
                        "message" => "Parâmetro Vazio ou Inválido. param_1 e param_1_value é obrigatório. Opções possíveis para param_1:  ". implode(", ",$listParamsPermitidos)
                    ];
                    return response(json_encode($content), $content['code'])
                                            ->header('Content-Type', 'JSON');   
        }
       

        //Verifica se o param2 está vazio e se é permitido na pesquisa - param2 é opcional
        if(!empty($dataRequest['param_2']) && !in_array($dataRequest['param_2'], $listParamsPermitidos)){
            $content = [
                    "code" => 501,
                    "message" => "Parâmetro Inválido param2. Opções possíveis para param_2: ". implode(", ",$listParamsPermitidos)
            ];
            return response(json_encode($content), $content['code'])
                    ->header('Content-Type', 'JSON');
        }elseif(empty($dataRequest['param_2']) && !empty($dataRequest['param_2_value'])){
            $content = [
                "code" => 501,
                "message" => "Valor do param_2 não pode ser vazio quando for enviado o param2."
            ];
            return response(json_encode($content), $content['code'])
                ->header('Content-Type', 'JSON');
        }
        $operador = "AND";
        if(!empty($dataRequest['operador']) &&  $dataRequest['operador'] != "AND"  && $dataRequest['operador'] != "OR")  {
            $content = [
                "code" => 501,
                "message" => "Parâmetro operador é inválido. Opções possíveis: 'AND' ou 'OR'"
            ];
            return response(json_encode($content), $content['code'])
                ->header('Content-Type', 'JSON');
        }else if (!empty($dataRequest['operador'])){
            $operador = $dataRequest['operador'];
        }
        
        
        if(!empty($dataRequest['param_2'])){
            if($operador == "AND"){
                $data = Payments::where($dataRequest['param_1'], '=', $dataRequest['param_1_value'])
                                ->where($dataRequest['param_2'], '=', $dataRequest['param_2_value'])->take(10)->get();
            }else{
                $data = Payments::where($dataRequest['param_1'], '=', $dataRequest['param_1_value'])
                                ->orWhere($dataRequest['param_2'], '=', $dataRequest['param_2_value'])->take(10)->get();
            }            
        }else{
            $data = Payments::where($dataRequest['param_1'], '=', $dataRequest['param_1_value'])->take(10)->get();
        }

        $content=[
            "code" => 202,
            "data" => $data
        ];

        return response($content, $content["code"])
        ->header('Content-Type', 'JSON');   
    }

}