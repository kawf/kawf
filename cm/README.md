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
brew cask install virtualbox
brew install packer
brew cask install vagrant
```

## Pre-run
Before you can run packer or vagrant, you need to build the chef cookbooks locally using `berks`.  From the repository root:
```
cd ./cm
berks vendor ./berks-cookbooks
```
The above will create a new directory in the repository root like so: `./chef/berks-cookbooks`, inside that is the chef code used in the chef-solo provider configuration for both vagrant and packer.  You will also need to modify the chef attributes for the build, see Chef Attributes section below.

## Packer usage
If you are using an AWS profile, use the profile variable, otherwise use the keys variables.

```
cd ./cm
packer build \
    -var 'profile=your_profile_name' \
    -var 'region=us-west-2' \
    -var 'account_id=your_AWS_account_number' \
    -var 'vpc_id=your_AWS_vpc_id' \
    -var 'subnet_id=your_VPC_subnet_id' \
    -var 'ami_type=instance_type_for_packer' \
    packer.json
```

## Vagrant usage
* Install virtualbox `brew cask install virtualbox` if you haven't already. From repository root
```
cd ./cm
vagrant up
vagrant ssh
```

## Testing Locally with Vagrant
Kawf needs the domain set up correctly.  The current code will create the following local kawf server in Vagrant:
`10.111.111.111   local.kawf.org`

You will need to create a hosts file entry on your development environment to access the local version of kawf using http://local.kawf.org/ .  You can modify the IP address by editing the Vagrantfile, specifically:
`dev.vm.network :private_network, ip: "10.111.111.111"`

## Chef Attributes
Chef uses an attributes file `./kawf/chef/attributes/default.rb` to set parameters.  These will likely remain largely unchanged, but it's worth mentioning a few:
* sql_username - this is the username for the database connection
* sql_password - this is the password for the username for the database connection

Issues:
* Using special characters in the mysql user password has caused issues on build
* Changing the database_dir attribute does not modify the MySQL installation location, this is simply to reference where MySQL installation is
* Don't modify the deploy_dir for kawf, there are assumptions made that it will be in /var/www, though changing the deploy_dir will load kawf to a new directory (with errors to follow)

## Chef Run List in Vagrant & Packer
Vagrant and Packer are set up to use chef-solo provider.  This requires a run list, the order is important, note that on Vagrant it triggers a reboot, on Packer it does not as the image will boot on next launch.

## Supported Platforms
* Ubuntu 16.04

## License
* Apache 2.0
