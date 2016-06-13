#!/usr/bin/env bash

# Portions of this script taken from https://gist.github.com/rrosiek/8190550

# Variables
SITEURL=newtvp.example.com
DBUSER=root
DBPASSWD=root

# install and configure apache
apt-get update
apt-get install -y apache2
if ! [ -L /var/www ]; then
  rm -rf /var/www
  ln -fs /vagrant /var/www
fi
a2enmod rewrite > /dev/null 2>&1
a2enmod ssl > /dev/null 2>&1

# install php packages
apt-get install -y php5 php5-cli libapache2-mod-php5 php5-mcrypt
apt-get install -y php5-mysql
apt-get install -y php5-curl
apt-get install -y php5-xdebug
sed -i "s/error_reporting = .*/error_reporting = E_ALL/" /etc/php5/apache2/php.ini
sed -i "s/display_errors = .*/display_errors = On/" /etc/php5/apache2/php.ini
if grep "xdebug" /etc/php5/apache2/php.ini > /dev/null
	then
	   echo 'do nothing' >> /dev/null
	else
		echo '[xdebug]
xdebug.idekey="debugit"
xdebug.remote_host=10.0.2.2
xdebug.remote_port=10000
xdebug.remote_enable=1
xdebug.remote_autostart=0
xdebug.remote_handler="dbgp"' >> /etc/php5/apache2/php.ini
fi 

# install mysql and phpmyadmin
echo "mysql-server mysql-server/root_password password $DBPASSWD" | debconf-set-selections
echo "mysql-server mysql-server/root_password_again password $DBPASSWD" | debconf-set-selections
echo "phpmyadmin phpmyadmin/dbconfig-install boolean true" | debconf-set-selections
echo "phpmyadmin phpmyadmin/app-password-confirm password $DBPASSWD" | debconf-set-selections
echo "phpmyadmin phpmyadmin/mysql/admin-pass password $DBPASSWD" | debconf-set-selections
echo "phpmyadmin phpmyadmin/mysql/app-pass password $DBPASSWD" | debconf-set-selections
echo "phpmyadmin phpmyadmin/reconfigure-webserver multiselect none" | debconf-set-selections
apt-get -y install mysql-server-5.5 phpmyadmin > /dev/null 2>&1
a2enconf phpmyadmin > /dev/null 2>&1

# add virtual host for the site
cat > /etc/apache2/sites-enabled/000-default.conf <<EOF
<VirtualHost *:80>
    DocumentRoot /var/www/newtvp
	ServerName SITEURL
	ServerAdmin webmaster@localhost
	
    ErrorLog /var/www/newtvp_error.log
    CustomLog /var/www/newtvp_access.log combined
	
	<Directory /var/www/newtvp>
		AllowOverride All
		Options FollowSymLinks
	</Directory>
</VirtualHost>

<VirtualHost *:443>
    DocumentRoot /var/www/newtvp
	ServerName SITEURL
	ServerAdmin webmaster@localhost
	
	SSLEngine on
	SSLCertificateFile /etc/ssl/certs/ssl-cert-snakeoil.pem
	SSLCertificateKeyFile /etc/ssl/private/ssl-cert-snakeoil.key
	
    ErrorLog /var/www/newtvp_error.log
    CustomLog /var/www/newtvp_access.log combined
	
	<Directory /var/www/newtvp>
		AllowOverride All
		Options FollowSymLinks
	</Directory>
</VirtualHost>
EOF

# Add swap memory - https://www.digitalocean.com/community/tutorials/how-to-add-swap-on-ubuntu-14-04
if grep "/swapfile" /etc/fstab > /dev/null
	then
	   echo 'do nothing' >> /dev/null
	else
		fallocate -l 4G /swapfile
		chmod 600 /swapfile
		mkswap /swapfile
		swapon /swapfile
		echo '/swapfile   none    swap    sw    0   0' >> /etc/fstab
fi 

# install other useful packages
apt-get install -y unzip

# Restart apache and mysql
service apache2 restart > /dev/null 2>&1
service mysql restart > /dev/null 2>&1
