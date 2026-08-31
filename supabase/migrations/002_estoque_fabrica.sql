-- Migration: adiciona o controle de estoque da fábrica (antes da distribuição).
-- Rode isso no SQL Editor do Supabase se seu banco já foi criado com o
-- schema.sql anterior — ele não inclui essa tabela/view ainda.

create table if not exists movimentacoes_fabrica (
  id uuid primary key default gen_random_uuid(),
  tipo text not null check (tipo in ('entrada', 'saida')),
  quantidade_kg numeric not null check (quantidade_kg > 0),
  valor_unitario numeric not null check (valor_unitario >= 0),
  data date not null default current_date,
  observacao text,
  created_by uuid references auth.users(id),
  created_at timestamptz not null default now()
);

create index if not exists idx_movimentacoes_fabrica_data on movimentacoes_fabrica(data);

create or replace view v_estoque_fabrica as
select
  coalesce((select sum(quantidade_kg) from movimentacoes_fabrica where tipo = 'entrada'), 0)
    - coalesce((select sum(quantidade_kg) from movimentacoes_fabrica where tipo = 'saida'), 0) as estoque_atual_kg,
  case when coalesce((select sum(quantidade_kg) from movimentacoes_fabrica where tipo = 'entrada'), 0) > 0
    then (select sum(quantidade_kg * valor_unitario) from movimentacoes_fabrica where tipo = 'entrada')
         / (select sum(quantidade_kg) from movimentacoes_fabrica where tipo = 'entrada')
    else 0
  end as custo_medio_kg,
  greatest(
    (select max(data) from movimentacoes_fabrica where tipo = 'entrada'),
    (select max(data) from movimentacoes_fabrica where tipo = 'saida')
  ) as ultimo_movimento;

alter table movimentacoes_fabrica enable row level security;

create policy "authenticated read movimentacoes_fabrica" on movimentacoes_fabrica for select to authenticated using (true);
create policy "authenticated write movimentacoes_fabrica" on movimentacoes_fabrica for insert to authenticated with check (true);
create policy "authenticated update movimentacoes_fabrica" on movimentacoes_fabrica for update to authenticated using (true);
create policy "authenticated delete movimentacoes_fabrica" on movimentacoes_fabrica for delete to authenticated using (true);
