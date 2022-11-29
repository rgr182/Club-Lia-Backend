<?php

namespace App\Http\Controllers;

use App\ContactType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $type = ContactType::get();

        if (!$type->isEmpty()) {
            return response()->json([
                "info" => $type,
                "message" => "Tipos de contacto",
            ], 200);
        }
        else{
            return response()->json([
                "message" => "No hay elementos que mostrar",
            ], 404);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return void
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'description' => 'required',
        ]);

        $type = new ContactType();

        $type->description = $request->description;

        $type->save();

        return response()->json([
            $type,
            "message" => "Nuevo tipo de contacto creado",
        ], 201);
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $type = ContactType::firstWhere('id', $id);
        if ($type !== null) {
            return response()->json([
                "info" => $type,
                "message" => "Contacto encontrado",
            ], 200);
        }
        else{
            return response()->json([
                "message" => "No hay elementos que coincidan con la busqueda",
            ], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param ContactType $contactType
     * @return void
     */
    public function edit(ContactType $contactType)
    {

    }

    /**
     * Update the specified resource in storage.
     *
     *
     * @param $id
     * @return JsonResponse
     */
    public function update($id)
    {
        ContactType::updateDataId($id);

        return response()->json([
            "message" => "Se ha actualizado la licencia existosamente",
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param ContactType $contactType
     * @param $id
     * @return JsonResponse
     */
    public function destroy(ContactType $contactType, $id)
    {
        $contactType::destroy($id);

        return response()->json([
            $contactType,
            "message" => "Se ha eliminado la licencia",
        ], 200);
    }
}
