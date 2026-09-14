-- Todas as pessoas
SELECT id, nome, COALESCE(UPPER(genero),'N/I') AS genero,
data_nascimento, EXTRACT(YEAR FROM AGE(CURRENT_DATE,data_nascimento))::integer AS idade, email, telefone
FROM pessoa ORDER BY nome,id;
