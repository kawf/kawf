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
default[:kawf][:deploy_dir]                         = '/var/www/kawf'
default[:kawf][:apache_user]                        = 'www-data'
default[:kawf][:apache_group]                       = 'www-data'
default[:kawf][:alias]                              = 'local'
default[:kawf][:domain]                             = 'wayot.org'
default[:kawf][:bounce_host]                        = 'bounce.kawf.org'
default[:kawf][:cookie_host]                        = '.wayot.org'
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
# apache2 override attributes
default[:apache][:contact]                          = 'info@wayot.org'
default[:apache][:timeout]                          = 40
default[:apache][:keepalive]                        = 'On'
default[:apache][:keepalivetimeout]                 = 2
default[:apache][:timeout]                          = 300
default[:apache][:default_modules]                  = ["mod_php5"]
default[:apache][:mpm]                              = 'prefork'
default[:apache][:prefork][:startservers]           = 10
default[:apache][:prefork][:minspareservers]        = 3
default[:apache][:prefork][:maxspareservers]        = 5
default[:apache][:prefork][:serverlimit]            = 20
default[:apache][:prefork][:maxrequestworkers]      = 10
default[:apache][:prefork][:maxconnectionsperchild] = 10
default[:apache][:worker][:startservers]            = 4
default[:apache][:worker][:serverlimit]             = 16
default[:apache][:worker][:minsparethreads]         = 64
default[:apache][:worker][:maxsparethreads]         = 192
default[:apache][:worker][:maxrequestworkers]       = 1024
default[:apache][:worker][:maxconnectionsperchild]  = 10
