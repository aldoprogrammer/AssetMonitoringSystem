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

  ecs_services = [
    "gateway",
    "identity-service",
    "swagger-ui",
    "inventory-service",
    "assignment-service",
    "health-monitor-service",
    "audit-service",
    "notification-service",
    "assignment-user-sync-worker",
    "audit-consumer-worker",
    "notification-consumer-worker",
    "health-monitor-scanner"
  ]

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
    "swagger-ui",
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

resource "aws_ecs_cluster" "main" {
  count = var.enable_ecs_foundation ? 1 : 0

  name = "${var.project_name}-${var.environment}"

  setting {
    name  = "containerInsights"
    value = "enabled"
  }

  tags = local.common_tags
}

resource "aws_security_group" "alb" {
  count = var.enable_ecs_foundation && length(var.public_subnet_ids) >= 2 ? 1 : 0

  name        = "${var.project_name}-${var.environment}-alb-sg"
  description = "Security group for the public application load balancer"
  vpc_id      = var.vpc_id

  ingress {
    description = "HTTP"
    from_port   = 80
    to_port     = 80
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = merge(local.common_tags, {
    Name = "${var.project_name}-${var.environment}-alb-sg"
  })
}

resource "aws_security_group" "gateway_service" {
  count = var.enable_ecs_foundation && length(var.public_subnet_ids) >= 2 ? 1 : 0

  name        = "${var.project_name}-${var.environment}-gateway-sg"
  description = "Security group for the ECS gateway service"
  vpc_id      = var.vpc_id

  ingress {
    description     = "HTTP from ALB"
    from_port       = 80
    to_port         = 80
    protocol        = "tcp"
    security_groups = [aws_security_group.alb[0].id]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = merge(local.common_tags, {
    Name = "${var.project_name}-${var.environment}-gateway-sg"
  })
}

resource "aws_security_group" "identity_service" {
  count = var.enable_ecs_foundation && length(var.public_subnet_ids) >= 2 ? 1 : 0

  name        = "${var.project_name}-${var.environment}-identity-sg"
  description = "Security group for the ECS identity service"
  vpc_id      = var.vpc_id

  ingress {
    description     = "HTTP from ALB"
    from_port       = 8000
    to_port         = 8000
    protocol        = "tcp"
    security_groups = [aws_security_group.alb[0].id]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = merge(local.common_tags, {
    Name = "${var.project_name}-${var.environment}-identity-sg"
  })
}

resource "aws_security_group" "swagger_service" {
  count = var.enable_ecs_foundation && length(var.public_subnet_ids) >= 2 ? 1 : 0

  name        = "${var.project_name}-${var.environment}-swagger-sg"
  description = "Security group for the ECS Swagger UI service"
  vpc_id      = var.vpc_id

  ingress {
    description     = "Swagger UI from ALB"
    from_port       = 8080
    to_port         = 8080
    protocol        = "tcp"
    security_groups = [aws_security_group.alb[0].id]
  }

  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = merge(local.common_tags, {
    Name = "${var.project_name}-${var.environment}-swagger-sg"
  })
}

resource "aws_security_group_rule" "identity_to_db" {
  count = var.enable_ecs_foundation && length(var.public_subnet_ids) >= 2 ? 1 : 0

  type                     = "ingress"
  from_port                = 5432
  to_port                  = 5432
  protocol                 = "tcp"
  security_group_id        = var.db_security_group_ids[0]
  source_security_group_id = aws_security_group.identity_service[0].id
  description              = "Allow identity ECS service to reach PostgreSQL"
}

resource "aws_security_group_rule" "identity_to_mq" {
  count = var.enable_ecs_foundation && length(var.public_subnet_ids) >= 2 ? 1 : 0

  type                     = "ingress"
  from_port                = 5672
  to_port                  = 5672
  protocol                 = "tcp"
  security_group_id        = var.mq_security_group_ids[0]
  source_security_group_id = aws_security_group.identity_service[0].id
  description              = "Allow identity ECS service to reach RabbitMQ"
}

resource "aws_lb" "public" {
  count = var.enable_ecs_foundation && length(var.public_subnet_ids) >= 2 ? 1 : 0

  name               = substr("${var.project_name}-${var.environment}-alb", 0, 32)
  internal           = false
  load_balancer_type = "application"
  security_groups    = [aws_security_group.alb[0].id]
  subnets            = var.public_subnet_ids

  tags = local.common_tags
}

resource "aws_lb_target_group" "gateway" {
  count = var.enable_ecs_foundation && length(var.public_subnet_ids) >= 2 ? 1 : 0

  name        = substr("${var.project_name}-${var.environment}-gw", 0, 32)
  port        = 80
  protocol    = "HTTP"
  target_type = "ip"
  vpc_id      = var.vpc_id

  health_check {
    enabled             = true
    healthy_threshold   = 2
    interval            = 30
    matcher             = "200"
    path                = "/healthz"
    port                = "traffic-port"
    protocol            = "HTTP"
    timeout             = 5
    unhealthy_threshold = 3
  }

  tags = local.common_tags
}

resource "aws_lb_listener" "gateway_http" {
  count = var.enable_ecs_foundation && length(var.public_subnet_ids) >= 2 ? 1 : 0

  load_balancer_arn = aws_lb.public[0].arn
  port              = 80
  protocol          = "HTTP"

  default_action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.gateway[0].arn
  }
}

resource "aws_lb_target_group" "identity" {
  count = var.enable_ecs_foundation && length(var.public_subnet_ids) >= 2 ? 1 : 0

  name        = substr("${var.project_name}-${var.environment}-id", 0, 32)
  port        = 8000
  protocol    = "HTTP"
  target_type = "ip"
  vpc_id      = var.vpc_id

  health_check {
    enabled             = true
    healthy_threshold   = 2
    interval            = 30
    matcher             = "200-399"
    path                = "/up"
    port                = "traffic-port"
    protocol            = "HTTP"
    timeout             = 5
    unhealthy_threshold = 3
  }

  tags = local.common_tags
}

resource "aws_lb_target_group" "swagger" {
  count = var.enable_ecs_foundation && length(var.public_subnet_ids) >= 2 ? 1 : 0

  name        = substr("${var.project_name}-${var.environment}-sw", 0, 32)
  port        = 8080
  protocol    = "HTTP"
  target_type = "ip"
  vpc_id      = var.vpc_id

  health_check {
    enabled             = true
    healthy_threshold   = 2
    interval            = 30
    matcher             = "200-399"
    path                = "/"
    port                = "traffic-port"
    protocol            = "HTTP"
    timeout             = 5
    unhealthy_threshold = 3
  }

  tags = local.common_tags
}

resource "aws_lb_listener_rule" "identity_api" {
  count = var.enable_ecs_foundation && length(var.public_subnet_ids) >= 2 ? 1 : 0

  listener_arn = aws_lb_listener.gateway_http[0].arn
  priority     = 10

  action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.identity[0].arn
  }

  condition {
    path_pattern {
      values = ["/api/v1/auth*", "/api/v1/users*", "/api/v1/employees*", "/up"]
    }
  }
}

resource "aws_lb_listener_rule" "identity_telescope" {
  count = var.enable_ecs_foundation && length(var.public_subnet_ids) >= 2 ? 1 : 0

  listener_arn = aws_lb_listener.gateway_http[0].arn
  priority     = 11

  action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.identity[0].arn
  }

  condition {
    path_pattern {
      values = ["/identity/telescope", "/identity/telescope/*"]
    }
  }
}

