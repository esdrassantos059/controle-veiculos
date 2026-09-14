<?php

namespace Tests\Feature;

use Tests\CadastroTestCase;

class CadastroFlowTest extends CadastroTestCase
{
    public function test_complete_flow_normalization_updates_and_deletion_protection(): void
    {
        $p = $this->postJson('/api/pessoas', $this->person())->assertCreated()->assertJsonPath('genero', 'F')->assertJsonPath('telefone', '11999999999')->json('id');
        $m = $this->postJson('/api/marcas', ['nome' => ' Fiat '])->assertCreated()->assertJsonPath('nome', 'Fiat')->json('id');
        $this->postJson('/api/marcas', ['nome' => 'FIAT'])->assertUnprocessable()->assertJsonValidationErrors('nome');
        $c = $this->postJson('/api/carros', ['pessoa_id' => $p, 'marca_id' => $m, 'modelo' => 'Uno', 'ano' => 2020, 'placa' => 'abc-1234'])->assertCreated()->assertJsonPath('placa', 'ABC1234')->json('id');
        $r = $this->postJson('/api/revisoes', ['carro_id' => $c, 'data_revisao' => '2025-01-01'])->assertCreated()->json('id');
        $this->getJson('/api/carros?pessoa_id='.$p)->assertOk()->assertJsonPath('data.0.pessoa.nome', 'Ana Teste');
        $this->getJson('/api/revisoes?carro_id='.$c)->assertOk()->assertJsonPath('data.0.carro.placa', 'ABC1234');
        $this->putJson('/api/pessoas/'.$p, $this->person(['nome' => 'Ana Atualizada']))->assertOk()->assertJsonPath('nome', 'Ana Atualizada');
        $this->putJson('/api/marcas/'.$m, ['nome' => 'Fiat Brasil'])->assertOk();
        $this->putJson('/api/revisoes/'.$r, ['carro_id' => $c, 'data_revisao' => '2025-02-01'])->assertOk()->assertJsonPath('data_revisao', '2025-02-01');
        $this->deleteJson('/api/pessoas/'.$p)->assertConflict();
        $this->deleteJson('/api/marcas/'.$m)->assertConflict();
        $this->deleteJson('/api/carros/'.$c)->assertConflict();
        $p2 = $this->postJson('/api/pessoas', $this->person(['email' => 'outra@example.com']))->assertCreated()->json('id');
        $this->putJson('/api/carros/'.$c, ['pessoa_id' => $p2, 'marca_id' => $m, 'modelo' => 'Uno', 'ano' => 2020, 'placa' => 'ABC1234'])->assertUnprocessable()->assertJsonValidationErrors('pessoa_id');
        $this->deleteJson('/api/revisoes/'.$r)->assertNoContent();
        $this->putJson('/api/carros/'.$c, ['pessoa_id' => $p2, 'marca_id' => $m, 'modelo' => 'Uno novo', 'ano' => 2020, 'placa' => 'ABC1234'])->assertOk();
        $this->deleteJson('/api/carros/'.$c)->assertNoContent();
        $this->deleteJson('/api/marcas/'.$m)->assertNoContent();
        $this->deleteJson('/api/pessoas/'.$p)->assertNoContent();
        $this->assertDatabaseCount('revisao', 0);
    }

    public function test_invalid_input_and_unique_email_cannot_be_saved(): void
    {
        $this->postJson('/api/pessoas', [])->assertUnprocessable()->assertJsonValidationErrors(['nome', 'email', 'genero', 'telefone', 'data_nascimento']);
        $this->postJson('/api/pessoas', $this->person())->assertCreated();
        $this->postJson('/api/pessoas', $this->person(['email' => 'ANA@EXAMPLE.COM']))->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->postJson('/api/pessoas', $this->person(['data_nascimento' => now()->addDay()->toDateString()]))->assertUnprocessable()->assertJsonValidationErrors('data_nascimento');
        $this->postJson('/api/carros', ['pessoa_id' => 999, 'marca_id' => 999, 'ano' => 7000, 'placa' => 'errada', 'modelo' => ''])->assertUnprocessable()->assertJsonValidationErrors(['pessoa_id', 'marca_id', 'ano', 'placa', 'modelo']);
        $this->postJson('/api/revisoes', ['carro_id' => 999, 'data_revisao' => now()->addDay()->toDateString()])->assertUnprocessable()->assertJsonValidationErrors(['carro_id', 'data_revisao']);
        $this->getJson('/api/pessoas/999')->assertNotFound();
        $this->getJson('/api/relatorios/inexistente')->assertNotFound();
    }

    public function test_lookup_search_and_summary(): void
    {
        $id = $this->postJson('/api/pessoas', $this->person())->json('id');
        $this->getJson('/api/opcoes/pessoas?q=ana')->assertOk()->assertJsonPath('0.id', $id);
        $this->getJson('/api/opcoes/pessoas?q=nada&selected='.$id)->assertOk()->assertJsonPath('0.id', $id);
        $this->getJson('/api/resumo')->assertOk()->assertJsonPath('pessoas', 1)->assertJsonPath('carros',0);
        $this->getJson('/api/relatorios')->assertOk()->assertJsonCount(12);
    }
}
