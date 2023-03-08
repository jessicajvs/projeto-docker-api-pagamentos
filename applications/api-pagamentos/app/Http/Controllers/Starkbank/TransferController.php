<?php

namespace App\Http\Controllers\Starkbank;

use StarkBank\Project;
use StarkBank\Settings;
use StarkBank\Transfer;

use App\Http\Controllers\AWS\S3Controller;

/**
 * Controller TransferController com os métodos necessários da transação 
 * Transferência Bancária (transfer) da api v2 da Starkbank
 * 
 * @link https://starkbank.com/docs/api#transfer
 * @see StarkBank\Transfer
 * 
 * @author Jéssica Vachelli - https://github.com/jessicajvs
 */
class TransferController 
{

    /**
     * Create a new controller instance.
     * Faz autenticação na API v2 da Starkbank
     *
     * @return void
     */
    public function __construct()
    {
        /*
        * Fazendo autenticação na API da starbank
        * array com as credenciais da Starbank
        */
        $user = new Project([
            "environment" => $_ENV['SB_AMBIENTE'],
            "id" => $_ENV['SB_PROJECT_ID'],
            "privateKey" => $_ENV['SB_PRIVATE_KEY']
        ]);

        Settings::setUser($user);
    }

    /**
     * Método responsável por validar os dados de acordo com a API da Starkbank
     * 
     * @param array $data
     *     [amount,
     *      name,
     *      tp_pessoa,
     *      tax_id,
     *      tp_transfer,
     *      bank_code,
     *      branch_code,
     *      account_number,
     *      account_type,
     *      scheduled ]
     * 
     * @return array $content
     *      [
     *       "code" => $state, (202 - sucesso ou 501 - erro nos parâmetros criados)
     *       "message" => $message,
     *       "data" => $data - dados formatados e validados
     *      ]
     */
    private function validate($data){
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
                "message" => "Parâmetro obrigatório. Info: TransferController>val. " . $data['amount']
            ]);
        }else{
            if(!is_numeric($data['amount'])){
                $state=501;
                array_push($message_error, [
                    "param" => "amount",
                    "message" => "Parâmetro deve ser númerico. Formato: 0.00. Info: TransferController>val"
                ]);
            }else
            if($data['amount'] <= 0){
                $state=501;
                array_push($message_error, [
                    "param" => "amount",
                    "message" => "Parâmetro deve ser maior que zero. Info: TransferController>val"
                ]);
            }else
            if(strlen($data['amount']) > 8){
                $state=501;
                array_push($message_error, [
                    "param" => "amount",
                    "message" => "Parâmetro deve conter menos de 9 caracteres. Info: TransferController>val"
                ]);
            }else{
                $data['amount'] = number_format($data['amount'], 2, '.', '');
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
     * Método responsável por enviar uma requisição para API v2 da Starkbank para criar uma nova transação do tipo Transfer
     * 
     * @see StarkBank\Transfer
     * 
     * @param array $data
     *     [amount,
     *      name,
     *      tp_pessoa,
     *      tax_id,
     *      tp_transfer,
     *      bank_code,
     *      branch_code,
     *      account_number,
     *      account_type,
     *      scheduled ]
     * 
     * @return array $content
     *      [
     *       "code" => $state, (202 - sucesso ou 501 - erro nos parâmetros criados)
     *       "message" => $message,
     *       "data" => $data - dados formatados e validados
     *      ]
     */
    public function create($data){
        $content = $this->validate($data);
        if($content["code"] != 202){
            return $content;
        }

        $state=202;
        $message = ['Everything went right.'];

        $data = $content['data'];

        $transfers = Transfer::create([
            new Transfer([
                'amount' => (int)$data['amount'],
                'name' => $data['name'],
                'taxId' => $data['tax_id'],
                'bankCode' => $data['bank_code'],
                'branchCode' => $data['branch_code'],
                'accountNumber' => $data['account_number'],
                'accountType' => $data['account_type'],
                'scheduled' => $data['scheduled']
            ])
        ]);

        $id = "";
        $status = "";
        foreach($transfers as $transfer){
            $id = $transfer->id;
            $status = $transfer->status;
        }
            
        

        $content=[
            "code" => $state,
            "message" => $message,
            "id_starkbank" => $id,
            "status_starkbank" => $status
        ];
        

        return $content;
    }


    /**
     * Método responsável por buscar na API v2 da Starkbank o arquivo PDF do comprovante da transferência
     * 
     * @link https://starkbank.com/docs/api#transfer - Get a transfer PDF
     * 
     * @param int $idTransfer Id da Transferência na API v2 da Starkbank 
     * 
     * @return array $content
     *      [
     *       "code" => $state, (202 - sucesso ou 501 - erro nos parâmetros criados)
     *       "message" => $message,
     *       "description" => "Return upload s3",
     *      ]
     */
    public function uploadTransferPDF($idTransfer){
        $diretorioTemp = $_ENV['AWS_S3_DIRETORIO_TEMP'];
        $nomeArquivo = "comprovante_transfer_".$idTransfer.".pdf";

        //Busca o PDF da transação de acordo com o $idTransfer na API v2 da Starkbank
        $pdf = Transfer::pdf($idTransfer);
        //Salva o conteúdo do arquivo no arquivo $nomeArquivo do diretório $diretorioTemp
        $fp = fopen($diretorioTemp."/".$nomeArquivo, 'w');
        fwrite($fp, $pdf);
        fclose($fp);
        
        //Faz o upload do arquivo de comprovante no S3
        $s3 = new S3Controller();
        $retorno = $s3->upload($nomeArquivo);

        $content=[
            "code" => 202,
            "description" => "Return upload s3",
            "message" => $retorno,
        ];

        //Deleta o arquivo da pasta temporária
        unlink($diretorioTemp."/".$nomeArquivo);

        return $content;

    }
}