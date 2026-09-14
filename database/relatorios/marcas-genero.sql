-- Marcas e gênero dos proprietários
SELECT m.nome AS marca,
COUNT(c.id) FILTER (WHERE UPPER(p.genero)='M') AS masculino,
COUNT(c.id) FILTER (WHERE UPPER(p.genero)='F') AS feminino,
COUNT(c.id) FILTER (WHERE p.genero IS NULL) AS nao_informado, COUNT(c.id) AS total
FROM marca m LEFT JOIN carro c ON c.marca_id=m.id LEFT JOIN pessoa p ON p.id=c.pessoa_id
GROUP BY m.id,m.nome ORDER BY total DESC,m.nome;
