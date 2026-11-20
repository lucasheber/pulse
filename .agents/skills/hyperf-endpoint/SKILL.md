---
name: hyperf-endpoint
description: >-
  Use esta skill ao criar ou alterar endpoints, recursos CRUD, migrations ou casos
  de uso nos microserviços Hyperf 3 do projeto Pulse (bff-gateway, finance-service
  ou planner-service).
---

# Criar Endpoint / Feature no Hyperf 3 (Pulse)

Siga este fluxo sempre que implementar um novo recurso ou endpoint em qualquer serviço em `services/<service>/`.

## 1. Validar Fronteira de Domínio e Regras de Negócio
1. Consulte [docs/business-rules.md](../../../docs/business-rules.md) para confirmar em qual microserviço a regra pertence (`finance-service`, `planner-service` ou `bff-gateway`).
2. Garanta isolamento total de banco (**Database per Service**): nunca acesse tabelas de outro database.
3. Confirme se a operação deve publicar um evento no RabbitMQ (se sim, combine com a skill `rabbitmq-event`).

## 2. Estrutura de Camadas Obrigatória
Crie os arquivos no serviço alvo mantendo `declare(strict_types=1);` em todos os arquivos PHP:

```text
services/<service>/
├── migrations/                  # Schema MySQL (UTF8MB4, BIGINT para centavos, DATE/TIMESTAMP UTC)
└── app/
    ├── Controller/<Domain>/     # PHP 8 Attributes (#[Controller], #[GetMapping], #[PostMapping]...)
    ├── Request/<Domain>/        # Hyperf\Validation\Request\FormRequest para validação de entrada
    ├── Service/<Domain>/        # Regras de negócio, transações DB e disparo de eventos
    ├── Repository/<Domain>/     # Consultas encapsuladas ao Model/QueryBuilder
    └── Model/                   # Models Hyperf\DbConnection\Model (casts explícitos)
```

## 3. Checklist de Segurança de Corrotinas (Swoole / Hyperf 3)
Antes de concluir o código, verifique cada item:
- [ ] **Zero estado mutável em singletons:** controllers, services e repositories injetados pelo DI são reutilizados entre requisições — nunca guarde `$this->userId`, `$this->request` ou caches mutáveis de request em propriedades da classe.
- [ ] **Contexto por corrotina:** leia `correlation_id` e `user_id` via `Hyperf\Context\Context::get(...)`.
- [ ] **Corrotinas filhas (`Parallel` / `Coroutine::create`):** capture valores do `Context` em variáveis locais *antes* de abrir a corrotina filha.
- [ ] **Dinheiro em centavos (`finance-service`):** campos monetários usam `BIGINT` no MySQL e `int` no PHP (`*_cents`). Jamais use `float`.
- [ ] **Soft delete / arquivamento:** entidades com histórico (`accounts`, `categories`, `subjects`) usam `status = 'archived'` ou `deleted_at` em vez de `DELETE` físico.
- [ ] **Header `X-Correlation-ID`:** propagado em qualquer chamada HTTP interna via `Hyperf\Guzzle\ClientFactory` com `timeout` explícito (ex.: `2.5`–`5.0`s).

## 4. Rota no BFF Gateway (se exposto ao Frontend)
Se o endpoint for consumido pelo Vue 3:
1. Exponha o proxy ou composição no `services/bff-gateway` sob `/api/<module>/...`.
2. Valide o JWT no middleware do BFF e encaminhe `X-User-Id` e `X-Correlation-ID` ao serviço interno.
3. Em endpoints de composição (ex.: Dashboard), use `Hyperf\Coroutine\Parallel` com tratamento de erro por ramo (degradação graciosa).

## 5. Validação e Testes
Execute no container Docker do serviço alterado:
```bash
docker compose exec <service> php bin/hyperf.php migrate
docker compose exec <service> composer analyse
docker compose exec <service> composer test
```
Confirme também o comportamento com uma requisição real (ex.: `curl` ou cliente HTTP) verificando o status HTTP, o corpo JSON e o header `X-Correlation-ID` na resposta.
