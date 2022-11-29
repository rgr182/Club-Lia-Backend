<?php

namespace App\Http\Controllers;

use App\SchoolLIA;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use \App\School;

class LiaSchoolController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function list(Request $r)
    {
        $user = Auth::user();
        $request = request()->all();
        $type = $r->type;
        $typeC = null;
        switch ($type) {
            case 'Empresa':
                $typeC = " = 'Empresa'";
                break;
            case 'Escuela':
                $typeC = 'IS NULL OR type = "Escuela"'; 
                break;
            default:
                $typeC = null;
                break;
        }
        if (array_key_exists('active', $request) && ($request['active'] == "false" || $request['active'] == false || $request['active'] == null  )) {
            $active = '';
        }else{
            $active = ' AND s.is_active = true ';
        }
        $typeW = !is_null($typeC) ? 'AND (s.type '.$typeC.')': '';
        if($user->role_id == 1 || $user->role_id == 2) {
            $schools = \DB::select('
                    Select  s.id, s.id as SchoolId, s.id as "value",s.name as title,s.type, s.name as School, s.description as Description, s.is_active as IsActive, s.current_user as CurrentUsers,
                    (Select COUNT(u.id) from users u WHERE u.school_id = s.id) as Usuarios
                    FROM schools s
                    WHERE 1=1 '. $active .'
                    '.$typeW.'
                    ORDER BY s.name'
            );
        }else{
            $schools = \DB::select('
                    Select  s.id, s.id as SchoolId,s.id as "value",s.name as title,s.type, s.name as School, s.description as Description, s.is_active as IsActive, s.current_user as CurrentUsers,
                    (Select COUNT(u.id) from users u WHERE u.school_id = s.id) as Usuarios
                    FROM schools s
                    WHERE s.id = '. $user->school_id.$active.'
                    ORDER BY s.name');
                    // AND type IN '. $type === 'Empresa' ? '("Empresa")' : '("Escuela", NULL)' .'
                }

        return response()->json($schools, 200);
    }
    public function sync()
    {
        $user = Auth::user();
        return response()->json(["message"=>"Sincronización completada"], 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\School  $school
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\School  $school
     * @return \Illuminate\Http\Response
     */
    public function edit(School $school)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update($id)
    {

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\School  $school
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(School $school, $id)
    {

    }
}
