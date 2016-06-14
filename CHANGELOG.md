CHANGELOG
=========

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
