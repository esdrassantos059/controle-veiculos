-- Todos os veículos
SELECT c.id, p.nome AS proprietario, m.nome AS marca, c.modelo, c.ano, c.placa
FROM carro c JOIN pessoa p ON p.id=c.pessoa_id JOIN marca m ON m.id=c.marca_id
ORDER BY m.nome, c.modelo, c.id;
