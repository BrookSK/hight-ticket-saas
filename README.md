# LRV Web — Plataforma para Agências Digitais

Sistema operacional completo para Agências Digitais: uma única plataforma para
acompanhar todo o ciclo, da prospecção do cliente à manutenção contínua.

## Descrição do projeto

O LRV Web não é apenas um CRM, scanner, ERP ou plataforma de IA. É a união de
todas essas categorias em uma única experiência, pensada para reduzir o trabalho
operacional da agência e ajudá-la a vender mais e entregar com mais qualidade.

## Objetivo

Permitir que qualquer agência digital consiga prospectar, vender, entregar e
manter clientes usando uma só plataforma, automatizando processos e eliminando
tarefas repetitivas.

Fluxo macro do produto:

```
Prospecção → Auditoria → CRM → Proposta → Fechamento → Projeto →
Entrega → Hospedagem → Manutenção → Relatórios → Renovação → Venda recorrente
```

## Tecnologias utilizadas

- PHP 8.3+
- MySQL (acesso via PDO com prepared statements)
- Arquitetura MVC própria (sem framework)
- HTML5, CSS3, JavaScript
- Bootstrap 5 + Bootstrap Icons (biblioteca única de ícones)

## Arquitetura

Fluxo em camadas obrigatório — nenhuma camada pula outra:

```
Request → Router → Middleware → Controller → Service → Repository → Database → (volta)
```

- **Controllers** apenas controlam fluxo (sem SQL, sem regra de negócio).
- **Services** contêm toda a regra de negócio. Comunicação entre módulos só via Services.
- **Repositories** só acessam o banco (sem regra de negócio, sempre `prepared statements`).
- **Models** representam dados.
- **Views** só renderizam.
- **Helpers** apenas funções auxiliares.
- **Middlewares** controlam autenticação, permissões, idioma etc.

Princípios: SOLID, baixo acoplamento, alta reutilização, arquitetura modular
(cada módulo com Controller/Service/Repository/Views/Permissões/Rotas/Traduções/
Docs). Preparada para eventos, filas, cache, API, webhooks, storage plugável e
múltiplos modelos de IA sem refatoração estrutural.

## Estrutura de pastas

```
/app
  /controllers      Controllers (fluxo)
  /models           Models (dados)
  /services         Services (regra de negócio)
  /repositories     Repositories (acesso a banco)
  /helpers          Funções auxiliares
  /middlewares      Middlewares (auth, permissões, CSRF, ...)
  /events           Sistema de eventos (EventDispatcher)
  /listeners        Listeners de eventos
  /jobs             Jobs (filas — preparado)
  /providers        Bootstrapping (Autoloader, ...)
  /libraries        Núcleo (Database, Request, Response, Session, ...)
  /lang             Traduções por idioma (ex.: pt-BR)
  /core             Kernel, Router, Container, View, bases MVC
  /views            Views, layouts e componentes
  bootstrap.php     Inicialização da aplicação
/config             app.php (bootstrap) e database.php (conexão)
/public             Front controller (index.php) e assets
  /assets           css, js, img, fonts
/routes             web.php e api.php
/database
  /migrations       Arquivos .sql cumulativos (execução manual)
/storage            logs, uploads, temp, cache (ignorados no git)
/docs               Documentação interna
```

## Como configurar

1. Requisitos: PHP 8.3+ (testado em 8.5) e MySQL.
2. Configure a conexão do banco em `config/database.php`.
   Para não versionar credenciais locais, copie os valores para
   `config/database.local.php` (ignorado pelo git) — ele sobrescreve o padrão.
   > O projeto **não usa `.env`**. Todas as demais configurações ficam no banco
   > (módulo Configurações Gerais) e são lidas pelo `ConfigService`.
3. Crie o banco e execute as migrations **manualmente**, em ordem crescente de
   versão (veja "Como atualizar"). Requer a extensão `pdo_mysql` do PHP.
4. Aponte o document root do servidor para `/public`.
   Localmente, para testar rápido:

   ```bash
   php -S 127.0.0.1:8000 -t public public/index.php
   ```

