<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\CadastroTestCase;

class PessoaListTest extends CadastroTestCase
{
    public function test_list_is_ordered_paginated_and_preserves_nullable_fields(): void
    {
        foreach (range(12, 1) as $number) {
            DB::table('pessoa')->insert([
                'nome' => sprintf('Pessoa %02d', $number),
                'genero' => null, 'data_nascimento' => '2000-06-15',
                'email' => null, 'telefone' => '11999999999',
            ]);
        }

        $this->getJson('/api/pessoas')->assertOk()
            ->assertJsonPath('total', 12)->assertJsonCount(10, 'data')
            ->assertJsonPath('data.0.nome', 'Pessoa 01')
            ->assertJsonPath('data.0.email', null);
        $this->getJson('/api/pessoas?page=2')->assertOk()
            ->assertJsonCount(2, 'data')->assertJsonPath('data.0.nome', 'Pessoa 11');
        $this->assertDatabaseCount('pessoa', 12);
    }

    public function test_empty_list_and_invalid_page(): void
    {
        $this->getJson('/api/pessoas')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/pessoas?page=0')->assertUnprocessable()
            ->assertJsonValidationErrors('page');
    }

    public function test_page_serves_vue_mount_point(): void
    {
        $this->withoutVite()->get('/pessoas')->assertOk()->assertSee('id="app"', false);
    }
}
