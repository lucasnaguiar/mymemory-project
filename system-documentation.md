# MyMemory — Documentação do Sistema

### Visão Geral

**MyMemory** é um SaaS para captura, organização e busca de anotações ("memos") com processamento por inteligência artificial. Suporta 6 tipos de mídia, compartilhamento em grupos e planos de assinatura com limites configuráveis.

**Stack:** Fastify + TypeScript + MySQL (API) · React + Vite (Web) · OpenAI + Whisper + Tesseract (IA) · S3 ou disco local (storage)

---

## Funcionalidades

### 1. Autenticação
- Cadastro com e-mail/senha e seleção de plano
- Login/logout com JWT em cookie HTTP-only (7 dias)
- Verificação de e-mail obrigatória (token com 48h)
- Recuperação de senha (token com 1h)

### 2. Memos — Criação em 6 tipos de mídia

Todos os tipos seguem o mesmo fluxo em **2 etapas**: `process` (IA processa e sugere resumo) → `confirm` (usuário revisa e salva).

| Tipo | Pipeline |
|---|---|
| **Texto** | Resumo por LLM |
| **URL** | Extração de conteúdo + resumo |
| **Imagem** | OCR (Tesseract) + correção por visão LLM + resumo |
| **Áudio** | Transcrição Whisper + resumo |
| **Vídeo** | Segmentação + Whisper + resumo |
| **Documento** | PDF/DOCX/MSG/EML → extração de texto + resumo |

Níveis de IA por tipo: `semIA` · `basico` (keywords) · `completo` (resumo completo)

### 3. Busca de Memos
- Operadores AND/OR entre termos
- Filtro por data, autor, grupo
- Sugestão de sinônimos via LLM
- Highlight dos termos encontrados

### 4. Grupos e Compartilhamento
- Criação de grupos com plano de assinatura próprio
- Convite de membros por e-mail (com papel: editor ou viewer)
- Workspace selector: alterna entre contexto pessoal e grupos
- Painel do proprietário para gerenciar convites

### 5. Contexto Estruturado (Categorias)
- Hierarquia: **Categorias** → **Subcategorias** → **Campos**
- Escopo por grupo ou global (admin)
- Filtro opcional por tipo de mídia
- Soft delete em todos os níveis

### 6. Planos e Limites
- Planos individuais e de grupo com limites configuráveis:
  - Máx. memos, armazenamento (GB), créditos de API/mês, downloads/mês
  - Suporte a áudio/vídeo grandes (chunking por minuto)
- Admin configura limites de mídia por plano (tamanho, chunk, OCR threshold)

### 7. Painel Admin
- CRUD de planos de assinatura
- Configurações de mídia por plano
- Roteamento de pipeline de documentos (JSON)
- Relatório de custos (por período, tipo de mídia, plano)
- Hard delete de memos soft-deleted por mês

### 8. Preferências do Usuário
- Nível de IA por tipo de mídia (por usuário)
- Confirmação antes de processar (on/off)
- Som (on/off)
- Threshold OCR para correção por visão (1–100 ou desligado)

---

## Endpoints da API

**Autenticação:** JWT via cookie `mm_access` (exceto rotas marcadas como públicas)

### Auth — `/api/auth/`

| Método | Endpoint | Auth | Descrição |
|---|---|---|---|
| GET | `/api/auth/individual-plans` | Não | Lista planos individuais ativos |
| POST | `/api/auth/register` | Não | Cadastro com e-mail, senha e plano |
| POST | `/api/auth/login` | Não | Login |
| POST | `/api/auth/logout` | Sim | Logout (limpa cookie) |
| POST | `/api/auth/verify-email` | Não | Verificação de e-mail por token |
| POST | `/api/auth/forgot-password` | Não | Solicita reset de senha |
| POST | `/api/auth/reset-password` | Não | Redefine senha com token |

### Me — `/api/me/`

| Método | Endpoint | Descrição |
|---|---|---|
| GET | `/api/me` | Perfil do usuário autenticado |
| GET | `/api/me/usage` | Consumo vs. limites do plano |
| GET | `/api/me/media-limits` | Limites de tamanho de arquivo por tipo |
| PATCH | `/api/me/preferences` | Atualiza preferências (IA, som, OCR) |
| GET | `/api/me/workspace-groups` | Grupos do usuário |
| PATCH | `/api/me/workspace` | Alterna workspace ativo |

### Memos — `/api/memos/`

**Texto**

| Método | Endpoint | Descrição |
|---|---|---|
| POST | `/api/memos/text/process` | Processa texto com IA, retorna sugestão |
| POST | `/api/memos/text/confirm` | Confirma e salva memo de texto |
| POST | `/api/memos/text` | Cria memo de texto sem revisão |

**URL**

| Método | Endpoint | Descrição |
|---|---|---|
| POST | `/api/memos/url/process` | Extrai conteúdo da URL e resume |
| POST | `/api/memos/url/confirm` | Confirma e salva memo de URL |
| POST | `/api/memos/url` | Cria memo de URL sem revisão |

**Imagem**

| Método | Endpoint | Descrição |
|---|---|---|
| POST | `/api/memos/image/process` | Upload + OCR + sugestão de resumo |
| POST | `/api/memos/image/confirm` | Confirma e salva memo de imagem |

**Áudio**

| Método | Endpoint | Descrição |
|---|---|---|
| POST | `/api/memos/audio/process` | Upload + transcrição Whisper + resumo |
| POST | `/api/memos/audio/confirm` | Confirma e salva memo de áudio |

**Vídeo**

| Método | Endpoint | Descrição |
|---|---|---|
| POST | `/api/memos/video/process` | Upload + transcrição + resumo |
| POST | `/api/memos/video/confirm` | Confirma e salva memo de vídeo |

