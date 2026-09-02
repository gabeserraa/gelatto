-- Migration: controle de despesas gerais da empresa (equipamento,
-- manutencao, reforma...) — separado das vendas/estoque, nao entra no
-- calculo de lucro por ponto. Parcelas pagas sao estimadas pela data de
-- compra + cadencia mensal (nao precisa marcar mes a mes na mao).

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
