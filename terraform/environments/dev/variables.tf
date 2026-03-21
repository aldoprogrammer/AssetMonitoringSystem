variable "aws_region" {
  type    = string
  default = "ap-southeast-1"
}

variable "project_name" {
  type    = string
  default = "assetmonitoringsystem"
}

variable "environment" {
  type    = string
  default = "dev"
}

variable "vpc_id" {
  type = string
}

variable "private_subnet_ids" {
  type = list(string)
}

variable "db_security_group_ids" {
  type = list(string)
}

variable "mq_security_group_ids" {
  type = list(string)
}

variable "db_master_username" {
  type    = string
  default = "asset_monitoring_system"
}

variable "db_master_password" {
  type      = string
  sensitive = true
}

variable "alarm_topic_arn" {
  type    = string
  default = ""
}

variable "mq_username" {
  type    = string
  default = "asset_monitoring_system"
}

variable "mq_password" {
  type      = string
  sensitive = true
}
