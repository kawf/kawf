#
# Cookbook Name:: kawf
# Attributes:: default
#
# Apache 2.0
#
# All rights reserved - Do Not Redistribute
#
##
# Check to see if we are building in Vagrant, or not
if Dir.exists? "/vagrant"
  default[:kawf][:vagrant]                          = true
  default[:kawf][:home]                             = '/home/vagrant'
  default[:kawf][:user]                             = 'vagrant'
  default[:kawf][:group]                            = 'vagrant'
  default[:kawf][:sql_host]                         = 'localhost'
  default[:kawf][:database_dir]                     = '/var/lib/mysql/kawf'
else
  default[:kawf][:vagrant]                          = false
  default[:kawf][:home]                             = '/home/ubuntu'
  default[:kawf][:user]                             = 'ubuntu'
  default[:kawf][:group]                            = 'ubuntu'
  default[:kawf][:sql_host]                         = 'search.chjqhtfjbqkx.us-west-2.rds.amazonaws.com'
end
# git repo settings
# repository 'git://github.com/kawf/kawf.git'
# revision 'master'
# git@bitbucket.org:kawf/wayot.git
# master
default[:kawf][:repository]                         = 'git@bitbucket.org:kawf/wayot.git'
default[:kawf][:revision]                           = 'master'
default[:kawf][:deploy_key]                         = 'wayot'
default[:kawf][:nye]                                = 'ssh-rsa AAAAB3NzaC1yc2EAAAABIwAAAQEAwGuir9/WdENQjGyFPEEjeKpwgjypiQdR0vltmGtrv20yXV0H2NRWQZYaEFtqC1ilDbueKqlxYMBLvJRR1znWNQS5+fuKefDqK5kz4iHrBz2bW4+bxsiwj/fywlluHjqZx+XQC2TcXZwDg5J7NGq3Dk3lHULVa6B4fqHnd2yo2UsDuU1BlUD3hA2KHCGr7iNL4/vQTN13q+HUlvQs3bywH9wua9vkcm2FhOKhSZtfIe8qSN0eEmIYLHXeTFnw12/Y8o50DWC/FX7+0AGe1n/GW01LbFsAetfMCBJfnSLYxntTXbOwU+TlxWqgOspyz4VzTBwp5e0KP0qXzVYt/viA6w== nyet@stupid'
default[:kawf][:christian]                          = ''
# kawf settings
default[:kawf][:restore]                            = false
default[:kawf][:search]                             = false
default[:kawf][:deploy_dir]                         = '/var/www/kawf'
default[:kawf][:apache_user]                        = 'www-data'
default[:kawf][:apache_group]                       = 'www-data'
default[:kawf][:contact]                            = 'info@wayot.org'
default[:kawf][:alias]                              = 'search'
default[:kawf][:domain]                             = 'wayot.org'
default[:kawf][:docroot]                            = "#{node[:kawf][:deploy_dir]}/config"
default[:kawf][:server_aliases]                     = ["#{node[:kawf][:alias]}.#{node[:kawf][:domain]}"]
default[:kawf][:server_name]                        = "#{node[:kawf][:alias]}.#{node[:kawf][:domain]}"
default[:kawf][:bounce_host]                        = "bounce.#{node[:kawf][:domain]}"
default[:kawf][:cookie_host]                        = ".#{node[:kawf][:domain]}"
default[:kawf][:imgur_client_id]                    = ''
default[:kawf][:imgur_client_secret]                = ''
default[:kawf][:sql_username]                       = 'www-data'
default[:kawf][:sql_password]                       = 'rtrzhc2aNc6VAQftxh'
default[:kawf][:db_user]                            = 'mysql'
default[:kawf][:db_group]                           = 'mysql'
default[:kawf][:database]                           = 'kawf'
default[:kawf][:database_dir]                       = '/var/lib/mysql/kawf'
