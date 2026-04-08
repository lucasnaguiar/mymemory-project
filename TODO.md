# Plano de desenvolvimento — API MyMemory + front-end (mymemory-spa)

Este arquivo organiza o desenvolvimento da **API** Laravel descrita em [`../system-documentation.md`](../system-documentation.md) (`mymemory-api`), e do **front-end** em [`../mymemory-spa/`](../mymemory-spa/), obedecendo a [`boas_praticas.md`](boas_praticas.md) (API) e a [`../mymemory-spa/react-typescript-tailwind-boas-praticas.md`](../mymemory-spa/react-typescript-tailwind-boas-praticas.md) (SPA).

**Referência de UI/UX:** o diretório [`../spa-old/`](../spa-old/) contém o protótipo anterior — usar **apenas** como guia de fluxos, hierarquia de telas e consistência visual ao implementar o `mymemory-spa`. Não reutilizar código do protótipo como base arquitetural; reimplementar no SPA alinhado às boas práticas.

---

## Convenções — API (Laravel)

**Checklist por entrega (back-end):**

- Controllers apenas orquestram; regras em **Services**
- Validação em **Form Requests**
- Troca de dados entre camadas com **DTOs** (sem arrays soltos)
- Respostas com **API Resources** (nunca Model direto)
- **PSR-1 / PSR-4 / PSR-12**; uma classe por arquivo; 4 espaços
- **Exceções customizadas** + tratamento centralizado no `Handler`
- **Migrations**; **transactions** em operações críticas
- Evitar **N+1**; **eager loading** onde fizer sentido
- Formato de sucesso: `{ "data": {}, "meta": {} }`; erro: `{ "error": true, "message": "..." }`
- **Versionamento** de rotas (`/api/v1/...` ou prefixo equivalente)
- **Policies** para autorização; autenticação alinhada à especificação (cookie `mm_access`, JWT 7 dias) usando padrões Laravel de forma segura (avaliar Sanctum SPA / tokens stateful vs JWT custom — documentar decisão na Etapa 0)
- **Testes**: Feature (fluxos HTTP) + Unit (services/DTOs críticos)

**Idioma do código (inglês — práticas universais) — API:**

- **Arquivos e namespaces:** nomes em inglês; classes PHP em `PascalCase` alinhadas ao PSR-4 (ex.: `MemoProcessService.php`, não `ProcessarMemoService.php`).
- **Variáveis, parâmetros, propriedades e métodos:** inglês; `camelCase` para membros de instância e locais; nomes descritivos, sem abreviações obscuras.
- **Constantes:** inglês; `UPPER_SNAKE_CASE` quando aplicável (PSR-1).
- **Banco de dados:** tabelas e colunas em inglês; **snake_case** para nomes SQL (padrão Laravel e interoperabilidade). Chaves estrangeiras e índices seguem o mesmo idioma.
- **Rotas e query params da API:** manter inglês nos segmentos técnicos; se a documentação do produto usar paths específicos, espelhar na implementação versionada sem misturar português em nomes internos (controllers, actions, policies).
- **Comentários e mensagens:** comentários no código preferencialmente em inglês; mensagens de erro expostas ao cliente podem seguir regra de produto (ex.: i18n futuro), mas **identificadores** (`error_code`, slugs) em inglês.

---

## Convenções — front-end (mymemory-spa)

**Estrutura alvo** (adaptar o projeto Vite existente):

`src/app/` · `components/` · `features/` · `hooks/` · `services/` · `stores/` (se necessário) · `types/` · `utils/` · `styles/` · `main.tsx`

**Checklist por entrega (front-end):**

- Componentes funcionais e hooks; **sem lógica pesada no JSX**; componentes pequenos e coesos
- **TypeScript:** evitar `any`; tipar props, respostas de API e erros; preferir `unknown` quando necessário
- **Comunicação com API:** centralizar em `services/`; **sem** `fetch` espalhado em páginas; tipar request/response; tratamento global de erros (`error` + `message` da API)
- **TanStack Query (React Query)** para cache, loading e revalidação; **clsx** (ou equivalente) + Tailwind utilitário; evitar duplicação de classes
- **SOLID / Clean Code** no front: uma responsabilidade por módulo; dependência de abstrações (ex.: cliente HTTP injetável ou encapsulado)
- **Testes:** Jest + React Testing Library nos fluxos críticos
- **Validação de formulários:** Zod ou Yup (sugerido no guia SPA), alinhada aos contratos da API
- **Nomenclatura em inglês:** componentes e arquivos de componente `PascalCase`; funções/variáveis `camelCase`; tipos e enums em inglês (alinhado à API)

