variable "project_id" {
  default = "cc-tracker-172907"
}

variable "credentials" {
  default = "credentials.json"
}

variable "region" {
  default = "europe-west1"
}

variable "zone" {
  default = "europe-west1-d"
}

variable "instance_type" {
  default = "f1-micro"
}

variable "startup_script" {
  default = "startup.sh"
}

variable "ssh_user" {
  default = "ubuntu"
}

variable "ssh_pub_key" {
  default = "~/.ssh/id_rsa.pub"
}

provider "google" {
  project     = "${var.project_id}"
  region      = "${var.region}"
  credentials = "${file(var.credentials)}"
}

resource "google_compute_network" "tracker-network" {
  name                    = "tracker-network"
  auto_create_subnetworks = "true"
}

resource "google_compute_firewall" "tracker-firewall" {
  name    = "tracker-firewall"
  network = "tracker-network"

  allow {
    protocol = "icmp"
  }

  allow {
    protocol = "tcp"
    ports    = ["22", "9000"]
  }
}

resource "google_compute_instance" "tracker" {
  name         = "tracker"
  machine_type = "${var.instance_type}"
  zone         = "${var.zone}"

  disk {
    image = "tracker"
  }

  network_interface {
    network = "tracker-network"

    access_config {
      // Ephemeral IP
    }
  }

  metadata {
    sshKeys = "${var.ssh_user}:${file(var.ssh_pub_key)}"
  }

  metadata_startup_script = "${file(var.startup_script)}"
}

output "ip" {
  value = "${google_compute_instance.tracker.network_interface.0.access_config.0.assigned_nat_ip}"
}
