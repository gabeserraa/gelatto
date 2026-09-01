-- Migration: meta mensal opcional por ponto (kg vendidos no mes), com
-- progresso calculado ao vivo — so faz sentido pra alguns pontos (ex:
-- baladas com meta combinada), entao fica null pros que nao tem meta.

alter table pontos add column if not exists meta_mensal_kg numeric;
alter table pontos add constraint pontos_meta_mensal_kg_check check (meta_mensal_kg is null or meta_mensal_kg > 0);

drop view if exists v_pontos_estoque;

create view v_pontos_estoque as
select
  p.*,
  greatest(coalesce(v.estoque_kg, 0) + coalesce(a.ajustes_kg, 0), 0) as estoque_atual_kg,
  coalesce(v.custo_medio_kg, 0) as custo_medio_kg,
  greatest(v.ultimo_movimento, a.ultimo_ajuste) as ultimo_movimento,
  case when p.consumo_medio_dia > 0
    then round(greatest(coalesce(v.estoque_kg, 0) + coalesce(a.ajustes_kg, 0), 0) / p.consumo_medio_dia, 1)
    else null
  end as previsao_esgotamento_dias,
  coalesce(m.vendido_mes_kg, 0) as vendido_mes_kg
from pontos p
left join (
  select
    ponto_id,
    sum(greatest(quantidade_kg - consumo_medio_dia_ref * (current_date - data), 0)) as estoque_kg,
    sum(quantidade_kg * custo_kg) / nullif(sum(quantidade_kg), 0) as custo_medio_kg,
    max(data) as ultimo_movimento
  from (
    select mv.*, p2.consumo_medio_dia as consumo_medio_dia_ref
    from movimentacoes_estoque mv
    join pontos p2 on p2.id = mv.ponto_id
  ) mv
  group by ponto_id
) v on v.ponto_id = p.id
left join (
  select ponto_id, sum(quantidade_kg) as ajustes_kg, max(data) as ultimo_ajuste
  from ajustes_estoque
  group by ponto_id
) a on a.ponto_id = p.id
left join (
  select ponto_id, sum(quantidade_kg) as vendido_mes_kg
  from movimentacoes_estoque
  where date_trunc('month', data) = date_trunc('month', current_date)
  group by ponto_id
) m on m.ponto_id = p.id;
