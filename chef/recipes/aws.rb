#
# Cookbook Name:: kawf
# Recipe:: aws
#
# All rights reserved - Do Not Redistribute
#
##

Chef::Log.info("download and install aws cli")
execute 'download_aws_cli' do
  cwd node['kawf']['home']
  user 'root'
  command 'curl "https://s3.amazonaws.com/aws-cli/awscli-bundle.zip" -o "awscli-bundle.zip" && unzip awscli-bundle.zip'
  not_if do ::File.exists? ("#{node['kawf']['home']}/awscli-bundle.zip") end
end

execute 'install_aws_cli' do
  cwd node['kawf']['home']
  user 'root'
  command './awscli-bundle/install -i /usr/local/aws -b /usr/local/bin/aws'
  not_if do ::File.exists? ('/usr/local/bin/aws') end
  only_if do ::Dir.exists? ("#{node['kawf']['home']}/awscli-bundle") end
end

directory "#{node['kawf']['home']}/.aws" do
  owner node['kawf']['user']
  group node['kawf']['group']
  mode 0755
  action :create
end

template "#{node['kawf']['home']}/.aws/config" do
  source 'config.erb'
  owner node['kawf']['user']
  group node['kawf']['group']
  mode 0600
end

template "#{node['kawf']['home']}/.aws/credentials" do
  source 'config.erb'
  owner node['kawf']['user']
  group node['kawf']['group']
  mode 0600
end

execute 'set_config_env_var_user_1' do
  user node['kawf']['user']
  command "export AWS_CONFIG_FILE=/#{node['kawf']['home']}/.aws/config"
end

execute 'set_config_env_var_user_2' do
  user node['kawf']['apache_user']
  command "export AWS_CONFIG_FILE=/#{node['kawf']['home']}/.aws/config"
end

execute 'set_keys_env_var_user_1' do
  user node['kawf']['user']
  command "export AWS_ACCESS_KEY_ID=/#{node['kawf']['s3_access']} && export AWS_SECRET_ACCESS_KEY=/#{node['kawf']['s3_secret']} && export AWS_DEFAULT_REGION=/#{node['kawf']['s3_region']}"
end

execute 'set_keys_env_var_user_2' do
  user node['kawf']['apache_user']
  command "export AWS_ACCESS_KEY_ID=/#{node['kawf']['s3_access']} && export AWS_SECRET_ACCESS_KEY=/#{node['kawf']['s3_secret']} && export AWS_DEFAULT_REGION=/#{node['kawf']['s3_region']}"
end
