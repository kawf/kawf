#
# Cookbook Name:: kawf
# Recipe:: install
#
# All rights reserved - Do Not Redistribute
#
##
include_recipe 'git'

Chef::Log.info("install git client for deploys")
git_client 'default' do
  action :install
end

Chef::Log.info("install required packages")
package ['unzip', 'curl'] do
  action :install
end

directory '/var' do
  owner node['kawf']['apache_user']
  group node['kawf']['apache_group']
  mode 0755
  action :create
end

directory '/var/www' do
  owner node['kawf']['apache_user']
  group node['kawf']['apache_group']
  mode 0755
  action :create
end

directory "#{node['kawf']['deploy_dir']}" do
  owner node['kawf']['apache_user']
  group node['kawf']['apache_group']
  mode 0755
  action :create
end

if node['kawf']['vagrant'] == true
  Chef::Log.info("install MySQL server 5.7.x")

  execute 'add_mysql_key' do
    user 'root'
    command 'apt-key adv --keyserver pgp.mit.edu --recv-keys 5072E1F5'
    action :run
  end

  template '/etc/apt/sources.list.d/mysql.list' do
    source 'mysql.list.erb'
    owner 'root'
    group 'root'
    mode 0644
  end

  execute 'update_apt_get_ahead_of_mysql_install' do
    user 'root'
    command 'apt-get update'
    action :run
  end

  package 'mysql-server' do
    retries 3
    retry_delay 5
    action :install
  end

else
  Chef::Log.info("install mysql client using remote MySQL server")
  package 'mysql-client-5.7' do
    retries 3
    retry_delay 5
    action :install
  end
end

Chef::Log.info("install php-mysql")
package 'php-mysql' do
  package_name value_for_platform_family(
    'rhel' => 'php-mysql',
    'debian' => 'php5-mysql'
  )
  retries 3
  retry_delay 5
  action :install
end

git "#{node['kawf']['deploy_dir']}" do
  repository 'https://github.com/kawf/kawf.git'
  user node['kawf']['apache_user']
  group node['kawf']['apache_group']
end

template '/var/www/kawf/config/config.inc' do
  source 'config.inc.erb'
  owner node['kawf']['apache_user']
  group node['kawf']['apache_group']
  mode 0644
end

template '/var/www/kawf/config/setup.inc' do
  source 'setup.inc.erb'
  owner node['kawf']['apache_user']
  group node['kawf']['apache_group']
  mode 0644
end

# [Date]
# ; Defines the default timezone used by the date functions
# ; http://www.php.net/manual/en/datetime.configuration.php#ini.date.timezone
# ; date.timezone = "UTC"
# modify /etc/php.ini to uncomment the last line
ruby_block 'php_fix_date_timezone' do
  block do
    file = Chef::Util::FileEdit.new("/etc/php5/apache2/php.ini")
    file.search_file_replace_line("/;date.timezone =/", "date.timezone = \"UTC\"")
    file.write_file
  end
end

web_app 'kawf' do
  server_name node['kawf']['domain']
  server_aliases ["#{node['kawf']['alias']}.#{node['kawf']['domain']}"]
  docroot "#{node['kawf']['deploy_dir']}/config"
  template 'kawf.conf.erb'
  cookbook 'kawf'
end

service 'mysql' do
  action [:start, :enable]
end

# setup AWS PHP SDK
execute "install_composer" do
  cwd node['kawf']['deploy_dir']
  command "curl -sS https://getcomposer.org/installer | php"
  user 'root'
  group 'root'
  action :run
end

execute "install_aws_php_sdk" do
  cwd node['kawf']['deploy_dir']
  command "php composer.phar require aws/aws-sdk-php"
  user node['kawf']['apache_user']
  group node['kawf']['apache_group']
  action :run
end

if (node['kawf']['vagrant'] == true) && (!Dir.exists? (node['kawf']['database_dir']))
  # configure local database
  execute "create_kawf_user" do
    cwd node['kawf']['home']
    command "mysql -u root -e \"CREATE USER '#{node['kawf']['sql_username']}'@'localhost' IDENTIFIED BY '#{node['kawf']['sql_password']}';\""
    user 'root'
    group 'root'
    action :run
  end

  execute "create_kawf_database" do
    cwd node['kawf']['home']
    command "mysql -u root -e \"CREATE DATABASE #{node['kawf']['database']};\""
    user 'root'
    group 'root'
    action :run
  end

  execute "grant_kawf_user_kawf_database" do
    cwd node['kawf']['home']
    command "mysql -u root -e \"GRANT ALL ON #{node['kawf']['database']}.* TO '#{node['kawf']['sql_username']}'@'localhost';\""
    user 'root'
    group 'root'
    action :run
  end

end

service 'apache2' do
  action :restart
end