**Integração com a API em cada etapa:** sempre que o back-end expuser o endpoint correspondente, o front deve consumir a **versão real** (`/api/v1/...`), com mocks/stubs apenas até o endpoint existir (documentar no PR ou na coluna de observações do registro).

**Nota:** A documentação do sistema cita Fastify na visão geral; a implementação da API aqui é **Laravel**, conforme `boas_praticas.md`.

---

## Etapas

Marque com `[x]` ao concluir. Subitens são critérios de aceite mínimos.

### Etapa 0 — Fundação técnica e contrato da API

**API**

- [x] Prefixo versionado (`v1`) em todas as rotas da API documentadas
- [x] Helpers ou classe base para respostas JSON (`data` / `meta` / erro padronizado)
- [x] Registro de exceções de domínio + mapeamento HTTP no `Handler`
- [x] Estrutura de pastas: `Http/Requests`, `Http/Resources`, `Services`, `DTOs`, `Exceptions` (e `Policies` quando houver modelos)
- [x] Decisão documentada (README interno ou comentário em `config`): autenticação cookie `mm_access` + JWT vs Sanctum; **Policies** em todo caso
- [x] `GET /api/v1/health` (equivalente a `/api/health` da doc — ajustar path se a doc for atualizada para incluir versão)

**SPA (mymemory-spa)**

- [x] Reorganizar/adicionar pastas conforme estrutura alvo em `react-typescript-tailwind-boas-praticas.md` (`app/`, `features/`, `services/`, `types/`, etc.)
- [x] Cliente HTTP em `services/` (base URL via env: `VITE_API_URL` ou equivalente), `credentials: 'include'` se a API usar cookie de sessão; parser único para `{ data, meta }` e `{ error, message }`
- [x] React Router + provedor TanStack Query; `clsx` instalado e padrão de uso definido
- [x] Página ou componente de desenvolvimento que chama `GET /api/v1/health` para validar CORS, URL base e (se aplicável) cookies
- [x] Mapear telas do protótipo em `spa-old/src/pages/` para rotas futuras do SPA (tabela ou comentário em `app/` — sem copiar implementação legada)

### Etapa 1 — Modelagem de dados e migrations

**API**

- [x] Nomes de tabelas, colunas, índices e FKs em **inglês** + **snake_case** (ver *Idioma do código* — API)
- [x] Modelos e migrations para: usuários, planos (individual e grupo), assinaturas/limites, grupos, membros, convites, memos (6 tipos + metadados), arquivos/storage keys, soft delete
- [x] Tabelas de uso (créditos API, downloads, armazenamento) alinhadas aos limites da documentação
- [x] Preferências de usuário (níveis de IA por tipo, confirmação antes de processar, som, threshold OCR)
- [x] Workspace ativo (contexto pessoal vs grupo)
- [x] Contexto estruturado: categorias, subcategorias, campos (escopo grupo/global, soft delete)
- [x] Tokens: verificação de e-mail (48h), reset de senha (1h)
- [x] Configurações admin: roteamento pipeline documentos (JSON), settings de mídia por plano
- [x] Índices e FKs; transações nas seeders críticas se aplicável

**SPA (mymemory-spa)**

- [x] Em `types/`, esboçar tipos TypeScript para entidades principais (User, Plan, Memo, Group, Workspace, Preferences, …) alinhados ao modelo da API; refinar quando Resources estabilizarem
- [x] Opcional: schemas Zod/Yup espelhando payloads de register/login e erros de validação

### Etapa 2 — Autenticação e rotas públicas de auth

Endpoints: `individual-plans`, `register`, `login`, `logout`, `verify-email`, `forgot-password`, `reset-password`.

**API**

- [ ] Form Requests + DTOs + Services + Resources para cada operação
- [ ] Hash de senha, rate limiting onde fizer sentido (esqueci senha / login)
- [ ] Envio de e-mail (filas recomendadas) para verificação e reset
- [ ] Logout limpando cookie/token conforme decisão da Etapa 0
- [ ] Feature tests dos fluxos felizes e erros de validação

**SPA (mymemory-spa)**

- [ ] `features/auth` + páginas: Login, Register (listar planos individuais via API), ForgotPassword, ResetPassword, VerifyEmail — fluxos espelhados em `spa-old` (UI/UX de referência)
- [ ] `services` tipados para auth; rotas protegidas vs públicas; após login, estado do usuário via React Query ou store mínima conforme guia
- [ ] Testes RTL: validação visível, submit e tratamento de erro da API

### Etapa 3 — `/api/me/*`

**API**