resource "aws_lb_listener_rule" "swagger_docs" {
  count = var.enable_ecs_foundation && length(var.public_subnet_ids) >= 2 ? 1 : 0

  listener_arn = aws_lb_listener.gateway_http[0].arn
  priority     = 5

  action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.swagger[0].arn
  }

  condition {
    path_pattern {
      values = ["/docs", "/docs/*", "/specs", "/specs/*"]
    }
  }
}

resource "aws_ecs_task_definition" "gateway" {
  count = var.enable_ecs_foundation && length(var.public_subnet_ids) >= 2 ? 1 : 0

  family                   = "${var.project_name}-${var.environment}-gateway"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = "256"
  memory                   = "512"
  execution_role_arn       = aws_iam_role.ecs_task_execution[0].arn
  task_role_arn            = aws_iam_role.ecs_task_app[0].arn

  container_definitions = jsonencode([
    {
      name      = "gateway"
      image     = "${aws_ecr_repository.services["gateway"].repository_url}:${var.gateway_image_tag}"
      essential = true
      portMappings = [
        {
          containerPort = 80
          hostPort      = 80
          protocol      = "tcp"
        }
      ]
      logConfiguration = {
        logDriver = "awslogs"
        options = {
          awslogs-group         = aws_cloudwatch_log_group.service_logs["gateway"].name
          awslogs-region        = var.aws_region
          awslogs-stream-prefix = "ecs"
        }
      }
    }
  ])

  tags = local.common_tags
}

