# AssetMonitoringSystem

AssetMonitoringSystem is a production-oriented Laravel 11 microservices system for enterprise IT asset management. It uses a six-service architecture, RabbitMQ topic messaging, PostgreSQL per service, Docker multi-stage builds, an Nginx API gateway, and Terraform for AWS provisioning.

## 📋 Table of Contents

- [🏗️ Architecture](#architecture)
- [🧩 System Design](#system-design)
- [🧠 Key Architecture Principles](#key-architecture-principles)
- [📦 Microservices Overview](#microservices-overview)
- [🔗 Service Dependencies Map](#service-dependencies-map)
- [🎯 Prerequisites](#prerequisites)
- [🗂️ Repository Layout](#repository-layout)
- [🐳 Local Run With Docker](#local-run-with-docker)
- [🔐 Seeded Credentials](#seeded-credentials)
- [🧪 Sample API Calls](#sample-api-calls)
- [📨 Message Flow](#message-flow)
- [⚡ Circuit Breaker](#circuit-breaker)
- [✅ Testing](#testing)
- [📘 Swagger / OpenAPI](#swagger--openapi)
- [🔭 Telescope](#telescope)
- [☁️ Terraform](#terraform)
- [🛠️ Operational Notes](#operational-notes)
- [☸️ Kubernetes](#kubernetes)

## 🏗️ Architecture

- API gateway: Nginx
- Services: 6 Laravel microservices
- Async messaging: RabbitMQ topic exchange
- Database strategy: one PostgreSQL database per service
- Dev observability: Telescope, Swagger UI, Mailpit
- Infra-as-code: Terraform for AWS-oriented provisioning

```text
┌─────────────────────────────────────────────────────────────────────────────┐
│                           Nginx API Gateway :8080                           │
└──────────────┬────────────────┬────────────────┬───────────────┬────────────┘
               │                │                │               │
        ┌──────▼──────┐  ┌──────▼──────┐  ┌──────▼──────┐  ┌────▼────────┐
        │  Identity   │  │  Inventory  │  │ Assignment  │  │   Health    │
        │  Service    │  │   Service   │  │   Service   │  │  Monitor    │
        │   :8000     │  │    :8000    │  │    :8000    │  │   :8000     │
        └──────┬──────┘  └──────┬──────┘  └──────┬──────┘  └────┬────────┘
               │                │                │               │
        ┌──────▼──────┐  ┌──────▼──────┐  ┌──────▼──────┐  ┌────▼────────┐
        │ Identity DB │  │Inventory DB │  │Assignment DB│  │ Health DB   │
        │ PostgreSQL  │  │ PostgreSQL  │  │ PostgreSQL  │  │ PostgreSQL  │
        └─────────────┘  └─────────────┘  └─────────────┘  └──────────────┘

                                ┌───────────────────────────┐
                                │ RabbitMQ Topic Exchange   │
                                │ asset_monitoring_system   │
                                └───────────┬───────────────┘
                                            │
                 ┌──────────────────────────┼──────────────────────────┐
                 │                          │                          │
         ┌───────▼────────┐        ┌────────▼───────┐         ┌────────▼────────┐
         │ Audit Service  │        │ Notification   │         │ Assignment User │
         │ + Consumer     │        │ Service        │         │ Sync Worker     │
         │ + Audit DB     │        │ + Consumer     │         │                 │
         └───────┬────────┘        │ + Notify DB    │         └─────────────────┘
                 │                 └────────┬───────┘
                 │                          │
         ┌───────▼────────┐         ┌───────▼────────┐
         │ Telescope      │         │ Mailpit :8025  │
         │ per service    │         │ Email testing  │
         └────────────────┘         └────────────────┘

                    ┌──────────────────────────────────────┐
                    │ Swagger UI :8081 / Kubernetes :9081 │
                    └──────────────────────────────────────┘
```

## 🧩 System Design

The system is designed as a modular microservices platform where each bounded context owns its own data, HTTP API, and async event responsibilities. Synchronous flows go through the gateway and internal REST calls, while cross-service propagation happens through RabbitMQ events so downstream consumers can stay decoupled.

This gives the project a clean separation between identity, inventory, assignment, health telemetry, auditing, and notifications. It also makes it easier to test failure handling, event-driven processing, and independent service scaling.

## 🧠 Key Architecture Principles

- Database per service to avoid tight coupling at the persistence layer
- API gateway as the single local entry point
- Event-driven integration for cross-service side effects
- Idempotent consumers for safer message retries
- Public resource lookup by UUID instead of leaking internal numeric IDs
- Local-first developer tooling with Docker Compose, Swagger, Telescope, and Kubernetes support

## 📦 Microservices Overview

- `identity-service`
  Handles authentication, Passport token issuance, employees, users, and `user.*` events.
- `inventory-service`
  Owns IT asset records, asset availability checks, and asset lookup endpoints.
- `assignment-service`
  Handles checkout/checkin, local user projection, and inventory circuit breaker logic.
- `health-monitor-service`
  Stores device heartbeats and detects inactive devices.
- `audit-service`
  Consumes platform events and stores immutable audit logs.
- `notification-service`
  Consumes relevant events and sends email or Slack-style notifications.

## 🔗 Service Dependencies Map

```text
Client
  |
  v
Nginx Gateway
  |
  +--> identity-service ------> identity-db
  |
  +--> inventory-service -----> inventory-db
  |
  +--> assignment-service ----> assignment-db
  |         |
  |         +--> inventory-service (sync validation)
  |
  +--> health-monitor-service -> health-monitor-db
  |
  +--> audit-service ---------> audit-db
  |
  +--> notification-service --> notification-db

RabbitMQ topic exchange
  |
  +--> assignment-user-sync-worker
  +--> audit-consumer-worker
  +--> notification-consumer-worker
  +--> health-monitor-scanner
```

### Service Interaction Flow

```text
Identity Service ──► RabbitMQ ──► Assignment User Sync Worker
        │
        └──────────────► Assignment Service

Inventory Service ◄────────────── Assignment Service

Assignment Service ──► RabbitMQ ──┬──► Audit Consumer Worker ──► Audit Service
                                  └──► Notification Consumer ──► Notification Service

Health Monitor Service ──► RabbitMQ ──┬──► Audit Consumer Worker
                                      └──► Notification Consumer

Swagger UI ──► Nginx Gateway ──► All HTTP API Services
Mailpit  ◄──────────────────── Notification Service
```

## 🎯 Prerequisites

Before you begin, make sure you understand both:

- the software you must have installed on your machine
- the main platform components this project uses internally

That way the setup section is not just “what to install”, but also “what this project is built with”.

### Core Project Stack

| Technology | Version | Role In This Project | Notes |
| --- | --- | --- | --- |
| PHP | 8.3 | Runtime for all Laravel microservices | Runs inside the service containers |
| Laravel | 11 | Main application framework | Used by all six backend services |
| Laravel Passport | Current project dependency | OAuth / API token issuance | Used by `identity-service` |
| Laravel Telescope | Current project dependency | Local observability and request inspection | Enabled in local development |
| PostgreSQL | 16 | Primary database engine | One database per service |
| RabbitMQ | 3.13 | Message broker | Used for async event-driven communication |
| Nginx | 1.27 | API gateway / reverse proxy | Routes requests to the correct service |
| Swagger UI / OpenAPI | Swagger UI v5 + OpenAPI YAML | API documentation and manual endpoint testing | Available through the local docs UI |
| Mailpit | v1.21 | Local email testing inbox | Captures notification emails in development |
| Terraform | 1.x | Infrastructure as code | Used for the AWS-oriented environment under `terraform/` |
| Kubernetes | Local cluster compatible | Container orchestration option | Supported through manifests in `kubernetes/base` |

### Required Software

| Software | Recommended Version | Purpose | Download / Notes |
| --- | --- | --- | --- |
| Docker Desktop | Latest stable with Compose v2 | Runs the full local stack, including API services, PostgreSQL, RabbitMQ, Mailpit, and Swagger UI | https://www.docker.com/products/docker-desktop |
| Docker Compose | v2.x | Builds and orchestrates the local multi-container environment | Included with Docker Desktop |
| Git | Latest stable | Clones the repository and helps manage changes locally | https://git-scm.com/downloads |
| PowerShell | 7.x or Windows PowerShell 5.1+ | Runs the project helper scripts and PowerShell API commands used in the README | Included on Windows, or install PowerShell 7 from Microsoft |

### Optional Software

| Software | Recommended Version | Purpose | Download / Notes |
| --- | --- | --- | --- |
| kubectl | v1.30+ | Applies and manages the Kubernetes manifests in `kubernetes/base` | https://kubernetes.io/docs/tasks/tools/ |
| Docker Desktop Kubernetes | Enabled in Docker Desktop | Runs the project on a local Kubernetes cluster without installing Minikube or Kind | Docker Desktop -> Settings -> Kubernetes |
| Terraform | v1.6+ | Applies the AWS-focused infrastructure in `terraform/environments/dev` | https://developer.hashicorp.com/terraform/downloads |

### Access Requirements

| Requirement | Why It Is Needed |
| --- | --- |
| Internet access during the first build | Each image scaffolds Laravel and installs Composer dependencies during the initial Docker build |
| Available local ports | Docker uses `8080`, `8081`, `15672`, `8025`, `1025`, and `54321`-`54326`; Kubernetes port-forward examples use `9080` and `9081` |
| Sufficient disk space and RAM | The stack includes multiple Laravel services, six PostgreSQL containers, RabbitMQ, Mailpit, Swagger UI, and optional Kubernetes workloads |

### Important Clarification

You do **not** need to install Node.js, npm, RabbitMQ, PostgreSQL, PHP, or Laravel manually on your host machine for the normal Docker-based workflow.

Those technologies are still part of the project stack, but they run inside containers managed by Docker Desktop.

You only need host-level installation of those tools if you intentionally want to develop or debug outside the container workflow.

## Services

- `identity-service`: Laravel Passport auth, RBAC, user and employee CRUD, `user.*` events.
- `inventory-service`: IT asset CRUD, asset availability validation, indexed asset lookups.
- `assignment-service`: device check-out and check-in, user sync projection, inventory circuit breaker.
- `health-monitor-service`: telemetry heartbeat ingestion and inactive device detection.
- `audit-service`: centralized event audit trail consumer.
- `notification-service`: email and Slack notifications for assignment and health events.

## Repository Layout

```text
.
|-- docker/
|-- docs/
|-- gateway/
|-- terraform/
|   |-- environments/dev/
|   `-- modules/
`-- services/
    |-- identity-service/
    |   |-- manifest.env
    |   `-- overlay/
    |       |-- app/
    |       |-- config/
    |       |-- database/
    |       |-- routes/
    |       `-- tests/
    |-- inventory-service/
    |-- assignment-service/
    |-- health-monitor-service/
    |-- audit-service/
    `-- notification-service/
```

Each service folder contains:

- `manifest.env`: build-time package requirements.
- `overlay/`: Laravel application files copied into a fresh Laravel 11 project during Docker build.
- `overlay/app`: Controllers, Services, Repositories, Models, infrastructure code.
- `overlay/database`: migrations and seeders.
- `overlay/routes`: service routes.
- `overlay/tests`: PHPUnit examples for critical flows.

## Local Run With Docker

### Prerequisites

- Docker Desktop or Docker Engine with Compose v2
- Internet access during the first build, because each image scaffolds Laravel 11 and installs Composer packages inside the container build

### 1. Build Images

```bash
docker compose build
```

For day-to-day development, source code and tests are bind-mounted into the PHP containers through `docker-compose.override.yml`. That means normal changes in `services/*/overlay/app`, `config`, `database`, `routes`, `tests`, and `resources` are live without rebuilding the image.

Because the services run as long-lived PHP HTTP processes inside containers, changes to `routes`, `bootstrap`, auth wiring, and some config may still require a service restart or recreate before the HTTP endpoint behavior catches up:

```bash
docker compose restart identity-service inventory-service assignment-service
```

If the change affects mounted files plus the container definition itself, use:

```bash
docker compose up -d --force-recreate
docker compose --profile workers up -d --force-recreate
```

Rebuild the images only when you change:

- `docker/service.Dockerfile`
- `docker/scaffold-service.sh`
- Composer dependencies or PHP extensions
- anything that changes the scaffolded base application rather than the mounted overlay files

If you rebuilt after changing migrations or Docker scaffolding, reset local volumes first so old tables do not remain:

```bash
docker compose down -v
docker compose build --no-cache
```

Telescope is installed as a dev dependency in every Laravel service. After pulling these changes, rebuild once so the package is present inside the containers:

```bash
docker compose build --no-cache
docker compose up -d
docker compose --profile workers up -d
```

### 2. Start Core Infrastructure And APIs

This starts the gateway, RabbitMQ, Mailpit, all PostgreSQL instances, and all HTTP API containers.

```bash
docker compose up -d
```

### 3. Run Database Migrations

Run these once after the containers are up:

```bash
docker compose exec identity-service php artisan migrate --seed --force
docker compose exec identity-service php artisan passport:install --force --no-interaction

docker compose exec inventory-service php artisan migrate --force
docker compose exec assignment-service php artisan migrate --force
docker compose exec health-monitor-service php artisan migrate --force
docker compose exec audit-service php artisan migrate --force
docker compose exec notification-service php artisan migrate --force
```

Notes:

- `identity-service` seeds an initial admin user.
- `passport:install` creates the encryption keys and personal access client required by Laravel Passport token issuance.
- Run `passport:install` only once per fresh identity database. Re-running it will generate new Passport clients again.
- These migrations also create the Telescope tables and the web `sessions` table used by each Telescope dashboard.

### 4. Start Background Workers

Workers are intentionally behind a Compose profile so they do not race the initial migrations.

```bash
docker compose --profile workers up -d
```

This starts:

- `assignment-user-sync-worker`
- `audit-consumer-worker`
- `notification-consumer-worker`
- `health-monitor-scanner`

### 5. Verify The Stack

Endpoints:

- Gateway health: `http://localhost:8080/healthz`
- Swagger UI: `http://localhost:8081`
- RabbitMQ UI: `http://localhost:15672`
- Mailpit UI: `http://localhost:8025`

Telescope dashboards:

- Identity: `http://localhost:8080/identity/telescope`
- Inventory: `http://localhost:8080/inventory/telescope`
- Assignment: `http://localhost:8080/assignments/telescope`
- Health Monitor: `http://localhost:8080/health/telescope`
- Audit: `http://localhost:8080/audit/telescope`
- Notification: `http://localhost:8080/notifications/telescope`

Default RabbitMQ credentials:

- username: `asset_monitoring_system`
- password: `asset_monitoring_system`
- Local Docker leaves `SLACK_WEBHOOK_URL` empty by default, so notification smoke tests use Mailpit email without trying to call an external Slack webhook.

### 6. Stop Everything

```bash
docker compose down
```

To also remove databases:

```bash
docker compose down -v
```

### 7. Restart Everything

```bash
docker compose up -d
docker compose --profile workers up -d
docker compose ps

```

### 8. Fast Dev Workflow

If you only changed PHP code, routes, config, tests, or Blade/resources:

```bash
docker compose up -d
docker compose --profile workers up -d
```

Then just rerun the command you need, for example:

```bash
docker compose exec identity-service php artisan test
```

`docker compose` automatically loads `docker-compose.override.yml` locally.

If you want to run the base file only, for example in CI or deployment-style usage, call:

```bash
docker compose -f docker-compose.yml up -d
```

If you changed Docker/scaffolding/dependencies:

```bash
docker compose build --no-cache
docker compose up -d --force-recreate
docker compose --profile workers up -d --force-recreate
```


## Seeded Credentials

After running `identity-service` migrations and seeder:

- email: `admin@assetmonitoringsystem.local`
- password: `AdminPass123!`

## Sample API Calls

### Login

```bash
curl --request POST http://localhost:8080/api/v1/identity/auth/login \
  --header "Content-Type: application/json" \
  --data '{"email":"admin@assetmonitoringsystem.local","password":"AdminPass123!"}'
```

You can also exercise the APIs in Swagger UI at `http://localhost:8081`.

- Use the Identity `POST /api/v1/identity/auth/login` operation first.
- Copy the `access_token` value.
- Click `Authorize` in Swagger UI and paste only the raw `access_token`.
- Swagger UI will automatically send it as `Authorization: Bearer <access_token>`.

### Create An Asset

```bash
curl --request POST http://localhost:8080/api/v1/inventory/assets \
  --header "Content-Type: application/json" \
  --data '{
    "serial_number":"LAP-1001",
    "asset_tag":"AST-1001",
    "specs":{"cpu":"Intel Core i7","ram":"16GB","storage":"512GB SSD"},
    "status":"available"
  }'
```

### Check Out An Asset

```bash
curl --request POST http://localhost:8080/api/v1/assignments/checkout \
  --header "Content-Type: application/json" \
  --data '{
    "user_id":1,
    "asset_serial_number":"LAP-1001"
  }'
```

### Post Device Heartbeat

```bash
curl --request POST http://localhost:8080/api/v1/health/heartbeats \
  --header "Content-Type: application/json" \
  --data '{
    "device_serial_number":"LAP-1001",
    "metadata":{"ip_address":"10.0.10.24","battery":94}
  }'
```

## Message Flow

### Identity To Assignment

1. `identity-service` creates or updates a user.
2. `identity-service` publishes `user.created` or `user.updated`.
3. `assignment-user-sync-worker` consumes `user.*`.
4. The consumer stores the latest user projection locally.
5. Assignment check-out uses the local projection and validates the asset through `inventory-service`.

### Assignment To Audit And Notifications

1. `assignment-service` publishes `assignment.checked_out` or `assignment.checked_in`.
2. `audit-consumer-worker` stores an immutable audit record.
3. `notification-consumer-worker` sends email and Slack notifications.

### Health Alerts

1. `health-monitor-service` stores heartbeats.
2. `health-monitor-scanner` detects inactive devices.
3. The scanner publishes `health.device_inactive`.
4. Audit and notification workers consume the event.

## Circuit Breaker

The Assignment Service protects synchronous calls to the Inventory Service with a circuit breaker:

- States: `CLOSED`, `OPEN`, `HALF_OPEN`
- Failure threshold: controlled by `CIRCUIT_BREAKER_FAILURE_THRESHOLD`
- Open interval: controlled by `CIRCUIT_BREAKER_OPEN_SECONDS`
- HTTP timeout: controlled by `CIRCUIT_BREAKER_TIMEOUT_SECONDS`
- Fallback behavior: reject assignment using cached or unavailable asset state

Code reference:

- `services/assignment-service/overlay/app/Support/CircuitBreaker/CircuitBreaker.php`
- `services/assignment-service/overlay/app/Support/Inventory/InventoryClient.php`

## Testing

The repository includes example PHPUnit coverage for the highest-risk flows:

- circuit breaker transitions and fallback behavior
- user sync integration from identity payload to assignment projection
- idempotent publish/consume flow around RabbitMQ message contracts
- identity employee/user API flow
- inventory asset CRUD and status validation
- health heartbeat ingestion and inactive device processing
- audit log recording and listing
- notification dispatch, email/slack delivery persistence, and listing

Run tests per service after the containers are built:

```bash
docker compose exec identity-service php artisan test
docker compose exec inventory-service php artisan test
docker compose exec assignment-service php artisan test
docker compose exec health-monitor-service php artisan test
docker compose exec audit-service php artisan test
docker compose exec notification-service php artisan test
```

## Swagger / OpenAPI

OpenAPI specs are stored in [api-docs/index.yaml](d:/kerja/Loker/Portfolio/Asset Monitoring System /api-docs/index.yaml) and the per-service files under [api-docs/specs](d:/kerja/Loker/Portfolio/Asset Monitoring System /api-docs/specs).

To start Swagger UI:

```bash
docker compose up -d swagger-ui
```

Then open:

```text
http://localhost:8081
```

Included specs:

- Identity Service
- Inventory Service
- Assignment Service
- Health Monitor Service
- Audit Service
- Notification Service

## Telescope

Each Laravel service includes Telescope in local development so you can inspect request timing, queries, exceptions, cache activity, jobs, and outbound mail while exercising the APIs.

Setup checklist:

```bash
docker compose exec identity-service php artisan migrate --force
docker compose exec inventory-service php artisan migrate --force
docker compose exec assignment-service php artisan migrate --force
docker compose exec health-monitor-service php artisan migrate --force
docker compose exec audit-service php artisan migrate --force
docker compose exec notification-service php artisan migrate --force
```

If you changed `docker/scaffold-service.sh`, `docker/service.Dockerfile`, or Composer dependencies, rebuild once:

```bash
docker compose build --no-cache
docker compose up -d --force-recreate
docker compose --profile workers up -d --force-recreate
```

Dashboards:

- Identity: `http://localhost:8080/identity/telescope`
- Inventory: `http://localhost:8080/inventory/telescope`
- Assignment: `http://localhost:8080/assignments/telescope`
- Health Monitor: `http://localhost:8080/health/telescope`
- Audit: `http://localhost:8080/audit/telescope`
- Notification: `http://localhost:8080/notifications/telescope`

Quick smoke traffic so the dashboards are not empty:

```bash
curl -X POST http://localhost:8080/api/v1/identity/auth/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@assetmonitoringsystem.local","password":"AdminPass123!"}'
```

Then call a few API endpoints from Swagger or PowerShell and open the matching Telescope dashboard to inspect:

- request duration
- SQL query count and timings
- exceptions
- cache activity
- mail events in notification-service

## Terraform

Terraform files live in `terraform/environments/dev`.

### Provisioning Steps

1. Copy the example variables file:

```bash
cd terraform/environments/dev
cp terraform.tfvars.example terraform.tfvars
```

2. Edit `terraform.tfvars` with your AWS VPC, subnet, security group, and secrets.

3. Initialize and review the plan:

```bash
terraform init
terraform plan
```

4. Apply:

```bash
terraform apply
```

### Provisioned AWS Resources

- Amazon RDS PostgreSQL instance per service
- Amazon MQ broker for RabbitMQ
- CloudWatch log groups for gateway and services
- CloudWatch alarms for:
  - high API latency
  - elevated 5xx error rate
  - queue lag / backlog

## Operational Notes

- Local Docker Compose is intended for development and integration testing.
- Container images use multi-stage builds so Composer and build-time tools stay out of the runtime stage.
- Service logs are structured for aggregation into CloudWatch when deployed on AWS.
- Consumers use `processed_messages` tables for idempotency.
- The monorepo intentionally keeps each service independent at the database and event-consumer level.

## Kubernetes

Kubernetes manifests live in `kubernetes/base` and are organized as a single Kustomize base.

For this project, the recommended local cluster is Docker Desktop Kubernetes. It is the best fit for your current setup because Docker Desktop is already installed and the manifests are configured to use the same locally built images.

### What Is Included

- namespace: `asset-monitoring-system`
- PostgreSQL deployment and PVC per service
- RabbitMQ and Mailpit
- all six Laravel API deployments and Services
- all four background worker deployments
- Nginx gateway and Swagger UI
- ingress resources for API and Swagger hosts
- bootstrap jobs for database migrations
- identity bootstrap job for Passport keys and personal access client setup

### Recommended Local Setup

Use Docker Desktop Kubernetes.

1. Open Docker Desktop.
2. Go to `Settings` -> `Kubernetes`.
3. Enable Kubernetes and wait until Docker Desktop shows it as running.
4. Keep your normal Docker Desktop engine running.

After that, use the helper script:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\k8s-up.ps1
```

That script:

- checks whether Docker Desktop Kubernetes is reachable
- builds the local images if you ask it to
- applies `kubernetes/base`
- shows jobs, pods, and services in the `asset-monitoring-system` namespace

### Local Image Strategy

The manifests expect the same local image names produced by Docker Compose:

- `asset_monitoring_system-identity-service:latest`
- `asset_monitoring_system-inventory-service:latest`
- `asset_monitoring_system-assignment-service:latest`
- `asset_monitoring_system-health-monitor-service:latest`
- `asset_monitoring_system-audit-service:latest`
- `asset_monitoring_system-notification-service:latest`
- worker images with the same `asset_monitoring_system-*` naming

Build them first:

```bash
docker compose build
```

Because these custom images use `imagePullPolicy: IfNotPresent`, Docker Desktop Kubernetes can use your local images directly after `docker compose build`. No separate `minikube image load` or `kind load` step is required for the default workflow.

### Deploy To The Cluster

Apply everything with Kustomize:

```bash
kubectl apply -k kubernetes/base
```

Watch the bootstrap jobs:

```bash
kubectl get jobs -n asset-monitoring-system
kubectl logs job/identity-bootstrap -n asset-monitoring-system
```

Watch the workloads:

```bash
kubectl get pods -n asset-monitoring-system
kubectl get svc -n asset-monitoring-system
```

Or use the helper scripts:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\k8s-up.ps1
powershell -ExecutionPolicy Bypass -File .\scripts\k8s-status.ps1
```

### Access The Stack

If you have an NGINX ingress controller installed, add these hosts locally:

- `api.asset-monitoring-system.local`
- `swagger.asset-monitoring-system.local`

Then use:

- API gateway: `http://api.asset-monitoring-system.local`
- Swagger UI: `http://swagger.asset-monitoring-system.local`

If you do not want to use ingress yet, port-forward instead:

```bash
kubectl port-forward svc/gateway 8080:80 -n asset-monitoring-system
kubectl port-forward svc/swagger-ui 8081:8080 -n asset-monitoring-system
```

If Docker Compose is still running on `8080` and `8081`, use separate local ports for Kubernetes so both stacks can run side by side:

```bash
kubectl port-forward svc/gateway 9080:80 -n asset-monitoring-system
kubectl port-forward svc/swagger-ui 9081:8080 -n asset-monitoring-system
```

Then open:

- Kubernetes gateway: `http://localhost:9080`
- Kubernetes Swagger UI: `http://localhost:9081`

### Operational Notes

- `identity-service` uses a dedicated PVC mounted at `/var/www/app/storage` so Passport keys survive pod restarts.
- The generated gateway config and Swagger specs are copied into `kubernetes/base/files` so Kustomize can build without external file-load restrictions.
- Docker Desktop Kubernetes is the supported local path in this repository. If you later switch to Minikube or Kind, you will need a cluster-specific image loading step.
- If you change `gateway/nginx.conf` or files under `api-docs`, refresh the copied Kubernetes inputs too:

```bash
copy gateway\nginx.conf kubernetes\base\files\nginx.conf /Y
copy api-docs\index.yaml kubernetes\base\files\swagger-specs\index.yaml /Y
copy api-docs\specs\*.yaml kubernetes\base\files\swagger-specs\ /Y
```
