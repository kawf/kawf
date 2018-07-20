# kawf

This cookbook contains recipes specific to installing PHP on Apache for Kawf running on Ubuntu 16.04.

## OSX Prerequisites
The following are prerequisites to get this to work on your mac using homebrew
* install homebrew / git
* install chefdk (mainly for berkshelf CLI): https://downloads.chef.io/chefdk , or if you only want berkshelf, `gem install berkshelf`
* install virtualbox / vagrant / packer
```
/usr/bin/ruby -e "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/master/install)"
brew install git
brew cask install virtualbox`
brew install packer
brew cask install vagrant
```

## Pre-run
Before you can run packer or vagrant, you need to build the chef cookbooks locally using `berks`.  From the repository root:
```
cd ./chef
berks vendor ./berks-cookbooks
```
The above will create a new directory in the repository root like so: `./chef/berks-cookbooks`, inside that is the chef code used in the chef-solo provider configuration for both vagrant and packer.  You will also need to modify the chef attributes for the build, see Chef Attributes section below.

## Packer usage
You will need to set variables values for AWS and a deploy_key for wayot (otherwise you need to pull from kawf). If you don't have access to AWS, just skip to vagrant.  If you are using an AWS profile, use the profile variable, otherwise use the keys variables.

```
cd ./chef
packer build \
    -var 'profile=your_profile_name' \
    -var 'region=us-west-2' \
    -var 'account_id=your_AWS_account_number' \
    -var 'vpc_id=your_AWS_vpc_id' \
    -var 'subnet_id=your_VPC_subnet_id' \
    -var 'ami_type=instance_type_for_packer' \
    -var 'deploy_key=wayot_private_deploy_key' \
    packer.json
```

## Vagrant usage
* Install virtualbox `brew cask install virtualbox` if you haven't already. You will need to set variables values and a deploy_key for wayot, otherwise you need to pull from kawf.  From repository root
```
cd ./chef
vim custom.json
vagrant up
vagrant ssh
```

## Testing Locally with Vagrant
Kawf needs the domain set up correctly.  The current code will create the following local kawf server in Vagrant:
`10.111.111.111   local.wayot.org`

You will need to create a hosts file entry on your development environment to access the local version of kawf using http://local.wayot.org/ .  You can modify the IP address by editing the Vagrantfile, specifically:
`dev.vm.network :private_network, ip: "10.111.111.111"`

## Chef Attributes
Chef uses an attributes file `./kawf/chef/attributes/default.rb` to set parameters.  These will likely remain largely unchanged, but it's worth mentioning a few:
* search - set to `true` if you want search installed as well
* wayot - set to `true` if we are cloning wayot instead of kawf
* restore - set to `true` if we are restoring wayot from S3 backup
* sql_username - this is the username for the database connection
* sql_password - this is the password for the username for the database connection

Issues:
* Using special characters in the mysql user password has caused issues on build
* Changing the database_dir attribute does not modify the MySQL installation location, this is simply to reference where MySQL installation is
* Don't modify the deploy_dir for kawf, there are assumptions made that it will be in /var/www, though changing the deploy_dir will load kawf to a new directory (with errors to follow)

## Chef Run List in Vagrant & Packer
Vagrant and Packer are set up to use chef-solo provider.  This requires a run list, the order is important, note that on Vagrant it triggers a reboot, on Packer it does not as the image will boot on next launch. To restore wayot from backup, you will need a modified run list.

## Chef Recipes
Chef uses recipes to configure resources, here they are with descriptions
* aws - installs the AWS CLI and configures keys, this is only used for restoring wayot
* nginxphp7 - installs nginx and php7, does not currently work with kawf
* php5 - installs apache and php5
* php7 - installs apache and php7 (this is basically where all the real work is happening)
* reboot - triggers a reboot post security updates install
* restore - restores wayot database from backup
* security_updates - installs security updates on ubuntu, does not restart

## Chef Templates
Chef uses templates (ERB) to create files with dynamic values.  In the case of kawf, we are modifying the config.inc and setup.inc files with local overrides.  Specific to Apache2, we are creating a custom sites-available kawf.conf file for the VHOST.  If you want to modify Apache2 config for kawf, modify the kawf.conf template.  At present, Apache2 installs the latest version.

## Supported Platforms
* Ubuntu 16.04

## License
* Apache 2.0