**Documento**

| Método | Endpoint | Descrição |
|---|---|---|
| POST | `/api/memos/document/process` | Extrai texto do documento e resume |
| POST | `/api/memos/document/confirm` | Confirma e finaliza memo de documento |

**Upload genérico + Busca + CRUD**

| Método | Endpoint | Descrição |
|---|---|---|
| POST | `/api/memos/upload` | Upload genérico (auto-detecta tipo) |
| GET | `/api/memos/recent` | Memos recentes (`?limit&groupId`) |
| POST | `/api/memos/search` | Busca com AND/OR, filtros e datas |
| POST | `/api/memos/search/synonyms` | Gera sinônimos de um termo via LLM |
| GET | `/api/memos/search/authors` | Lista autores disponíveis |
| GET | `/api/memos/:id` | Detalhe do memo |
| GET | `/api/memos/:id/file` | Download do arquivo do memo |
| PATCH | `/api/memos/:id` | Edita texto/keywords |
| DELETE | `/api/memos/:id` | Soft delete |

### Grupos — `/api/groups/`

| Método | Endpoint | Descrição |
|---|---|---|
| GET | `/api/group-plans` | Planos de grupo ativos (público) |
| POST | `/api/groups` | Cria grupo |
| GET | `/api/groups/:groupId/owner-panel` | Painel do proprietário |
| POST | `/api/groups/:groupId/invites` | Convida membro por e-mail |
| POST | `/api/group-invites/accept` | Aceita convite por token |

### Contexto de Memo — `/api/memo-context/`

| Método | Endpoint | Descrição |
|---|---|---|
| GET | `/api/memo-context/groups` | Grupos gerenciáveis pelo usuário |
| GET | `/api/memo-context/editor-meta` | Flags de permissão do editor |
| GET | `/api/memo-context/structure` | Estrutura completa de categorias |
| GET | `/api/memo-context/groups/:groupId/structure` | Estrutura de grupo específico |
| POST | `/api/memo-context/categories` | Cria categoria |
| PATCH | `/api/memo-context/categories/:id` | Edita categoria |
| DELETE | `/api/memo-context/categories/:id` | Soft delete de categoria |
| POST | `/api/memo-context/categories/:id/subcategories` | Cria subcategoria |
| PATCH | `/api/memo-context/subcategories/:id` | Edita subcategoria |
| DELETE | `/api/memo-context/subcategories/:id` | Soft delete de subcategoria |
| POST | `/api/memo-context/categories/:id/campos` | Cria campo |
| PATCH | `/api/memo-context/campos/:id` | Edita campo |
| DELETE | `/api/memo-context/campos/:id` | Soft delete de campo |

### Admin — `/api/admin/` (role `admin`)

| Método | Endpoint | Descrição |
|---|---|---|
| GET | `/api/admin/subscription-plans` | Lista planos |
| POST | `/api/admin/subscription-plans` | Cria plano |
| PATCH | `/api/admin/subscription-plans/:id` | Edita plano |
| DELETE | `/api/admin/subscription-plans/:id` | Deleta plano |
| GET | `/api/admin/subscription-plans/:id/media-settings` | Configurações de mídia do plano |
| PUT | `/api/admin/subscription-plans/:id/media-settings` | Atualiza configurações de mídia |
| GET | `/api/admin/document-ai-routing` | Configuração de roteamento de documentos |
| PUT | `/api/admin/document-ai-routing` | Atualiza roteamento de documentos |
| GET | `/api/admin/cost-report` | Relatório de custos (`?dateFrom&dateTo&mediaType`) |
| GET | `/api/admin/soft-deleted-memos/monthly-summary` | Resumo de memos deletados por mês |
| POST | `/api/admin/soft-deleted-memos/hard-delete-month` | Hard delete de um mês específico |

### Sistema

| Método | Endpoint | Auth | Descrição |
|---|---|---|---|
| GET | `/api/health` | Não | Health check |
| GET | `/media/:authorId/:fileName` | Sim | Download de mídia local protegida |

---

**Total: 62 endpoints**

---

## Páginas do Frontend

| Página | Funcionalidade |
|---|---|
| **HomePage** | Dashboard principal com memos recentes e painel de criação |
| **LoginPage** | Formulário de login |
| **RegisterPage** | Cadastro com seleção de plano |
| **ForgotPasswordPage** | Solicitação de reset de senha |
| **ResetPasswordPage** | Redefinição de senha com token |
| **VerifyEmailPage** | Verificação de e-mail com token |
| **MemoSearchPage** | Busca avançada com filtros, sinônimos e highlight |
| **MemoTextReviewPage** | Revisão de memo de texto antes de confirmar |
| **MemoImageReviewPage** | Revisão de memo de imagem (OCR + visão) |
| **MemoAudioReviewPage** | Revisão de memo de áudio (transcrição Whisper) |
| **MemoVideoReviewPage** | Revisão de memo de vídeo (transcrição) |
| **MemoDocumentReviewPage** | Revisão de memo de documento |
| **MemoEditPage** | Edição de memo existente |
| **SelectPlanPage** | Seleção de plano individual |
| **GroupCreatePage** | Criação de grupo com plano |
| **GroupOwnerPanelPage** | Gerenciamento de grupo (convites, membros) |
| **GroupInviteAcceptPage** | Aceitação de convite de grupo |
| **MemoContextPage** | Gerenciador de categorias/subcategorias/campos |
| **AdminPage** | Painel admin (planos, custos, memos deletados) |
| **AdminMediaSettingsPage** | Configuração de limites de mídia por plano |
| **AdminDocumentAiPage** | Configuração do pipeline de documentos |
| **UserPreferencesPage** | Preferências do usuário (IA, som, OCR) |