5. Acesse `http://127.0.0.1:8000`. Faça login em `/login` com o Super Admin
   inicial e **altere a senha imediatamente**:
   - E-mail: `admin@lrvweb.local`
   - Senha: `ChangeMe!2026`

6. No painel (`/app`), abra **Configurações** e preencha:
   - **Site**: nome, logo, contato, WhatsApp e redes sociais.
   - **E-mail (SMTP)**: host, porta, usuário, senha, remetente — necessário para
     enviar a confirmação da lista de espera e a recuperação de senha. Enquanto
     o SMTP não estiver configurado, o cadastro funciona normalmente e o envio é
     apenas registrado no log.
   - **SEO** e **Integrações** (Analytics): opcionais.
   - **WhatsApp** e **IA**: campos preparados para integrações futuras.

### Acesso ao Super Admin

O Super Admin tem acesso absoluto (todos os módulos e configurações) e pode
personificar outros usuários pelo painel de Usuários, com registro em log e
retorno imediato à conta original.

## Como atualizar

Toda alteração no banco gera um **novo** arquivo `.sql` em
`/database/migrations`. As migrations são cumulativas e **nunca** editadas
depois de aplicadas. Não há execução automática nem rota de migration.

Para aplicar as migrations, abra cada arquivo `.sql`, revise e execute o
conteúdo manualmente no MySQL, **em ordem crescente de versão**:

```bash
mysql -u USUARIO -p NOME_DO_BANCO < database/migrations/0001_20260907_create_core_tables.sql
mysql -u USUARIO -p NOME_DO_BANCO < database/migrations/0002_20260907_create_plans.sql
mysql -u USUARIO -p NOME_DO_BANCO < database/migrations/0003_20260907_create_waitlist.sql
mysql -u USUARIO -p NOME_DO_BANCO < database/migrations/0004_20260907_create_password_resets.sql
mysql -u USUARIO -p NOME_DO_BANCO < database/migrations/0005_20260907_add_phone_to_users.sql
mysql -u USUARIO -p NOME_DO_BANCO < database/migrations/0006_20260907_seed_phase1_permissions_settings.sql
```

Nunca edite uma migration já aplicada: para mudar o banco, crie uma nova.

## Como realizar backup

- **Banco de dados**: gere um dump completo do MySQL, por exemplo:

  ```bash
  mysqldump -u USUARIO -p NOME_DO_BANCO > backup-AAAA-MM-DD.sql
  ```

- **Arquivos enviados**: faça cópia da pasta `storage/uploads`.
- **Configuração de conexão**: guarde `config/database.php` (ou
  `config/database.local.php`) em local seguro.

Recomenda-se automatizar backups diários e testar a restauração periodicamente.

## Como contribuir

1. Leia o Master Specification em `.kiro/steering/master-specification.md`. Ele
   é a "constituição" do projeto e tem prioridade máxima.
2. Cada funcionalidade só é considerada concluída após atender o **Definition of
   Done** (ver `docs/development.md`), incluindo atualização de README, CHANGELOG
   e documentação do módulo.
3. Commits com mensagens claras e descritivas (ex.: "Adiciona módulo de
   auditoria."). Versionamento semântico `MAJOR.MINOR.PATCH`.

## Padrões utilizados

- MVC em camadas, SOLID, baixo acoplamento, alta reutilização.
- Todo código em inglês; toda interface traduzível (nada de texto fixo).
- Segurança: validação de input, prepared statements, CSRF, XSS, sessões e
  cookies seguros, uploads validados.
- Configurações no banco (exceto conexão). Chaves de API nunca no código.
- Design System único, Mobile First, CSS com variáveis (dark mode preparado),
  notificações via Toasts (nunca `alert()`), confirmação em ações destrutivas.
- Respostas de API no envelope `{ success, message, data, errors, meta }`.

## Licença

Proprietária. Consulte o arquivo [LICENSE](LICENSE).

## Histórico resumido

Consulte o [CHANGELOG](CHANGELOG.md) para o histórico de versões escrito para o
usuário final.
