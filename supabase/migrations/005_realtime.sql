-- Migration: liga o Supabase Realtime nas tabelas que os dois usuarios
-- editam ao mesmo tempo, pra tela atualizar sozinha quando um lanca uma
-- venda/ajuste/movimentacao e o outro esta com o app aberto — evita
-- lancar a mesma coisa duas vezes por nao ver que o outro ja fez.

alter publication supabase_realtime add table pontos;
alter publication supabase_realtime add table movimentacoes_estoque;
alter publication supabase_realtime add table ajustes_estoque;
alter publication supabase_realtime add table movimentacoes_fabrica;