resource "aws_ecs_task_definition" "identity" {
  count = var.enable_ecs_foundation && length(var.public_subnet_ids) >= 2 ? 1 : 0

  family                   = "${var.project_name}-${var.environment}-identity"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = "512"
  memory                   = "1024"
  execution_role_arn       = aws_iam_role.ecs_task_execution[0].arn
  task_role_arn            = aws_iam_role.ecs_task_app[0].arn

  container_definitions = jsonencode([
    {
      name      = "identity-service"
      image     = "${aws_ecr_repository.services["identity-service"].repository_url}:${var.identity_image_tag}"
      essential = true
      portMappings = [
        {
          containerPort = 8000
          hostPort      = 8000
          protocol      = "tcp"
        }
      ]
      environment = [
        { name = "PASSPORT_ENABLED", value = "true" },
        { name = "APP_ENV", value = "production" },
        { name = "APP_DEBUG", value = "false" },
        { name = "APP_URL", value = "http://${aws_lb.public[0].dns_name}/api/v1/identity" },
        { name = "TELESCOPE_ENABLED", value = "true" },
        { name = "TELESCOPE_RECORD_ALL", value = "true" },
        { name = "TELESCOPE_PATH", value = "identity/telescope" },
        { name = "TELESCOPE_SERVICE_NAME", value = "identity-service" },
        { name = "DB_CONNECTION", value = "pgsql" },
        { name = "DB_HOST", value = split(":", module.rds_services["identity"].endpoint)[0] },
        { name = "DB_PORT", value = "5432" },
        { name = "DB_DATABASE", value = "identity_db" },
        { name = "DB_USERNAME", value = var.db_master_username },
        { name = "DB_PASSWORD", value = var.db_master_password },
        { name = "QUEUE_CONNECTION", value = "sync" },
        { name = "CACHE_STORE", value = "file" },
        { name = "SESSION_DRIVER", value = "file" },
        { name = "LOG_CHANNEL", value = "stack" },
        { name = "RABBITMQ_HOST", value = replace(aws_mq_broker.rabbitmq.instances[0].endpoints[0], "amqps://", "") },
        { name = "RABBITMQ_PORT", value = "5672" },
        { name = "RABBITMQ_USER", value = var.mq_username },
        { name = "RABBITMQ_PASSWORD", value = var.mq_password },
        { name = "RABBITMQ_VHOST", value = "/" },
        { name = "RABBITMQ_EXCHANGE", value = "asset_monitoring_system.events" },
        { name = "RABBITMQ_QUEUE_PREFIX", value = "identity" },
        { name = "RUN_MIGRATIONS", value = "true" },
        { name = "RUN_SEEDERS", value = "true" },
        { name = "RUN_PASSPORT_CLIENT_SETUP", value = "true" }
      ]
      logConfiguration = {
        logDriver = "awslogs"
        options = {
          awslogs-group         = aws_cloudwatch_log_group.service_logs["identity-service"].name
          awslogs-region        = var.aws_region
          awslogs-stream-prefix = "ecs"
        }
      }
    }
  ])

  tags = local.common_tags
}

resource "aws_ecs_task_definition" "swagger" {
  count = var.enable_ecs_foundation && length(var.public_subnet_ids) >= 2 ? 1 : 0

  family                   = "${var.project_name}-${var.environment}-swagger"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = "256"
  memory                   = "512"
  execution_role_arn       = aws_iam_role.ecs_task_execution[0].arn
  task_role_arn            = aws_iam_role.ecs_task_app[0].arn

  container_definitions = jsonencode([
    {
      name      = "swagger-ui"
      image     = "${aws_ecr_repository.services["swagger-ui"].repository_url}:${var.swagger_image_tag}"
      essential = true
      portMappings = [
        {
          containerPort = 8080
          hostPort      = 8080
          protocol      = "tcp"
        }
      ]
      logConfiguration = {
        logDriver = "awslogs"
        options = {
          awslogs-group         = aws_cloudwatch_log_group.service_logs["swagger-ui"].name
          awslogs-region        = var.aws_region
          awslogs-stream-prefix = "ecs"
        }
      }
    }
  ])

  tags = local.common_tags
}

