-- Gelatto ICE CO. — Supabase schema
-- Run this once in the Supabase SQL editor (Project → SQL Editor → New query).

-- ============================================================
-- Tables
-- ============================================================

create table if not exists pontos (
  id uuid primary key default gen_random_uuid(),
  nome text not null,
  endereco text not null,
  tipo text not null check (tipo in ('balada', 'mercado', 'evento', 'bar')),
  capacidade_kg numeric not null check (capacidade_kg > 0),
  consumo_medio_dia numeric not null default 0 check (consumo_medio_dia >= 0),
  regiao text not null,
  status text not null default 'ativo' check (status in ('ativo', 'inativo', 'manutencao')),
  latitude numeric,
  longitude numeric,
  created_at timestamptz not null default now()
);

create table if not exists movimentacoes_estoque (
  id uuid primary key default gen_random_uuid(),
  ponto_id uuid not null references pontos(id) on delete cascade,
  tipo text not null check (tipo in ('entrada', 'saida')),
  quantidade_kg numeric not null check (quantidade_kg > 0),
  -- entrada: custo pago por kg. saida: preço de venda por kg.
  valor_unitario numeric not null check (valor_unitario >= 0),
  data date not null default current_date,
  observacao text,
  created_by uuid references auth.users(id),
  created_at timestamptz not null default now()
);

create table if not exists profiles (
  id uuid primary key references auth.users(id) on delete cascade,
  full_name text not null,
  role text not null default 'operador' check (role in ('admin', 'operador')),
  created_at timestamptz not null default now()
);

create table if not exists app_settings (
  id boolean primary key default true check (id),
  empresa_nome text not null default 'Gelatto ICE CO.',
  empresa_cnpj text,
  moeda text not null default 'BRL',
  fuso_horario text not null default 'America/Sao_Paulo',
  tema text not null default 'claro' check (tema in ('claro', 'escuro')),
  notificacoes_email boolean not null default true,
  updated_at timestamptz not null default now()
);
insert into app_settings (id) values (true) on conflict (id) do nothing;

create table if not exists relatorios_gerados (
  id uuid primary key default gen_random_uuid(),
  tipo text not null check (tipo in ('consumo_por_ponto', 'financeiro_mensal', 'reposicoes', 'estoque_consolidado')),
  formato text not null check (formato in ('pdf', 'excel')),
  periodo_inicio date not null,
  periodo_fim date not null,
  gerado_por uuid references auth.users(id),
  created_at timestamptz not null default now()
);

create index if not exists idx_movimentacoes_ponto on movimentacoes_estoque(ponto_id);
create index if not exists idx_movimentacoes_data on movimentacoes_estoque(data);

-- ============================================================
-- Views — lucro/margem/estoque are always derived, never stored
-- ============================================================

-- Estoque atual e custo médio ponderado por ponto (a partir de todas as entradas).
create or replace view v_pontos_estoque as
select
  p.*,
  coalesce(ent.total_kg, 0) - coalesce(sai.total_kg, 0) as estoque_atual_kg,
  case when coalesce(ent.total_kg, 0) > 0
    then ent.total_valor / ent.total_kg
    else 0
  end as custo_medio_kg,
  greatest(ent.ultimo, sai.ultimo) as ultimo_movimento,
  case when p.consumo_medio_dia > 0
    then round((coalesce(ent.total_kg, 0) - coalesce(sai.total_kg, 0)) / p.consumo_medio_dia, 1)
    else null
  end as previsao_esgotamento_dias
from pontos p
left join (
  select ponto_id, sum(quantidade_kg) total_kg, sum(quantidade_kg * valor_unitario) total_valor, max(data) ultimo
  from movimentacoes_estoque where tipo = 'entrada' group by ponto_id
) ent on ent.ponto_id = p.id
left join (
  select ponto_id, sum(quantidade_kg) total_kg, max(data) ultimo
  from movimentacoes_estoque where tipo = 'saida' group by ponto_id
) sai on sai.ponto_id = p.id;

