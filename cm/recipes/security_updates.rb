#
# Cookbook Name:: kawf
# Recipe:: security_updates
#
# All rights reserved - Do Not Redistribute
#
##

Chef::Log.info("install security updates")
execute "ubuntu_security_updates" do
  command 'apt-get -s dist-upgrade | grep "^Inst" | grep -i securi | awk -F " " {\'print $2\'} | xargs apt-get install'
  user 'root'
  group 'root'
  action :run
end
