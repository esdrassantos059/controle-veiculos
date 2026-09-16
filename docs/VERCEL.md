# Deploy na Vercel

O runtime comunitario `vercel-php@0.9.0` executa PHP 8.5. O Docker continua
sendo o ambiente local; a Vercel nao executa o compose.yaml.

`vercel.json` compila Vue, prepara os assets em dist e encaminha as paginas
e a API para api/index.php. O entrypoint coloca caches/views em armazenamento
temporario. Sessoes usam cookies criptografados, evitando depender dos arquivos
de uma instancia de funcao. APP_KEY deve ser estavel entre deploys.

Configurar na Vercel (nunca versionar os valores secretos):

- APP_KEY: chave exclusiva da instalacao online.
- APP_URL: URL HTTPS publica.
- DATABASE_URL: conexao do PostgreSQL online, fornecida pela integracao Neon.

Criar o schema e tabelas de docker/postgres/bootstrap/01-estrutura.sql uma unica
vez no banco novo. Nao executar migrate:fresh ou importar o backup privado.
O banco online comeca vazio; os dados locais continuam no Docker.

O build e a publicacao precisam ser verificados nos logs da Vercel. A simples
presenca de dist nao comprova que o PHP ou o banco estejam funcionando.

Verificar /inicio, /api/resumo, assets HTTPS e o fluxo de cadastro com CSRF.
Este sistema nao possui login; quem acessar a URL podera alterar cadastros.
