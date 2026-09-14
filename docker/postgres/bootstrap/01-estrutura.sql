-- Estrutura reproduzivel para um volume NOVO. Nao altera bancos existentes.
CREATE SCHEMA IF NOT EXISTS esdras;
SET search_path TO esdras;

CREATE TABLE pessoa (
 id SERIAL PRIMARY KEY, nome VARCHAR(150) NOT NULL CHECK (BTRIM(nome)<>''),
 genero CHAR(1) NOT NULL CHECK (genero IN ('M','F')),
 data_nascimento DATE NOT NULL CHECK (data_nascimento<=CURRENT_DATE),
 email VARCHAR(150) NOT NULL UNIQUE CHECK (BTRIM(email)<>''),
 telefone VARCHAR(20) NOT NULL CHECK (BTRIM(telefone)<>''),
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX pessoa_email_unico ON pessoa (LOWER(email));
CREATE TABLE marca (
 id SERIAL PRIMARY KEY,nome VARCHAR(100) NOT NULL CHECK(nome=BTRIM(nome) AND nome<>''),
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX marca_nome_unico ON marca (LOWER(nome));
CREATE TABLE carro (
 id SERIAL PRIMARY KEY,pessoa_id INTEGER NOT NULL REFERENCES pessoa(id) ON DELETE RESTRICT,
 marca_id INTEGER NOT NULL REFERENCES marca(id) ON DELETE RESTRICT,
 modelo VARCHAR(100) NOT NULL CHECK(BTRIM(modelo)<>''),
 ano INTEGER NOT NULL CHECK(ano BETWEEN 1960 AND EXTRACT(YEAR FROM CURRENT_DATE)::integer+1),
 placa VARCHAR(7) NOT NULL UNIQUE CHECK(placa ~ '^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$'),
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE revisao (
 id SERIAL PRIMARY KEY,carro_id INTEGER NOT NULL REFERENCES carro(id) ON DELETE RESTRICT,
 data_revisao DATE NOT NULL CHECK(data_revisao<=CURRENT_DATE),
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX carro_pessoa_idx ON carro(pessoa_id);
CREATE INDEX carro_marca_idx ON carro(marca_id);
CREATE INDEX revisao_carro_data_idx ON revisao(carro_id,data_revisao);
