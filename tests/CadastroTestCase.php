<?php

namespace Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

abstract class CadastroTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('pessoa', function (Blueprint $t) {
            $t->id();
            $t->string('nome');
            $t->string('genero')->nullable();
            $t->date('data_nascimento');
            $t->string('email')->nullable()->unique();
            $t->string('telefone');
            $t->timestamps();
        });
        Schema::create('marca', function (Blueprint $t) {
            $t->id();
            $t->string('nome');
            $t->timestamps();
        });
        Schema::create('carro', function (Blueprint $t) {
            $t->id();
            $t->foreignId('pessoa_id')->constrained('pessoa')->restrictOnDelete();
            $t->foreignId('marca_id')->constrained('marca')->restrictOnDelete();
            $t->string('modelo');
            $t->integer('ano');
            $t->string('placa')->unique();
            $t->timestamps();
        });
        Schema::create('revisao', function (Blueprint $t) {
            $t->id();
            $t->foreignId('carro_id')->constrained('carro')->restrictOnDelete();
            $t->date('data_revisao');
            $t->timestamps();
        });
    }

    protected function person(array $overrides = []): array
    {
        return array_merge(['nome' => 'Ana Teste', 'genero' => 'f', 'data_nascimento' => '1995-06-15', 'email' => 'ana@example.com', 'telefone' => '(11) 99999-9999'], $overrides);
    }
}
