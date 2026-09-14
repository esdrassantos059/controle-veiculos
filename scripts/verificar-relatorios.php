<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

// Executar no container: php scripts/verificar-relatorios.php
// Apenas tabelas TEMPORARIAS da conexao; nao escreve nas tabelas do usuario.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$db = DB::connection();
$checks = 0;
$assert = function ($condition, $label) use (&$checks) {
    if (! $condition) {
        throw new RuntimeException($label);
    }$checks++;
};
try {
    $db->beginTransaction();
    $db->statement('SET LOCAL search_path TO pg_temp');
    $db->unprepared("CREATE TEMP TABLE pessoa(id int,nome text,genero char(1),data_nascimento date,email text,telefone text) ON COMMIT DROP;
    CREATE TEMP TABLE marca(id int,nome text) ON COMMIT DROP;
    CREATE TEMP TABLE carro(id int,pessoa_id int,marca_id int,modelo text,ano int,placa text) ON COMMIT DROP;
    CREATE TEMP TABLE revisao(id int,carro_id int,data_revisao date) ON COMMIT DROP;
    INSERT INTO pessoa VALUES (1,'Ana','F','1990-01-01','ana@example.com','11999999999'),(2,'Beto','M','1980-01-01','beto@example.com','11999999999'),(3,'Carla','F','2000-01-01','carla@example.com','11999999999');
    INSERT INTO marca VALUES (1,'Fiat'),(2,'Ford');
    INSERT INTO carro VALUES (1,1,1,'Uno',2020,'AAA1234'),(2,1,2,'Ka',2020,'BBB1234'),(3,2,1,'Argo',2020,'CCC1234');
    INSERT INTO revisao VALUES (1,1,'2025-01-01'),(2,1,'2025-01-11'),(3,2,'2025-01-31'),(4,2,'2025-01-11'),(5,3,'2025-02-01');");
    $catalog = json_decode(file_get_contents(__DIR__.'/../database/relatorios/catalogo.json'), true);
    $results = [];
    foreach ($catalog as $key => $meta) {
        $results[$key] = $db->select(file_get_contents(__DIR__."/../database/relatorios/$key.sql"), $key === 'revisoes-periodo' ? ['inicio' => '2025-01-01', 'fim' => '2025-01-31'] : []);
        $assert(count($results[$key]) > 0, "Relatorio vazio: $key");
    }
    $assert(count($results['veiculos']) === 3, 'Total de veiculos');
    $assert(count($results['veiculos-pessoa']) === 3, 'Veiculos por pessoa');
    $assert((int) $results['veiculos-genero'][0]->total === 2, 'Comparacao de generos');
    $assert((int) $results['marcas-veiculos'][0]->total === 2, 'Ranking marcas');
    $assert((int) $results['marcas-genero'][0]->feminino === 1, 'Marcas por genero');
    $assert(count($results['pessoas']) === 3, 'Pessoas sem veiculos incluidas');
    $assert(count($results['pessoas-genero']) === 3, 'Pessoas por genero');
    $assert(count($results['revisoes-periodo']) === 4, 'Periodo inclusivo');
    $assert((int) $results['marcas-revisoes'][0]->total === 3, 'Ranking revisoes marca');
    $assert((int) $results['pessoas-revisoes'][0]->total === 4, 'Ranking revisoes pessoa');
    $ana = array_values(array_filter($results['intervalos'], fn ($r) => $r->id === 1))[0];
    $assert((float) $ana->media_dias === 15.0, 'Media de intervalos e deduplicacao no mesmo dia');
    $predictions = [];
    foreach ($results['proximas'] as $r) {
        $predictions[$r->id] = $r;
    }
    $assert($predictions[1]->proxima_revisao === '2025-02-15', 'Previsao por media');
    $assert($predictions[2]->proxima_revisao === null && $predictions[3]->proxima_revisao === null, 'Historico insuficiente');
    echo "12 relatorios executados; $checks verificacoes passaram em tabelas temporarias.\n";
} finally {
    if ($db->transactionLevel() > 0) {
        $db->rollBack();
    }
}
