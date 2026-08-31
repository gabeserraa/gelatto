# Gelatto ICE CO. — Painel de Gestão

Painel de gestão para distribuição de gelo em cubo: pontos de freezer parceiros, estoque, financeiro e relatórios. React + Vite + Tailwind, dados sincronizados via Supabase, hospedado gratuitamente no GitHub Pages.

## Stack

- **Frontend**: React 19 + Vite
- **Estilização**: Tailwind CSS v4 (tokens em [src/index.css](src/index.css))
- **Gráficos**: Recharts
- **Mapa**: React Leaflet + OpenStreetMap
- **Banco de dados & Auth**: Supabase (Postgres + Supabase Auth)
- **Exportação**: jsPDF (PDF) e SheetJS (Excel)
- **Deploy**: GitHub Actions → GitHub Pages

## Configuração inicial

### 1. Criar o projeto no Supabase

1. Crie um projeto gratuito em [supabase.com](https://supabase.com).
2. Abra **SQL Editor** e rode o conteúdo de [supabase/schema.sql](supabase/schema.sql) — cria as tabelas, views de lucro/estoque e as políticas de RLS.
3. Em **Authentication → Users**, crie os dois usuários (admin e operador) por e-mail/senha. Um perfil é criado automaticamente na tabela `profiles` (role padrão `operador` — promova o admin manualmente na aba Usuários do app, ou via SQL: `update profiles set role = 'admin' where id = '<uuid>'`).
4. Em **Project Settings → API**, copie a `Project URL` e a `anon public key`.

### 2. Configurar variáveis de ambiente

```bash
cp .env.example .env
```

Preencha `VITE_SUPABASE_URL` e `VITE_SUPABASE_ANON_KEY` com os valores do passo anterior.

### 3. Rodar localmente

```bash
npm install
npm run dev
```

### 4. Deploy no GitHub Pages

1. No repositório GitHub, vá em **Settings → Pages** e selecione a origem **GitHub Actions**.
2. Em **Settings → Secrets and variables → Actions**, adicione `VITE_SUPABASE_URL` e `VITE_SUPABASE_ANON_KEY` como *repository secrets*.
3. Faça push na branch `main` — o workflow em [.github/workflows/deploy.yml](.github/workflows/deploy.yml) builda e publica automaticamente.

## Estrutura

- `src/pages/` — as 6 telas (Dashboard, Pontos, Estoque, Financeiro, Relatórios, Configurações)
- `src/components/dashboard/` — cards, badges, modais de movimentação/ponto
- `src/lib/` — cliente Supabase, formatação, geração de relatórios
- `supabase/schema.sql` — schema completo (tabelas, views calculadas, RLS)
- `DESIGN.md` — sistema de design (paleta, tipografia, componentes)
