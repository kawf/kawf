#
# Cookbook Name:: kawf
# Recipe:: reboot
#
# All rights reserved - Do Not Redistribute
#
##

Chef::Log.info("reboot post security updates (also reloads apache which is required)")
reboot 'security_updates_reboot' do
  action :reboot_now
  reason 'Need to reboot after security updates.'
end
