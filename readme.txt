=== Hierarchical Groups for BP ===
Contributors: dcavins
Tags: BuddyPress
Requires at least: 4.6
Tested up to: 5.3.2
Stable tag: 1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add hierarchical group functionality to your BuddyPress-powered community site.

== Description ==

Add hierarchical group functionality to your BuddyPress-powered community site.

= Features =
* Adds hierarchical browsing of the main groups directory.
* Allows users to select parent group for a group.
* Allows group activity syndication (show child or parent group activity in the current group's activity stream).
* Produce group URLs that reflect hierarchical relationships, like `example.com/groups/parent-group/child-group/`

= Thanks =
This plugin owes a great deal to the original "BP Group Hierarchy" plugin written by [David Dean](https://profiles.wordpress.org/ddean/).
It also incorporates good ideas from the "BP Group Hierarchy Propagate" plugin written by [Chistian Wach](https://profiles.wordpress.org/needle/).


== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Visit the Groups->Hierarchy Options screen to configure the plugin.


== Screenshots ==

1. Browse the groups directory as an interactive tree.
2. See the child groups of the current group on the new 'hierarchy' screen.


== Frequently Asked Questions ==

= Which versions of BuddyPress are supported? =

This plugin makes use of features found only in BuddyPress 2.7 or newer.

= How can I use this plugin in my language? =

Create a new language file from the included `hierarchical-groups-for-bp.pot` file.
Save the language files in the directory, `wp-content/languages/plugins/`, with the filenames `hierarchical-groups-for-bp-{locale}.mo`
and `hierarchical-groups-for-bp-{locale}.po`. Please consider contributing to the translation project for this plugin (add link once published). Read more about [creating custom language files](https://codex.buddypress.org/getting-started/customizing/customizing-labels-messages-and-urls/).


== Changelog ==

= 1.0 =
* First version.
