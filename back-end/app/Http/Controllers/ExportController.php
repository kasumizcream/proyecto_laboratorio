<?php

namespace App\Http\Controllers;

use App\Area;
use App\Carrera;
use App\Encuesta;
use App\EncuestaGeneral;
use App\EncuestaPuntaje;
use App\Exports\LinkExport;
use App\Exports\StatusExport;
use App\Jobs\PDFConsolidados;
use App\Jobs\PDFIntereses;
use App\Persona;
use App\Rueda;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
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
        if ($request->campo == "persona") {
            return response()->download(storage_path("app/public/importar-alumnos.xlsx"));
        } else if ($request->campo == "links") {
            return $this->links($request);
        } else if ($request->campo == "status") {
            return $this->status($request);
        } else if ($request->campo == "pdf") {
            return $this->jobs($request);
        } else if ($request->campo == "intereses") {
            return $this->intereses($request);
        }
    }

    public function intereses(Request $request)
    {
        $temperamento_id = "";

        $encuesta = Encuesta::where('id', $request->interes_id)
            ->with('empresa')
            ->first();

        $general =  EncuestaGeneral::where('id', $encuesta['encuesta_general_id'])
            ->with('personas')
            ->first();

        $encuesta_temp = Encuesta::where('encuesta_general_id', $general['id'])
            ->where('tipo_encuesta_id', 3)
            ->first();

        $personas = EncuestaPuntaje::where('encuesta_id', $request->interes_id)
            ->with('persona')
            ->with('punintereses.carrera')
            ->get();

        if ($encuesta_temp) {
            $temperamento_id = $encuesta_temp['id'];
        }

        if ($personas->isEmpty()) {
            return response()->json(['error' => 'No hay encuestas resueltas.'], 401);
        } else {
            foreach ($personas as $p) {
                PDFIntereses::dispatchNow($p['persona'], $p['punintereses'], $encuesta['empresa']['nombre'], $request->hour);
            }
        }

        Excel::store(new StatusExport($general['personas'], $encuesta['id'], $temperamento_id), 'Consolidado-' . $request->hour . '/consolidado.xlsx', 'local');

        return $this->descargarZip($request->hour);
    }

    public function jobs(Request $request)
    {
        $descargar = false;

        $encuesta = Encuesta::where('id', $request->interes_id)
            ->with('empresa')
            ->first();

        $general =  EncuestaGeneral::where('id', $encuesta['encuesta_general_id'])
            ->with('personas')
            ->first();

        $encuesta_temp = Encuesta::where('encuesta_general_id', $general['id'])
            ->where('tipo_encuesta_id', 3)
            ->first();

        $areas = Area::with('items.items')
            ->with('formulas')
            ->where('estado', '1')->get();

        $ruedas = Rueda::where('estado', '1')->get();

        $temperamento_id = "";


        foreach ($general['personas'] as $p) { //PARA LOS CONSOLIDADOS
            $p_intereses = EncuestaPuntaje::where('encuesta_id', $request->interes_id)
                ->where('persona_id', $p['id'])
                ->with('punintereses.carrera.intereses')
                ->first();

            $p_temperamentos = EncuestaPuntaje::where('encuesta_id', $encuesta_temp['id'])
                ->where('persona_id', $p['id'])
                ->with('puntemperamentos.formula')
                ->with('areatemperamentos')
                ->first();

            if ($p_intereses && $p_temperamentos) {
                PDFConsolidados::dispatchNow($p, $p_intereses['punintereses'], $p_temperamentos['puntemperamentos'], $p_temperamentos['areatemperamentos'], $encuesta['empresa']['nombre'], $request->hour, $areas, $ruedas);
                $descargar = true;
            }
        }

        if ($encuesta_temp) {
            $temperamento_id = $encuesta_temp['id'];
        }

        Excel::store(new StatusExport($general['personas'], $encuesta['id'], $temperamento_id), 'Consolidado-' . $request->hour . '/consolidado.xlsx', 'local');

        if ($descargar) {
            return $this->descargarZip($request->hour);
        } else {
            return response()->json(['error' => 'No hay encuestas resueltas.'], 404);
        }
    }

    public function descargarZip($hour)
    {
        $zip_file = 'PDF-' . $hour . '.zip';
        $zip = new \ZipArchive();
        $zip->open($zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $path = storage_path('app/public/PDF-' . $hour);
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path));
        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();

                $relativePath = substr($filePath, strlen($path));

                $zip->addFile($filePath, $relativePath);
            }
        }
        $path2 = storage_path('app/Consolidado-' . $hour);
        $files2 = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path2));
        foreach ($files2 as $name => $file) {
            if (!$file->isDir()) {
                $filePath2 = $file->getRealPath();

                $relativePath2 = substr($filePath2, strlen($path2));

                $zip->addFile($filePath2, $relativePath2);
            }
        }
        $zip->close();

        return response()->download($zip_file);
    }


    public function links(Request $request)
    {
        $temperamento_id = '';

        $interes = Encuesta::where('id', $request->interes_id)
            ->with(['general' => function ($query) {
                $query->with(['personas' => function ($query) {
                    $query->wherePivot('estado', '1');
                }]);
            }])
            ->first();

        if ($request->temperamento_id != null) {
            $temperamento = Encuesta::where('id', $request->temperamento_id)
                ->with(['general' => function ($query) {
                    $query->with(['personas' => function ($query) {
                        $query->wherePivot('estado', '1');
                    }]);
                }])
                ->first();
            $temperamento_id = $temperamento['id'];
        }

        if ($interes['general']['personas']->isEmpty()) {
            return response()->json(['error' => 'No hay alumnos registrados'], 404);
        } else {
            return Excel::download(new LinkExport($interes['general']['personas'], $interes['id'], $temperamento_id), 'encuesta.xlsx');
        }
    }

    public function pdf_intereses($interes_id, $persona_id)
    {
        $carreras = Carrera::where('estado', 1)->orderBy('nombre', 'asc')
            ->get();

        $persona = Persona::where('id', $persona_id)
            ->first();

        $encuesta = EncuestaPuntaje::where('encuesta_id', $interes_id)
            ->where('persona_id', $persona_id)
            ->with('punintereses.carrera')
            ->first();

        $pdf = \PDF::loadView('reporte_interes', array('carreras' => $carreras, 'persona' => $persona, 'puntajes' => $encuesta['punintereses']));
        return $pdf->download('Reporte-Intereses-' . $persona->nombres . '-' . $persona->apellido_paterno . '-' . $persona->apellido_materno . '.pdf');
    }

    public function pdf_temperamentos($temperamento_id, $persona_id)
    {
        $areas = Area::with('items.items')
            ->with('formulas')
            ->where('estado', '1')->get();

        $ruedas = Rueda::where('estado', '1')->get();

        $persona = Persona::where('id', $persona_id)
            ->first();

        $encuesta = EncuestaPuntaje::where('encuesta_id', $temperamento_id)
            ->where('persona_id', $persona_id)
            ->with('puntemperamentos.formula')
            ->with('areatemperamentos')
            ->first();

        $pdf = \PDF::loadView('reporte_temperamentos', array('ruedas' => $ruedas, 'persona' => $persona, 'p_temperamentos' => $encuesta['puntemperamentos'], 'a_temperamentos' => $encuesta['areatemperamentos'], 'areas' => $areas));

        return $pdf->download('Reporte-Temperamentos-' . $persona->nombres . '-' . $persona->apellido_paterno . '-' . $persona->apellido_materno . '.pdf');
    }

    public function status(Request $request)
    {
        $temperamento_id = '';

        $interes = Encuesta::where('id', $request->interes_id)
            ->with(['general' => function ($query) {
                $query->with(['personas' => function ($query) {
                    $query->wherePivot('estado', '1');
                }]);
            }])
            ->first();

        if ($request->temperamento_id != null) {
            $temperamento = Encuesta::where('id', $request->temperamento_id)
                ->with(['general' => function ($query) {
                    $query->with(['personas' => function ($query) {
                        $query->wherePivot('estado', '1');
                    }]);
                }])
                ->first();

            $temperamento_id = $temperamento['id'];
        }

        if ($interes['general']['personas']->isEmpty()) {
            return response()->json(['error' => 'No hay alumnos registrados'], 404);
        } else {
            return Excel::download(new StatusExport($interes['general']['personas'], $interes['id'], $temperamento_id), 'encuesta.xlsx');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
    }
}
