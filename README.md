# The-Venus-Project-website
Github repository for The Venus Project's website


## Docker (Recommended)

If you choose to use Docker for your working environment you'll need first to install Docker in your machine:
https://docs.docker.com/engine/installation/

Go through all get started steps to be familiar with docker:
https://docs.docker.com/get-started/

Afterwards you should follow these steps for using Docker in your The Venus Project development site:

- Copy `.env.dist` file to `.env` and fill in ONLY missing values which you can find in your original `wp-config.php`.
- Copy your original `wp-content` directory from server or backup to `docker/www/wp-content/` directory.
- Run `$ sudo docker-compose up`. You should see that `wordpress` container has been started successfully: `NOTICE: ready to handle connections`.
- In the new terminal import DB backups as follows (order matters, because dumps overlap on `civicrm_*` tables):
  + `sudo docker-compose run --rm -v /full/path/to/_wordpressdb.sql:/dump.sql db sh -c 'mysql -hdb -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" < /dump.sql'`
  + `sudo docker-compose run --rm -v /full/path/to/_civicrmdb.sql:/dump.sql db sh -c 'mysql -hdb -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE" < /dump.sql'`
- Run `$ sudo docker-compose run --rm wordpress php tools/init.php`.
- Run `$ sudo docker-compose run --rm wordpress php tools/update-site-domain.php localhost:8080`.
- Grant write permissions to the web-server, e.g.:
  + `HTTPDUSER=www-data`
  + `sudo setfacl -dR -m u:"$HTTPDUSER":rwX -m u:$(whoami):rwX www`
  + `sudo setfacl -R -m u:"$HTTPDUSER":rwX -m u:$(whoami):rwX www`

Now you have several endpoints to work with:
- `http://localhost:8080/` - The Venus Project development website.
- `http://localhost:8081/` - Adminer DB interface to make DB administration easier.
- `http://localhost:8082/` - MailHog, mail debugger. All emails from WordPress and CiviCRM are redirected to MailHog SMTP server (see `mail` container) and are displayed on this nice web interface.

To setup your admin password go to My Account -> Lost password and restore password for the user `thevenusproject`. Check your inbox in MailHog and follow the instructions.

More info in these tutorials:
- https://codeable.io/wordpress-developers-intro-docker/
- https://codeable.io/wordpress-developers-intro-to-docker-part-two/



## Vagrant
Vagrant allows you to run a virtual machine (vm) on your computer and do your development work within the vm. It can automate the installation and configuration of an operating system in the vm, including the installation and configuration of any software packages you want. It also can keep folders in sync between your host OS and your guest OS (on the vm). 

In the `vagrant` directory here are included configuration files and shell scripts for an automated creation of a full LAMP stack with all needed configurations and software packages for running the TVP website.

To get started with Vagrant:  
1. [Install the Vagrant package](https://www.vagrantup.com/downloads.html)
2. [Install VirtualBox](https://www.virtualbox.org/wiki/Downloads) - this provides the vm functionality
3. Go through the [Vagrant Getting Started Guide](https://www.vagrantup.com/intro/getting-started/) (this is only for learning)
4. Place the `Vagrantfile`, `bootstrap.sh` and `startup.sh` files in the root Vagrant directory on your host OS
    - `Vagrantfile` contains general setup instructions for your vagrant box
    - `bootstrap.sh` instructions get executed when provisioning the vagrant box (i.e. installing or reinstalling the guest OS)
    - `startup.sh` instructions get executed each time you start the guest OS (i.e. on `vagrant up`)
4. Create on your host OS the directories that will be synced between host and guest OS:
    - Create `var_log` inside the root Vagrant directory on your host OS.  
    - Create `sites-available` inside the root Vagrant directory on your host OS.
    - Put the `000-default.conf` file inside `sites-available`. This is Ubuntu's file for virtual hosts.
5. Add newtvp.example.com to your hosts file on your host OS, map it to 127.0.0.1 ([instructions](https://support.rackspace.com/how-to/modify-your-hosts-file/))
6. Create `newtvp` directory inside the root Vagrant directory on your host OS.

Troubleshooting  
- If you get the error Errno::EADDRNOTAVAIL when doing `vagrant up`, see [this comment](https://github.com/mitchellh/vagrant/issues/3031#issuecomment-288570525).

Debugging:  
- [How to configure Xdebug in PhpStorm through Vagrant](https://danemacmillan.com/how-to-configure-xdebug-in-phpstorm-through-vagrant/#content-remote-debugger-v8)
- [Some more info on debugging webhooks](http://www.devinzuczek.com/anything-at-all/i-have-xdebug-and-php-is-slow/ )
- [Debugging with PHPStorm and the PODS framework](https://docs.google.com/document/d/1WOzgYlU8PnJ99ScRePumfUwg645vmuE4v5MyshOYF4M/edit)

Path mappings:  
Q: In phpstorm, I have to set up path mappings. does this mean that I have to keep two repositories - one in my host OS and one in the guest OS? even though the one in the guest OS is shared between the two?  
A: Vagrant shares the folders, so you only maintain one repo, but for the remote debugging to work, it needs to know where to map them to inside of vagrant

## Automation scripts
The file `tvp-auto.php` automates the creation of the TVP website from filesystem and database backups.

The file `ngrok-auto.php` automates the changing of the TVP website's domain (on your local environment). This becomes useful when you need to often change the local domain that the site runs on, for example when you are using a tool like ngrok (since ngrok runs on a new domain every time you start it). ngrok comes quite handy when you are testing webhooks (e.g. from Stripe and Paypal) and you need to expose the local website to the Internet in order for the webhooks to reach it. For more information on ngrok, [see their docs](https://ngrok.com/docs).

If you use docker, you can run `ngrok` from a docker image:
- Run `$ docker run --rm -it --net=host wernight/ngrok ngrok http localhost:8080`
- Run `$ docker-compose run --rm wordpress php tools/update-site-domain.php yourtempdomain.ngrok.io`
- `ngrok` web interface is accessible by visiting `http://localhost:4040`
- When you are done using `ngrok`, restore your site URL to localhost: `$ docker-compose run --rm wordpress php tools/update-site-domain.php localhost:8080`

## Automated acceptance tests
The `tests.zip` file contains a backup of the git repository that we have on Bitbucket. It contains our automated acceptance tests.
