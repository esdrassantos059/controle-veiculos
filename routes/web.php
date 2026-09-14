<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/pessoas');
Route::view('/{pagina}', 'pessoas')->whereIn('pagina', ['pessoas', 'marcas', 'carros', 'revisoes', 'relatorios', 'inicio']);
