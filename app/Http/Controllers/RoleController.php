<?php

namespace App\Http\Controllers;

use App\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Auth::user();

        if($user->role_id == 1 || $user->role_id == 2){
            $role = Role::find([1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,28,29,30,31,32,33,34,35,36]);
            if (!$role->isEmpty()) {
                return response()->json(
                    $role
                    , 200);
            }
        }
        if(in_array($user->role_id, [3,9,25,26,27])){
            $role = Role::find([4, 5, 13, 6, 7, 8, 10, 13]);
            if (!$role->isEmpty()) {
                return response()->json(
                    $role
                    , 200);
            }
        }

        if(in_array($user->role_id, [4,7,8,17,22,23,24,28,29,30])){
            $role = Role::find([5,6,13,18,19,20,21,34,35,36]);
            if (!$role->isEmpty()) {
                return response()->json(
                    $role
                    , 200);
            }
        }

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
        $request->validate([
            'name' => 'required',
            'slug' => 'required',
            'description' => 'required',
        ]);

        $role = Role::create($request->all());

        return response()->json([
            $role,
            "message" => "Nuevo rol creado",
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function show(Role $role)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function edit(Role $role)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function update( Role $role, $id)
    {
        $role::updateDataId($id);

        return response()->json([
            "message" => "Se ha actualizado existosamente",
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Role  $role
     * @return \Illuminate\Http\Response
     */
    public function destroy(Role $role)
    {
        //
    }
}
