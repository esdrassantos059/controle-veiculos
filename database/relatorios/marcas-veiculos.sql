-- Marcas por quantidade de veículos
SELECT m.nome AS marca, COUNT(c.id) AS total FROM marca m LEFT JOIN carro c ON c.marca_id=m.id
GROUP BY m.id,m.nome ORDER BY total DESC,m.nome;
