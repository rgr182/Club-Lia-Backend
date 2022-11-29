<?php

namespace App\Http\Controllers;

use App\Order;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class OrderController extends ApiController
{
    public function orderList(){
        try {
            $user = Auth::user();

            if(Order::where('user_id', '=', $user->id)->exists()) {

                $order = Order::join('users', 'orders.user_id', '=', 'users.id')
                    ->join('licenses_type', 'orders.id_licenses_type', '=', 'licenses_type.id')
                    ->select('orders.*', 'users.name', 'licenses_type.title as licenses_type', Order::raw('CONCAT(COALESCE(users.name,"")," ",COALESCE(users.second_name+" ",""),COALESCE(users.last_name,"")) as member_name'))
                    ->where('user_id', '=', $user->id)
                    ->latest()->first();
            }

            return $this->successResponse($order, 'Lista de Pagos', 200);

        }catch (Exception $exception){
            return $this->errorResponse("Error al consultar la información", 442);
        }
    }
}
