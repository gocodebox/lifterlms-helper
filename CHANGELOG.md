LifterLMS Helper Changelog
==========================

v2.5.0 - 2017-07-18
-------------------

+ Allow add-ons to be bulk deactivated
+ Integrates with LifterLMS site clone detection in order to automatically activate plugins on your new URL when cloning to staging / production.
+ Following clone detection if activation fails the plugin will no longer show the add-ons as activated (since they're not activated on the new URL)
+ Minor admin-panel performance improvements
+ Now uses minified JS and CSS assets
+ Now fully translateable!


v2.4.3 - 2017-02-09
-------------------

+ Handle undefined errors during post plugin install from zip file

v2.4.2 - 2017-01-20
-------------------

+ Handle failed api calls gracefully


v2.4.1 - 2016-12-30
-------------------

+ Cache add-on list prior to filtering


v2.4.0 - 2016-12-20
-------------------

+ Added a unified Helper sceen accessible via LifterLMS -> Settings -> Helper
+ Activate multiple addons simultaneously via one API call
+ Site deactivation now deactivates from remote activation server in addition to local deactivation
+ Upgraded database key handling prevents accidental duplicate activation attempts
+ Fixed several undefined index warnings
+ Normalized option fields keys


v2.3.1 - 2016-10-12
-------------------

+ Fixes issue with theme upgrade post install not working resulting in themes existing in the wrong directory after an upgrade


v2.3.0 - 2016-10-10
-------------------

+ Significantly upgrades the speed of version checks. Previously checked each LifterLMS Add-on separately, now makes one API call to retreive versions of all installed LifterLMS Add-ons.
+ Adds support for the Universe Bundle which is one key associated with multiple products


v2.2.0 - 2016-07-06
-------------------

+ After updates, clear cached update data so the upgrade doesn't still appear as pending
+ After changing license keys, clear cahced data so the next upgrade attempt will not fail again (unless it's still supposed to fail)
+ After updating the currently active theme, correctly reactivate the theme


v2.1.0 - 2016-06-14
-------------------

+ Prevent hijacking the LifterLMS Core lightbox data when attempting to view update details on the plugin update screen.
+ Added [Parsedown](https://github.com/erusev/parsedown) to render Markdown style changelogs into HTML when viewing extension changelogs in the the lightbox on plugin update screens.


v2.0.0 - 2016-04-08
-------------------

+ Includes theme-related APIs for serving updates for themes
+ Better error reporting and handling
+ A few very exciting performance enhancements


v1.0.2 - 2016-03-07
-------------------

+ Fixed an undefined variable which produced a php warning when `WP_DEBUG` was enabled
+ Resolved an issue that caused the LifterLMS Helper to hijack the "details" and related plugin screens that display inside a lightbox in the plugins admin page.
+ Added a .editorconfig file
+ Added changelog file