- [x] `GET me` — perfil
- [x] `GET me/usage` — consumo vs limites
- [x] `GET me/media-limits` — limites por tipo de mídia
- [x] `PATCH me/preferences`
- [x] `GET me/workspace-groups` + `PATCH me/workspace`
- [x] Policies garantindo que o recurso é sempre do usuário autenticado
- [x] Feature tests

**SPA (mymemory-spa)**

- [x] Integrar perfil, usage e media-limits no shell (ex.: header/dashboard) e na `UserPreferencesPage` (referência: `spa-old/src/pages/UserPreferencesPage.tsx`)
- [x] Seletor de workspace + persistência via `PATCH me/workspace`; listar grupos com `GET me/workspace-groups`
- [x] Preferências: `PATCH me/preferences` com UI alinhada a níveis de IA por tipo, som, confirmação pré-processamento, threshold OCR

### Etapa 4 — Memos: texto e URL (process / confirm / atalho) ✅

**API**

- [x] Texto: `process`, `confirm`, criação direta
- [x] URL: `process`, `confirm`, criação direta
- [x] Integração com camada de IA (LLM) encapsulada em service/interface injetável
- [x] Enforcement de limites de plano e nível de IA (`semIA` / `basico` / `completo`)
- [x] Feature tests com mocks da IA

**SPA (mymemory-spa)**

- [x] Fluxo em duas etapas **process → confirm** para texto e URL; atalho “sem revisão” se a API expuser
- [x] Páginas de revisão e painel inicial alinhados a `MemoTextReviewPage`, fluxo URL e componentes de revisão em `spa-old` (`MemoReviewChrome`, `MemoRegisterPanel`, etc. como referência visual/comportamental)
- [x] Services/hooks dedicados; estados de loading/erro e desabilitar ações quando limites do plano exigirem (dados de `me/usage` / preferências quando aplicável)

### Etapa 5 — Memos: imagem, áudio, vídeo e documento ✅

**API**

- [x] Upload, storage (S3 ou disco local conforme config), validação de tamanho/tipo por plano
- [x] Imagem: OCR + pipeline visão + resumo (services separados, DTOs entre etapas)
- [x] Áudio/Vídeo: Whisper + chunking quando aplicável
- [x] Documento: PDF/DOCX/MSG/EML → extração + resumo; roteamento JSON admin
- [x] `process` e `confirm` para cada tipo; tratamento de jobs assíncronos se necessário (fila + status)
- [x] Feature tests principais; mocks de provedores externos

**SPA (mymemory-spa)**

- [x] Upload multipart tipado; páginas de revisão: imagem, áudio, vídeo, documento (`spa-old`: `MemoImageReviewPage`, `MemoAudioReviewPage`, `MemoVideoReviewPage`, `MemoDocumentReviewPage`)
- [x] Progresso e mensagens de erro; respeitar `me/media-limits` na UI (tamanho/tipo antes do envio)
- [x] Se a API usar job assíncrono: polling ou subscription conforme contrato exposto pelo back-end

### Etapa 6 — Memos: upload genérico, listagem, busca e CRUD ✅

**API**

- [x] `POST upload` (detecção de tipo)
- [x] `GET recent` com `limit` e `groupId`
- [x] `POST search` (AND/OR, filtros data/autor/grupo, highlight na resposta)
- [x] `POST search/synonyms` (LLM)
- [x] `GET search/authors`
- [x] `GET :id`, `GET :id/file`, `PATCH :id`, `DELETE :id` (soft delete)
- [x] Autorização: memos pessoais vs grupo (Policies + queries escopadas)
- [x] Evitar N+1 em listagens e busca
- [x] Feature tests de busca e permissões

**SPA (mymemory-spa)**

- [x] Home/dashboard com memos recentes (`GET recent`) e cartões alinhados a `spa-old` (`MemoCard*`, `RecentMemos`)
- [x] `MemoSearchPage`: busca com AND/OR, filtros, autores, sinônimos via API; renderizar **highlight** retornado pela API
- [x] `MemoEditPage`, exclusão (soft delete), download (`GET :id/file` ou rota de mídia final)
- [x] Upload genérico (`POST upload`) integrado ao fluxo de criação quando fizer sentido na UX

### Etapa 7 — Grupos, planos de grupo e convites ✅

**API**

- [x] `GET group-plans` (público)
- [x] `POST groups` (plano de grupo)
- [x] `GET groups/:id/owner-panel`
- [x] `POST groups/:id/invites` (papel editor/viewer)
- [x] `POST group-invites/accept` (token)
- [x] Policies para dono vs membro vs viewer
- [x] Feature tests

**SPA (mymemory-spa)**

