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
  default[:kawf][:sql_host]                         = ''
end
# git repo settings
default[:kawf][:repository]                         = 'git://github.com/kawf/kawf.git'
default[:kawf][:revision]                           = 'master'
default[:kawf][:deploy_key]                         = ''
# kawf settings
default[:kawf][:restore]                            = false
default[:kawf][:search]                             = false
default[:kawf][:deploy_dir]                         = '/var/www/html'
default[:kawf][:apache_user]                        = 'www-data'
default[:kawf][:apache_group]                       = 'www-data'
default[:kawf][:contact]                            = 'info@kawf.org'
default[:kawf][:alias]                              = ''
default[:kawf][:domain]                             = 'kawf.org'
default[:kawf][:docroot]                            = '/var/www/html/config'
default[:kawf][:server_aliases]                     = ["#{node[:kawf][:alias]}.#{node[:kawf][:domain]}"]
default[:kawf][:server_name]                        = "#{node[:kawf][:alias]}.#{node[:kawf][:domain]}"
default[:kawf][:bounce_host]                        = 'bounce.kawf.org'
default[:kawf][:cookie_host]                        = ".#{node[:kawf][:domain]}"
default[:kawf][:imgur_client_id]                    = ''
default[:kawf][:imgur_client_secret]                = ''
default[:kawf][:sql_username]                       = ''
default[:kawf][:sql_password]                       = ''
default[:kawf][:db_user]                            = ''
default[:kawf][:db_group]                           = ''
default[:kawf][:database]                           = 'kawf'
default[:kawf][:database_dir]                       = '/var/lib/mysql/kawf'
