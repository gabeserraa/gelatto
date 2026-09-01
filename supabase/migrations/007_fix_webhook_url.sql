-- Migration: corrige a chamada da Edge Function no trigger de estoque
-- critico. A 006 usava a URL errada (https://<ref>.functions.supabase.co/
-- <nome>, que nao existe) — o formato certo do Supabase e
-- https://<ref>.supabase.co/functions/v1/<nome>. Tambem faltava o header
-- Authorization que o Supabase exige antes de qualquer coisa chegar no
-- codigo da funcao (a checagem de x-webhook-secret dentro dela e a
-- seguranca de verdade; a anon key aqui so satisfaz esse portao — ela e
-- publica por natureza, sem problema estar no repo).

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
