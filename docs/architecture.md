# Arquitetura

Este documento descreve a fundação técnica (Fase 0). O Master Specification
(`.kiro/steering/master-specification.md`) é a referência oficial e prevalece.

## Fluxo de uma requisição

```
public/index.php
  → app/bootstrap.php (autoloader + Kernel)
    → Kernel::boot() (container, serviços, sessão, erros, locale)
    → Kernel::handle() (carrega routes/web.php e routes/api.php)
      → Router::dispatch()
        → Middlewares (pipeline)
          → Controller (fluxo)
            → Service (regra de negócio)
              → Repository (banco, prepared statements)
```

Nenhuma camada pula outra. Views nunca acessam banco nem contêm regra de
negócio.

## Componentes do núcleo (`app/core`)

- **Kernel** — composition root. Registra serviços no container, prepara
  ambiente (sessão, tratamento de erros, dados compartilhados nas views) e
  despacha a requisição.
- **Container** — injeção de dependência simples (singletons e instâncias).
- **Router** — mapeia método+rota para `[Controller, ação]`, suporta grupos,
  parâmetros nomeados (`/users/{id}`) e pipeline de middlewares.
- **View** — renderiza templates PHP, com layouts e parciais/componentes.
- **Controller / Service / Repository / Model** — classes base das camadas.
- **ErrorHandler** — registra erros em log e mostra mensagem amigável.

## Bibliotecas (`app/libraries`)

- **Database** — wrapper PDO (singleton) com `run/fetch/fetchAll/execute` e
  transações; sempre prepared statements.
- **Request / Response** — entrada HTTP e saída (HTML ou JSON no envelope
  padrão `success/message/data/errors/meta`).
- **Session / Csrf** — sessão endurecida e proteção CSRF.
- **Translator** — traduções por chave em notação de ponto
  (`grupo.item`), arquivos em `app/lang/{locale}/{grupo}.php`.
- **Cache** — cache em arquivo com limpeza centralizada (`flush`).
- **Logger** — log diário em `storage/logs`.

## Configuração

- `config/database.php` — **única** configuração em arquivo (conexão do banco).
  Sobrescreva localmente com `config/database.local.php` (git-ignored).
- `config/app.php` — apenas valores de bootstrap (locale padrão, sessão, debug).
- Todas as demais configurações ficam no banco (tabela `settings`) e são lidas
  pelo `ConfigService`. **Nunca** usar `.env` ou variáveis de ambiente.

## Segurança e ACL

- **CsrfMiddleware** valida o token em métodos que alteram estado.
- **AuthMiddleware** exige usuário autenticado.
- **PermissionMiddleware** (abstrato) — subclasses declaram a permissão
  granular exigida (ex.: `users.view`). **SuperAdminMiddleware** restringe a
  Super Admins.
- **AuthService** concentra login/logout, usuário atual, verificação de
  permissões (com cache), Super Admin e impersonação.

## Eventos

- **EventDispatcher** (`app/events`) permite publicar eventos nomeados e
  registrar múltiplos listeners, sem acoplar funcionalidades. Preparado para,
  no futuro, executar listeners via filas (jobs).

## Front-end

- Tokens de design em `public/assets/css/tokens.css` (paleta roxa; dark mode via
  `[data-theme="dark"]`). Nunca usar cores fixas fora dos tokens.
- Componentes do Design System em `public/assets/css/app.css`.
- `public/assets/js/app.js`: Toasts, confirmação de ações destrutivas
  (`data-confirm`) e estado de carregamento. Nunca `alert()`.
- Layouts em `app/views/layouts`: `public` (institucional) e `app`
  (administrativo, com sidebar responsiva). Ícones: apenas Bootstrap Icons.

## Módulos da Fase 1

### Site institucional (público)
- `PublicController` renderiza Home, Recursos, Como funciona, Planos, FAQ,
  Contato, Termos e Privacidade, usando o layout `public`.
- `SeoController` serve `/robots.txt` e `/sitemap.xml` dinamicamente.
- Header, footer e ações flutuantes (WhatsApp/Ajuda) leem dados das
  Configurações Gerais. Recursos futuros são exibidos como "em desenvolvimento".

### Lista de espera
- Público: `WaitlistController` (form simples nome/e-mail/telefone + opcionais)
  com CSRF, honeypot + time-trap (anti-spam), rate limit e captura de UTM/origem.
- Regras em `WaitlistService`: validação, deduplicação por e-mail, registro de
  atividade (timeline), evento `waitlist.lead.created` e e-mail de confirmação
  (quando o SMTP estiver configurado).
- Admin: `Admin\WaitlistController` com listagem (busca, filtros, ordenação,
  paginação), detalhe com timeline, troca de status, notas internas, exclusão
  (soft delete) e exportação CSV dos registros filtrados.

### Painel Super Admin (`/app`)
- `Admin\DashboardController`: métricas da lista de espera.
- `Admin\PlanController`: CRUD de planos (preços editáveis, nunca hardcoded).
- `Admin\UserController`: CRUD de usuários + impersonação (Super Admin).
- `Admin\RoleController`: edição de permissões por perfil (ACL), limpando o
  cache de permissões após salvar.
