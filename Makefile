AWS_CONFIG_DIR     = config/aws
AMI_CONFIG         = $(AWS_CONFIG_DIR)/tracker_ami.json

AWS_INSTANCE_TYPE ?= t2.micro

PACKER_FLAGS       = -var 'aws_access_key=$(AWS_ACCESS_KEY_ID)' -var 'aws_secret_key=$(AWS_SECRET_ACCESS_KEY)'
TERRAFORM_FLAGS    = -input=false -var 'aws_access_key=$(AWS_ACCESS_KEY_ID)' -var 'aws_secret_key=$(AWS_SECRET_ACCESS_KEY)' -var 'instance_type=$(AWS_INSTANCE_TYPE)'

.PHONY: build_ami
build_ami:
	packer validate                 $(AMI_CONFIG)
	packer build    $(PACKER_FLAGS) $(AMI_CONFIG)

.PHONY: build_aws
build_aws:
	terraform plan  $(TERRAFORM_FLAGS) $(AWS_CONFIG_DIR)
	terraform apply $(TERRAFORM_FLAGS) $(AWS_CONFIG_DIR)

.PHONY: destroy_aws
destroy_aws:
	terraform plan -destroy $(TERRAFORM_FLAGS) $(AWS_CONFIG_DIR)
	terraform destroy       $(TERRAFORM_FLAGS) $(AWS_CONFIG_DIR)
