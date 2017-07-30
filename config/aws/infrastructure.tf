variable "aws_access_key" {}
variable "aws_secret_key" {}
variable "aws_region" {
  default = "eu-west-1"
}

variable "instance_type" {
  default = "c4.xlarge"
}

variable "ssh_user" {
  default = "ubuntu"
}

variable "ssh_public_key" {
  default = "~/.ssh/id_rsa.pub"
}

variable "ssh_private_key" {
  default = "~/.ssh/id_rsa"
}

provider "aws" {
  access_key = "${var.aws_access_key}"
  secret_key = "${var.aws_secret_key}"
  region     = "${var.aws_region}"
}

resource "aws_key_pair" "tracker" {
  key_name   = "tracker"
  public_key = "${file(var.ssh_public_key)}"
}

resource "aws_security_group" "tracker" {
  name = "tracker"

  ingress {
    from_port = 0
    to_port = 0
    protocol = "-1"
    self = true
  }

  ingress {
    from_port = 22
    to_port = 22
    protocol = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  ingress {
    from_port = 9000
    to_port = 9000
    protocol = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  egress {
    from_port = 0
    to_port = 0
    protocol = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
}

data "aws_ami" "tracker" {
  most_recent = true

  name_regex = "^tracker-\\d+"

  owners = ["self"]
}

resource "aws_instance" "tracker" {
  ami           = "${data.aws_ami.tracker.id}"
  instance_type = "${var.instance_type}"

  key_name      = "tracker"

  security_groups = ["tracker"]

  tags {
    Name = "tracker"
  }
}

resource "aws_eip" "ip" {
  instance = "${aws_instance.tracker.id}"
}

output "ip" {
  value = "${aws_eip.ip.public_ip}"
}
