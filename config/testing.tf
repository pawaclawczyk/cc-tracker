variable "aws_access_key" {}
variable "aws_secret_key" {}
variable "aws_region" {
  default = "eu-west-1"
}

provider "aws" {
  access_key = "${var.aws_access_key}"
  secret_key = "${var.aws_secret_key}"
  region     = "${var.aws_region}"
}

resource "aws_key_pair" "deployer" {
  key_name   = "deployer-key"
  public_key = "ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQC2rv2xoKkJb9WLZThCkea4TySJhpHYBMu5cZ/8vjijgwU7mmwWQVa4mqrrYYFNzf6GiR0hId09/4mdllXysdoy05QkAdq0drJrxNB5t+bAuPDJXOwVIaKV8TLlcoDRy7M82eyyvB3COa+liccoPz+5E/R8YUsrPZLAIi9yJsjSMoQ6HdpLt68sSret5Q460+BgVJBTzjEbL89gUHdzYcCEDobeXF3rO1qo0Wnf3pCtFUnJKhVmAbTzCqwimEG61pLYvufai9EmIuA7B9X6J9ce9vrpqlsedIVGd6Pvr7kAKPnzT0nUfxFEuLVNGfihJF7H4CoO7h4/lQCl43ZQwawn pwc@MacBook-Pro-Pawe.local"
}

resource "aws_security_group" "tracker" {
  name = "tracker"
  description = "Traffic inside tracker security group"

  ingress {
    from_port = 0
    to_port = 0
    protocol = "-1"
    self = true
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

resource "aws_security_group" "ssh" {
  name = "ssh"
  description = "All SSH connections."

  ingress {
    from_port = 22
    to_port = 22
    protocol = "tcp"
    cidr_blocks = ["91.217.18.18/32"]
  }
}

resource "aws_instance" "example" {
  ami           = "ami-8551b7fc"
  instance_type = "t2.micro"

  key_name      = "deployer-key"

  security_groups = ["tracker", "ssh"]

  provisioner "remote-exec" {
    connection {
      user = "ubuntu"
      private_key = "${file("/Users/pwc/.ssh/id_rsa")}"
    }

    inline = [
      "~/tracker/vendor/bin/aerys --config ~/tracker/src/aerys.php -w 8 &"
    ]
  }
}

resource "aws_eip" "ip" {
  instance = "${aws_instance.example.id}"
}

output "ip" {
  value = "${aws_eip.ip.public_ip}"
}
