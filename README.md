# hcpp-webdav
Adds a compression-enabled WebDAV service for HestiaCP user accounts; optimized for developer files.

&nbsp;
## Installation
HCPP-WebDAV requires an Ubuntu or Debian based installation of [Hestia Control Panel](https://hestiacp.com) in addition to an installation of [HestiaCP-Pluginable](https://github.com/virtuosoft-dev/hestiacp-pluginable) to function; please ensure that you have first installed pluginable on your Hestia Control Panel before proceeding. Switch to a root user and simply clone this project to the /usr/local/hestia/plugins folder. It should appear as a subfolder with the name `webdav`, i.e. `/usr/local/hestia/plugins/webdav`.

First, switch to root user:
```
sudo -s
```

Then simply clone the repo to your plugins folder, with the name `webdav`:

```
cd /usr/local/hestia/plugins
git clone https://github.com/virtuosoft-dev/hcpp-webdav webdav
```

Note: It is important that the destination plugin folder name is `webdav`.


Be sure to logout and login again to your Hestia Control Panel as the admin user or, as admin, visit Server (gear icon) -> Configure -> Plugins -> Save; the plugin will immediately start installing NodeJS depedencies in the background. 

A notification will appear under the admin user account indicating *"WebDWAV plugin has finished installing"* when complete. This may take awhile before the options appear in Hestia. You can force manual installation via:

```
cd /usr/local/hestia/plugins/webdav
./install
touch "/usr/local/hestia/data/hcpp/installed/webdav"
```

&nbsp;
## Using WebDAV
This plugin will create a new domain for each HestiaCP user with the naming convention:

```
webdav-&gt;username&lt;.domain.tld
```

Where &gt;username&lt is the username of the HestiaCP user account and domain.tld is the domain for the HestiaCP instance.


  
