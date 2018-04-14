#
# Cookbook Name:: kawf
# Attributes:: default
#
# Apache 2.0
#
# All rights reserved - Do Not Redistribute
#
##
# Check to see if we are building in Vagrant, or Ubuntu
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
  default[:kawf][:sql_host]                         = 'some_database_endpoint'
end
# git repo settings
# repository 'git://github.com/kawf/kawf.git'
# revision 'master'
default[:kawf][:repository]                         = 'git://github.com/rfackett/kawf.git'
default[:kawf][:revision]                           = 'vagrant_chef'
# kawf settings
default[:kawf][:deploy_dir]                         = '/var/www/html'
default[:kawf][:apache_user]                        = 'www-data'
default[:kawf][:apache_group]                       = 'www-data'
default[:kawf][:contact]                            = 'info@wayot.org'
default[:kawf][:alias]                              = 'local'
default[:kawf][:domain]                             = 'wayot.org'
default[:kawf][:docroot]                            = '/var/www/html/config'
default[:kawf][:server_aliases]                     = ["#{node[:kawf][:alias]}.#{node[:kawf][:domain]}"]
default[:kawf][:server_name]                        = "#{node[:kawf][:alias]}.#{node[:kawf][:domain]}"
default[:kawf][:bounce_host]                        = 'bounce.kawf.org'
default[:kawf][:cookie_host]                        = ".#{node[:kawf][:domain]}"
default[:kawf][:sql_username]                       = 'www-data'
default[:kawf][:sql_password]                       = 'changeMe'
default[:kawf][:db_user]                            = 'mysql'
default[:kawf][:db_group]                           = 'mysql'
default[:kawf][:database]                           = 'kawf'
default[:kawf][:database_dir]                       = '/var/lib/mysql/kawf'
default[:kawf][:s3_access]                          = ''
default[:kawf][:s3_secret]                          = ''
default[:kawf][:s3_region]                          = ''
default[:kawf][:s3_location]                        = ''
default[:kawf][:s3_backup]                          = ''
