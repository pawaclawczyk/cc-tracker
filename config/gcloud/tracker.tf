variable "startup_script" {
  default = "startup.sh"
}

provider "google" {
  project     = "cc-tracker-172907"
  region      = "europe-west1"
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
  machine_type = "f1-micro"
  zone         = "europe-west1-d"

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
    sshKeys = "ubuntu:ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABAQC2rv2xoKkJb9WLZThCkea4TySJhpHYBMu5cZ/8vjijgwU7mmwWQVa4mqrrYYFNzf6GiR0hId09/4mdllXysdoy05QkAdq0drJrxNB5t+bAuPDJXOwVIaKV8TLlcoDRy7M82eyyvB3COa+liccoPz+5E/R8YUsrPZLAIi9yJsjSMoQ6HdpLt68sSret5Q460+BgVJBTzjEbL89gUHdzYcCEDobeXF3rO1qo0Wnf3pCtFUnJKhVmAbTzCqwimEG61pLYvufai9EmIuA7B9X6J9ce9vrpqlsedIVGd6Pvr7kAKPnzT0nUfxFEuLVNGfihJF7H4CoO7h4/lQCl43ZQwawn pwc@MacBook-Pro-Pawe.local"
  }

  metadata_startup_script = "${file(var.startup_script)}"
}

output "ip" {
  value = "${google_compute_instance.tracker.network_interface.0.access_config.0.assigned_nat_ip}"
}
