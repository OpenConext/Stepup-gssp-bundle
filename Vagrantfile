Vagrant.configure(2) do |config|
  config.vm.box = "CentOS-7.0"
  config.vm.box_url = "https://build.openconext.org/vagrant_boxes/virtualbox-centos7.box"

  config.vm.network "private_network", ip: "192.168.77.43"
  config.vm.synced_folder ".", "/vagrant", :nfs => true, type: "nfs"

  config.vm.provider "virtualbox" do |v|
    v.customize ["modifyvm", :id, "--memory", "1024"]
  end

  config.vm.provision "ansible" do |ansible|
    ansible.playbook = "ansible/vagrant.yml"
    ansible.groups = {"dev" => "default"}
    ansible.extra_vars = {
      develop_spd: true
    }
  end
end
