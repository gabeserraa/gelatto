-- Migration: adiciona o horario+data real da ultima movimentacao da
-- fabrica na view (created_at, nao a data de negocio escolhida no
-- formulario) — pra mostrar "atualizado em .. as .." na Visao Geral.

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
  ) as ultimo_movimento,
  (select max(created_at) from movimentacoes_fabrica) as ultima_atualizacao;
