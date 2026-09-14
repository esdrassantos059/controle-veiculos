-- Revisões no período
SELECT r.id,r.data_revisao,p.nome AS proprietario,m.nome AS marca,c.modelo,c.placa
FROM revisao r JOIN carro c ON c.id=r.carro_id JOIN pessoa p ON p.id=c.pessoa_id JOIN marca m ON m.id=c.marca_id
WHERE r.data_revisao BETWEEN :inicio AND :fim ORDER BY r.data_revisao,r.id;
