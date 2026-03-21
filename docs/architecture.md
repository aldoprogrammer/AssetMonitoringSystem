# AssetMonitoringSystem Architecture

AssetMonitoringSystem is a Laravel 11 microservices monorepo organized around six bounded contexts:

- Identity Service: JWT authentication with Passport, RBAC, employee directory, and user sync events.
- Inventory Service: IT asset catalog, asset status validation, and indexed lookup paths.
- Assignment Service: device checkout/check-in workflows, user sync projection, inventory verification, and circuit breaker protection.
- Health Monitor Service: heartbeat ingestion, inactivity detection, and health alert events.
- Audit Service: centralized audit ingestion for security and activity events.
- Notification Service: asynchronous delivery of email and Slack notifications.

Integration patterns:

- Sync traffic: REST through a single Nginx API gateway.
- Async traffic: RabbitMQ topic exchange with routing keys such as `user.*`, `assignment.*`, `audit.*`, and `health.*`.
- Persistence: one PostgreSQL database per service.
- Observability: structured logs written to stdout for CloudWatch ingestion, plus Terraform-managed CloudWatch log groups and alarms.

Repository layout:

```text
.
|-- docker/
|-- gateway/
|-- terraform/
|   |-- environments/dev/
|   `-- modules/
`-- services/
    |-- identity-service/
    |-- inventory-service/
    |-- assignment-service/
    |-- health-monitor-service/
    |-- audit-service/
    `-- notification-service/
```
