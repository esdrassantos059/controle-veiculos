# Guia para entender o projeto

## Caminho de uma operação

1. O navegador abre `/pessoas`, `/marcas`, `/carros`, `/revisoes` ou `/relatorios`.
2. `routes/web.php` entrega o HTML `resources/views/pessoas.blade.php`.
3. O HTML carrega os arquivos compilados pelo Vite. `resources/js/app.js`
   monta o componente `App.vue` dentro de `#app`.
4. `App.vue` mostra navegação e escolhe o cadastro ou relatório.
5. O formulário chama `api.js`, que envia JSON e token CSRF ao Laravel.
6. `routes/api.php` encaminha a requisição ao Controller.
7. `CadastroController` normaliza/valida os dados e usa os Models do Eloquent.
8. `RelatorioController` executa SQLs salvos em `database/relatorios`.
9. A resposta JSON atualiza a tela; erros de campos aparecem no formulário.

## Pastas

| Pasta | Responsabilidade |
| --- | --- |
| `app/Models` | Pessoa, Marca, Carro e Revisao: tabelas, campos e relações |
| `app/Http/Controllers` | Operações dos cadastros e relatórios |
| `resources/js/components` | Telas Vue, formulário, seleção pesquisável e gráficos |
| `resources/js/definitions.js` | Campos e colunas de cada cadastro, evitando repetição |
| `resources/css/app.css` | Estilos responsivos compartilhados |
| `routes` | Endereços de páginas, API e comandos |
| `config/database.php` | Configuração da conexão e schema |
| `database/relatorios` | Os 12 SQLs e catálogo |
| `docker/postgres/bootstrap` | Estrutura para criar um banco novo |
| `tests` | Testes de cadastros em SQLite temporário |
| `scripts` | Verificação dos SQLs em tabelas temporárias PostgreSQL |
| `storage/logs` | Logs do Laravel |
| `public/build` | Resultado do build do Vue; não editar manualmente |
| `vendor`, `node_modules` | Dependências instaladas; não editar manualmente |

## Por que um Controller compartilhado?

Os quatro cadastros têm o mesmo ciclo: listar, validar, salvar e excluir.
`CadastroController` reaproveita esse ciclo com uma lista fechada de entidades.
As regras específicas continuam explícitas para cada tabela. O cliente não pode
informar um nome arbitrário de tabela ou coluna para executar SQL.

## Docker e conexão

`Dockerfile` tem duas etapas. Node compila Vue; PHP instala as dependências
Laravel e recebe os arquivos compilados. Node não precisa rodar junto com PHP.
`compose.yaml` inicia `app` e `db` e cria o volume persistente `postgres_data`.
Dentro dessa rede, Laravel usa `db:5432`. O pgAdmin do Windows usa
`localhost:5433`. O banco original Windows, porta 5432, permanece independente.

O `.env` fica somente no computador, fora da imagem e do Git. O Compose
substitui DB_HOST para `db` dentro do container. `artisan serve --no-reload`
preserva as variáveis no processo HTTP; a ausência dessa opção causou o erro
anterior em que o navegador tentava conectar a `::1`.

O servidor é de desenvolvimento. Para produção, é preciso configurar um
servidor apropriado e controle de acesso antes de hospedar dados reais.

## O banco não é a pasta database

As tabelas reais estão no PostgreSQL. Os arquivos SQL são instruções para
recriá-las. Não executamos migrations nas tabelas existentes.
Os arquivos de migrations padrão Laravel referentes a users/cache/jobs não são
usados por este projeto. Não execute `migrate:fresh`: pode apagar tabelas.

## Validações

O navegador oferece campos obrigatórios, datas e seletores. Laravel repete a
validação no servidor, pois requisições podem ser feitas sem usar o formulário.
O banco novo também possui NOT NULL, CHECK, UNIQUE e FOREIGN KEY.
O banco antigo ainda tem colunas opcionais em pessoa: isso foi preservado.
A aplicação exige preenchimento ao cadastrar/editar; os registros antigos não
foram alterados automaticamente.

## Para explicar na apresentação

Mostre a sequência: pessoa → veículo → revisão → relatório. Explique uma FK,
uma regra de validação, a normalização da placa e a diferença entre Eloquent
nos cadastros e SQL nos relatórios. Abra um dos SQLs e explique os JOINs.
Na média de intervalos, explique LAG e por que uma única data não gera previsão.
