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
  -- Meta mensal opcional (kg) — so faz sentido pra alguns pontos (ex:
  -- baladas com meta combinada), fica null pros demais.
  meta_mensal_kg numeric check (meta_mensal_kg is null or meta_mensal_kg > 0),
  created_at timestamptz not null default now()
);

-- Cada linha e uma venda/entrega completa pro ponto: entrega + venda no
-- mesmo lancamento, com preco e custo por kg lado a lado. Nao ha um
-- evento separado de "saida" — o estoque do freezer e estimado por
-- decaimento a partir da data da entrega (ver v_pontos_estoque).
create table if not exists movimentacoes_estoque (
  id uuid primary key default gen_random_uuid(),
  ponto_id uuid not null references pontos(id) on delete cascade,
  quantidade_kg numeric not null check (quantidade_kg > 0),
  preco_venda_kg numeric not null check (preco_venda_kg >= 0),
  custo_kg numeric not null check (custo_kg >= 0),
  data date not null default current_date,
  observacao text,
  created_by uuid references auth.users(id),
  created_at timestamptz not null default now()
);

-- Baixa/correcao manual do estoque do freezer, sem preco/custo — usada
-- quando o ponto vende mais rapido que o decaimento estimado prevê (ou
-- pra corrigir uma contagem errada).
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

-- Estoque da fábrica: gelo produzido/armazenado antes de ser distribuído
-- aos pontos parceiros. Ledger separado — nao participa dos calculos de
-- lucro/ranking por ponto, e apenas um controle de entrada/saida.
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

-- Estoque atual do freezer: decaimento das vendas (o que sobra de cada
-- entrega apos o consumo medio estimado desde a data dela) somado aos
-- ajustes manuais (baixa rapida ou correcao), sem deixar o total negativo.
create or replace view v_pontos_estoque as
select
  p.*,
  greatest(coalesce(v.estoque_kg, 0) + coalesce(a.ajustes_kg, 0), 0) as estoque_atual_kg,
  coalesce(v.custo_medio_kg, 0) as custo_medio_kg,
  greatest(v.ultimo_movimento, a.ultimo_ajuste) as ultimo_movimento,
  case when p.consumo_medio_dia > 0
    then round(greatest(coalesce(v.estoque_kg, 0) + coalesce(a.ajustes_kg, 0), 0) / p.consumo_medio_dia, 1)
    else null
  end as previsao_esgotamento_dias,
  coalesce(m2.vendido_mes_kg, 0) as vendido_mes_kg
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
) a on a.ponto_id = p.id
left join (
  select ponto_id, sum(quantidade_kg) as vendido_mes_kg
  from movimentacoes_estoque
  where date_trunc('month', data) = date_trunc('month', current_date)
  group by ponto_id
) m2 on m2.ponto_id = p.id;

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

-- Estoque atual e custo médio ponderado da fábrica (linha única).
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
alter table ajustes_estoque enable row level security;
alter table movimentacoes_fabrica enable row level security;
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

create policy "authenticated read ajustes_estoque" on ajustes_estoque for select to authenticated using (true);
create policy "authenticated write ajustes_estoque" on ajustes_estoque for insert to authenticated with check (true);
create policy "authenticated update ajustes_estoque" on ajustes_estoque for update to authenticated using (true);
create policy "authenticated delete ajustes_estoque" on ajustes_estoque for delete to authenticated using (true);

create policy "authenticated read movimentacoes_fabrica" on movimentacoes_fabrica for select to authenticated using (true);
create policy "authenticated write movimentacoes_fabrica" on movimentacoes_fabrica for insert to authenticated with check (true);
create policy "authenticated update movimentacoes_fabrica" on movimentacoes_fabrica for update to authenticated using (true);
create policy "authenticated delete movimentacoes_fabrica" on movimentacoes_fabrica for delete to authenticated using (true);

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

-- ============================================================
-- Realtime — os dois usuarios veem a tela atualizar sozinha quando
-- um deles lanca uma venda/ajuste/movimentacao.
-- ============================================================

alter publication supabase_realtime add table pontos;
alter publication supabase_realtime add table movimentacoes_estoque;
alter publication supabase_realtime add table ajustes_estoque;
alter publication supabase_realtime add table movimentacoes_fabrica;

-- ============================================================
-- Push notifications — alerta no celular (app fechado ou nao) quando
-- um ponto entra em estoque critico. Ver README pros passos de Vault +
-- Edge Function depois de rodar isso.
-- ============================================================

create table if not exists push_subscriptions (
  id uuid primary key default gen_random_uuid(),
  user_id uuid not null references auth.users(id) on delete cascade,
  endpoint text not null unique,
  p256dh text not null,
  auth text not null,
  created_at timestamptz not null default now()
);

alter table push_subscriptions enable row level security;

