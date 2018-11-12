=== KAGG Notification ===
Author: KAGG Design
Author URI: http://kagg.eu/en/
Contributors: kaggdesign
Tags: notification
Requires at least: 4.4
Tested up to: 5.0
Version: 1.0.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

KAGG Notification plugin creates and maintains notification system for users on WordPress site.

== Description ==

KAGG Notification plugin creates and maintains notification system for users on WordPress site.

Plugin has options page in the site console, with relevant buttons and controls. Please see screenshots.

= REST API =

Get all notifications
http://site.org/wp-json/kagg/v1/notifications

Get notifications by taxonomies
Available taxonomies are: ...
http://site.org/wp-json/kagg/v1/notifications?location=Estonia&brand=Premium&dealer_type=point-of-sales

Get dealer by id (for instance, id=8)
http://site.org/wp-json/kagg/v1/notifications/8

Get dealer by slug
http://site.org/wp-json/kagg/v1/notifications/slug=fun-cars

Get dealer by status
http://site.org/wp-json/kagg/v1/notifications/status=publish

notifications can be created, read, updated, and deleted via REST API. Javascript interface is based on ES6 + Backbone.

= Backend =

Notifications are custom posts of type notification. There is a standard custom post interface, with menu in admin.

Administrator can create, edit, and delete notifications. 3 taxonomies are available: location, types, brand. Administrator is able to add, edit, and delete taxonomy terms.

= Frontend =

Plugin creates page /notifications "on the fly" - it does not exists in database. Information on this page is visible only to logged in users (restricted by permissions in REST API Controller).

Logged-in users can list notifications, and filter them by location, brand, and type.

Show more button provides pagination of the notifications list.

For those users who have capability 'edit_posts', additional buttons are shown: Create Dealer, Update, and Delete. Popup windows provide relevant inputs.

Any logged-on user can click on "Make Order" button and make an "order", which is done via AJAX XMLHttpRequest.

= Translation-ready =

Plugin is prepared for translation. All strings are output via gettext fucntions. There is a .pot file in /language directory with strings collected.

= Code =

php code conforms to php 5.2.4 level.
js code conforms to ES6 level.

All code is checked by php Code Sniffer, and conforms to WordPress Extra standard.

== Installation ==

= Minimum Requirements =

* PHP version 5.2.4 or greater (PHP 7.2 or greater is recommended)
* MySQL version 5.0 or greater (MySQL 5.6 or greater is recommended)

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser. To do an automatic install of KAGG Notification plugin, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type “KAGG Notification” and click Search Plugins. Once you’ve found our plugin you can view details about it such as the point release, rating and description. Most importantly of course, you can install it by simply clicking “Install Now”.

= Manual installation =

The manual installation method involves downloading our plugin and uploading it to your webserver via your favourite FTP application. The WordPress codex contains [instructions on how to do this here](https://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

= Updating =

Automatic updates should work like a charm; as always though, ensure you backup your site just in case.

== Frequently Asked Questions ==

= Where can I get support or talk to other users? =

If you get stuck, you can ask for help in the [KAGG Notification Plugin Forum](https://wordpress.org/support/plugin/kagg-notification).

== Screenshots ==

1. The KAGG Notification /notifications page (frontend).

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==
