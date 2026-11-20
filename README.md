# Pulse — Gerenciador Pessoal de Performance

Pulse é um ecossistema de microserviços projetado para centralizar a gestão do dia a dia: **Finanças**, **Estudos**, **Tarefas** e **Dashboard de Performance**.

O projeto foi construído como um ambiente prático de engenharia de software, explorando arquitetura distribuída com **Hyperf 3 (PHP/Swoole)**, **Vue 3**, **RabbitMQ**, **Redis** e **Docker**.

---

## 🚀 Tecnologias e Arquitetura

O projeto utiliza uma abordagem de **Monorepo** com containers isolados e comunicação via eventos assíncronos e chamadas paralelas.

- **Frontend:** Vue 3 (Composition API, TypeScript), Vite, Pinia, Tailwind CSS.
- **Backend:** PHP 8.5+ com [Hyperf 3](https://hyperf.wiki/) (Framework de alta performance rodando sobre Swoole/Swow).
- **Mensageria:** RabbitMQ para eventos entre microserviços.
- **Cache & Contexto:** Redis.
- **Banco de Dados:** MySQL 8 (isolamento por serviço: `bff_db`, `finance_db`, `planner_db`).
- **Orquestração:** Docker & Docker Compose.

### Microserviços
1.  **`bff-gateway`:** Porta de entrada (API Gateway), Autenticação JWT, Composição de Dashboards em paralelo e Projeções de Desempenho.
2.  **`finance-service`:** Gestão financeira estilo Mobills (Contas, Transações, Parcelamentos, Recorrências).
3.  **`planner-service`:** Gestão de estudos (Matérias, Tópicos, Sessões) e Tarefas diárias (Backlog e "Meu Dia").

---

## 🛠️ Como Iniciar

### Pré-requisitos
- Docker e Docker Compose instalados.
- Node.js (opcional, para desenvolvimento frontend fora do container).

### Passo a Passo

1.  **Clone o repositório:**
    ```bash
    git clone https://github.com/seu-usuario/pulse.git
    cd pulse
    ```

2.  **Configure o ambiente:**
    Copie o arquivo de exemplo na raiz e configure se necessário (os padrões já funcionam com o Docker).
    ```bash
    cp .env.example .env
    ```

3.  **Suba os containers:**
    ```bash
    docker compose up -d --build
    ```

4.  **Execute as Migrations:**
    Cada serviço gerencia seu próprio banco de dados. Execute as migrations para todos:
    ```bash
    # BFF Gateway
    docker compose exec bff-gateway php bin/hyperf.php migrate

    # Finance Service
    docker compose exec finance-service php bin/hyperf.php migrate

    # Planner Service
    docker compose exec planner-service php bin/hyperf.php migrate
    ```

---

## 🔗 Portas e Acessos

| Serviço | URL Local | Descrição |
| :--- | :--- | :--- |
| **Frontend** | `http://localhost:5173` | Interface Web (Vue 3) |
| **BFF Gateway** | `http://localhost:9501` | Gateway Principal / Auth |
| **Finance Service** | `http://localhost:9502` | API de Finanças |
| **Planner Service** | `http://localhost:9503` | API de Estudos & Tarefas |
| **RabbitMQ UI** | `http://localhost:15672` | Gestão de Filas (`pulse` / `pulse_secret`) |
| **SonarQube** *(opcional)* | `http://localhost:9000` | Qualidade de código, coverage e PHPStan (`--profile quality`) |

---

## 📖 Regras de Negócio e Convenções

O projeto segue regras rigorosas para garantir a integridade dos dados e performance:

- **Financeiro:** Valores sempre em centavos (`BIGINT`) para evitar erros de arredondamento.
- **Estudos:** Registro honesto de cumprimento (tempo planejado vs. tempo real).
- **Eventos:** Envelope padrão com `event_id` e `correlation_id` para rastreabilidade total.
- **Isolamento:** Um serviço **nunca** acessa o banco de dados de outro. A comunicação é via HTTP (BFF) ou RabbitMQ (Eventos).

Para mais detalhes, consulte a documentação técnica em:
- [Regras de Negócio (docs/business-rules.md)](./docs/business-rules.md)
- [Plano de Implementação (docs/implementation-plan-v1.md)](./docs/implementation-plan-v1.md)
- [Diretrizes de Desenvolvimento (GEMINI.md)](./GEMINI.md)

---

## 🧪 Comandos Úteis (Desenvolvimento & Qualidade)

- **Logs em tempo real (erros e exceptions):**
  ```bash
  docker compose logs -f <nome-do-serviço>
  ```

- **Pipeline completo de qualidade (CS-Fixer + Rector + PHPStan + Testes + Coverage + TS):**
  ```bash
  ./scripts/quality.sh
  ```

- **Executar por serviço (`bff-gateway`, `finance-service` ou `planner-service`):**
  ```bash
  docker compose exec <serviço> composer test            # PHPUnit (co-phpunit)
  docker compose exec <serviço> composer test:coverage   # Testes + Coverage (HTML + Clover XML via PCOV)
  docker compose exec <serviço> composer analyse         # PHPStan nível 8
  docker compose exec <serviço> composer rector:dry      # Rector (simulação)
  docker compose exec <serviço> composer rector          # Rector (aplicar refatorações)
  docker compose exec <serviço> composer cs-fix          # PHP-CS-Fixer (PSR-12)
  docker compose exec <serviço> composer quality         # Todos os checks do serviço
  ```

- **SonarQube (sob demanda com Docker Compose profile):**
  ```bash
  # 1. Subir o servidor SonarQube (http://localhost:9000 — login inicial: admin / admin)
  docker compose --profile quality up -d sonarqube

  # 2. Gerar relatórios de coverage (Clover) e PHPStan (JSON)
  ./scripts/quality.sh

  # 3. Rodar o scanner (informe o token gerado no SonarQube em .env -> SONAR_TOKEN)
  docker compose --profile quality-scan run --rm sonar-scanner
  ```

---

Desenvolvido para fins de aprendizado e performance pessoal. 🚀
