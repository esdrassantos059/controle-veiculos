-- Intervalo médio entre revisões
WITH datas AS (
SELECT DISTINCT c.pessoa_id,r.data_revisao FROM revisao r JOIN carro c ON c.id=r.carro_id
), intervalos AS (
SELECT pessoa_id,data_revisao,data_revisao-LAG(data_revisao) OVER (PARTITION BY pessoa_id ORDER BY data_revisao) AS dias
FROM datas
), medias AS (
SELECT pessoa_id,COUNT(*) AS datas_distintas,MAX(data_revisao) AS ultima_revisao,ROUND(AVG(dias),2) AS media_dias
FROM intervalos GROUP BY pessoa_id
)
SELECT p.id,p.nome,COALESCE(m.datas_distintas,0) AS datas_distintas,m.ultima_revisao,m.media_dias
FROM pessoa p LEFT JOIN medias m ON m.pessoa_id=p.id ORDER BY p.nome,p.id;
