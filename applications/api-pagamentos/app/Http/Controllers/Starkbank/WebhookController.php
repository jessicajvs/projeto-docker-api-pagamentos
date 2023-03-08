<?php

namespace App\Http\Controllers\Starkbank;

use StarkBank\Settings;
use StarkBank\Project;
use StarkBank\Webhook;

/**
 * Controller WebhookController com os métodos necessários 
 * para manipular o webhook da api v2 da Starkbank
 * 
 * @link https://starkbank.com/docs/api#webhook
 * @see StarkBank\Webhook
 * 
 * @author Jéssica Vachelli - https://github.com/jessicajvs
 */
class WebhookController 
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
     * Cria um novo webhook caso ainda não exista nenhum para a transaction passada pelo parâmetro
     * @param mixed $transaction
     * @return array 
     */
    public function create($transaction){
        $state=202;
        $message = 'Everything went right.';

        $url = $_ENV['APP_URL'];
        if($url  == "http://localhost")
            $url = 'https://webhook.site/287d2ec8-bc67-4a1e-83b9-d3151ebfcfb7';
         else
            $url = $_ENV['APP_URL'] . $_ENV['SB_WEBHOOK_URL'];

        $needCreate = true;
        $idDelete = "";
        $subscriptions = [];
        $webhooks = Webhook::query();
        foreach($webhooks as $webhook){
            foreach($webhook->subscriptions as $subscription){
                if ($subscription == $transaction) 
                    $needCreate = false;

                array_push($subscriptions, $subscription);
            }
            if($url == $webhook->url )
                $idDelete = $webhook->id;
        }

        if($needCreate){
            array_push($subscriptions, $transaction);
            if(!empty($idDelete)){
                $message.= 'Webhook deleted: ' . $idDelete;
                $webhook = Webhook::delete($idDelete);
            }
                
      
            $webhook = Webhook::create([
                    "url" => $url,
                    "subscriptions" => $subscriptions
            ]);
            $message.= 'Webhook created: ' . $webhook->id;
        }
                
        $content=[
            "code" => $state,
            "description" => "Return create webhook",
            "message" => $message
        ];
        

        return $content;
    }
}