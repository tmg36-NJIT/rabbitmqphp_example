#!/usr/bin/bash

#Deletes existing rules
echo "Deleting current firewall rules..."
sudo ufw --force reset

#Deny incoming connections - allow outgoing connections
echo "Creating default rules..."
sudo ufw default deny incoming
sudo ufw default allow outgoing

sudo ufw logging on low #Adds logging

#Public Access Ports
echo "Setting port rules..."
sudo ufw allow 80/tcp comment "Allow HTTP"
sudo ufw allow 443/tcp comment "Allow HTTPS"

#Local System Service Ports
sudo ufw allow in on lo to any port 53 comment "Allow local DNS"

#Internal Network Access Ports
sudo ufw allow from 192.168.192.0/24 to any port 9993/udp comment "Allow ZeroTier Internally"
sudo ufw allow from 192.168.192.0/24 to any port 22/tcp comment "Allow SSH Internally"
sudo ufw allow from 192.168.192.0/24 to any port 5672 comment "Allow RabbitMQ Internally"
sudo ufw allow from 192.168.192.0/24 to any port 15672 comment "Allow RabbitMQ MGMT Internally"
sudo ufw allow from 192.168.192.0/24 to any port 25672/tcp comment "Allow RabbitMQ Cluster Internally"
sudo ufw allow from 192.168.192.0/24 to any port 4369/tcp comment "Allow Erlang Node Discovery Internally"
sudo ufw allow from 192.168.192.0/24 to any port 19999/tcp comment "Allow Netdata Dashboard Internally"
sudo ufw allow from 192.168.192.0/24 to any port 8125/tcp comment "Allow Netdata/statsd Internally"
sudo ufw allow from 192.168.192.0/24 to any port 8125/udp comment "Allow Netdata/statsd Internally"
sudo ufw allow from 192.168.192.0/24 to any port 3306/tcp comment "Allow MySQL Server Internally"
sudo ufw allow from 192.168.192.0/24 to any port 33060/tcp comment "Allow MySQL Plugin Internally"
sudo ufw allow from 192.168.192.0/24 to any port 5353/udp comment "Allow mDNS/Avahi Internally"

#Deny Unused or Unnecessary Ports
sudo ufw deny 23/tcp comment "Deny Telnet"
sudo ufw deny 25/tcp comment "Deny SMTP"
sudo ufw deny 21/tcp comment "Deny FTP"
sudo ufw deny 110/tcp comment "Deny POP3"
sudo ufw deny 143/tcp comment "Deny IMAP"
sudo ufw deny 631/tcp comment "Deny Printing (CUPS)"

#Firewall will remain on after restarts
echo "Enabling firewall..."
sudo ufw --force enable

#Status message
echo "Firewall configuration complete."
sudo ufw status verbose
