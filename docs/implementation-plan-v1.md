# Plano de Implementação — Pulse v1

Roadmap incremental em 7 fases para construir o **Pulse** (Vue 3 + 3 Microserviços Hyperf 3 + MySQL + RabbitMQ + Redis + Docker), priorizando aprendizado sólido de arquitetura distribuída e entregas funcionais desde o início.

Referência de domínio: [business-rules.md](./business-rules.md).

---

## Fase 0 — Bootstrap Docker & Smoke Test *(Esqueleto pronto)*
**Objetivo:** subir toda a infraestrutura e confirmar comunicação paralela entre os 3 serviços Hyperf 3 e o frontend Vue 3.

- [x] `docker-compose.yml` com `mysql` (3 databases isolados: `bff_db`, `finance_db`, `planner_db`), `redis`, `rabbitmq` (Management UI), `bff-gateway`, `finance-service`, `planner-service` e `frontend`.
- [x] `CorrelationIdMiddleware` com `Hyperf\Context\Context` nos 3 serviços.
- [x] Endpoint `/health` validando conexão MySQL em cada serviço.
- [x] Endpoint inicial `GET /api/dashboard/today` no `bff-gateway` usando `Hyperf\Coroutine\Parallel`.
- [ ] **Verificação (Done):**
  1. Copiar `.env.example` → `.env` na raiz e rodar `docker compose up -d --build`.
  2. Abrir `http://localhost:5173` e ver os 3 cards (`bff-gateway`, `finance-service`, `planner-service`) **Online** com o mesmo `correlation_id`.
  3. Acessar RabbitMQ UI em `http://localhost:15672`.

---

## Fase 1 — Autenticação JWT e Proxy Base no `bff-gateway` (`bff_db`)
**Objetivo:** proteger o sistema com JWT padrão e preparar o gateway para rotear chamadas autenticadas com `X-User-Id` e `X-Correlation-ID`.

### Modelagem (`bff_db`)
- `users`: `id`, `name`, `email (unique)`, `password_hash`, `timezone (default 'America/Sao_Paulo')`, `created_at`, `updated_at`.
- `processed_events`: `event_id (PK, CHAR(36))`, `event_name`, `processed_at`.

### Entregas
- [ ] Migrations + `User` model + `AuthService` (hash `PASSWORD_BCRYPT` / `PASSWORD_ARGON2ID` + geração/validação JWT com `firebase/php-jwt`).
- [ ] Endpoints no `bff-gateway`:
  - `POST /api/auth/register` (opcionalmente travável após criar seu usuário pessoal)
  - `POST /api/auth/login`
  - `GET /api/auth/me`
- [ ] `JwtAuthMiddleware` gravando `user_id` em `Hyperf\Context\Context`.
- [ ] Cliente HTTP interno reutilizável (`InternalServiceClient`) que sempre envia `X-User-Id`, `X-Correlation-ID` e aplica timeout explícito.
- [ ] Tela de Login + Store Pinia (`useAuthStore`) + Navigation Guard no Vue 3.

### Verificação (Done)
- Login gera JWT válido, `GET /api/auth/me` retorna o usuário e rotas protegidas retornam `401` sem token.

---

## Fase 2 — `finance-service` Completo (`finance_db`)
**Objetivo:** entregar o controle financeiro estilo Mobills com precisão monetária em centavos.

### Modelagem (`finance_db`)
- `accounts`: `id`, `user_id`, `name`, `type (checking|wallet|savings|investment)`, `initial_balance_cents (BIGINT)`, `status (active|archived)`, `created_at`, `updated_at`.
- `categories`: `id`, `user_id`, `name`, `type (income|expense)`, `color`, `icon`, `status (active|archived)`, `created_at`, `updated_at`.
- `transactions`: `id`, `user_id`, `account_id`, `destination_account_id (nullable)`, `category_id (nullable)`, `type (income|expense|transfer)`, `description`, `amount_cents (BIGINT UNSIGNED)`, `due_date (DATE)`, `settled_at (TIMESTAMP nullable)`, `status (pending|settled)`, `group_id (CHAR(36) nullable)`, `recurrence_type (none|installment|fixed)`, `installment_number (nullable)`, `installment_total (nullable)`, `created_at`, `updated_at`.

