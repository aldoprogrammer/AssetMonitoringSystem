output "rds_endpoints" {
  value = {
    for name, module_output in module.rds_services : name => module_output.endpoint
  }
}

output "rabbitmq_console_url" {
  value = aws_mq_broker.rabbitmq.instances[0].console_url
}

output "cloudwatch_log_groups" {
  value = {
    for name, log_group in aws_cloudwatch_log_group.service_logs : name => log_group.name
  }
}

output "ecs_cluster_name" {
  value = var.enable_ecs_foundation ? aws_ecs_cluster.main[0].name : null
}

output "ecs_ecr_repositories" {
  value = {
    for name, repository in aws_ecr_repository.services : name => repository.repository_url
  }
}

output "ecs_task_execution_role_arn" {
  value = var.enable_ecs_foundation ? aws_iam_role.ecs_task_execution[0].arn : null
}

output "ecs_task_app_role_arn" {
  value = var.enable_ecs_foundation ? aws_iam_role.ecs_task_app[0].arn : null
}

output "alb_dns_name" {
  value = var.enable_ecs_foundation && length(var.public_subnet_ids) >= 2 ? aws_lb.public[0].dns_name : null
}

output "gateway_target_group_arn" {
  value = var.enable_ecs_foundation && length(var.public_subnet_ids) >= 2 ? aws_lb_target_group.gateway[0].arn : null
}

output "gateway_ecs_service_name" {
  value = var.enable_ecs_foundation && length(var.public_subnet_ids) >= 2 ? aws_ecs_service.gateway[0].name : null
}

output "gateway_task_definition_arn" {
  value = var.enable_ecs_foundation && length(var.public_subnet_ids) >= 2 ? aws_ecs_task_definition.gateway[0].arn : null
}

output "identity_ecs_service_name" {
  value = var.enable_ecs_foundation && length(var.public_subnet_ids) >= 2 ? aws_ecs_service.identity[0].name : null
}

output "identity_target_group_arn" {
  value = var.enable_ecs_foundation && length(var.public_subnet_ids) >= 2 ? aws_lb_target_group.identity[0].arn : null
}

output "identity_telescope_url" {
  value = var.enable_ecs_foundation && length(var.public_subnet_ids) >= 2 ? "http://${aws_lb.public[0].dns_name}/identity/telescope" : null
}

output "gateway_health_url" {
  value = var.enable_ecs_foundation && length(var.public_subnet_ids) >= 2 ? "http://${aws_lb.public[0].dns_name}/healthz" : null
}

output "identity_login_url" {
  value = var.enable_ecs_foundation && length(var.public_subnet_ids) >= 2 ? "http://${aws_lb.public[0].dns_name}/api/v1/auth/login" : null
}

output "swagger_ecs_service_name" {
  value = var.enable_ecs_foundation && length(var.public_subnet_ids) >= 2 ? aws_ecs_service.swagger[0].name : null
}

output "swagger_url" {
  value = var.enable_ecs_foundation && length(var.public_subnet_ids) >= 2 ? "http://${aws_lb.public[0].dns_name}/docs/" : null
}
