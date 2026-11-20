# Pulse — Gerenciador Pessoal de Performance

Pulse é um ecossistema de microserviços projetado para centralizar a gestão do dia a dia: **Finanças**, **Estudos**, **Tarefas** e **Dashboard de Performance**.

O projeto foi construído como um ambiente prático de engenharia de software, explorando arquitetura distribuída com **Hyperf 3 (PHP/Swoole)**, **Vue 3**, **RabbitMQ**, **Redis** e **Docker**.

---

## 🚀 Tecnologias e Arquitetura

O projeto utiliza uma abordagem de **Monorepo** com containers isolados e comunicação via eventos assíncronos e chamadas paralelas.

- **Frontend:** Vue 3 (Composition API, TypeScript), Vite, Pinia, Tailwind CSS.
- **Backend:** PHP 8.2+ com [Hyperf 3](https://hyperf.wiki/) (Framework de alta performance rodando sobre Swoole/Swow).
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
| **RabbitMQ UI** | `http://localhost:15672` | Gestão de Filas (guest/guest) |

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

## 🧪 Comandos Úteis (Desenvolvimento)

- **Logs em tempo real:**
  ```bash
  docker compose logs -f <nome-do-serviço>
  ```

- **Executar Testes:**
  ```bash
  docker compose exec <serviço> composer test
  ```

- **Análise Estática (PHPStan):**
  ```bash
  docker compose exec <serviço> composer analyse
  ```

---

Desenvolvido para fins de aprendizado e performance pessoal. 🚀
