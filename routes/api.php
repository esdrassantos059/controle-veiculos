<?php

use App\Http\Controllers\CadastroController;
use App\Http\Controllers\RelatorioController;
use Illuminate\Support\Facades\Route;

// A interface usa a mesma origem e token CSRF nas alteracoes.
Route::middleware('web')->group(function () {
    Route::get('/resumo', [RelatorioController::class, 'summary']);
    Route::get('/relatorios', [RelatorioController::class, 'index']);
    Route::get('/relatorios/{relatorio}', [RelatorioController::class, 'show']);
    Route::get('/opcoes/{entidade}', [CadastroController::class, 'options']);
    Route::get('/{entidade}', [CadastroController::class, 'index'])->whereIn('entidade', ['pessoas', 'marcas', 'carros', 'revisoes']);
    Route::post('/{entidade}', [CadastroController::class, 'store'])->whereIn('entidade', ['pessoas', 'marcas', 'carros', 'revisoes']);
    Route::get('/{entidade}/{id}', [CadastroController::class, 'show'])->whereIn('entidade', ['pessoas', 'marcas', 'carros', 'revisoes'])->whereNumber('id');
    Route::put('/{entidade}/{id}', [CadastroController::class, 'update'])->whereIn('entidade', ['pessoas', 'marcas', 'carros', 'revisoes'])->whereNumber('id');
    Route::delete('/{entidade}/{id}', [CadastroController::class, 'destroy'])->whereIn('entidade', ['pessoas', 'marcas', 'carros', 'revisoes'])->whereNumber('id');
});
