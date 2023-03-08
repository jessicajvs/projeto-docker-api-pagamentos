<?php

namespace App\Http\Controllers\AWS;

use Aws\S3\S3Client;

/**
 * Classe responsável por controlar o Client do SDK do serviço S3 da AWS
 * 
 * @link https://starkbank.com/docs/api#transfer
 * 
 * @author Jéssica Vachelli - https://github.com/jessicajvs
 */
class S3Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Método responsável por autenticar no S3 Client através do SDK da AWS
     * Parâmetros das credenciais contidas no arquivo .env
     * 
     * AWS_S3_REGION
     * AWS_S3_ACCESS_KEY
     * AWS_S3_SECRET_KEY
     * 
     * @return mixed objeto contendo todas as funções do S3 client do SDK da AWS já autenticado  
     */
    private function autenticar(){
        $s3 = new S3Client([
            'region'  => $_ENV['AWS_S3_REGION'],
            'version' => 'latest',
            'credentials' => [
                'key'    => $_ENV['AWS_S3_ACCESS_KEY'],
                'secret' => $_ENV['AWS_S3_SECRET_KEY'],
            ]
        ]);

        return $s3;
    }

    /**
     * Método responsável por chamar a função no SDK da AWS para upar o comprovante PDF da transferencia no S3
     * 
     * AWS_S3_REGION
     * AWS_S3_ACCESS_KEY
     * AWS_S3_SECRET_KEY
     * 
     * @return array Código e mensagem de retorno
     */
    function upload ($nomeArquivo) {
        $bucket = $_ENV['AWS_S3_BUCKET'];
        $diretorioTemp = $_ENV['AWS_S3_DIRETORIO_TEMP'];

        //Autenticando na API S3Client através do SDK da AWS 
        $s3 = $this->autenticar();
        
        
        // Aws\S3\S3Client -  SDK da AWS - fazer upload do arquivo $nome_arquivo localizado localmente no diretorio /public/$diretorio_temp
        $result = $s3->putObject([
            'Bucket' => $bucket,
            'Key'    => $nomeArquivo,
            'SourceFile' => $diretorioTemp."/".$nomeArquivo //-- use this if you want to upload a file from a local location
        ]);
       
        $content=[
            "code" => '200',
            "message" => $result
        ];

        return $content;
    }


}