create policy "user manages own push subscriptions" on push_subscriptions
  for all to authenticated using (auth.uid() = user_id) with check (auth.uid() = user_id);

create table if not exists alertas_criticos_enviados (
  ponto_id uuid not null references pontos(id) on delete cascade,
  data date not null default current_date,
  primary key (ponto_id, data)
);

alter table alertas_criticos_enviados enable row level security;

create policy "authenticated read alertas_criticos_enviados" on alertas_criticos_enviados
  for select to authenticated using (true);

create extension if not exists pg_net;

create or replace function check_estoque_critico()
returns trigger as $$
declare
  v_ponto_id uuid := coalesce(new.ponto_id, old.ponto_id);
  v_ponto record;
  v_secret text;
begin
  select nome, estoque_atual_kg, capacidade_kg
  into v_ponto
  from v_pontos_estoque
  where id = v_ponto_id;

  if v_ponto.capacidade_kg is null or v_ponto.capacidade_kg <= 0 then
    return coalesce(new, old);
  end if;

  if v_ponto.estoque_atual_kg / v_ponto.capacidade_kg > 0.15 then
    return coalesce(new, old);
  end if;

  insert into alertas_criticos_enviados (ponto_id, data)
  values (v_ponto_id, current_date)
  on conflict (ponto_id, data) do nothing;

  if not found then
    return coalesce(new, old);
  end if;

  select decrypted_secret into v_secret
  from vault.decrypted_secrets
  where name = 'push_webhook_secret';

  if v_secret is null then
    return coalesce(new, old);
  end if;

  perform net.http_post(
    url := 'https://jwrvgzzzosvwfimlmiqy.supabase.co/functions/v1/send-critical-alert',
    headers := jsonb_build_object(
      'Content-Type', 'application/json',
      'Authorization', 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Imp3cnZnenp6b3N2d2ZpbWxtaXF5Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODgxNTM5MjAsImV4cCI6MjEwMzcyOTkyMH0.4qpZgMyBundI0R46sHW6GSVlNc8rCwp9xXn5bZOfX08',
      'x-webhook-secret', v_secret
    ),
    body := jsonb_build_object(
      'ponto_nome', v_ponto.nome,
      'estoque_atual_kg', v_ponto.estoque_atual_kg,
      'capacidade_kg', v_ponto.capacidade_kg
    )
  );

  return coalesce(new, old);
end;
$$ language plpgsql security definer set search_path = public, vault, extensions;

drop trigger if exists trg_estoque_critico_movimentacoes on movimentacoes_estoque;
create trigger trg_estoque_critico_movimentacoes
  after insert or update or delete on movimentacoes_estoque
  for each row execute function check_estoque_critico();

drop trigger if exists trg_estoque_critico_ajustes on ajustes_estoque;
create trigger trg_estoque_critico_ajustes
  after insert or update or delete on ajustes_estoque
  for each row execute function check_estoque_critico();

-- ============================================================
-- Despesas gerais da empresa (equipamento, manutencao, reforma...) —
-- separado das vendas/estoque, nao entra no calculo de lucro por ponto.
-- ============================================================

create table if not exists despesas (
  id uuid primary key default gen_random_uuid(),
  descricao text not null,
  categoria text not null check (categoria in ('equipamento', 'manutencao', 'reforma', 'outro')),
  valor_total numeric not null check (valor_total > 0),
  parcelado boolean not null default false,
  numero_parcelas integer check (numero_parcelas is null or numero_parcelas >= 1),
  data date not null default current_date,
  observacao text,
  created_by uuid references auth.users(id),
  created_at timestamptz not null default now(),
  constraint despesas_parcelas_consistentes check (
    (parcelado and numero_parcelas is not null) or (not parcelado and numero_parcelas is null)
  )
);

create index if not exists idx_despesas_data on despesas(data);

alter table despesas enable row level security;

create policy "authenticated read despesas" on despesas for select to authenticated using (true);
create policy "authenticated write despesas" on despesas for insert to authenticated with check (true);
create policy "authenticated update despesas" on despesas for update to authenticated using (true);
create policy "authenticated delete despesas" on despesas for delete to authenticated using (true);

-- Parcelas pagas estimadas por cadencia mensal a partir da data da compra
-- (a compra em si conta como a 1a parcela). A vista = sempre "quitado".
create or replace view v_despesas as
select
  d.*,
  case when d.parcelado then round(d.valor_total / d.numero_parcelas, 2) else d.valor_total end as valor_parcela,
  case when not d.parcelado then 1
    else least(
      d.numero_parcelas,
      greatest(
        1,
        (extract(year from age(current_date, d.data)) * 12 + extract(month from age(current_date, d.data)) + 1)::int
      )
    )
  end as parcelas_pagas
from despesas d;

alter publication supabase_realtime add table despesas;
