# The-Venus-Project-website
Github repository for The Venus Project's website

## Vagrant
In the `vagrant` directory are included configuration files and shell scripts for an automated creation of a full LAMP stack with all needed configurations and software packages for running the TVP website. Check the [vagrant documentation](https://www.vagrantup.com/docs/) for more details.

Some additional info about Vagrant:  
How to configure Xdebug in PhpStorm through Vagrant: https://danemacmillan.com/how-to-configure-xdebug-in-phpstorm-through-vagrant/#content-remote-debugger-v8
Some more info on debugging webhooks: http://www.devinzuczek.com/anything-at-all/i-have-xdebug-and-php-is-slow/ 
Debugging with PHPStorm and the PODS framework: https://docs.google.com/document/d/1WOzgYlU8PnJ99ScRePumfUwg645vmuE4v5MyshOYF4M/edit

Path mappings:  
Q: in phpstorm, I have to set up path mappings. does this mean that I have to keep two repositories - one in my host OS and one in the guest OS? even though the one in the guest OS is shared between the two?  
A: vagrant shares the folders, so you only maintain one repo, but for the remote debugging to work, it needs to know where to map them to inside of vagrant

## Docker

If you choose to use Docker for your working environment you'll need first to install Docker in your machine:
https://docs.docker.com/engine/installation/

Afterwards you should follow these steps for using Docker in your WordPress site:

- Create an .env file and put in values that you have in your existing wp-config.php.
- (Optionally) Delete wp-admin, wp-includes and all .php files in the project directory. Remember, WordPress core is provided by the image!
- (Optionally) Export the database from where you've used it until now, put it into wp-content, log into the container ($ docker-compose run wordpress /bin/bash, cd to /var/www/wp-content where the SQL dump should be and import it into our mysql container)
- Run $ docker-compose up

More info in these tutorials:
- https://codeable.io/wordpress-developers-intro-docker/
- https://codeable.io/wordpress-developers-intro-to-docker-part-two/

## Automation scripts
The file `newtvp-auto.php` automates the creation the TVP website from file and database backups.

The file `ngrok-auto.php` automates the changing of the TVP website's domain (on your local environment). This becomes useful when you often need to change the local domain that the site runs on, for example when you are using ngrok (since ngrok runs on a new domain every time you start it). ngrok comes quite handy when you are testing webhooks (e.g. from Stripe and Paypal) and you need to expose the local website to the Internet in order for the webhooks to reach it. For more information on ngrok, [see their docs](https://ngrok.com/docs).

## Automated acceptance tests
The `tests.zip` file contains a backup of the git repository that we have on Bitbucket. It contains our automated acceptance tests.
