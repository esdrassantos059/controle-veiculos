<?php

use App\Models\Pessoa;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('banco:verificar', function () {
    $connection = DB::connection();

    try {
        $connection->beginTransaction();
        // Protege esta verificacao contra qualquer escrita acidental.
        $connection->statement('SET TRANSACTION READ ONLY');
        $context = $connection->selectOne(
            'SELECT current_database() AS banco, current_schema() AS esquema'
        );
        $this->info("Banco: {$context->banco}; schema: {$context->esquema}");

        foreach (['pessoa', 'marca', 'carro', 'revisao'] as $table) {
            $this->line($table.': '.$connection->table($table)->count().' registro(s)');
        }

        $this->line('Pessoas consultadas pelo Eloquent: '.Pessoa::query()->count());
        $this->info('Conexao e consultas de leitura funcionando.');

        return 0;
    } catch (Throwable $exception) {
        $this->error('Nao foi possivel verificar o banco. Confira o servico PostgreSQL e DB_* no .env.');
        $this->line('Nenhum dado foi alterado por esta verificacao.');

        return 1;
    } finally {
        if ($connection->transactionLevel() > 0) {
            $connection->rollBack();
        }
    }
})->purpose('Verifica as quatro tabelas e o Eloquent sem alterar o banco');
