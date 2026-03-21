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
