---
name: conventional-commits
description: >-
  Use esta skill sempre que criar, redigir, sugerir ou revisar mensagens de commit
  Git no projeto Pulse. Garante o padrão Conventional Commits em inglês com corpo
  detalhado descrevendo tudo o que foi feito.
---

# Padrão de Commits — Conventional Commits (Pulse)

Todo commit neste repositório deve seguir a especificação **Conventional Commits 1.0.0**, escrito inteiramente em **inglês**, com **descrição detalhada no corpo (body)** explicando o que foi feito e o motivo das alterações.

---

## 1. Estrutura Obrigatória da Mensagem

```text
<type>(<scope>): <short summary in imperative English>

<detailed body explaining what changed and why, using clear bullet points>

<optional footer: BREAKING CHANGE / Refs>
```

### Regras do Cabeçalho (Header)
- **Idioma:** estritamente em **inglês**.
- **Modo verbal:** imperativo no presente (`add`, `fix`, `update`, `upgrade`, `refactor`, `remove`) — nunca no passado (`added`, `fixed`).
- **Capitalização e pontuação:** iniciar o resumo com letra minúscula e **não** terminar com ponto final (`.`).
- **Tamanho:** idealmente até **50 caracteres** (máximo **72 caracteres**).
- **Escopo (`<scope>`):** opcional, mas recomendado quando a mudança é concentrada em um módulo ou camada específica.

### Regras do Corpo (Body) — Obrigatório
- Separe o cabeçalho do corpo com **exatamente uma linha em branco**.
- Escrito inteiramente em **inglês**.
- **Nunca faça commits apenas com título** (exceto se o usuário pedir explicitamente).
- Descreva de forma clara e detalhada:
  - **O que foi feito:** principais mudanças técnicas, arquivos/componentes adicionados ou alterados, dependências atualizadas, novos endpoints/eventos/migrations.
  - **Por que foi feito:** contexto da decisão técnica, bug corrigido ou regra de negócio atendida.
- Prefira **bullet points (`- ...`)** agrupados de maneira lógica, quebrando linhas em ~72–100 caracteres para boa leitura no `git log`.

### Rodapé (Footer) — Opcional
- Para mudanças incompatíveis, adicione `!` após o tipo/escopo (ex.: `feat(finance)!: ...`) e inclua `BREAKING CHANGE: <description>` no rodapé.
- Referências a issues/PRs: `Refs: #123` ou `Closes: #123`.

---

## 2. Tipos Permitidos (`<type>`)

| Type | Quando usar |
| :--- | :--- |
| `feat` | Nova funcionalidade visível ou novo recurso/endpoint/evento |
| `fix` | Correção de bug ou erro de comportamento |
| `refactor` | Reestruturação interna de código sem alterar comportamento externo |
| `perf` | Melhoria de performance (queries, corrotinas, cache, bundle) |
| `docs` | Alterações apenas em documentação (`README.md`, `docs/`, `GEMINI.md`) |
| `test` | Adição ou ajuste de testes automatizados |
| `build` | Alterações no sistema de build, Dockerfiles ou dependências (`composer.json`, `package.json`) |
| `ci` | Configuração de pipelines de CI/CD |
| `chore` | Tarefas de manutenção, configs de tooling, linters, skills/regras de agente |
| `style` | Formatação e estilo de código (PSR-12, Prettier) sem alteração lógica |
| `revert` | Reversão de um commit anterior |

---

## 3. Escopos Recomendados no Monorepo Pulse (`<scope>`)

- `bff` — `services/bff-gateway`
- `finance` — `services/finance-service`
- `planner` — `services/planner-service`
- `frontend` — `frontend/`
- `docker` — `docker/` e `docker-compose.yml`
- `deps` — atualização de dependências Composer/NPM
- `docs` — `docs/`, `README.md`, `GEMINI.md`
- `agents` — `.agents/skills/` e regras/customizações de agente
- *(omitir escopo)* — quando o commit atravessar múltiplos serviços ou a infraestrutura global do monorepo

---

## 4. Fluxo Antes de Executar o Commit

1. **Inspecione as alterações:** execute `git status` e `git diff --staged` (ou `git diff`) para garantir que o corpo do commit reflita fielmente tudo o que está sendo commitado.
2. **Segurança:** confirme que nenhum arquivo sensível (`.env`, segredos JWT, credenciais reais) está incluído no staging.
3. **Atomicidade:** se houver mudanças não relacionadas, sugira ou organize commits separados por assunto.
4. **Execute o commit** preservando a linha em branco e as quebras de linha do corpo (ex.: `git commit -m "<header>" -m "<body>"` ou via heredoc `git commit -F - <<'EOF'`).
5. **Valide o resultado:** rode `git log -1` para confirmar a formatação final da mensagem.

---

## 5. Exemplo de Commit Esperado

```text
build(docker): upgrade all PHP microservices to PHP 8.5 and Hyperf 3.2

Migrate bff-gateway, finance-service, and planner-service from PHP 8.3
(Hyperf 3.1) to PHP 8.5 with Swoole 6.2 and Hyperf 3.2.

- Replace hyperf/hyperf:8.3 base image in docker/php/Dockerfile with
  phpswoole/swoole:6.2-php8.5-alpine, adding bcmath and pcntl extensions
  and configuring swoole.use_shortname=Off, memory_limit=512M, and UTC timezone
- Update composer.json across all three PHP services to require php >=8.5,
  ext-swoole >=6.2, hyperf/* ~3.2.0, phpstan/phpstan ^2.1, and
  swoole/ide-helper ^6.2
- Upgrade firebase/php-jwt to ^7.0 in bff-gateway to resolve Composer
  security advisory PKSA-y2cr-5h3j-g3ys
- Regenerate composer.lock files for all PHP services and update PHP version
  references in README.md and GEMINI.md
```
