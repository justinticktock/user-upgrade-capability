=== Plugin Name ===
Contributors: justinticktock
Tags: multisite, role, user, cms, groups, teams, access, capability, permission, security
Requires at least: 3.5
Tested up to: 4.6
Stable tag: 2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Link multiple network sites/blogs together - Maintain only one site list of users.

== Description ==

'User Upgrade Capability' is a plugin to help with a multi-site network and helps with the administration of users and their roles. When you start using a multi-site WordPress installation you soon realise the power of having the ability to use a separate site for different functions (e.g. main site, separate blog, separate calendar …etc) each can then be handled separately and even with different themes.

However, without 'User Upgrade Capabilities' you would need to maintain the user access on each site, this is an overhead for administrators since for each site Admins will need to grant access and add/remove capabilties & roles as required, which all takes time.  'User Upgrade Capability' helps with this admin task and allows you to create a new site and point back to a master reference site re-using its user listing and capabilties/roles to define access permissions for the new linked site.

One example of where this approach is helpful is for the case where you want multiple calendars for different purposes on the same WordPress site.  Calendar plugins generally use a fixed database table name, this means that you can't install two calendars on the same site.  With 'User Upgrade Capability' you can create a new site for each calendar and point back to the reference site re-using its user base and capabilties.  The end user doesn't even know that the calendars are on a different site.


= Plugin site =

http://justinandco.com/plugins/user-upgrade-capabilities/

= GitHub =

https://github.com/justinticktock/user-upgrade-capability


= WARNING = Activating this plugin on a site will replace the available user roles/capabilities with a copy from the reference site you will not be able to undo.

Translation:

* English
* Serbo-Croatian, sr_RS ( props Borisa Djuraskovic [webhostinghub.com](http://webhostinghub.com) )

Extensions:

If you select the options for extending functionality through other plugins the following are available for ease of installing..

* [User Role Editor](http://wordpress.org/plugins/user-role-editor/) for Admins to control user access/capability.
* [Blog Copier](http://wordpress.org/plugins/blog-copier/) for ease of duplicating sites.


== Installation ==

You can install it through the WordPress plugin repository “add plugins” automatic installer. So follow these steps..

1. remove/delete the plugin folder you created manually (e.g. ../wp-content/plugins/user-upgrade-capability/
2. Go to site dashboard ‘My Sites’ > ‘Network Admin’ > ‘Plugins’
3. Click on the ‘Add New’ button
4. search for ‘User upgrade capability’ and install
5. Network Activate the plugin

To Manually install follow these steps..

1. [Download](https://wordpress.org/plugins/user-upgrade-capability/) the plugin.zip file 
2. Go to site dashboard ‘My Sites’ > ‘Network Admin’ > ‘Plugins’
3. Click on the ‘Add New’ button
4. Click on the ‘Upload Plugin’ button
5. follow instructions to upload the zip file and install
6. Network Activate the plugin


Once activated follow these steps on the site you which to use the plugin..

1. Goto the "Users" Menu and "Reference Site" sub menu.
2. Defined the "Reference Site" to point back to the site that you will be using to define your user capabilities
3. Once the Ref Site is defined you will see two new sub-menus under the "Users" Menu. (1) "Upgrade Roles" (2) "Upgrade Caps".  Under these two menus you will be able to set keys (role or capabilities).
4. Each Key that you define will provide a new settings tab where you can select new access to the local site where the users have the key role/cap on the reference site.

For example if you simply want to add and new site and grant every subscriber on the primary reference site the same access to your new site.  
> Then under the "Upgrade Roles" settings pages and the "Key Roles:" tab tick the "Subscriber" option save changes.

> Select the new "subscriber" tab settings page.

> Tick the "Subscriber" option and save changes.

> Now all subscribers on the primary reference site will automatically be given subscriber access to the local site.

== Frequently Asked Questions ==

== Screenshots ==

1. General Settings Screen (Reference Site).
2. Define Key Roles
3. Users with the sp_staff role on the primary reference site will have the administrator & author roles on the local site
2. Define Key Capabilities
5. Users with the view_sportspress_reports capability on the primary reference site will have the more capabilities added to this local site.
6. Plugin Suggestions

== Changelog ==

Change log is maintained on [the plugin website](http://justinandco.com/plugins/user-upgrade-capability-change-log/ "User Upgrade Capability – Change Log")

== Upgrade Notice ==

Version 2.0 is a major rewrite to include flexibilty in the number of Key Capabilties allows and now Roles have also been added.  The upgrade will remove the existing settings and copy your setup into the new format.  So there shouldn't be any impact on your site.
