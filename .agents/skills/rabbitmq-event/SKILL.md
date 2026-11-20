---
name: rabbitmq-event
description: >-
  Use esta skill ao criar produtores (Producers) ou consumidores (Consumers)
  de eventos assíncronos no RabbitMQ com hyperf/amqp nos microserviços do Pulse.
---

# Publicar e Consumir Eventos no RabbitMQ (Pulse)

Este fluxo padroniza toda comunicação assíncrona via `hyperf/amqp` nos serviços Hyperf 3 do Pulse.

## 1. Convenção de Nomes e Topologia
- **Exchange principal:** `pulse.events` (tipo `topic`, durável).
- **Routing key:** `<service>.<entity>.<action>` (ex.: `finance.transaction.settled`, `planner.study.checked_in`, `planner.task.completed`).
- **Fila do consumidor:** `<consumer-service>.<purpose>.queue` (ex.: `bff.daily-performance.queue`).
- **Dead Letter Exchange / Queue:** `pulse.events.dlx` / `<queue>.dlq`.

## 2. Envelope JSON Obrigatório
Todo evento publicado deve seguir exatamente esta estrutura:

```json
{
  "event_id": "550e8400-e29b-41d4-a716-446655440000",
  "event_name": "planner.study.checked_in",
  "occurred_at": "2026-11-20T16:45:00Z",
  "correlation_id": "req-uuid-here",
  "user_id": 1,
  "payload": {}
}
```

## 3. Criando um Producer (Serviço Publicador)
1. Crie a classe em `services/<service>/app/Amqp/Producer/<EventName>Producer.php` estendendo `Hyperf\Amqp\Message\ProducerMessage`.
2. Use o atributo `#[Producer(exchange: 'pulse.events', routingKey: '<routing.key>')]`.
3. Monte o envelope com `Uuid::uuid4()->toString()`, `occurred_at` em UTC (`DATE_ATOM`) e `correlation_id` capturado do `Hyperf\Context\Context`.
4. Publique **somente após o commit** da transação no banco de dados (evita publicar eventos de operações que sofreram rollback).

## 4. Criando um Consumer Idempotente (Serviço Consumidor)
1. Garanta que o serviço consumidor possui a tabela `processed_events` (`event_id CHAR(36) PRIMARY KEY`, `event_name VARCHAR(120)`, `processed_at TIMESTAMP`).
2. Crie a classe em `services/<service>/app/Amqp/Consumer/<Purpose>Consumer.php` estendendo `Hyperf\Amqp\Message\ConsumerMessage`.
3. Use `#[Consumer(exchange: 'pulse.events', routingKey: '<pattern>', queue: '<queue>', nums: 1)]`.
4. No método `consumeMessage($data, AMQPMessage $message)`:
   - Valide a presença de `event_id`, `event_name` e `payload`.
   - Dentro de `Db::transaction(...)`:
     1. Tente inserir `event_id` em `processed_events` (ou verifique existência com lock/unique key).
     2. Se `event_id` já existir, **ignore silenciosamente** e retorne `Result::ACK` (idempotência para entrega *at-least-once*).
     3. Execute a atualização da projeção/regra local.
   - Em erro permanente de payload inválido, registre log com `correlation_id` e retorne `Result::DROP` (encaminhando para DLQ quando configurada).
   - Em erro transitório (ex.: queda momentânea de DB), retorne `Result::REQUEUE` com cuidado ou delegue para retry/DLQ.

## 5. Verificação Prática
1. Reinicie/verifique o serviço consumidor nos logs:
   ```bash
   docker compose logs -f <consumer-service>
   ```
2. Acesse o painel do RabbitMQ em `http://localhost:15672` (credenciais do `.env`) e confirme:
   - Exchange `pulse.events` criada e vinculada à fila do consumidor.
   - Mensagem publicada e consumida (`Ready: 0`, `Unacked: 0`).
3. Teste a **idempotência** republicando manualmente a mesma mensagem (mesmo `event_id`) pelo painel do RabbitMQ e confirme que o banco do consumidor **não** duplicou contadores ou registros.
