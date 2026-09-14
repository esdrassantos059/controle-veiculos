-- Pessoas por gênero e idade média
SELECT id,nome,COALESCE(UPPER(genero),'N/I') AS genero,data_nascimento,
EXTRACT(YEAR FROM AGE(CURRENT_DATE,data_nascimento))::integer AS idade,
ROUND(AVG(EXTRACT(YEAR FROM AGE(CURRENT_DATE,data_nascimento))) OVER (PARTITION BY UPPER(genero)),2) AS idade_media
FROM pessoa ORDER BY UPPER(genero),nome,id;
