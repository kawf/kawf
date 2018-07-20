#
# Cookbook Name:: kawf
# Recipe:: restore
#
# All rights reserved - Do Not Redistribute
#
##
if (node['kawf']['vagrant'] == true) && (!Dir.exists? (node['kawf']['database_dir']))

  service 'mysql' do
    action :stop
  end

  Chef::Log.info("Get kawf database backup from S3")
  execute "pull_kawf_from_s3" do
    command "AWS_CONFIG_FILE=/#{node['kawf']['home']}/.aws/config aws s3 cp #{node['wayot']['s3_backup']} #{node['kawf']['database_dir']} --recursive"
    user 'root'
    action :run
  end

  execute "chown_backup_from_s3" do
    command "chown -R #{node['kawf']['db_user']}:#{node['kawf']['db_group']} #{node['kawf']['database_dir']}"
    user 'root'
    group 'root'
    action :run
  end

  service 'mysql' do
    action :start
  end

  service 'apache2' do
    action :reload
  end

end
