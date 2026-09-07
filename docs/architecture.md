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
