# Regras de Negócio — Pulse (v1)

Documento de referência das regras de negócio por microserviço, contratos de eventos e escopo da v1 vs. v2.

---

## 1. `finance-service` (`finance_db`)

Responsável por todo o domínio financeiro pessoal (inspirado no Mobills).

### Entidades Principais
- **`Account` (Conta):** nome, tipo (`checking`, `wallet`, `savings`, `investment`), saldo inicial em centavos, status (`active`, `archived`).
- **`Category` (Categoria):** nome, tipo (`income`, `expense`), cor/ícone, status (`active`, `archived`).
- **`Transaction` (Transação):** tipo (`income`, `expense`, `transfer`), descrição, valor em centavos (`amount_cents > 0`), data de vencimento/competência (`due_date`), data de efetivação (`settled_at`, nullable), status (`pending`, `settled`), `account_id`, `destination_account_id` (apenas para `transfer`), `category_id` (null em transferências), metadados de grupo para parcelamento/recorrência.

### Regras de Negócio (v1)
1. **Dinheiro em Centavos:** todos os valores são inteiros em centavos (`amount_cents`). Nunca usar ponto flutuante.
2. **Saldo Real vs. Saldo Previsto:**
   - **Saldo Real da conta** = `initial_balance_cents` + soma(`income` efetivadas) − soma(`expense` efetivadas) + transferências recebidas efetivadas − transferências enviadas efetivadas.
   - **Saldo Previsto (no mês/período)** = Saldo Real + soma(`income` pendentes até o fim do período) − soma(`expense` pendentes até o fim do período).
3. **Pendências e Atraso Dinâmico:**
   - Uma transação nasce como `pending` ou `settled`.
   - O indicador **`overdue` (atrasada)** é derivado em tempo de consulta: `status === 'pending' && due_date < today` (não é salvo como status separado para evitar jobs de atualização desnecessários).
   - Ao marcar uma pendência como paga/recebida (`settle`), preenche-se `settled_at` (padrão: agora, editável).
4. **Transferências entre Contas:**
   - Exige `account_id !== destination_account_id`.
   - Afeta o saldo das duas contas, mas **não entra** nos totais de Receitas nem Despesas dos relatórios mensais.
5. **Parcelamentos e Recorrências:**
   - **Parcelamento:** gera $N$ transações vinculadas pelo mesmo `group_id` com `installment_number` (`1..N`) e `installment_total` (`N`), vencendo mês a mês. Edição/exclusão aceita escopo: `single`, `this_and_future` ou `all`.
   - **Recorrência fixa:** gera transações vinculadas com opção de encerrar ou alterar valor a partir de um mês sem alterar lançamentos passados já efetivados.
6. **Proteção de Histórico:**
   - Contas e categorias com transações vinculadas não são removidas fisicamente; são marcadas como `archived` (deixam de aparecer para novos lançamentos, mas continuam visíveis no histórico).

### Eventos Publicados (RabbitMQ)
- `finance.transaction.created` — quando uma transação é criada.
- `finance.transaction.settled` — quando uma transação pendente é paga/recebida (ou criada já efetivada).
- `finance.transaction.deleted` — quando uma transação é removida.

---

## 2. `planner-service` (`planner_db`)

Responsável pelo domínio de **Estudos & Agenda**, **Meu Dia / Tarefas** e **Métricas de Desempenho**.

### Subdomínio A — Estudos & Agenda

#### Entidades Principais
- **`Subject` (Matéria / Trilha):** nome (ex.: Hyperf 3, Microserviços, Inglês), cor, meta semanal em minutos (`weekly_goal_minutes`), status (`active`, `archived`).
- **`Topic` (Tópico / Backlog):** vinculado a `subject_id`, título, ordem/prioridade, status (`todo`, `in_progress`, `done`, `review`), notas/links de referência.
- **`StudySession` (Sessão Agendada):** `subject_id`, `topic_id` (opcional), `scheduled_date` (`DATE`), turno ou horário opcional, `planned_minutes`, `actual_minutes` (preenchido no check-in), status (`planned`, `completed`, `partial`, `missed`, `rescheduled`), nota de continuidade (`notes` — "onde parei / próximo passo").

#### Regras de Negócio (v1)
1. **Backlog do "O que estudar":** cada matéria mantém sua fila priorizada de tópicos com progresso visível (`tópicos concluídos / total`).
2. **Check-in de Cumprimento:**
   - Ao marcar sessão como `completed` ou `partial`, registra `actual_minutes` e permite salvar a nota de onde parou.
   - Se a sessão estiver vinculada a um `Topic`, permite atualizar o status do tópico (`in_progress` ou `done`) no mesmo fluxo.
   - Ao marcar `missed` ou `rescheduled`, a sessão original mantém o registro histórico e oferece criação rápida de uma nova sessão na data escolhida (evitando perder a métrica real de cumprimento).
