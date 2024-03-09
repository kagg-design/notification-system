=== Notification system ===
Contributors: kaggdesign
Donate link: https://www.paypal.me/kagg
Tags: notification, notification system, user notification, notification channel
Requires at least: 5.0
Tested up to: 6.5
Requires PHP: 7.0
Stable tag: 2.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Notification System plugin creates and maintains a notification system for users on WordPress site.

== Description ==

Notification System plugin creates and maintains a notification system for users on WordPress site.

The plugin has the Options page in the site console, with relevant buttons and controls. Please see screenshots.

= Backend =

Notifications are custom posts of type notification. There is a standard custom post interface, with a menu in admin.

Administrator can create, edit, and delete notifications. One taxonomy is available: Channel. Administrator is able to add, edit, and delete taxonomy terms. To restrict notification to some users, add user logins as a comma-separated list.

= Frontend =

Plugin creates page /notifications "on the fly" — it does not exist in the database. Information on this page is visible only to the logged-in users (restricted by permissions in REST API Controller).

Plugin creates a popup window if url contains hash #notifications, like that: `http://my.site.org/some-url#notifications`. This is an easy way to show a popup window with notifications from anywhere. Just create a link like `<a href="http://my.site.org#notifications">Notifications</a>` and a click on this link will open a popup window with notifications.

Logged-in users can list notifications and filter them by a channel.

Show more button provides pagination of the notification list.

For those users who have the capability 'edit_posts', additional buttons are shown: Create, Update, and Delete. Popup windows provide relevant inputs. All notifications can be edited right from the frontend.

= Site administrators  =

Add a custom link to the menu, with `#notifications` url. Use any navigation label or space(s) for empty label. Save menu. On the site frontend, you will see a new menu item with icon and unread count. By clicking on this menu item, a popup window with notifications will be opened.

= Developers =

Create an element(s) with the class `unread-notifications-count`. The best place for such element(s) is somewhere in the header or menu. It(they) can be updated by plugin during custom JS event `update_unread_counts`. Example:

`
    const count = 5;
    document.dispatchEvent(
        new CustomEvent(
            'update_unread_counts',
            { 'detail': count }
        )
    );
`

From php code, count element(s) for current user can be updated using 'update_unread_counts' action. Example:

`
    do_action( 'update_unread_counts' );
`

= Translation-ready =

The plugin is prepared for translation. All strings are output via gettext functions. There is a .pot file in /language directory with strings collected.

= Code =

The PHP code conforms to PHP 7.0 level.
The JS code conforms to ES6 level.

All code is checked by php Code Sniffer, and conforms to WordPress Coding Standards.

== Installation ==

= Minimum Requirements =

* PHP version 7.0 or greater (PHP 7.4 or greater is recommended)
* MySQL version 5.5 or greater (MySQL 8.0 or greater is recommended)

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself, and you don’t need to leave your web browser. To do an automatic installation of Notification Systems plugin, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type “notification system” and click Search Plugins. Once you’ve found our plugin, you can view details about it such as the point release, rating and description. Most importantly, you can install it by simply clicking “Install Now”.

= Manual installation =

The manual installation method involves downloading our plugin and uploading it to your webserver via your favourite FTP application. The WordPress codex contains [instructions on how to do this here](https://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

= Updating =

Automatic updates should work like a charm; as always though, ensure you back up your site just in case.

== Frequently Asked Questions ==

= Where can I get support or talk to other users? =

If you get stuck, you can ask for help in the [Notification System Plugin Forum](https://wordpress.org/support/plugin/notification-system).

== Screenshots ==

1. The Notification System /#notifications popup (frontend).
2. The Notification System Update Notification popup.

== Changelog ==

= 2.0.0 =
* Dropped support for PHP 5.6. The minimum required PHP version is now 7.0.
* The minimal WordPress version required is 5.0 now.
* Tested with WordPress 6.5.

= 1.3 =
* Tested with WordPress 6.0.

= 1.2 =
* Tested with WordPress 5.7.

= 1.1 =
* Tested with WordPress 5.5
* Fix bug with inability to filter notifications by channel.
* Fix notice in admin if user does not exist anymore.

= 1.0.4 =
* Tested with WordPress 5.4.

= 1.0.3 =
* Tested with WordPress 5.3.

= 1.0.2 =
* Php version bumped up to 5.6.
* Tested with WordPress 5.2.

= 1.0.1 =
* Code refactoring to conform to WordPress Coding Standards.

= 1.0.0 =
* Initial release.