- `Admin\SettingsController`: Configurações Gerais por categoria (site, e-mail,
  SEO, WhatsApp, IA, integrações, segurança); segredos são write-only na UI.
- `Admin\LogController`: visualização do log de auditoria.

### Segurança da Fase 1
- `RateLimiter` (cache) em login, recuperação de senha e lista de espera.
- Recuperação de senha com token único e expiração (`UserService`,
  `PasswordResetRepository`), sempre com mensagem neutra.
- `ActivityLogService` registra login/logout, CRUD, alterações de configuração,
  cadastro/edição de leads, impersonação e exportações.

### Preparação para o futuro (sem implementar agora)
- Multi-tenancy: `waitlist_leads.tenant_id` (nullable) já previsto.
- WhatsApp (Evolution API) e IA: campos de configuração prontos, sem automação.
- API: `routes/api.php` separado, com envelope JSON padrão.

## Módulo de Auditoria (Fase 2)

Motor de análise de sites, dividido em módulos independentes para permitir
adicionar novos analisadores sem reescrever o scanner.

### Pipeline

```
AuditService::process()
  -> coleta robots.txt + sitemap.xml
  -> Crawler (SSRF-safe HttpClient) -> páginas (persistidas incrementalmente)
  -> Analyzers determinísticos (SEO, HTTPS, cabeçalhos, performance,
     acessibilidade, conteúdo, tecnologias, contatos, links)
  -> ScoringEngine (scores por categoria + geral)
  -> persistência (issues/metrics/technologies/contacts/links + resultado)
```

### Camada HTTP e SSRF (reutilizável)

- `App\Libraries\Http\SsrfGuard`: validação/normalização de URL e bloqueio de
  destinos internos; usada por qualquer recurso que faça requisições externas.
- `App\Libraries\Http\HttpClient`: cURL (fallback stream) com timeouts, limite
  de tamanho, User-Agent próprio, redirects re-validados e IP "pinado"
  (anti-DNS-rebinding).

### Processamento assíncrono

- `bin/audit-worker.php` processa a fila. Idempotente: claim atômico
  (`AuditRepository::claimNextQueued`), heartbeat durante o crawl e recuperação
  de auditorias presas (`requeueStuck`). Compatível com CLI e cron.
- O request web apenas cria a auditoria (status `queued`); nunca executa o crawl.

### Isolamento de dados

`App\Services\AccessContext` abstrai o "dono" do recurso (hoje o usuário;
futuramente um tenant). Toda leitura de auditoria passa por
`AuditRepository::findForContext/listForContext`. Super Admin vê tudo.

### Relatório e PDF

O relatório é um HTML único (`app/views/audits/report.php`), servido print-ready
e reutilizado por `PdfService` quando o Dompdf está instalado. Trocar o motor de
PDF não exige reescrever o relatório.

### Dados reutilizáveis (fases futuras)

Os dados ficam separados em brutos (páginas), detalhados (issues, métricas,
tecnologias, contatos, links) e processados (`audit_results`, chave `summary`),
permitindo reaproveitamento futuro (CRM, leads, IA, comparação) sem recrawl.

## Módulo Comercial / CRM (Fase 3)

Base comercial construída sobre o mesmo contexto de acesso e ACL das fases
anteriores. Transforma auditorias técnicas em oportunidades gerenciáveis.

### Entidades

- `companies` (empresas) e `contacts` (contatos, com contato principal).
- `leads` (oportunidades) com pipeline, temperatura, qualificação e desfecho.
- `lead_audits` (N:N lead ↔ auditoria, sem duplicar dados) e
  `lead_status_history` (histórico de etapas).
- `activities` (notas, tarefas e eventos de timeline automáticos), `tags` +
  `taggables`, `services` e `lead_sources` (catálogos configuráveis).

### Camadas

- Repositories owner-scoped: `CompanyRepository`, `ContactRepository`,
  `LeadRepository` (junta o score da auditoria mais recente por subconsulta),
  `ActivityRepository`.
- Services: `CompanyService` (normalização de domínio + deduplicação),
  `ContactService`, `LeadService` (status + histórico + timeline, ganho/perda,
  `createFromAudit`), `ActivityService` (timeline automática), `CrmDashboardService`.
- Controllers em `App\Controllers\Admin` com rotas em `routes/admin.php`,
  protegidas por `PermissionMiddleware` + `CsrfMiddleware`.

### Isolamento (obrigatório)

Toda consulta passa por `AccessContext` (owner = usuário nesta fase; preparado
para tenant). `findForContext`/`listForContext` impedem que um usuário acesse
registros de outro contexto, mesmo alterando IDs na URL. Super Admin vê tudo.

### Vínculo com a auditoria (Fase 2)

A auditoria pode ser transformada em oportunidade: a empresa é encontrada ou
criada pelo domínio normalizado, a oportunidade é criada e a auditoria é
vinculada (`lead_audits`). O score mais recente aparece na oportunidade com link
para o relatório — sem recrawl e sem duplicar dados.
