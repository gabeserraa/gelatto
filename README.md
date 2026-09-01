# Gelatto ICE CO. — Painel de Gestão

Painel de gestão para distribuição de gelo em cubo: estoque da fábrica, pontos de freezer parceiros, vendas, financeiro e relatórios. React + Vite + Tailwind, dados sincronizados em tempo real via Supabase, instalável como PWA, hospedado gratuitamente no GitHub Pages.

## Stack

- **Frontend**: React 19 + Vite, PWA instalável (service worker custom + notificações push)
- **Estilização**: Tailwind CSS v4 (tokens em [src/index.css](src/index.css)), modo claro/escuro por dispositivo
- **Gráficos**: Recharts
- **Mapa**: React Leaflet + OpenStreetMap
- **Banco de dados & Auth**: Supabase (Postgres + Auth + Realtime + Edge Functions)
- **Exportação**: jsPDF (PDF) e SheetJS (Excel)
- **Deploy**: GitHub Actions → GitHub Pages, com backup diário do banco

## Telas

Visão Geral, Pontos de Freezer (lista/mapa, detalhe por ponto), Estoque, Estoque da Fábrica, Financeiro & Lucro, Relatórios, Configurações (Perfil/Empresa/Usuários/Preferências/Integrações).

## Configuração inicial

### 1. Criar o projeto no Supabase

1. Crie um projeto gratuito em [supabase.com](https://supabase.com).
2. Abra **SQL Editor** e rode o conteúdo de [supabase/schema.sql](supabase/schema.sql) — cria as tabelas, views calculadas (lucro/margem/estoque, sempre derivadas, nunca armazenadas fixas), triggers e políticas de RLS. Se o banco já existir de uma versão anterior, rode as migrations em [supabase/migrations/](supabase/migrations/) em ordem, uma a uma.
3. Em **Authentication → Users**, crie os usuários por e-mail/senha. Um perfil é criado automaticamente na tabela `profiles` (role padrão `operador` — promova o admin via SQL: `update profiles set role = 'admin' where id = '<uuid>'`).
4. Em **Project Settings → API**, copie a `Project URL` e a `anon public key`.

### 2. Configurar variáveis de ambiente

```bash
cp .env.example .env
```

Preencha `VITE_SUPABASE_URL` e `VITE_SUPABASE_ANON_KEY`. `VITE_VAPID_PUBLIC_KEY` só é necessário se for usar notificações push (veja abaixo).

### 3. Rodar localmente

```bash
npm install
npm run dev
```

### 4. Deploy no GitHub Pages

1. No repositório GitHub, vá em **Settings → Pages** e selecione a origem **GitHub Actions**.
2. Em **Settings → Secrets and variables → Actions**, adicione `VITE_SUPABASE_URL` e `VITE_SUPABASE_ANON_KEY` como *repository secrets*.
3. Faça push na branch `main` — o workflow em [.github/workflows/deploy.yml](.github/workflows/deploy.yml) builda e publica automaticamente.

## Notificações push (opcional)

Alerta no celular quando um ponto entra em estoque crítico — funciona mesmo com o app fechado (no iPhone, só depois de instalado via "Adicionar à Tela de Início", pela restrição da Apple ao Safari).

1. Gere um par de chaves VAPID (`npx web-push generate-vapid-keys` ou qualquer gerador VAPID).
2. Adicione `VITE_VAPID_PUBLIC_KEY` (a chave pública) como repository secret no GitHub — o workflow de deploy já repassa pro build.
3. No SQL Editor do Supabase, rode `select vault.create_secret('<um segredo qualquer>', 'push_webhook_secret');` — guarda um segredo no Vault pra autenticar a Edge Function.
4. No dashboard do Supabase, em **Edge Functions**, publique a função em [supabase/functions/send-critical-alert](supabase/functions/send-critical-alert) com o nome exato `send-critical-alert`, e configure os secrets dela: `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY` e `PUSH_WEBHOOK_SECRET` (o mesmo valor do passo 3).
5. No app, em Configurações → Preferências → "Ativar notificações" (por aparelho).

## Backup automático

O workflow [.github/workflows/backup.yml](.github/workflows/backup.yml) roda todo dia às 3h (Brasília) e gera um dump completo do banco, guardado como *artifact* do GitHub Actions (privado, não vai pro histórico do repositório — o repo é público) com retenção de 90 dias.

Precisa de um repository secret `SUPABASE_DB_URL`: em **Project Settings → Database → Connection string**, copie a URI no modo *Session pooler* (porta 5432 ou 6543 em modo sessão — não use o *Transaction pooler*, que não suporta `pg_dump`).

Pra baixar um backup: aba **Actions** do repositório → roda `backup.yml` (ou espera o agendado) → abre o run → baixa o artifact.

## Estrutura

- `src/pages/` — as telas
- `src/components/dashboard/` — cards, badges, modais de venda/ajuste/ponto/movimentação
- `src/lib/` — cliente Supabase, formatação, realtime, push, classes de UI compartilhadas, geração de relatórios
- `src/contexts/` — auth e tema (claro/escuro)
- `src/sw.js` — service worker custom (precache + push notifications)
- `supabase/schema.sql` — schema completo (fonte da verdade para instalação do zero)
- `supabase/migrations/` — migrations incrementais, pra bancos já existentes
- `supabase/functions/` — Edge Functions
- `DESIGN.md` — sistema de design (paleta, tipografia, componentes)
