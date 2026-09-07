---
inclusion: always
---

# Master Specification — Constituição do Projeto

Este documento tem prioridade máxima. Toda implementação deve segui-lo integralmente.
Em caso de conflito entre uma solicitação e este documento, este documento prevalece
(salvo instrução explícita do usuário para o contrário na sessão).

## Produto

Plataforma SaaS (não apenas CRM/scanner/ERP) para Agências Digitais, cobrindo o ciclo:
Prospecção → Auditoria → CRM → Proposta → Fechamento → Projeto → Entrega → Hospedagem →
Manutenção → Relatórios → Renovação → Venda recorrente. Preparada para novos módulos sem
refatoração estrutural.

## Tecnologias

- Backend: PHP 8.3+
- Arquitetura: MVC próprio (sem framework), SOLID, baixo acoplamento, alta reutilização
- Banco: MySQL (acesso via PDO + prepared statements)
- Frontend: HTML5, CSS3, JavaScript, Bootstrap 5
- Ícones: uma única biblioteca (Bootstrap Icons)

## Regras invioláveis

### Configuração
- PROIBIDO `.env` e variáveis de ambiente, em qualquer hipótese.
- Todas as configurações ficam no banco, acessadas por uma camada única (ConfigService),
  no módulo "Configurações Gerais" (apenas Super Admin).
- ÚNICA exceção: a conexão com o banco fica em um arquivo PHP próprio (`config/database.php`).
- Chaves de API sempre nas Configurações Gerais. Nunca no código, nunca em constantes fixas.

### Banco de dados
- Toda alteração de banco gera um NOVO arquivo `.sql` em `/database/migrations`.
- NUNCA editar migrations antigas. Migrations são cumulativas.
- NUNCA executar migrations automaticamente. NUNCA criar rota de migration.
- NUNCA executar SQL ao acessar páginas. Execução é SEMPRE manual.
- Cada migration deve conter cabeçalho: Nome, Versão, Data, Descrição, Objetivo, Autor.
- Nunca `SELECT *`. Selecionar apenas campos necessários. Usar índices.

### Arquitetura em camadas (fluxo obrigatório, nenhuma camada pula outra)
Request → Router → Middleware → Controller → Service → Repository → Database → (volta).
- Controllers apenas controlam fluxo (sem SQL, sem cálculos complexos, sem regra de negócio).
- Services contêm TODA a regra de negócio. Comunicação entre módulos só via Services.
- Repositories só acessam banco (sem regra de negócio).
- Models representam dados.
- Views só renderizam (sem SQL, sem regra de negócio).
- Helpers só funções auxiliares (sem banco, sem regra de negócio, sem Controllers).
- Middlewares controlam permissões/autenticação/idioma/etc.

### Código
- Todo código em inglês. Métodos pequenos, uma responsabilidade por classe/método.
- Nunca duplicar código. Nunca HTML misturado com regra de negócio. Reutilizar sempre.
- Arquitetura modular: cada módulo com Controller/Service/Repository/Views/Permissões/
  Rotas/Traduções/Documentação. Sem depender da estrutura interna de outro módulo.
- Preparar (sem necessariamente implementar já): eventos/listeners, jobs/filas, cache
  centralizado com limpeza, API (Web x API controllers, respostas JSON), webhooks, storage
  plugável (local/S3/R2/GCS), múltiplos modelos de IA via camada intermediária.

### Traduções
- Nenhum texto fixo. TUDO traduzível: labels, placeholders, validações, mensagens de erro.
- Usar o sistema de tradução (`__()`), com estrutura preparada para múltiplos idiomas.
  Idioma inicial: pt-BR.

### Permissões (ACL)
- ACL granular por permissão (ex.: `users.view`, `users.create`, `settings.smtp`).
- Perfis configuráveis: Super Admin, Admin, Gerente, Comercial, Marketing, Financeiro,
  Suporte, Desenvolvedor, Cliente, Visualizador (e novos no futuro).
- Super Admin: acesso absoluto, impersonação (com logs e retorno imediato).

### Auditoria, soft delete
- Registrar ações importantes (login, logout, CRUD, exportação, permissões, config, etc.)
  com usuário, data, hora, IP, navegador, SO, página, ação, objeto, resultado.
- Usar Soft Delete sempre que possível, com restauração.

### Segurança
- Validar todo input; sanitizar output quando necessário. Prepared statements.
- CSRF, XSS, SQL Injection, upload malicioso, força bruta. Rate limit preparado.
- Sessões e cookies seguros. Nunca exibir erro técnico ao usuário (só nos logs).
- Uploads validados (tipo, tamanho, extensão, nome); nunca executar arquivos enviados.

### UI / UX
- Design System único (botões, inputs, selects, checkbox, cards, tabelas, badges, alertas,
  toasts, modais). Nunca estilos diferentes para componentes iguais.
- Mobile First; funcional de verdade no celular. CSS com variáveis (dark mode preparado);
  nunca cores fixas espalhadas. Acessibilidade (labels, ARIA, foco, teclado, contraste).
- Notificações via Toasts (nunca `alert()`). Loading em toda ação assíncrona.
- Confirmação em toda ação destrutiva. Nunca tela vazia (empty state com ação/ajuda).
- Componentização: nunca duplicar HTML. Uma única biblioteca de ícones.
- Botão de ajuda por módulo; chat flutuante (WhatsApp + Ajuda) no institucional.

### SEO institucional
- Cada página: Title, Meta Description, Canonical, Open Graph, Twitter Card, Schema.org,
  Robots, Breadcrumb, URLs amigáveis, Sitemap. Core Web Vitals, lazy loading, minificação.
- Analytics (GA, GTM, Meta Pixel, Clarity, Search Console) ativável nas Configurações Gerais.

### Documentação e versionamento
- Toda funcionalidade concluída deve atualizar README.md, CHANGELOG.md e docs do módulo.
- CHANGELOG voltado ao usuário final (ex.: "Novo módulo de relatórios."), cronológico.
- Versionamento semântico MAJOR.MINOR.PATCH. Commits descritivos e claros.
- Dependências só quando necessárias (avaliar nativo, performance, manutenção).

## Definition of Done (checklist obrigatório)
Código desenvolvido e revisado; responsividade validada; traduções adicionadas; permissões
configuradas; logs quando necessário; tratamento de erros; performance revisada; segurança
validada; README atualizado; CHANGELOG atualizado; migration SQL criada quando necessário e
a anterior preservada; interface validada; testes realizados.

## Padrão de resposta de API
JSON com: `success`, `message`, `data`, `errors`, `meta`. Códigos HTTP apropriados
(200, 201, 400, 401, 403, 404, 422, 500). Nunca vazar erro interno ao usuário.

## Roadmap
1. Landing/institucional, lista de espera, painel Super Admin, gestão de interessados.
2. Scanner/Auditoria, dashboard, histórico, export PDF.
3. CRM, pipeline, leads, propostas, follow-up.
4. Descoberta de oportunidades, score de leads, IA comercial.
5. Entrega automatizada, WordPress, hospedagem, provisionamento.
6. IA criando landing pages/conteúdo/SEO.
7. Portal do cliente, manutenção, monitoramento, relatórios mensais, renovações.

## Nota sobre arquivos padrão
`public/index.php` e `.htaccess` devem, quando disponíveis, usar exatamente o padrão oficial
da LRV Web (nunca substituir/alterar estrutura). Enquanto não fornecidos, versões iniciais
coerentes com esta arquitetura são usadas e marcadas para substituição.