### Endpoints (`finance-service` → expostos via `bff-gateway` em `/api/finance/*`)
- `GET/POST /accounts`, `PATCH /accounts/{id}`, `POST /accounts/{id}/archive`
- `GET/POST /categories`, `PATCH /categories/{id}`, `POST /categories/{id}/archive`
- `GET /transactions?month=YYYY-MM&status=&type=&account_id=`
- `POST /transactions` (suporta lançamento único, transferência, parcelamento e recorrência)
- `PATCH /transactions/{id}` (com `scope: single|this_and_future|all` quando pertencer a `group_id`)
- `POST /transactions/{id}/settle` e `POST /transactions/{id}/unsettle`
- `DELETE /transactions/{id}` (com `scope`)
- `GET /summary?month=YYYY-MM` (Saldo Real por conta, Saldo Previsto do mês, Receitas vs. Despesas efetivadas/pendentes, lista de pendências de hoje e atrasadas)

### Verificação (Done)
- Testes unitários/integração cobrindo: cálculo de Saldo Real vs. Previsto, transferências sem inflar receitas/despesas, atraso dinâmico (`overdue`), geração/edição de parcelas e arquivamento sem quebrar histórico.
- Tela **Finanças** no Vue 3 funcional (cards de saldo, filtros por mês, cadastro de transação e botão de 1 clique para pagar pendência).

---

## Fase 3 — `planner-service` Completo (`planner_db`)
**Objetivo:** entregar **Estudos & Agenda** + **Meu Dia / Tarefas** com histórico honesto de desempenho.

### Modelagem (`planner_db`)
- `subjects`: `id`, `user_id`, `name`, `color`, `weekly_goal_minutes (INT)`, `status (active|archived)`, `created_at`, `updated_at`.
- `topics`: `id`, `user_id`, `subject_id`, `title`, `position (INT)`, `status (todo|in_progress|done|review)`, `reference_url (nullable)`, `notes (TEXT nullable)`, `created_at`, `updated_at`.
- `study_sessions`: `id`, `user_id`, `subject_id`, `topic_id (nullable)`, `scheduled_date (DATE)`, `period (morning|afternoon|evening|any)`, `planned_minutes (INT)`, `actual_minutes (INT nullable)`, `status (planned|completed|partial|missed|rescheduled)`, `notes (TEXT nullable)`, `created_at`, `updated_at`.
- `tasks`: `id`, `user_id`, `title`, `description (TEXT nullable)`, `priority (low|medium|high)`, `planned_date (DATE nullable)`, `original_planned_date (DATE nullable)`, `rescheduled_count (INT default 0)`, `status (pending|in_progress|completed|canceled)`, `completed_at (TIMESTAMP nullable)`, `created_at`, `updated_at`.

### Endpoints (`planner-service` → expostos via `bff-gateway` em `/api/planner/*`)
- **Estudos:**
  - `GET/POST /subjects`, `PATCH /subjects/{id}`, `POST /subjects/{id}/archive`
  - `GET/POST /subjects/{id}/topics`, `PATCH /topics/{id}`, `DELETE /topics/{id}`
  - `GET/POST /study-sessions?from=YYYY-MM-DD&to=YYYY-MM-DD`
  - `POST /study-sessions/{id}/check-in` (`completed|partial|missed|rescheduled`, `actual_minutes`, `notes`, opcionalmente `reschedule_to` e `topic_status`)
- **Meu Dia & Tarefas:**
  - `GET /tasks?view=today|backlog|overdue|week`
  - `POST /tasks`, `PATCH /tasks/{id}`
  - `POST /tasks/{id}/complete`, `POST /tasks/{id}/reopen`, `POST /tasks/{id}/reschedule` (incrementa `rescheduled_count` preservando `original_planned_date`)