- [x] `GroupCreatePage` com `GET group-plans` + `POST groups`
- [x] `GroupOwnerPanelPage`: painel do dono e convites (`spa-old` como referência)
- [x] `GroupInviteAcceptPage`: aceite via token na URL/query
- [x] Garantir que o workspace (Etapa 3) reflete grupos disponíveis após criar/aceitar

### Etapa 8 — Contexto de memo (categorias / subcategorias / campos) ✅

**API**

- [x] `memo-context/groups`, `editor-meta`, `structure`, `groups/:groupId/structure`
- [x] CRUD com soft delete: categorias, subcategorias, campos
- [x] Filtro opcional por tipo de mídia onde a doc exigir
- [x] Feature tests e autorização por grupo/admin

**SPA (mymemory-spa)**

- [x] `MemoContextPage`: árvore e CRUD completo consumindo a API; `editor-meta` para habilitar/desabilitar ações na UI
- [x] UX alinhada a `spa-old/src/pages/MemoContextPage.tsx` (referência)

### Etapa 9 — Admin (`role admin`) ✅

**API**

- [x] CRUD `subscription-plans`
- [x] `media-settings` por plano (GET/PUT)
- [x] `document-ai-routing` (GET/PUT)
- [x] `cost-report` (query params período, tipo, plano)
- [x] `soft-deleted-memos/monthly-summary` + `hard-delete-month`
- [x] Middleware/gate `admin` + Policies
- [x] Feature tests restritos a admin

**SPA (mymemory-spa)**

- [x] Rotas `/admin` protegidas no front (além do 403 da API): `AdminPage`, `AdminMediaSettingsPage`, `AdminDocumentAiPage` (referência `spa-old`)
- [x] Relatório de custos com filtros; fluxo de hard delete por mês com confirmação explícita (destrutivo)

### Etapa 10 — Sistema, mídia local e hardening

**API**

- [ ] `GET /media/:authorId/:fileName` protegido (autenticação + autorização)
- [ ] Revisão de CORS, cookies seguros (httpOnly, SameSite), headers de segurança
- [ ] Logging e monitoração de erros em operações de IA e storage
- [ ] Suite de testes: objetivo de cobrir fluxos críticos auth + memo + grupo + admin

**SPA (mymemory-spa)**

- [ ] Componentes de preview/download usando URLs protegidas da API; mesma política de credenciais que o restante do app
- [ ] Passada de UX: loading skeletons, erros de rede, estados vazios; checagem de regressão nos fluxos principais
- [ ] Ampliar testes RTL/E2E (se adotado) nos caminhos: login → criar memo texto → buscar → logout

---

## Registro de conclusão — API

| Etapa | Concluída em | Observações |
|-------|----------------|-------------|
| 0 — Fundação | 2026-04-07 | Sanctum SPA cookie; ApiResponse; Exceptions + Handler; HealthController; routes/api.php v1 |
| 1 — Modelagem | 2026-04-07 | 17 migrations + 14 models; subscription_plans, groups, memos (6 tipos), memo_context, plan_media_settings, document_ai_routing |
| 2 — Auth | | |
| 3 — Me | 2026-04-07 | MeController (6 endpoints); DTOs, Requests, Resources, Services, UserPreferencePolicy; Sanctum instalado; 12 feature tests |
| 4 — Memos texto/URL | | |
| 5 — Memos mídia/doc | | |
| 6 — Busca/CRUD | | |
| 7 — Grupos | | |
| 8 — Memo context | | |
| 9 — Admin | | |
| 10 — Sistema/hardening | | |

---

## Registro de conclusão — front-end (mymemory-spa)

| Etapa | Concluída em | Observações |
|-------|----------------|-------------|
| 0 — Fundação | 2026-04-07 | Tailwind v4; React Router; TanStack Query; cliente HTTP (axios + ApiError); HealthCheckPage; mapa de rotas |
| 1 — Tipos / contratos | 2026-04-07 | types/models.ts, types/api.ts, types/auth.ts com Zod schemas (register, login, forgot/reset password, verify email) |
| 2 — Auth | | |
| 3 — Me / workspace / preferências | 2026-04-07 | meService.ts; hooks useMe/useUsage/useMediaLimits/usePreferences/useWorkspaceGroups; UserPreferencesPage (tabs prefs/uso); WorkspaceSelector |
| 4 — Memos texto/URL | | |
| 5 — Memos mídia/doc | | |
| 6 — Busca/CRUD | | |
| 7 — Grupos | | |
| 8 — Memo context | | |
| 9 — Admin | | |
| 10 — Mídia / hardening UX-testes | | |

---

## Referência rápida: total de endpoints (documentação)

**62 endpoints** — conferir cobertura ao final da Etapa 10 (lista em `system-documentation.md`).
