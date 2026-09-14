<?php

namespace App\Http\Controllers;

use App\Models\Carro;
use App\Models\Marca;
use App\Models\Pessoa;
use App\Models\Revisao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RelatorioController extends Controller
{
    private function catalog(): array
    {
        return json_decode(file_get_contents(database_path('relatorios/catalogo.json')), true, 512, JSON_THROW_ON_ERROR);
    }

    public function index()
    {
        return $this->catalog();
    }

    public function show(Request $request, string $relatorio)
    {
        $catalog = $this->catalog();
        abort_unless(isset($catalog[$relatorio]), 404);
        $bindings = [];
        if ($relatorio === 'revisoes-periodo') {
            $request->merge(['inicio' => $request->input('inicio', now()->startOfMonth()->toDateString()), 'fim' => $request->input('fim', today()->toDateString())]);
            $bindings = $request->validate(['inicio' => ['required', 'date_format:Y-m-d'], 'fim' => ['required', 'date_format:Y-m-d', 'after_or_equal:inicio']]);
        }
        // O nome do arquivo vem exclusivamente do catalogo, nunca de um caminho livre.
        $rows = DB::select(file_get_contents(database_path("relatorios/$relatorio.sql")), $bindings);

        return ['meta' => $catalog[$relatorio], 'data' => $rows];
    }

    public function summary()
    {
        return ['pessoas' => Pessoa::count(), 'marcas' => Marca::count(),
            'carros' => Carro::count(), 'revisoes' => Revisao::count()];
    }
}