- **Resumo do Dia e Semana:**
  - `GET /summary/today?date=YYYY-MM-DD` (tarefas de hoje, pendentes de dias anteriores, sessões de estudo de hoje e progresso da meta semanal)

### Verificação (Done)
- Testes cobrindo: regra da virada do dia (não sobrescrever histórico), reagendamento com `rescheduled_count`, check-in de estudos atualizando minutos reais e status do tópico.
- Telas **Estudos** e **Meu Dia** no Vue 3 funcionais.

---

## Fase 4 — Eventos Assíncronos no RabbitMQ & Projeção de Desempenho
**Objetivo:** aplicar mensageria real (`hyperf/amqp`) com idempotência e DLQ para alimentar métricas e histórico de desempenho no `bff-gateway`.

### Modelagem (`bff_db`)
- `daily_performance_snapshots`: `id`, `user_id`, `date (DATE)`, `tasks_completed_count`, `study_minutes_completed`, `study_sessions_completed`, `transactions_settled_count`, `updated_at` (`UNIQUE(user_id, date)`).
- `activity_feed`: `id`, `user_id`, `event_id (CHAR(36) unique)`, `event_name`, `summary_text`, `occurred_at`.

### Entregas
- [ ] Producers em `finance-service`: `finance.transaction.created`, `finance.transaction.settled`, `finance.transaction.deleted`.
- [ ] Producers em `planner-service`: `planner.task.completed`, `planner.task.reopened`, `planner.study.checked_in`.
- [ ] Consumer idempotente em `bff-gateway` na exchange `pulse.events` (fila `bff.daily-performance.queue` + DLQ `bff.daily-performance.dlq`) usando tabela `processed_events`.
- [ ] Endpoint `GET /api/dashboard/performance?days=30` lendo direto da projeção do `bff_db` (rápido e desacoplado).

### Verificação (Done)
- Completar tarefa ou efetivar transação → mensagem aparece no RabbitMQ → `daily_performance_snapshots` e `activity_feed` atualizados no `bff_db`.
- Republicar manualmente o mesmo `event_id` no RabbitMQ → ignorado sem duplicar contadores.

---

## Fase 5 — Dashboard "Meu Dia" Completo (API Composition + Vue 3)
**Objetivo:** transformar o Dashboard na tela principal de execução e acompanhamento diário.

### Entregas
- [ ] Evoluir `GET /api/dashboard/today` no `bff-gateway` para agregar em paralelo (`Hyperf\Coroutine\Parallel`):
  1. `finance-service` (`GET /summary`) — saldo real, saldo previsto e pendências de hoje/atrasadas.
  2. `planner-service` (`GET /summary/today`) — tarefas de hoje, pendentes anteriores, estudos de hoje e meta semanal.
  3. `bff_db` — score diário, streak e histórico dos últimos 7/30 dias.
- [ ] Tratar falha isolada de cada serviço (degradação graciosa exibindo aviso no card específico).
- [ ] Interface Vue 3 completa no Dashboard:
  - Ações rápidas de 1 clique (pagar conta, concluir tarefa, fazer check-in de estudo).
  - Gráfico semanal de horas de estudo vs. meta e consistência de tarefas (*heatmap* / barras).

---

## Fase 6 — Qualidade, Observabilidade e Hardening
**Objetivo:** deixar o projeto com padrão profissional próximo ao ambiente corporativo.

- [ ] PHPStan nível 5+ e PHP-CS-Fixer rodando sem erros nos 3 serviços.
- [ ] Suite de testes automatizados (`composer test`) para regras críticas de cada serviço.
- [ ] Logs estruturados com `correlation_id` e `user_id` em todos os serviços.
- [ ] Script de seed de desenvolvimento (`php bin/hyperf.php db:seed`) para popular contas, categorias, matérias e tarefas de exemplo.
- [ ] `README.md` na raiz explicando como subir e testar o Pulse em 2 minutos.
