-- Marcas com mais revisões
SELECT m.nome AS marca,COUNT(r.id) AS total FROM marca m
LEFT JOIN carro c ON c.marca_id=m.id LEFT JOIN revisao r ON r.carro_id=c.id
GROUP BY m.id,m.nome ORDER BY total DESC,m.nome;
