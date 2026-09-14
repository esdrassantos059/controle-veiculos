-- Previsão das próximas revisões
WITH datas AS (
SELECT DISTINCT c.pessoa_id,r.data_revisao FROM revisao r JOIN carro c ON c.id=r.carro_id
), intervalos AS (
SELECT pessoa_id,data_revisao,data_revisao-LAG(data_revisao) OVER (PARTITION BY pessoa_id ORDER BY data_revisao) AS dias
FROM datas
), medias AS (
SELECT pessoa_id,COUNT(*) AS datas_distintas,MAX(data_revisao) AS ultima_revisao,ROUND(AVG(dias),2) AS media_dias
FROM intervalos GROUP BY pessoa_id
)
SELECT p.id,p.nome,m.ultima_revisao,m.media_dias,
m.ultima_revisao+ROUND(m.media_dias)::integer AS proxima_revisao,
CASE WHEN m.media_dias IS NULL THEN 'Histórico insuficiente'
WHEN m.ultima_revisao+ROUND(m.media_dias)::integer<CURRENT_DATE THEN 'Previsão vencida'
WHEN m.ultima_revisao+ROUND(m.media_dias)::integer=CURRENT_DATE THEN 'Prevista para hoje'
ELSE 'Prevista' END AS situacao
FROM pessoa p LEFT JOIN medias m ON m.pessoa_id=p.id ORDER BY proxima_revisao NULLS LAST,p.nome,p.id;
