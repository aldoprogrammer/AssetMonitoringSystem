# AssetMonitoringSystem

AssetMonitoringSystem is a production-oriented Laravel 11 microservices system for enterprise IT asset management. It uses a six-service architecture, RabbitMQ topic messaging, PostgreSQL per service, Docker multi-stage builds, an Nginx API gateway, and Terraform for AWS provisioning.

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

## Architecture

- Single entry point: Nginx gateway on `http://localhost:8080`
- Sync communication: REST
- Async communication: RabbitMQ topic exchange `asset_monitoring_system.events`
- Routing keys:
  - `user.*`
  - `assignment.*`
  - `audit.*`
  - `health.*`
- Database per service: PostgreSQL
- Local notification sink: Mailpit

## Local Run With Docker

### Prerequisites

- Docker Desktop or Docker Engine with Compose v2
- Internet access during the first build, because each image scaffolds Laravel 11 and installs Composer packages inside the container build

### 1. Build Images

```bash
docker compose build
```

For day-to-day development, source code and tests are bind-mounted into the PHP containers through `docker-compose.override.yml`. That means normal changes in `services/*/overlay/app`, `config`, `database`, `routes`, `tests`, and `resources` are live without rebuilding the image.

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
