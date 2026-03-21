provider "aws" {
  region = var.aws_region
}

locals {
  service_databases = {
    identity     = "identity_db"
    inventory    = "inventory_db"
    assignment   = "assignment_db"
    health       = "health_monitor_db"
    audit        = "audit_db"
    notification = "notification_db"
  }

  common_tags = {
    Project     = var.project_name
    Environment = var.environment
    ManagedBy   = "Terraform"
  }

  alarm_actions = var.alarm_topic_arn == "" ? [] : [var.alarm_topic_arn]
}

module "rds_services" {
  source = "../../modules/rds-service"

  for_each = local.service_databases

  name               = "${var.project_name}-${var.environment}-${each.key}"
  database_name      = each.value
  username           = var.db_master_username
  password           = var.db_master_password
  subnet_ids         = var.private_subnet_ids
  security_group_ids = var.db_security_group_ids
  tags               = local.common_tags
}

resource "aws_mq_broker" "rabbitmq" {
  broker_name                = "${var.project_name}-${var.environment}-mq"
  engine_type                = "RabbitMQ"
  engine_version             = "3.13"
  host_instance_type         = "mq.t3.micro"
  deployment_mode            = "SINGLE_INSTANCE"
  publicly_accessible        = false
  auto_minor_version_upgrade = true
  subnet_ids                 = [var.private_subnet_ids[0]]
  security_groups            = var.mq_security_group_ids

  user {
    username = var.mq_username
    password = var.mq_password
  }

  logs {
    general = true
  }

  tags = local.common_tags
}

resource "aws_cloudwatch_log_group" "service_logs" {
  for_each = toset([
    "gateway",
    "identity-service",
    "inventory-service",
    "assignment-service",
    "health-monitor-service",
    "audit-service",
    "notification-service"
  ])

  name              = "/aws/asset_monitoring_system/${var.environment}/${each.value}"
  retention_in_days = 30
  tags              = local.common_tags
}

module "high_latency_alarm" {
  source = "../../modules/cloudwatch-alarm"

  alarm_name          = "${var.project_name}-${var.environment}-api-latency-high"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = 3
  metric_name         = "TargetResponseTime"
  namespace           = "AWS/ApplicationELB"
  period              = 60
  statistic           = "Average"
  threshold           = 1.5
  alarm_description   = "AssetMonitoringSystem API latency is above 1.5 seconds."
  alarm_actions       = local.alarm_actions
}

module "high_error_rate_alarm" {
  source = "../../modules/cloudwatch-alarm"

  alarm_name          = "${var.project_name}-${var.environment}-api-error-rate-high"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = 2
  metric_name         = "HTTPCode_Target_5XX_Count"
  namespace           = "AWS/ApplicationELB"
  period              = 60
  statistic           = "Sum"
  threshold           = 25
  alarm_description   = "AssetMonitoringSystem API 5xx count exceeded the allowed threshold."
  alarm_actions       = local.alarm_actions
}

module "queue_lag_alarm" {
  source = "../../modules/cloudwatch-alarm"

  alarm_name          = "${var.project_name}-${var.environment}-queue-lag-high"
  comparison_operator = "GreaterThanThreshold"
  evaluation_periods  = 3
  metric_name         = "MessageCount"
  namespace           = "AWS/AmazonMQ"
  period              = 60
  statistic           = "Average"
  threshold           = 200
  alarm_description   = "RabbitMQ backlog is higher than expected."
  alarm_actions       = local.alarm_actions
  dimensions = {
    Broker = aws_mq_broker.rabbitmq.broker_name
  }
}
