resource "aws_db_subnet_group" "this" {
  name       = "${var.name}-subnet-group"
  subnet_ids = var.subnet_ids

  tags = merge(var.tags, {
    Name = "${var.name}-subnet-group"
  })
}

resource "aws_db_instance" "this" {
  identifier              = var.name
  allocated_storage       = var.allocated_storage
  engine                  = "postgres"
  engine_version          = var.engine_version
  instance_class          = var.instance_class
  db_name                 = var.database_name
  username                = var.username
  password                = var.password
  db_subnet_group_name    = aws_db_subnet_group.this.name
  vpc_security_group_ids  = var.security_group_ids
  skip_final_snapshot     = true
  deletion_protection     = false
  backup_retention_period = var.backup_retention_period
  storage_encrypted       = true
  multi_az                = var.multi_az
  publicly_accessible     = false

  tags = merge(var.tags, {
    Name = var.name
  })
}
