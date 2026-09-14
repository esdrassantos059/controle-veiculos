-- Veículos por pessoa
SELECT p.id AS pessoa_id, p.nome AS proprietario, m.nome AS marca, c.modelo, c.placa
FROM pessoa p JOIN carro c ON c.pessoa_id=p.id JOIN marca m ON m.id=c.marca_id
ORDER BY p.nome, p.id, c.id;
