-- Veículos por gênero
SELECT COALESCE(UPPER(p.genero),'N/I') AS genero, COUNT(c.id) AS total
FROM pessoa p JOIN carro c ON c.pessoa_id=p.id GROUP BY UPPER(p.genero) ORDER BY total DESC, genero;
