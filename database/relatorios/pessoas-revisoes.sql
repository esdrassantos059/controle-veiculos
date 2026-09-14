-- Pessoas com mais revisões
SELECT p.id,p.nome,COUNT(r.id) AS total FROM pessoa p
LEFT JOIN carro c ON c.pessoa_id=p.id LEFT JOIN revisao r ON r.carro_id=c.id
GROUP BY p.id,p.nome ORDER BY total DESC,p.nome,p.id;
