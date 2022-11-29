<?php

namespace App\Http\Controllers;

use App\Tutor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Appointments;
use App\User;
use App\GlobalSubscription;

class TutorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $tutors = Tutor::get()->toJson(JSON_PRETTY_PRINT);
        return response($tutors, 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Tutor::create($request->all());

        return ('Tutor creado exitosamente');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Tutor  $tutor
     * @return \Illuminate\Http\Response
     */
    public function show(Tutor $tutor)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Tutor  $tutor
     * @return \Illuminate\Http\Response
     */
    public function edit(Tutor $tutor)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Tutor  $tutor
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Tutor $tutor)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Tutor  $tutor
     * @return \Illuminate\Http\Response
     */
    public function destroy(Tutor $tutor)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Tutor  $tutor
     * @return \Illuminate\Http\Response
     */
    public function getInfoSubscription(Request $request)
    {
        $user = Auth::user();
        $sql = Appointments::select('city', 'phone_number')->where('user_id', $user->id)->get();
        $sqlSubscription = self::getInfoTableSubscription();
        $result = collect([['info' => $sql], ['subscription' => $sqlSubscription]]);
        return $result;
    }

    public function getInfoTableSubscription()
    {   
        $user = Auth::user();
        $revision = User::select('status', 'name')->where([['tutor_id', '=', $user->id], ['status', '=', 'En revisión']])
        ->leftJoin('global_subscriptions', 'global_subscriptions.user_id', '=', 'users.id')
        ->get();

        $proceso = User::select('status', 'name')->where([['tutor_id', '=', $user->id], ['status', '=', 'En proceso']])
        ->leftJoin('global_subscriptions', 'global_subscriptions.user_id', '=', 'users.id')
        ->get();

        $actualizado = User::select('status', 'name')->where([['tutor_id', '=', $user->id], ['status', '=', 'Actualizado']])
        ->leftJoin('global_subscriptions', 'global_subscriptions.user_id', '=', 'users.id')
        ->get();

        $aprobado = User::select('status', 'name')->where([['tutor_id', '=', $user->id], ['status', '=', 'Aprobado']])
        ->leftJoin('global_subscriptions', 'global_subscriptions.user_id', '=', 'users.id')
        ->get();

        $result = collect([['revision' => $revision], ['en_proceso' => $proceso], ['actualizado' => $actualizado], ['aprobado' => $aprobado]]);
        return $result;
    }

    public function updateInfo(Request $request)
    {
        $user = Auth::user();
        $input = $request->all();

        $dataUpdate = ([
            'name' => $input['name'],
            'last_name' => $input['last_name'],
            'email' => $input['email']
        ]);

        $dataUpdateAppointment = ([
            'city' => $input['city'],
            'phone_number' => $input['phone_number']
        ]);

        $sql = User::where('id', $user->id)->update($dataUpdate);
        $sqlTwo = Appointments::where('user_id', $user->id)->update($dataUpdateAppointment);

        return $sql;
    }
}
 