-- Cada saída (venda) com receita/custo/lucro/margem calculados no custo médio do ponto.
create or replace view v_movimentacoes_margem as
select
  m.*,
  pe.custo_medio_kg,
  m.quantidade_kg * m.valor_unitario as receita,
  m.quantidade_kg * pe.custo_medio_kg as custo,
  m.quantidade_kg * (m.valor_unitario - pe.custo_medio_kg) as lucro,
  case when m.valor_unitario > 0
    then round(((m.valor_unitario - pe.custo_medio_kg) / m.valor_unitario) * 100, 1)
    else 0
  end as margem_pct
from movimentacoes_estoque m
join v_pontos_estoque pe on pe.id = m.ponto_id
where m.tipo = 'saida';

-- Receita/custo/lucro por mês (últimos 12 meses).
create or replace view v_financeiro_mensal as
select
  date_trunc('month', data)::date as mes,
  sum(receita) as receita,
  sum(custo) as custo,
  sum(lucro) as lucro
from v_movimentacoes_margem
group by 1
order by 1;

-- Ranking de lucro por ponto no mês corrente vs mês anterior.
create or replace view v_lucro_por_ponto as
select
  ponto_id,
  sum(lucro) filter (where date_trunc('month', data) = date_trunc('month', current_date)) as lucro_mes_atual,
  sum(lucro) filter (where date_trunc('month', data) = date_trunc('month', current_date) - interval '1 month') as lucro_mes_anterior
from v_movimentacoes_margem
group by ponto_id;

-- ============================================================
-- Row Level Security — any authenticated user (admin or operador) can
-- read and write; only two trusted users exist for this internal tool.
-- ============================================================

alter table pontos enable row level security;
alter table movimentacoes_estoque enable row level security;
alter table profiles enable row level security;
alter table relatorios_gerados enable row level security;
alter table app_settings enable row level security;

create policy "authenticated read pontos" on pontos for select to authenticated using (true);
create policy "authenticated write pontos" on pontos for insert to authenticated with check (true);
create policy "authenticated update pontos" on pontos for update to authenticated using (true);
create policy "authenticated delete pontos" on pontos for delete to authenticated using (true);

create policy "authenticated read movimentacoes" on movimentacoes_estoque for select to authenticated using (true);
create policy "authenticated write movimentacoes" on movimentacoes_estoque for insert to authenticated with check (true);
create policy "authenticated update movimentacoes" on movimentacoes_estoque for update to authenticated using (true);
create policy "authenticated delete movimentacoes" on movimentacoes_estoque for delete to authenticated using (true);

create policy "authenticated read profiles" on profiles for select to authenticated using (true);
create policy "user updates own profile" on profiles for update to authenticated using (auth.uid() = id);
create policy "admin updates any profile" on profiles for update to authenticated using (
  exists (select 1 from profiles me where me.id = auth.uid() and me.role = 'admin')
);

create policy "authenticated read relatorios" on relatorios_gerados for select to authenticated using (true);
create policy "authenticated write relatorios" on relatorios_gerados for insert to authenticated with check (true);

create policy "authenticated read settings" on app_settings for select to authenticated using (true);
create policy "admin write settings" on app_settings for update to authenticated using (
  exists (select 1 from profiles me where me.id = auth.uid() and me.role = 'admin')
);

-- Auto-create a profile row whenever a new auth user signs up.
create or replace function handle_new_user()
returns trigger as $$
begin
  insert into public.profiles (id, full_name, role)
  values (new.id, coalesce(new.raw_user_meta_data->>'full_name', new.email), 'operador');
  return new;
end;
$$ language plpgsql security definer;

drop trigger if exists on_auth_user_created on auth.users;
create trigger on_auth_user_created
  after insert on auth.users
  for each row execute function handle_new_user();
