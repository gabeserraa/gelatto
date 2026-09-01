-- Migration: notificacoes push (funcionam com o app fechado) quando um
-- ponto entra em estoque critico.
--
-- Depois de rodar isso, veja o README (secao "Notificacoes push") pros
-- proximos passos: guardar o segredo do webhook no Vault e publicar a
-- Edge Function send-critical-alert.

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

-- No maximo um alerta por ponto por dia, pra varias movimentacoes num
-- ponto ja critico nao virarem uma notificacao pra cada uma.
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

  -- Segredo ainda nao configurado no Vault — nao dispara, mas nao quebra
  -- o insert/update/delete que acionou o trigger.
  if v_secret is null then
    return coalesce(new, old);
  end if;

  perform net.http_post(
    url := 'https://jwrvgzzzosvwfimlmiqy.functions.supabase.co/send-critical-alert',
    headers := jsonb_build_object('Content-Type', 'application/json', 'x-webhook-secret', v_secret),
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
