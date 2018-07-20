#
# Cookbook Name:: kawf
# Attributes:: default
#
# Apache 2.0
#
# All rights reserved - Do Not Redistribute
#
##

# Check to see if we are building in Vagrant or Packer
if Dir.exists? "/vagrant"
  default[:kawf][:vagrant]                          = true
  default[:kawf][:home]                             = '/home/vagrant'
  default[:kawf][:user]                             = 'vagrant'
  default[:kawf][:group]                            = 'vagrant'
  default[:kawf][:sql_host]                         = 'localhost'
  default[:kawf][:database_dir]                     = '/var/lib/mysql/kawf'
  default[:kawf][:alias]                            = 'local'
else
  default[:kawf][:vagrant]                          = false
  default[:kawf][:home]                             = '/home/ubuntu'
  default[:kawf][:user]                             = 'ubuntu'
  default[:kawf][:group]                            = 'ubuntu'
  default[:kawf][:sql_host]                         = 'search.chjqhtfjbqkx.us-west-2.rds.amazonaws.com'
  default[:kawf][:alias]                            = 'search'
end

# kawf settings
default[:kawf][:search]                             = false
default[:kawf][:wayot]                              = false
default[:kawf][:restore]                            = false
default[:kawf][:deploy_dir]                         = '/var/www/kawf'
default[:kawf][:apache_user]                        = 'www-data'
default[:kawf][:apache_group]                       = 'www-data'
default[:kawf][:contact]                            = 'info@wayot.org'
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

# git repo settings
if node[:kawf][:wayot]
  default[:kawf][:repository]                         = 'git@bitbucket.org:kawf/wayot.git'
  default[:kawf][:revision]                           = 'master'
  default[:kawf][:deploy_key]                         = 'wayot'
else
  default[:kawf][:repository]                         = 'git://github.com/kawf/kawf.git'
  default[:kawf][:revision]                           = 'master'
  default[:kawf][:deploy_key]                         = 'wayot'
end
