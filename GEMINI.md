# Diretrizes do Projeto — Pulse

Este arquivo define o contexto arquitetural, as regras técnicas e as convenções que o agente de IA deve seguir sempre que trabalhar neste repositório.

---

## 1. Visão Geral do Projeto
- **Nome:** Pulse
- **Propósito:** Aplicativo pessoal de gerenciamento diário (Finanças estilo Mobills, Estudos & Agenda, Meu Dia/Tarefas e Dashboard de Desempenho).
- **Objetivo técnico:** Servir como ambiente prático de engenharia com **Microserviços em Hyperf 3 (PHP + Swoole)**, **Vue 3**, **MySQL**, **RabbitMQ**, **Redis** e **Docker**.
- **Regras de negócio detalhadas:** Consulte [docs/business-rules.md](./docs/business-rules.md) antes de modelar tabelas, endpoints ou eventos.

---

## 2. Arquitetura e Stack (Monorepo + Docker)
- **Frontend:** Vue 3 (Composition API + `<script setup lang="ts">`), TypeScript, Vite, Pinia, Vue Router, Tailwind CSS.
- **Backend (Microserviços):** PHP 8.2+ com **Hyperf 3** (Swoole), `hyperf/database`, `hyperf/amqp`, `hyperf/redis`, `hyperf/guzzle`.
- **Banco de Dados:** MySQL 8 (1 container em dev, **databases isolados por serviço**).
- **Mensageria & Cache:** RabbitMQ (eventos assíncronos entre serviços) e Redis (cache/sessão).
- **Orquestração local:** Docker + Docker Compose.

### Estrutura do Monorepo
```text
.
├── docker/                     # Dockerfiles, configs de init do MySQL, etc.
├── docs/                       # Regras de negócio, ADRs e contratos de API/eventos
│   └── business-rules.md
├── frontend/                   # App Vue 3 (Vite + TS)
├── services/
│   ├── bff-gateway/            # Hyperf 3 — Auth (JWT), Gateway, Dashboard (Parallel) e Projeções
│   ├── finance-service/        # Hyperf 3 — Contas, Categorias, Transações, Pendências (finance_db)
│   └── planner-service/        # Hyperf 3 — Estudos, Agenda, Meu Dia/Tarefas, Desempenho (planner_db)
├── docker-compose.yml
└── GEMINI.md
```

---

## 3. Regras Inegociáveis de Microserviços e Hyperf 3

### Isolamento de Dados (*Database per Service*)
- Cada microserviço acessa **exclusivamente** o seu próprio database (`bff_db`, `finance_db`, `planner_db`).
- **Nunca** faça `JOIN` ou consulta SQL direta no banco de outro serviço.
- A composição de telas que cruzam domínios (ex.: Dashboard "Meu Dia") acontece no `bff-gateway` via chamadas paralelas (`Hyperf\Coroutine\Parallel`) ou via projeções atualizadas por eventos do RabbitMQ.

### Segurança de Corrotinas (Swoole / Hyperf 3)
- O Hyperf roda em processo *long-running* com corrotinas: **nunca** armazene dados específicos de uma requisição (usuário autenticado, correlation ID, estado temporário) em propriedades mutáveis de classes injetadas (singletons) ou propriedades `static`.
- Use `Hyperf\Context\Context` para qualquer estado com escopo de requisição/corrotina.
- Evite chamadas bloqueantes que ignorem o hook do Swoole.

### Comunicação entre Serviços
- **Síncrona (HTTP):** defina timeouts curtos explícitos, propague o header `X-Correlation-ID` em todas as requisições e logs, e trate falhas parciais no `bff-gateway` (degradação graciosa: se um serviço falhar, o Dashboard deve indicar a indisponibilidade parcial sem quebrar a tela inteira).
- **Assíncrona (RabbitMQ / `hyperf/amqp`):**
  - Todo evento deve seguir envelope padrão contendo `event_id` (UUID), `event_name`, `occurred_at` (ISO-8601 UTC), `correlation_id` e `payload`.
  - Consumidores devem ser **idempotentes** (tratar entrega duplicada *at-least-once* sem corromper dados).
  - Configurar Dead Letter Queue (DLQ) para mensagens com falha recorrente.

### Integridade de Domínio
- **Valores monetários:** sempre armazenados em **centavos (`BIGINT`)** no banco e manipulados como `int` no PHP/TypeScript. Nunca usar `FLOAT`/`DOUBLE` para dinheiro.
- **Datas e horários:** armazenar timestamps em **UTC** no banco e converter para o fuso do usuário no frontend; campos puramente de calendário (ex.: data de vencimento, dia planejado) usam tipo `DATE` (`YYYY-MM-DD`).
- **Exclusões:** usar **soft delete / arquivamento** em entidades referenciadas por histórico (contas, categorias, matérias).

---

## 4. Padrões de Código e Convenções
- **Idioma:** respostas, explicações e documentação em **Português (PT-BR)**. Código (classes, variáveis, tabelas, rotas, eventos e commits) em **Inglês**.
- **PHP / Hyperf 3:**
  - `declare(strict_types=1);` obrigatório em todos os arquivos PHP.
  - Seguir PSR-12, tipagem estrita em parâmetros/retornos, PHP 8 Attributes (`#[Controller]`, `#[Inject]`, etc.) e DTOs/FormRequests para validação.
  - Separar camadas com clareza: `Controller` → `Service` / `UseCase` → `Repository` / `Model`.
- **Vue 3:**
  - Usar sempre Composition API com `<script setup lang="ts">`.
  - Organizar por módulo/domínio (`src/modules/finance`, `src/modules/planner`, `src/modules/dashboard`).
- **Qualidade:** funções pequenas, sem duplicação desnecessária. Sempre rodar testes e linters do serviço alterado antes de concluir uma tarefa.

---

## 5. Comandos Principais (Docker)
- **Preparar `.env` na raiz:** `cp .env.example .env`
- **Subir ambiente completo:** `docker compose up -d --build`
- **Portas locais:**
  - Frontend (Vue 3): `http://localhost:5173`
  - BFF Gateway: `http://localhost:9501`
  - Finance Service: `http://localhost:9502`
  - Planner Service: `http://localhost:9503`
  - RabbitMQ Management UI: `http://localhost:15672`
- **Logs de um serviço:** `docker compose logs -f <service>`
- **Executar comandos no serviço Hyperf:** `docker compose exec <service> php bin/hyperf.php <comando>`
- **Migrations:** `docker compose exec <service> php bin/hyperf.php migrate`
- **Testes PHP:** `docker compose exec <service> composer test`
- **Análise estática PHP:** `docker compose exec <service> composer analyse`
- **Frontend typecheck/build:** `docker compose exec frontend npm run typecheck`

---

## 6. Restrições
- Nunca commitar `.env`, senhas reais ou chaves JWT/API (manter apenas `.env.example`).
- Não fazer refatorações fora do escopo solicitado sem alinhar antes.
- Quando houver ambiguidade de regra de negócio ou decisão arquitetural, perguntar antes de implementar.
- Ao introduzir padrões novos de Hyperf 3 / Microserviços, explicar brevemente o *porquê* da decisão (contexto de aprendizado prático).
