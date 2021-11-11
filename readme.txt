=== TRACK Connect ===
Contributors: trackhs
Tags: trackpm, track sync, track hs, track
Requires at least: 3.7
Tested up to: 5.0.3
Stable tag: 4.0.5

Creates and syncs listing-type posts from TRACK PM, a cloud-based property management system (www.trackhs.com).

== Description ==

TRACK Connect uses custom post types, taxonomies, templates, and widgets to create a listing management system for WordPress. It includes custom templates and widgets for front end display.

Single listings display the custom data automatically with no need to insert shortcodes to display listing data. If it's entered, it will display on the page.

Allows for any number of custom single listing templates to be created and displayed on a per listing basis.

== Installation ==

1. Upload the entire `track-connect` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to Listings > Settings and input your Domain code and API token, then Save
4. Now you can hit the Sync Units button

= How to use the listings shortcode =

= Basic usage =
Just enter the following shortcode on any post or page

`[listings]`

= Advanced usage =
The shortcode accepts the following parameters:

`id` = listing post id (accepts one or more id's), exclusive, cannot be combined with other parameters, except for columns
`limit` = limit the number of posts to show, defaults to all
`columns` = display output in columns, accepts values 2-6, default is 1 column
`taxonomy` = taxonomy to display (must be used with the term parameter)
`term` = term to display (must be used with the taxonomy parameter)

Example advanced usage:
`[listings taxonomy="status" term="active" limit="10" columns="3"]`
This will display all listings in the "Status" taxonomy, assigned to the "Active" term, limited to 10 listings, in 3 columns

== Changelog ==
= 4.0.5 =
Fixed bug with calendar showing availability.

= 4.0.4 =
Fixed bedroom filters for studio.

= 4.0.3 =
Fixed small issues with release.

= 4.0.2 =
Fixed issue of displaying correctly when no listings are returned.

= 4.0.1 =
Removed a typo of a closing bracket.

= 4.0.0 =
Added override and made it possible for all clients to upgrade to same version.

= 3.1.0 =
Fixed issue with Lodging Type Filter and added bathroom breakdown.

= 3.0.9 =
Fixed issue with jquery ui slider include missing.

= 3.0.8 =
Fixed issue with lodging types and added version logging in API request.

= 3.0.7 =
Fixed another issue with sidebar bedroom slider.

= 3.0.6 =
Fixed sleeps filter, sidebar date picker for same day checkin, sidebar bedroom slider.

= 3.0.5 =
Fixed bug with displaying availability.

= 3.0.3 =
Fixed issue with insecure link on page.

= 3.0.2 =
Fixed javascript error on settings page.

= 3.0.1 =
Major performance improvement to search widget.

= 3.0.0 =
Changes to architecture.
Node tree support.
Prefix support.
