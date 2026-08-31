-- Migration: consolida entrada+saida dos pontos em um unico registro de "venda".
-- Cada venda ja carrega quantidade, preco de venda/kg e custo/kg juntos —
-- nao precisa mais de dois lancamentos separados pra cada entrega.
--
-- O estoque atual do freezer passa a ser calculado por decaimento: cada
-- venda contribui com (quantidade_kg - consumo_medio_dia * dias desde a
-- entrega), somado entre todas as vendas do ponto. Isso preserva a barra
-- de progresso, a previsao de esgotamento e os alertas de reposicao sem
-- precisar de um evento explicito de "saida".
--
-- ATENCAO: isso apaga movimentacoes de pontos existentes (nao ha como
-- migrar valor_unitario de saida antigo pra um custo_kg que nunca foi
-- informado). Rode so se ainda nao tiver dados reais de vendas.

truncate table movimentacoes_estoque;

-- As views precisam sumir antes de mexer nas colunas, senao o Postgres
-- recusa o DROP COLUMN por causa da dependencia.
drop view if exists v_lucro_por_ponto;
drop view if exists v_financeiro_mensal;
drop view if exists v_movimentacoes_margem;
drop view if exists v_pontos_estoque;

alter table movimentacoes_estoque drop constraint if exists movimentacoes_estoque_tipo_check;
alter table movimentacoes_estoque drop column if exists tipo;
alter table movimentacoes_estoque rename column valor_unitario to preco_venda_kg;
alter table movimentacoes_estoque add column if not exists custo_kg numeric not null default 0;
alter table movimentacoes_estoque alter column custo_kg drop default;
alter table movimentacoes_estoque add constraint movimentacoes_estoque_custo_kg_check check (custo_kg >= 0);

-- Estoque atual do freezer: soma, entre todas as vendas do ponto, o que
-- sobra de cada entrega apos o consumo medio estimado desde a data dela.
create or replace view v_pontos_estoque as
select
  p.*,
  coalesce(v.estoque_atual_kg, 0) as estoque_atual_kg,
  coalesce(v.custo_medio_kg, 0) as custo_medio_kg,
  v.ultimo_movimento,
  case when p.consumo_medio_dia > 0 and v.estoque_atual_kg is not null
    then round(v.estoque_atual_kg / p.consumo_medio_dia, 1)
    else null
  end as previsao_esgotamento_dias
from pontos p
left join (
  select
    ponto_id,
    sum(greatest(quantidade_kg - consumo_medio_dia_ref * (current_date - data), 0)) as estoque_atual_kg,
    sum(quantidade_kg * custo_kg) / nullif(sum(quantidade_kg), 0) as custo_medio_kg,
    max(data) as ultimo_movimento
  from (
    select m.*, p2.consumo_medio_dia as consumo_medio_dia_ref
    from movimentacoes_estoque m
    join pontos p2 on p2.id = m.ponto_id
  ) m
  group by ponto_id
) v on v.ponto_id = p.id;

-- Cada venda ja tem receita/custo/lucro/margem direto nos seus proprios campos.
create or replace view v_movimentacoes_margem as
select
  m.*,
  m.quantidade_kg * m.preco_venda_kg as receita,
  m.quantidade_kg * m.custo_kg as custo,
  m.quantidade_kg * (m.preco_venda_kg - m.custo_kg) as lucro,
  case when m.preco_venda_kg > 0
    then round(((m.preco_venda_kg - m.custo_kg) / m.preco_venda_kg) * 100, 1)
    else 0
  end as margem_pct
from movimentacoes_estoque m;

create or replace view v_financeiro_mensal as
select
  date_trunc('month', data)::date as mes,
  sum(receita) as receita,
  sum(custo) as custo,
  sum(lucro) as lucro
from v_movimentacoes_margem
group by 1
order by 1;

create or replace view v_lucro_por_ponto as
select
  ponto_id,
  sum(lucro) filter (where date_trunc('month', data) = date_trunc('month', current_date)) as lucro_mes_atual,
  sum(lucro) filter (where date_trunc('month', data) = date_trunc('month', current_date) - interval '1 month') as lucro_mes_anterior
from v_movimentacoes_margem
group by ponto_id;