resource "aws_ecs_service" "gateway" {
  count = var.enable_ecs_foundation && length(var.public_subnet_ids) >= 2 ? 1 : 0

  name            = "gateway"
  cluster         = aws_ecs_cluster.main[0].id
  task_definition = aws_ecs_task_definition.gateway[0].arn
  desired_count   = var.gateway_desired_count
  launch_type     = "FARGATE"

  network_configuration {
    subnets          = var.public_subnet_ids
    security_groups  = [aws_security_group.gateway_service[0].id]
    assign_public_ip = true
  }

  load_balancer {
    target_group_arn = aws_lb_target_group.gateway[0].arn
    container_name   = "gateway"
    container_port   = 80
  }

  depends_on = [aws_lb_listener.gateway_http, aws_iam_service_linked_role.ecs]

  tags = local.common_tags
}

resource "aws_ecs_service" "identity" {
  count = var.enable_ecs_foundation && length(var.public_subnet_ids) >= 2 ? 1 : 0

  name            = "identity-service"
  cluster         = aws_ecs_cluster.main[0].id
  task_definition = aws_ecs_task_definition.identity[0].arn
  desired_count   = var.identity_desired_count
  launch_type     = "FARGATE"

  network_configuration {
    subnets          = var.public_subnet_ids
    security_groups  = [aws_security_group.identity_service[0].id]
    assign_public_ip = true
  }

  load_balancer {
    target_group_arn = aws_lb_target_group.identity[0].arn
    container_name   = "identity-service"
    container_port   = 8000
  }

  depends_on = [
    aws_lb_listener_rule.identity_api,
    aws_lb_listener_rule.identity_telescope,
    aws_iam_service_linked_role.ecs,
    aws_security_group_rule.identity_to_db,
    aws_security_group_rule.identity_to_mq
  ]

  tags = local.common_tags
}

resource "aws_ecs_service" "swagger" {
  count = var.enable_ecs_foundation && length(var.public_subnet_ids) >= 2 ? 1 : 0

  name            = "swagger-ui"
  cluster         = aws_ecs_cluster.main[0].id
  task_definition = aws_ecs_task_definition.swagger[0].arn
  desired_count   = var.swagger_desired_count
  launch_type     = "FARGATE"

  network_configuration {
    subnets          = var.public_subnet_ids
    security_groups  = [aws_security_group.swagger_service[0].id]
    assign_public_ip = true
  }

  load_balancer {
    target_group_arn = aws_lb_target_group.swagger[0].arn
    container_name   = "swagger-ui"
    container_port   = 8080
  }

  depends_on = [
    aws_lb_listener_rule.swagger_docs,
    aws_iam_service_linked_role.ecs
  ]

  tags = local.common_tags
}

resource "aws_ecr_repository" "services" {
  for_each = var.enable_ecs_foundation ? toset(local.ecs_services) : toset([])

  name                 = "${var.project_name}/${var.environment}/${each.value}"
  image_tag_mutability = "MUTABLE"

  image_scanning_configuration {
    scan_on_push = true
  }

  tags = local.common_tags
}

data "aws_iam_policy_document" "ecs_task_assume_role" {
  statement {
    actions = ["sts:AssumeRole"]

    principals {
      type        = "Service"
      identifiers = ["ecs-tasks.amazonaws.com"]
    }
  }
}

resource "aws_iam_service_linked_role" "ecs" {
  count = var.enable_ecs_foundation ? 1 : 0

  aws_service_name = "ecs.amazonaws.com"
  description      = "Service-linked role for ECS managed by Terraform"
}

resource "aws_iam_role" "ecs_task_execution" {
  count = var.enable_ecs_foundation ? 1 : 0

  name               = "${var.project_name}-${var.environment}-ecs-execution-role"
  assume_role_policy = data.aws_iam_policy_document.ecs_task_assume_role.json
  tags               = local.common_tags
}

resource "aws_iam_role_policy_attachment" "ecs_task_execution" {
  count = var.enable_ecs_foundation ? 1 : 0

  role       = aws_iam_role.ecs_task_execution[0].name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AmazonECSTaskExecutionRolePolicy"
}

resource "aws_iam_role" "ecs_task_app" {
  count = var.enable_ecs_foundation ? 1 : 0

  name               = "${var.project_name}-${var.environment}-ecs-app-role"
  assume_role_policy = data.aws_iam_policy_document.ecs_task_assume_role.json
  tags               = local.common_tags
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
