# The-Venus-Project-website
Github repository for The Venus Project's website

In the `vagrant` directory are included configuration files and shell scripts for an automated creation of a full LAMP stack with all needed configurations and software packages for running the TVP website. Check the [vagrant documentation](https://www.vagrantup.com/docs/) for more details.

The file `newtvp-auto.php` automates the creation the TVP website from file and database backups.

The file `ngrok-auto.php` automates the changing of the TVP website's domain (on your local environment). This becomes useful when you often need to change the local domain that the site runs on, for example when you are using ngrok (since ngrok runs on a new domain every time you start it). ngrok comes quite handy when you are testing webhooks (e.g. from Stripe and Paypal) and you need to expose the local website to the Internet in order for the webhooks to reach it. For more information on ngrok, [see their docs](https://ngrok.com/docs).
