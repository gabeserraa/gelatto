-- Migration: ajuste manual de estoque do freezer.
-- Alem do decaimento automatico (consumo_medio_dia por dia desde a
-- ultima venda), agora da pra registrar uma baixa manual rapida quando
-- vender mais rapido que o esperado (ou uma correcao pra cima, se
-- contou errado). Nao tem preco/custo — so corrige a quantidade.

create table if not exists ajustes_estoque (
  id uuid primary key default gen_random_uuid(),
  ponto_id uuid not null references pontos(id) on delete cascade,
  quantidade_kg numeric not null check (quantidade_kg <> 0),
  motivo text,
  data date not null default current_date,
  created_by uuid references auth.users(id),
  created_at timestamptz not null default now()
);

create index if not exists idx_ajustes_estoque_ponto on ajustes_estoque(ponto_id);

alter table ajustes_estoque enable row level security;

create policy "authenticated read ajustes_estoque" on ajustes_estoque for select to authenticated using (true);
create policy "authenticated write ajustes_estoque" on ajustes_estoque for insert to authenticated with check (true);
create policy "authenticated update ajustes_estoque" on ajustes_estoque for update to authenticated using (true);
create policy "authenticated delete ajustes_estoque" on ajustes_estoque for delete to authenticated using (true);

-- Estoque atual agora soma o decaimento das vendas com os ajustes manuais,
-- nunca deixando o total ficar negativo.
drop view if exists v_lucro_por_ponto;
drop view if exists v_financeiro_mensal;
drop view if exists v_movimentacoes_margem;
drop view if exists v_pontos_estoque;

create or replace view v_pontos_estoque as
select
  p.*,
  greatest(coalesce(v.estoque_kg, 0) + coalesce(a.ajustes_kg, 0), 0) as estoque_atual_kg,
  coalesce(v.custo_medio_kg, 0) as custo_medio_kg,
  greatest(v.ultimo_movimento, a.ultimo_ajuste) as ultimo_movimento,
  case when p.consumo_medio_dia > 0
    then round(greatest(coalesce(v.estoque_kg, 0) + coalesce(a.ajustes_kg, 0), 0) / p.consumo_medio_dia, 1)
    else null
  end as previsao_esgotamento_dias
from pontos p
left join (
  select
    ponto_id,
    sum(greatest(quantidade_kg - consumo_medio_dia_ref * (current_date - data), 0)) as estoque_kg,
    sum(quantidade_kg * custo_kg) / nullif(sum(quantidade_kg), 0) as custo_medio_kg,
    max(data) as ultimo_movimento
  from (
    select m.*, p2.consumo_medio_dia as consumo_medio_dia_ref
    from movimentacoes_estoque m
    join pontos p2 on p2.id = m.ponto_id
  ) m
  group by ponto_id
) v on v.ponto_id = p.id
left join (
  select ponto_id, sum(quantidade_kg) as ajustes_kg, max(data) as ultimo_ajuste
  from ajustes_estoque
  group by ponto_id
) a on a.ponto_id = p.id;

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
