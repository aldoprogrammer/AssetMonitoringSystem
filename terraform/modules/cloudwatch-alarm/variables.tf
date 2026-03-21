variable "alarm_name" {
  type = string
}

variable "comparison_operator" {
  type = string
}

variable "evaluation_periods" {
  type = number
}

variable "metric_name" {
  type = string
}

variable "namespace" {
  type = string
}

variable "period" {
  type = number
}

variable "statistic" {
  type = string
}

variable "threshold" {
  type = number
}

variable "alarm_description" {
  type = string
}

variable "treat_missing_data" {
  type    = string
  default = "notBreaching"
}

variable "dimensions" {
  type    = map(string)
  default = {}
}

variable "alarm_actions" {
  type    = list(string)
  default = []
}
