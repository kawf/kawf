#
# Cookbook Name:: kawf
# Recipe:: php5
#
# All rights reserved - Do Not Redistribute
#
##

include_recipe 'git'

Chef::Log.info("install git client for kawf clone")
git_client 'default' do
  action :install
end

Chef::Log.info("install apache")
package 'apache2' do
  action :install
end

service 'apache2' do
  action :stop
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

directory '/var/www/html' do
  owner node['kawf']['apache_user']
  group node['kawf']['apache_group']
  recursive true
  action :delete
end

if node['kawf']['vagrant'] == true
  Chef::Log.info("install MySQL server 5.7.x")
  package 'mysql-server-5.7' do
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

Chef::Log.info("set up 16.04 for php5")
execute "clean_out_php" do
  command "apt-get purge `dpkg -l | grep php| awk '{print $2}' |tr \"\n\" \" \"`"
  user 'root'
  action :run
end

execute "add_php5_ppa" do
  command "add-apt-repository ppa:ondrej/php"
  user 'root'
  action :run
end

execute "update_apt_cache" do
  command "apt-get update"
  user 'root'
  action :run
end

Chef::Log.info("install required packages")
package ['unzip', 'curl', 'php5.6', 'libapache2-mod-php5.6', 'php5.6-mysql'] do
  action :install
end

git "#{node['kawf']['deploy_dir']}" do
  repository node['kawf']['repository']
  revision node['kawf']['revision']
  user node['kawf']['user']
  group node['kawf']['group']
end

execute "chown_docroot" do
  command "chown -R root:root /var/www"
  user 'root'
  group 'root'
  action :run
end

template "#{node['kawf']['deploy_dir']}/config/config-local.inc" do
  source 'config.inc.erb'
  owner 'root'
  group node['kawf']['apache_group']
  mode 0640
end

template "#{node['kawf']['deploy_dir']}/config/setup-local.inc" do
  source 'setup.inc.erb'
  owner 'root'
  group 'root'
  mode 0644
end

## [Date]
## ; Defines the default timezone used by the date functions
## ; http://www.php.net/manual/en/datetime.configuration.php#ini.date.timezone
## ; date.timezone = "UTC"
## modify /etc/php.ini to uncomment the last line
#ruby_block 'php_fix_date_timezone' do
#  block do
#    file = Chef::Util::FileEdit.new("/etc/php/5.6/apache2/php.ini")
#    file.search_file_replace_line("/;date.timezone =/", "date.timezone = \"UTC\"")
#    file.write_file
#  end
#end

link '/etc/apache2/sites-enabled/000-default.conf' do
  action :delete
  only_if 'test -L /etc/apache2/sites-enabled/000-default.conf'
end

template '/etc/apache2/sites-available/kawf.conf' do
  source 'kawf.conf.erb'
  owner 'root'
  group 'root'
  mode 0644
end

execute "enable_kawf" do
  command 'a2ensite kawf.conf'
  user 'root'
  group 'root'
  action :run
end

execute "enable_mod_rewrite" do
  command 'a2enmod rewrite'
  user 'root'
  group 'root'
  action :run
end

service 'apache2' do
  action [:start, :enable]
end

if (node['kawf']['vagrant'] == true) && (!Dir.exists? (node['kawf']['database_dir']))

  service 'mysql' do
    action [:start, :enable]
  end

  # configure local database
  execute 'create_kawf_user' do
    cwd node['kawf']['home']
    command "mysql -u root -e \"CREATE USER '#{node['kawf']['sql_username']}'@'localhost' IDENTIFIED BY '#{node['kawf']['sql_password']}';\""
    user 'root'
    group 'root'
    action :run
  end

  execute 'create_kawf_database' do
    cwd node['kawf']['home']
    command "mysql -u root -e \"CREATE DATABASE #{node['kawf']['database']};\""
    user 'root'
    group 'root'
    action :run
  end

  execute 'grant_kawf_user_kawf_database' do
    cwd node['kawf']['home']
    command "mysql -u root -e \"GRANT ALL ON #{node['kawf']['database']}.* TO '#{node['kawf']['sql_username']}'@'localhost';\""
    user 'root'
    group 'root'
    action :run
  end

  execute 'php_tools_initial' do
    cwd node['kawf']['home']
    command "#{node['kawf']['deploy_dir']}/tools/initial.php"
    user node['kawf']['apache_user']
    group node['kawf']['apache_group']
    action :run
  end
end
