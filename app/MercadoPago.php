<?php

namespace App;

use App\Traits\ApiResponser;
use Illuminate\Database\Eloquent\Model;
use App\Order;
use App\MercadoPago as AppMercadoPago;
use Facade\FlareClient\Api;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use MercadoPago as MercadoPagoSDK;
use Auth;

class MercadoPago extends Model
{
    use ApiResponser;
    protected $token;

    public function __construct()
    {
        $this->token = Config::get('app.mercadopago_token');
    }

    // membresías-pagos recurrentes
    public function processOrderMembership($dataPayment)
    {
        // Agrega credenciales MercadoPago
        $expiration = [
            '2' => 1, //Papá mensual
            '3' => 12, //Papá anual
            '5' => 1, //Maestro mensual
            '6' => 12, //Maestro anual
            '7' => 6, //Papá Semestral
            '18' => 1, //gloab schooling mensual
            '19' => 6, //gloab schooling semestral
            '20' => 12, //gloab schooling anual
        ];

        $unitPrice = $dataPayment['item']['unit_price'];
        $quantity = $dataPayment['quantity'];
        $type = $dataPayment['id_licenses_type'];

        $total = $quantity * $unitPrice;

        $path = '/invoice';
        if (Auth::user()) {
            $path = '/bill';
        }

        $payment = Http::withToken($this->token)->post('https://api.mercadopago.com/preapproval',[
            "auto_recurring"=>[
                "currency_id"=>"MXN",
                "transaction_amount"=>$total,
                "frequency"=>$expiration[$type],
                "frequency_type"=>'months'
            ],
            "back_url"=>"https://test.clublia.com".$path,
            "payer_email"=> $dataPayment['payer']['email'],
            "reason"=>$dataPayment['item']['title'],
            "status"=>"pending"
        ]);

        return $payment->json();
    }

    // productos-un solo pago
    public function processOrderProducts($dataPayment)
    {

        // Agrega credenciales
        MercadoPagoSDK\SDK::setAccessToken($this->token);

        // Crea un objeto de preferencia
        $preference = new MercadoPagoSDK\Preference();

        // Crea un ítem en la preferencia
        $items = [];
        foreach($dataPayment['items'] as $item){
            $itemM = new MercadoPagoSDK\Item();
            $itemM->title = $item['title'];
            $itemM->quantity = $item['quantity'];
            $itemM->unit_price = $item['unit_price'];
            array_push($items,$itemM);
        }
        $preference->items = $items;

        $payer = new MercadoPagoSDK\Payer();
        $payer->name = $dataPayment['name'];
        $payer->surname = $dataPayment['surname'];
        $payer->first_name = $dataPayment['name'];
        $payer->last_name = $dataPayment['surname'];
        $payer->email = $dataPayment['email'];
        $payer->phone = array(
            "area_code" => "",
            "number" => ""
        );
        $preference->payer = $payer;

        $path = '/invoice';
        if (Auth::user()) {
            $path = '/bill';
        }
        $preference->back_urls = array(
            "success" => env('REACT_APP_URL').$path,
            "failure" => env('REACT_APP_URL')."/login",
            "pending" => env('REACT_APP_URL')."/login",
        );

        $preference->auto_return = "approved";

        $preference->payment_methods = array(
            "excluded_payment_types" => array( array( "id"=>"ticket"), array("id"=>"bank_transfer"), array("id"=>"atm"), array("id"=>"digital_wallet"))
        );

        $preference->save();

        $success['payment'] = $preference->getAttributes();
        return $preference->getAttributes();
    }

    public function processOrderSubscription($dataPayment)
    {
        try
        {
            
            $idPay = $dataPayment['id_order'];
            $total = $dataPayment['unit_price'];

            error_log('uwu');

            $payment = Http::withToken($this->token)->put('https://api.mercadopago.com/preapproval/' .$idPay,[
                "auto_recurring"=>[
                    "currency_id"=>"MXN",
                    "transaction_amount"=>$total,
                ],
                //"back_url"=>env('REACT_APP_URL').$path,
                //"payer_email"=> $dataPayment['payer']['email'],
                //"reason"=>$dataPayment['item']['title'],
                
            ]);



            return $payment->json();

        }catch(Exception $e){

            return $e;

        }

        
    }

}