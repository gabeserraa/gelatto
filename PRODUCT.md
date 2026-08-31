# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Administrador único da Gelatto ICE CO. (distribuidora de gelo, revenda sem produção própria). Usa o painel no dia a dia para acompanhar estoque de gelo em freezers colocados em pontos parceiros (baladas, casas de eventos, mercados, bares, restaurantes) em regime de comodato, decidir quando repor cada ponto, e entender receita/custo/lucro por ponto e consolidado.

## Product Purpose

Sistema de gestão que substitui controle manual (planilha/memória) do ciclo de comodato de freezers: quanto estoque cada ponto tem, quando vai acabar, quanto custou repor, quanto o ponto rendeu. Sucesso = o admin nunca é pego de surpresa por um ponto zerado e sabe, de relance, quais pontos dão lucro.

## Positioning

Não é um ERP genérico nem uma planilha — é um painel construído em cima de um único conceito central (o histórico de movimentações de cada ponto: reposição/retirada/ajuste) do qual todo o resto — estoque atual, previsão de esgotamento, receita, custo, lucro, giro — é derivado matematicamente, nunca digitado à mão. Isso é uma decisão de arquitetura deliberada e não deve ser perdida no redesign: nenhuma tela deve ganhar um campo de "estoque atual" ou "lucro" editável.

## Operating Context

Uso diário, provavelmente em desktop (painel de gestão), mas responsivo o bastante para consulta rápida em mobile. Um único usuário logado por vez, sem troca de contas. Fluxo típico: abrir Visão Executiva → checar pontos com alerta de reposição → abrir detalhe do ponto → lançar movimentação (reposição ou retirada) → voltar ao dashboard.

## Capabilities and Constraints

- Único admin, sem sistema de papéis/permissões, sem registro público (decisão de escopo confirmada, não deve virar multiusuário no redesign).
- Seis áreas hoje: Visão Executiva (tela inicial), Pontos de Freezer, Estoque, Financeiro/Lucro, Relatórios, Configurações.
- Toda edição (cadastro de ponto, lançamento de movimentação) acontece em modal/inline via Livewire — nunca navega pra uma página de edição separada.
- Nomes de tabela/coluna em inglês; toda a interface (labels, textos, mensagens) em português do Brasil.
- Terminologia do domínio: "ponto" (ponto parceiro com freezer), "reposição"/"retirada"/"ajuste" (tipos de movimentação), "estoque atual", "situação" (crítico/repor em breve/OK — 3 níveis por dias até esgotar).

## Brand Commitments

Nome: **Gelatto ICE CO.** Identidade visual azul e branco já é uma decisão confirmada pelo usuário nesta sessão (não é aberta a redesign de paleta). Sem logo real fornecido — o app usa hoje um ícone de gota abstrato como placeholder de marca.

## Evidence on Hand

- Protótipo visual de referência em Figma Make (`https://churn-space-56833911.figma.site/`), com as 6 telas do produto já desenhadas — é a autoridade visual pra este redesign. Uso um droplet azul + "Gelatto ICE CO." como wordmark, sidebar escura, cards brancos com sombra suave, badges coloridos de status/situação, gráficos de pizza/rosca e barra em paleta azul.
- Dados fictícios (seeder) já povoam o app: ~10 pontos, histórico de 3-6 meses de movimentações cada.
- Specs e plano de implementação em `docs/superpowers/specs/2026-08-26-sistema-gestao-gelatto-design.md` e `docs/superpowers/plans/2026-08-26-sistema-gestao-gelatto.md` documentam as regras de negócio e a arquitetura de dashboards atuais.

## Product Principles

1. Nada de estoque/financeiro digitado à mão — sempre derivado do histórico de movimentações.
2. Edição sempre em modal/inline, nunca em página separada.
3. Adicionar um dashboard novo é mecânico: controller + view + rota + entrada no registro (`config/dashboards.php`) — o redesign não deve quebrar esse contrato.
4. Um único admin — sem preparar UI para múltiplos usuários/papéis.
5. Interface 100% português do Brasil, nomes técnicos (tabelas, rotas, variáveis) em inglês.
