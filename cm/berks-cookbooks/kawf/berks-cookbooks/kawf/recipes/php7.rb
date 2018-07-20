#
# Cookbook Name:: kawf
# Recipe:: php7
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

directory '/var/www' do
  user node['kawf']['user']
  group node['kawf']['group']
  mode 0755
  action :create
end

directory '/var/www/html' do
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

Chef::Log.info("install required packages")
package ['unzip', 'curl', 'php', 'libapache2-mod-php', 'php-mysql'] do
  action :install
end

file "#{node['kawf']['home']}/.ssh/#{node['kawf']['deploy_key']}" do
  content node['wayot']['deploy_key']
  sensitive true
  owner node['kawf']['user']
  group node['kawf']['group']
  mode 0400
end

template "#{node['kawf']['home']}/git_wrapper.sh" do
  source 'git_wrapper.sh.erb'
  owner node['kawf']['user']
  group node['kawf']['group']
  mode 0755
end

git "#{node['kawf']['deploy_dir']}" do
  repository node['kawf']['repository']
  revision node['kawf']['revision']
  ssh_wrapper "#{node['kawf']['home']}/git_wrapper.sh"
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

if node['kawf']['search'] == true
  cookbook_file "#{node['kawf']['home']}/search.tar.gz" do
    source 'search.tar.gz'
    user node['kawf']['user']
    group node['kawf']['group']
    mode 0755
    action :create
  end

  execute 'extract_search_to_kawf' do
    cwd node['kawf']['home']
    command "tar -xvzf search.tar.gz -C #{node['kawf']['deploy_dir']}/config"
    owner 'root'
    group 'root'
    action :run
  end
end

# [Date]
# ; Defines the default timezone used by the date functions
# ; http://www.php.net/manual/en/datetime.configuration.php#ini.date.timezone
# ; date.timezone = "UTC"
# modify /etc/php.ini to uncomment the last line
ruby_block 'php_fix_date_timezone' do
  block do
    file = Chef::Util::FileEdit.new("/etc/php/7.0/apache2/php.ini")
    file.search_file_replace_line("/;date.timezone =/", "date.timezone = \"UTC\"")
    file.write_file
  end
end

template '/etc/apache2/sites-available/wayot.conf' do
  source 'wayot.conf.erb'
  owner 'root'
  group 'root'
  mode 0644
end

link '/etc/apache2/sites-enabled/000-default.conf' do
  action :delete
  only_if 'test -L /etc/apache2/sites-enabled/000-default.conf'
end

execute "enable_wayot" do
  command 'a2ensite wayot.conf'
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

  if node['kawf']['restore'] == false
    execute 'php_tools_initial' do
      cwd node['kawf']['home']
      command "#{node['kawf']['deploy_dir']}/tools/initial.php"
      user node['kawf']['apache_user']
      group node['kawf']['apache_group']
      action :run
    end
  end
end
