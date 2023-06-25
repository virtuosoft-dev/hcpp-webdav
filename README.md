# hcpp-webdav
Adds a compression-enabled WebDAV service for HestiaCP user accounts; optimized for developer files.

&nbsp;
 > :warning: !!! Note: this repo is in progress; when completed, a release will appear in the release tab.
 
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


Be sure to logout and login again to your Hestia Control Panel as the admin user or, as admin, visit Server (gear icon) -> Configure -> Plugins -> Save; the plugin will immediately start installing WebDAV depedencies in the background. 

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
webdav-username.domain.tld
```

Where `username` is the username of the HestiaCP user account and `domain.tld` is the domain for the HestiaCP instance.

## Extending HCPP-WebDAV via Pluginable Actions
The following [hestiacp-pluginable](https://github.com/virtuosoft-dev/hestiacp-pluginable) actions are invoked when using
the WebDAV. Developers can hook and implement their own WebDAV modifications using these actions:

* **webdav_write_vhost** - *occurs before the Apache WebDAV vhost file is written.*
* **webdav_write_nginx** - *occurs before the Apache WebDAV nginx file is written.* 
 

## Support the creator
You can help this author’s open source development endeavors by donating any amount to Stephen J. Carnam @ Virtuosoft. Your donation, no matter how large or small helps pay for essential time and resources to create MIT and GPL licensed projects that you and the world can benefit from. Click the link below to donate today :)
<div>
         

[<kbd> <br> Donate to this Project <br> </kbd>][KBD]


</div>


<!-—————————————————————————>

[KBD]: https://virtuosoft.com/donate

https://virtuosoft.com/donate


