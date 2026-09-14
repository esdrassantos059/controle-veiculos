# API do Revisar

Base local: `http://localhost:8000/api`. Respostas em JSON.
Sem autenticação de usuários nesta etapa (opcional no teste). O Compose publica
somente em `127.0.0.1`. Não disponibilize dados reais em uma demonstração pública.

## Cadastros

Substitua `{entidade}` por `pessoas`, `marcas`, `carros` ou `revisoes`.

| Método e caminho | Resultado |
| --- | --- |
| GET `/{entidade}` | Lista paginada de 10 registros |
| GET `/{entidade}/{id}` | Um registro, com seus relacionamentos usados pela tela |
| POST `/{entidade}` | Cria um registro; HTTP 201 |
| PUT `/{entidade}/{id}` | Atualiza todos os campos editáveis; HTTP 200 |
| DELETE `/{entidade}/{id}` | Exclui se não houver dependências; HTTP 204 |

GET aceita `page` (inteiro >= 1) e `q` (texto até 150 caracteres). Pessoas e
marcas pesquisam nome; carros pesquisam placa/modelo; revisões pesquisam placa.
`/carros?pessoa_id=1` filtra os veículos da pessoa. `/revisoes?carro_id=1` filtra
o histórico do veículo. Lista retorna `data`, `total`, `current_page`, `last_page`,
`per_page` e links de paginação. IDs inexistentes retornam 404.

### Corpos de POST e PUT

Pessoa:
```json
{"nome":"Ana Exemplo","genero":"F","data_nascimento":"1995-06-15","email":"ana@example.com","telefone":"11999999999"}
```

Marca:
```json
{"nome":"Fiat"}
```

Carro (IDs devem existir):
```json
{"pessoa_id":1,"marca_id":1,"modelo":"Argo","ano":2022,"placa":"ABC1D23"}
```

Revisão:
```json
{"carro_id":1,"data_revisao":"2025-01-10"}
```

Todos os campos acima são obrigatórios. `id`, `created_at` e `updated_at` são
administrados pelo Eloquent; campos adicionais enviados não são preenchidos.
Telefone aceita pontuação e é normalizado para 10 ou 11 dígitos (DDD incluído).
Placa aceita hífen, espaços e minúsculas, depois é normalizada. Gênero é M/F.
Ano-modelo: de 1960 até ano corrente + 1. Nascimento e revisão não podem ser
futuros. Marca e e-mail são comparados sem diferença entre maiúsculas/minúsculas.

### Sessão e CSRF

A interface e API usam a mesma origem. A página `/pessoas` cria uma sessão e
inclui `<meta name="csrf-token" ...>`. Para POST, PUT e DELETE, envie os cookies
da sessão e os cabeçalhos `Accept: application/json`, `Content-Type:
application/json` e `X-CSRF-TOKEN` com o token daquela sessão. O cliente Vue já
implementa esse fluxo em `resources/js/api.js`. Uma sessão expirada exige
recarregar a página. Não desabilitar CSRF para contornar erros.

### Erros

| Código | Significado |
| --- | --- |
| 404 | Registro ou rota inexistente |
| 409 | Duplicidade concorrente, vínculo inválido ou exclusão bloqueada |
| 419 | Token CSRF ausente/expirado |
| 422 | Validação; objeto `errors` relaciona campos a mensagens |
| 503 | Falha de acesso ao banco; detalhes técnicos ficam nos logs |

Exemplo de validação:
```json
{"message":"...","errors":{"email":["Informe um e-mail válido."]}}
```

## Seletores e resumo

`GET /opcoes/{entidade}` aceita pessoas, marcas e carros. Retorna até 50 objetos
`{id,label}`. `q` refina a pesquisa. `selected` inclui o registro já selecionado
mesmo que não esteja entre os 50 primeiros. Isso evita carregar o banco inteiro.

`GET /resumo` retorna contagens de pessoas, marcas, carros e revisões.

## Relatórios

`GET /relatorios` retorna o catálogo de 12 relatórios.
`GET /relatorios/{slug}` retorna `{meta,data}`: título/configuração do gráfico e
linhas do resultado SQL. Slugs válidos:

1. `veiculos`
2. `veiculos-pessoa`
3. `veiculos-genero`
4. `marcas-veiculos`
5. `marcas-genero`
6. `pessoas`
7. `pessoas-genero`
8. `revisoes-periodo`
9. `marcas-revisoes`
10. `pessoas-revisoes`
11. `intervalos`
12. `proximas`

`revisoes-periodo` aceita `inicio=AAAA-MM-DD&fim=AAAA-MM-DD`, com extremos
inclusivos e fim >= início. O padrão é primeiro dia do mês até hoje.
As consultas usam parâmetros vinculados. Os nomes dos relatórios vêm de um
catálogo fechado; não é possível enviar SQL pela API.

Os relatórios não têm paginação no servidor nesta versão: a interface pagina
as linhas em grupos de 20 e o CSV inclui tudo. Gráficos exibem até os 20 maiores
valores, com aviso quando há mais categorias. Para bases grandes, paginação de
relatórios no servidor é uma evolução necessária.

## Regras de cálculo e propriedade

- Idade em anos completos, na data da consulta.
- Revisões são atribuídas ao proprietário vinculado ao veículo. A aplicação
  bloqueia troca de proprietário quando já existem revisões, pois não há tabela
  de histórico de propriedade.
- Média calculada por pessoa, entre datas distintas consecutivas de revisões,
  considerando todos os veículos dessa pessoa. Datas iguais são deduplicadas.
- Próxima data = última data + média de dias arredondada ao inteiro mais próximo.
- Com menos de duas datas distintas, a média e previsão ficam nulas e a tela
  informa histórico insuficiente. Uma previsão anterior a hoje é sinalizada.
- Previsão é estatística, não recomendação técnica do fabricante.
- Gêneros ausentes em registros antigos aparecem como não informados.
