<?php

namespace App\Http\Controllers;

use App\Order;
use App\MercadoPago as AppMercadoPago;
use Facade\FlareClient\Api;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use MercadoPago;

class MercadoPagoController extends ApiController
{
    protected $token;

    public function __construct()
    {
        $this->token = Config::get('app.mercadopago_token');
    }

    public function processPayment(Request $request)
    {
        $dataPayment = $request->all();
        $appMercadoPago = new AppMercadoPago();
        $mercadoPago = $appMercadoPago->processOrderMembership($dataPayment);
        // $mercadoPago = $appMercadoPago->processOrderProducts($dataPayment);
        return $mercadoPago;
    }

    public function listPaymentMethods(){
        $methods = Http::withToken($this->token)->get('https://api.mercadopago.com/v1/payment_methods');
        return $methods->json();
    }

    public function validateOrder(){
        $messages = [
            'name.required' => 'El campo nombre es requerido.',
            'last_name.required' => 'El campo apellido paterno es requerido',
            'email.required' => 'El correo electrónico es requerido.',
            'id_license_type.required' => 'Es necesario asignar un tipo de licencia',
            'unit_price.required' => 'Es necesario agregar un precio',
        ];

        return Validator::make(request()->all(), [
            'name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email',
            'id_license_type' => 'required',
            'unit_price' => 'required',
        ], $messages);
    }
}
