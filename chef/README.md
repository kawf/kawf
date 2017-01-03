# kawf

This cookbook contains recipes specific to installing PHP on Apache for Kawf running on Ubuntu 14.04 LTS (trusty).


## Instructions
Install virtualbox on your computer.  CWD to ./kawf/chef and verify you see the Vagrantfile in the working directory.  Don't edit the Vagrantfile unless you know what you are doing.

run vagrant up

It will:
* Download Ubuntu 14.04 LTS image
* apt-get update using community cookbook
* install Apache2 using community cookbook and create kawf web app
* install git client
* create application directories
* install MySQL server (5.7)
* install PHP MySQL
* git clone from kawf repo
* create kawf config/setup inc templates
* fix PHP time for UTC
* install composer for PHP
* load AWS PHP SDK using composer
* create MySQL user, database and grants
* optionally, install AWS CLI and configure an AWS credentials profile on the system (for S3)
* optionally, restore the current wayot backup from S3 to MySQL


## Testing Locally with Vagrant
Kawf needs the domain setup correctly.  The current code will create the following local kawf server in Vagrant:
192.168.1.111   local.wayot.org

You will need to create a host file entry on your development environment to access the local version of kawf using http://local.wayot.org/ .  The other options is to change the IP and domain and server values in the attributes file.


## Chef Run List in Vagrant
Vagrant is setup to use Chef provider.  This requires a run list, the order is important:

At a minimum, you would need:
["recipe[apt::default]","recipe[apache2::default]","recipe[kawf::install]"]

We have a longer run list to support the AWS CLI installation and MySQL database restore, but these are not supported in the publicly available kawf version.


## Chef Recipes
Chef uses recipes to configure resources.  There are 3 resources for the kawf cookbook:
* aws
* install
* restore

They are self explanatory, aws sets up the AWS CLI and credentials file and ENV VARS.  install loads the kawf specific items and creates the apache2 kawf web app.  restore will download and install the MySQL wayot backup.

## Chef Attributes
Chef uses an attributes file to override community cookbook values for Apache2 as well as configure other things like users, apache2 user, mysql user, etc.  This is the ./kawf/chef/attributes/default.rb file.  If you know the resources of the server, you can modify Apache2 configuration to match the available resources on the server.  You can also use this to change usernames and passwords, deploy directories, etc.  That said, I wouldn't change any of it unless you know what each value impacts.

Issues:
* Using special characters in the mysql user password has caused issues in build
* Changing the database_dir attribute does not modify the MySQL installation location, this is simply to store where MySQL defaults the installation location to.
* Don't modify the deploy_dir for kawf, there are assumptions made that it will be in /var/www, though changing the deploy_dir will load kawf to a new directory (with errors to follow)
* The S3 keys are for restoring the wayot backup, you don't need them to simply mess around with kawf.


## Chef Templates
Chef uses templates to (ERB) to create files with dynamic values.  In the case of kawf, we are modifying the config.inc and setup.inc files.  Specific to Apache2, we are creating a custom sites-available kawf.conf file for the VHOST.  If you want to modify Apache2 config for kawf, modify the kawf.conf template.  At present, Apache2 installs the latest version.


## Supported Platforms

* Ubuntu 14.04


## License

* Apache 2.0
