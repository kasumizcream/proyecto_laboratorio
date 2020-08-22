<?php

namespace App\Http\Controllers;

use App\EncuestaDoctor;
use Illuminate\Http\Request;
use App\Doctores;

class DoctorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $paginate = $request->input('paginate');

        $offset = $request->input('offset') * $paginate;

        $searchValue = $request->input('search');

        $data = Doctores::where('estado', '1')
            ->where('rol_id', '2')->where(function ($query) use ($searchValue) {
                $query->where("id", "LIKE", "%$searchValue%")
                    ->orWhere('tipo_documento', "LIKE", "%$searchValue%")
                    ->orWhere('nro_documento', "LIKE", "%$searchValue%")
                    ->orWhere('nombres', "LIKE", "%$searchValue%")
                    ->orWhere('apellido_materno', "LIKE", "%$searchValue%")
                    ->orWhere('apellido_paterno', "LIKE", "%$searchValue%")
                    ->orWhere('especialidad', "LIKE", "%$searchValue%")
                    ->orWhere('nro_colegiatura', "LIKE", "%$searchValue%")
                    ->orWhere('fecha_nacimiento', "LIKE", "%$searchValue%")
                    ->orWhere('edad', "LIKE", "%$searchValue%")
                    ->orWhere('sexo', "LIKE", "%$searchValue%")
                    ->orWhere('nro_celular', "LIKE", "%$searchValue%")
                    ->orWhere('email', "LIKE", "%$searchValue%")
                    ->orWhere('direccion', "LIKE", "%$searchValue%")
                    ->orWhere('latitud', "LIKE", "%$searchValue%")
                    ->orWhere('longitud', "LIKE", "%$searchValue%")
                    ->orWhere('departamento', "LIKE", "%$searchValue%")
                    ->orWhere('provincia', "LIKE", "%$searchValue%")
                    ->orWhere('referencias', "LIKE", "%$searchValue%")
                    ->orWhere('tipo_paciente', "LIKE", "%$searchValue%")
                    ->orWhere('observaciones1', "LIKE", "%$searchValue%")
                    ->orWhere('observaciones2', "LIKE", "%$searchValue%")
                    ->orWhere('estado', "LIKE", "%$searchValue%");
            });

        if (!$paginate) {
            $data = $data->count();
        } else {
            $data = $data
                ->skip($offset)
                ->take($paginate)
                ->orderBy('id', 'DESC')->get();
        }

        return response()->json($data, 200);
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
        $data = $request->all();
        $data['rol_id'] = 2;

        $registro = Doctores::create($data);

        return response()->json($registro, 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $data = Doctores::with('insert')
            ->with('edit')
            ->where('rol_id', '2')
            ->where('id', $id)
            ->first();

        return response()->json($data, 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $data = $request->all();

        $registro = Doctores::find($id);
        $registro->update($data);
        $registro->save();

        return response()->json($registro, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $registro = Doctores::find($id);
        $registro->estado = '0';
        $registro->save();

        $encuestas = EncuestaDoctor::where('doctor_id', $id)->get();
        foreach ($encuestas as $e) {
            $e->estado = '0';
            $e->save();
        }

        return response()->json($registro, 200);
    }
}