3. **Meta Semanal e Consistência:**
   - Horas estudadas na semana = soma de `actual_minutes` das sessões `completed` e `partial` da semana.
   - Taxa de cumprimento = `(sessões completed + 0.5 * sessões partial) / sessões planejadas` no período (ou exibição separada de `completed` e `partial`).

### Subdomínio B — Meu Dia & Tarefas

#### Entidades Principais
- **`Task` (Tarefa):** título, descrição opcional, prioridade (`low`, `medium`, `high`), `planned_date` (`DATE`, nullable — `null` significa que está no **Backlog**), status (`pending`, `in_progress`, `completed`, `canceled`), `completed_at` (nullable).

#### Regras de Negócio (v1)
1. **Backlog vs. Meu Dia:**
   - Tarefas com `planned_date = null` ficam no **Backlog**.
   - Tarefas com `planned_date = hoje` aparecem em **Meu Dia**.
2. **Regra da Virada do Dia (Preservação de Desempenho):**
   - Tarefas com `planned_date < hoje` e `status` em (`pending`, `in_progress`) **não** têm sua data sobrescrita automaticamente.
   - Elas aparecem na seção **"Pendentes anteriores"** com ações explícitas:
     - **Trazer para hoje:** registra o reagendamento (`rescheduled_count++`, mantém `original_planned_date` para métricas honestas) e atualiza `planned_date = hoje`.
     - **Reagendar para outra data:** mesmo comportamento com a data escolhida.
     - **Cancelar:** muda status para `canceled`.
3. **Integração na Visão "Meu Dia":**
   - A tela "Meu Dia" lista tanto as `Tasks` de hoje quanto as `StudySessions` agendadas para hoje, em uma visão unificada de execução diária.

### Eventos Publicados (RabbitMQ)
- `planner.task.completed` — quando uma tarefa é concluída.
- `planner.task.reopened` — quando uma tarefa concluída volta para pendente.
- `planner.study.checked_in` — quando uma sessão de estudo recebe check-in (`completed`, `partial`, `missed`).

---

## 3. `bff-gateway` (`bff_db`)

Porta de entrada única do frontend Vue 3.

### Responsabilidades (v1)
1. **Autenticação:** cadastro/login do usuário (single-user / uso pessoal, mas estruturado com JWT padrão para boas práticas) e validação do token antes de encaminhar chamadas aos microserviços.
2. **Roteamento / Proxy:** encaminha rotas `/api/finance/*` para `finance-service` e `/api/planner/*` para `planner-service`, injetando `X-User-Id` e `X-Correlation-ID`.
3. **Dashboard "Meu Dia" & Desempenho (API Composition + Projeção):**
   - **Composição em paralelo (`Hyperf\Coroutine\Parallel`):** consulta simultaneamente resumo financeiro do mês + pendências de hoje/atrasadas (`finance-service`) e tarefas/estudos de hoje + métricas da semana (`planner-service`).
   - **Projeção Assíncrona (RabbitMQ):** consome os eventos publicados por `finance-service` e `planner-service` para manter uma tabela de **snapshots/atividade diária (`daily_activity_log` / `daily_performance`)** no `bff_db` — permitindo gráficos de desempenho e *heatmap* rápido sem sobrecarregar os serviços de domínio.
   - **Degradação Graciosa:** se um microserviço estiver fora do ar, o endpoint do Dashboard retorna os dados do serviço saudável + indicador de erro parcial no bloco indisponível.

### Cálculo do Desempenho Diário (v1)
- **Taxa de Tarefas do Dia:** `tarefas concluídas / tarefas planejadas para o dia`.
- **Taxa de Estudos do Dia:** `minutos estudados / minutos planejados para o dia`.
- **Score Consolidado do Dia (0–100%):** média apenas das frentes que tinham itens planejados naquele dia (ex.: se não havia estudo agendado no domingo, o score considera apenas as tarefas planejadas).

---

## 4. Contrato Padrão de Eventos (RabbitMQ)

- **Exchange:** `pulse.events` (tipo `topic`, durável).
- **Routing keys:** `<service>.<entity>.<action>` (ex.: `finance.transaction.settled`, `planner.study.checked_in`).
- **Envelope JSON:**
```json
{
  "event_id": "550e8400-e29b-41d4-a716-446655440000",
  "event_name": "planner.study.checked_in",
  "occurred_at": "2026-11-20T16:45:00Z",
  "correlation_id": "req-123456",
  "user_id": 1,
  "payload": {}
}
```
- **Idempotência:** consumidores registram `event_id` processados (`processed_events`) para ignorar reentregas duplicadas.

---

## 5. Backlog Futuro (Fora da v1 — Planejado para v2)
- Cartão de Crédito com limite, dia de fechamento e gestão de faturas (`finance-service`).
- Tarefas recorrentes e rotinas/hábitos automáticos (`planner-service`).
- Revisão espaçada automática de estudos (+1d, +7d, +30d) (`planner-service`).
- Notificações/lembretes (e-mail/Telegram/push) consumindo eventos do RabbitMQ.
