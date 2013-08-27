#!/bin/bash

TestId=###TESTID###
VmId=###VMID###

URL="http://<your_server_name>/openstack-boottime/index.php/ready/"

url_ready=$URL$TestId"/"$VmId"/1"
url_configured=$URL$TestId"/"$VmId"/2"

# Before contextualization
curl url_ready

# This file is a template. You can put all your contextualization stuff here.

# After contextualization
curl $url_configured