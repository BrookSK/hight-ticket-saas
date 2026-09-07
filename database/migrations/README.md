# Migrations

Regras (do Master Specification):

- Toda alteração no banco gera um **novo** arquivo `.sql` aqui.
- Migrations são **cumulativas**. **Nunca** edite uma migration já aplicada.
- **Nunca** há execução automática, nem rota de migration. Execução é **manual**.
- Nenhuma funcionalidade depende de execução automática.

## Nomenclatura

```
<versao>_<AAAAMMDD>_<descricao_curta>.sql
```

Exemplo: `0001_20260907_create_core_tables.sql`

## Cabeçalho obrigatório

Todo arquivo de migration começa com:

```
-- Nome:      ...
-- Versão:    ...
-- Data:      AAAA-MM-DD
-- Descrição: ...
-- Objetivo:  ...
-- Autor:     ...
```

## Como executar (manual)

Aplique em ordem crescente de versão:

```bash
mysql -u USUARIO -p NOME_DO_BANCO < 0001_20260907_create_core_tables.sql
```

Revise o conteúdo antes de executar em qualquer ambiente.
