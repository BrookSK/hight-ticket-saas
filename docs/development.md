# Guia de desenvolvimento

## Definition of Done (checklist obrigatório)

Nenhuma funcionalidade é considerada concluída sem atender todos os itens:

- [ ] Código desenvolvido e revisado
- [ ] Responsividade validada (desktop, tablet, mobile)
- [ ] Traduções adicionadas (nenhum texto fixo)
- [ ] Permissões configuradas (ACL granular)
- [ ] Logs implementados quando necessário
- [ ] Tratamento de erros implementado (mensagens amigáveis; detalhes só em log)
- [ ] Performance revisada (sem `SELECT *`, com índices, com paginação)
- [ ] Segurança validada (input validado, prepared statements, CSRF, XSS)
- [ ] README atualizado
- [ ] CHANGELOG atualizado (linguagem de usuário final)
- [ ] Migration SQL criada quando necessário, e a anterior preservada
- [ ] Interface validada
- [ ] Testes realizados

## Como criar um módulo

Cada módulo deve ser independente e conter:

- Controller em `app/controllers` (apenas fluxo)
- Service em `app/services` (regra de negócio)
- Repository em `app/repositories` (acesso a banco)
- Views em `app/views/<modulo>`
- Traduções em `app/lang/<locale>/<modulo>.php`
- Permissões (chaves granulares) registradas via migration
- Rotas em `routes/web.php` e/ou `routes/api.php`
- Documentação do módulo em `docs`

Comunicação entre módulos: **somente via Services**. Um módulo nunca depende da
estrutura interna de outro.

## Nomenclatura

- Controllers: `UserController.php`
- Models: `User.php`
- Services: `AuthService.php`
- Repositories: `UserRepository.php`
- Middlewares: `AuthMiddleware.php`

## Traduções

Use o helper `__('grupo.item', ['placeholder' => 'valor'])`. Nenhum texto
visível ao usuário pode ser fixo — inclui labels, placeholders, validações e
mensagens de erro.

## Respostas de API

Sempre o envelope padrão, com códigos HTTP apropriados:

```json
{ "success": true, "message": "", "data": null, "errors": [], "meta": {} }
```

Use os helpers de `App\Libraries\Response`: `success()`, `error()`, `json()`.

## Verificação local

- Lint de sintaxe: `php -l caminho/arquivo.php`
- Servidor local: `php -S 127.0.0.1:8000 -t public public/index.php`
