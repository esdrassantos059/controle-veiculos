# Revisar — controle de revisões de veículos

Aplicação Laravel 13, PHP 8.5, Vue 3 e PostgreSQL 18, com Docker Compose.
Implementa cadastros de pessoas, marcas, veículos e revisões, além dos 12
relatórios SQL com gráficos e exportação CSV.

## Atualizar o ambiente existente

Na pasta que contém `compose.yaml`:

```sh
docker compose up -d --build
docker compose exec app php artisan route:clear
docker compose exec app php artisan banco:verificar
```

Abra http://localhost:8000/inicio. O volume existente preserva os dados.
O build compila o Vue e instala as dependências PHP. Não executa migrations.

## Instalação nova em outro computador

1. Instale e inicie Docker Desktop com Linux containers.
2. Copie `.env.example` para `.env` e preencha `DB_PASSWORD` localmente.
3. Gere a chave da aplicação (substitui apenas APP_KEY no .env):

```sh
docker compose build app
docker compose run --rm --no-deps app php artisan key:generate --show
```

Copie o valor `base64:...` retornado para `APP_KEY` no `.env`, sem publicá-lo.
O `.env` é montado somente para leitura no container, por isso usamos `--show`.

4. Execute `docker compose up -d`. No primeiro uso do volume, PostgreSQL cria
   o schema `esdras` e as quatro tabelas com `docker/postgres/bootstrap/01-estrutura.sql`.
   O banco novo começa vazio. Não importa contatos privados do computador original.
5. Cadastre pessoas e marcas, depois veículos e revisões.

## Usar e parar

- Painel: `/inicio`.
- Cadastros: `/pessoas`, `/marcas`, `/carros`, `/revisoes`.
- Relatórios: `/relatorios`.
- pgAdmin para banco Docker: localhost, porta 5433, banco `controle_revisoes`.
- Parar: `docker compose stop`.
- Retomar: `docker compose start`.
- Logs: `docker compose logs --tail=80 app` ou `db`.
- Não use `docker compose down -v`: essa opção apaga os volumes.
- Mudanças em Vue/CSS ou dependências exigem novo build neste fluxo.

## Testes

```sh
docker compose exec app php artisan test
docker compose exec app php scripts/verificar-relatorios.php
```

A suíte PHP usa SQLite em memória e não toca no PostgreSQL. O script de
relatórios usa tabelas TEMPORÁRIAS da conexão e rollback: não altera tabelas reais.
No Windows preparado nesta conversa, também é possível usar `./laravel-local.ps1 test`.

Validação realizada nesta entrega:
- 8 testes PHP e 92 asserções passaram.
- 12 relatórios executados em PostgreSQL, com 25 verificações de resultados.
- 24 páginas/endpoints HTTP retornaram 200 no servidor local de verificação.
- Respostas 422 para formulário inválido e 419 sem CSRF confirmadas por HTTP.
- Build Vite passou; painel, formulários, vínculos e gráfico conferidos no navegador.
- O build final do Docker precisa ser executado no terminal do usuário, pois
  docker.exe é bloqueado para a sessão do assistente.

## Documentação

- `docs/COMO-FUNCIONA.md`: pastas, fluxo e explicação para estudar.
- `docs/API.md`: endpoints, corpos JSON, erros, CSRF e regras dos relatórios.
- `database/relatorios/*.sql`: consultas exigidas pelo teste.
- `scripts/verificar-relatorios.php`: exemplos temporários e resultados esperados.

## Escopo e decisões

- Todos os campos editáveis são obrigatórios pela aplicação.
- Placa brasileira antiga/Mercosul, normalizada para maiúsculas e sem hífen.
- Ano-modelo de 1960 até ano atual + 1.
- Marcas não duplicam por diferença de maiúsculas; e-mail segue a mesma regra na aplicação.
- Exclusão de pessoa/marca com veículos e veículo com revisões é bloqueada.
- Troca de proprietário de veículo com revisões é bloqueada, preservando o vínculo histórico.
- Revisões futuras são rejeitadas; previsão é calculada à parte.
- Média/previsão por pessoa, com datas distintas; menos de duas datas = histórico insuficiente.
- Relatórios usam SQL; cadastros usam Eloquent. Não há quilometragem, valor ou descrição.
- Login é opcional no documento e não foi implementado. A publicação é limitada ao localhost.
- O banco original do usuário não recebeu alterações automáticas de restrições.
  A estrutura inicial nova tem as restrições completas; registros antigos continuam preservados.
- Relatórios grandes retornam todas as linhas: interface pagina e gráfico limita a 20 categorias.

## Demonstração

A aplicação local está pronta para demonstrar. Publicação externa e Ngrok ainda
não foram executados. Para a apresentação externa, use uma cópia com dados
fictícios e escolha o serviço de hospedagem/túnel. A configuração atual usa o
servidor de desenvolvimento PHP, não um ambiente de produção.
