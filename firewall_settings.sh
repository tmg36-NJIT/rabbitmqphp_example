#!/usr/bin/bash

#Deletes existing rules
echo "Deleting current firewall rules..."
sudo ufw --force reset

#Deny incoming connections - allow outgoing connections
echo "Creating default rules..."
sudo ufw default deny incoming
sudo ufw default allow outgoing

#Creating custom port rules to deny unnecessary ports 
echo "Setting port rules..."
sudo ufw allow 80/tcp comment "Allow HTTP"
sudo ufw allow 22/tcp comment "Allow SSH"
sudo ufw allow 443/tcp comment "Allow HTTPS"

#Firewall will remain on after restarts
echo "Enabling firewall..."
sudo ufw --force enable

#Status message
echo "Firewall configuration complete."
sudo ufw status verbose
