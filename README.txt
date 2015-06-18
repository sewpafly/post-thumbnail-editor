=== Post Thumbnail Editor ===
Contributors: sewpafly
Donate link: http://sewpafly.github.io/post-thumbnail-editor/#toc_donations
Tags: post-thumbnail, post thumbnail, featured image, featured, editor, image, awesome, crop
Requires at least: 4.1
Tested up to: 4.2.x
Stable tag: trunk
License: GPLv2

Fed up with the lack of automated tools to properly crop and scale post thumbnails? Maybe this plugin can help.

== Description ==

To meet the needs of themes where the post-thumbnails have random and capricious sizes (which causes wordpress to crop images simply from the middle (either chopping off the top and bottom or chopping off the sides), this plugin attempts to give the users an interface to manually fix those thumbnail and random images.

== Installation ==

1. Download the zip file from <http://downloads.wordpress.org/plugin/post-thumbnail-editor.zip>
2. Unzip to your wp-content/plugins directory under the wordpress installation.
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Rock On

= or =

1. Install from within your wordpress admin area by searching for "post thumbnail editor"

== Frequently Asked Questions ==

= Usage =

1. Start with the Media Library
	1. Open Media Library
	2. Click the "Thumbnail" link in the rollover options.
2. or start within image editor interface (when viewing image details)
	1. Click "Edit Image"
	2. Click the "Post Thumbnail Editor" link under the other thumbnail options.
3. Using Post Thumbnail Editor Interface
	1. Select the thumbnails you want to edit. If a thumbnail defines a specific aspect ratio it will be applied to the editor.  If you select thumbnails with different aspect ratios (`width / height`), this feature is disabled.  Be careful or you might make some of your pictures look funny.
	2. Select the cropped/scaled area, by clicking and dragging on the left-hand image.
	3. Click the "Crop" button.
	4. Use the save icon to save the pictures, or use the view tab to compare the old and new versions before you commit.
4. It's possible that you might have to refresh the cache (ctrl+f5 on the page) to see changes, but they should be there.

= Did you even test this? =

Yes. No. Sort of. Thanks for asking. But [let me know if you're having problems][gh] and I'll see what I can do.

= So it's installed but it doesn't appear to be working, how do I troubleshoot the problems? =

Before posting a question or requesting support, disable all other plugins to verify that the problem isn't caused by some esoteric conflict. If you find a conflict with another plugin, let me know and I will fix it.

If you want to post a problem on the [support forums][wp] or at [github][gh] please include the following information:

1. What browser (and version) are you using, or which browsers have you seen the behavior in?
2. What wordpress version are you using?
3. Does the javascript console report any exceptions/errors?
   * Google Chrome: ctrl+shift+j
   * Firefox: ctrl+shift+j
   * IE: F12 to open developer tools, click on the "Script" tab and view the "Console" sidebar.
4. Detail the steps to reproduce your problem
   * What did you do?
   * What did you expect to happen?
   * What actually happened?

[wp]: http://wordpress.org/support/plugin/post-thumbnail-editor
[gh]: https://github.com/sewpafly/post-thumbnail-editor/issues

== Screenshots ==

1. Before/After
2. To edit from Media Library click "Thumbnail" in the rollover options for the row.
3. In the Edit subpage for media locate the box titled "Thumbnail Settings", and click the link to "Post Thumbnail Editor".
4. Crop the image as you see fit, select the thumbnails you wish to change, and click "Crop".
5. Save the resized/recropped images by clicking the save icon.

== Changelog ==

= 2.4.6 =
* Updated polish translation
* Check if featuredImage exists before override to avoid JS error (@gbonhevi)

= 2.4.5 =
* Fixed bug with post's media library

= 2.4.4 =
* Fixed bug with debug mode and certain plugins
* Updated French translation

= 2.4.3 =
* Debug mode was broken
* Update the generated .gz javascript file
* modify the media library to have a fallback url
* check that option `pte_jpeg_compression` exists before trying to access it

= 2.4.2 =
* Fix CSRF vulnerabilities.  Thanks to [J.D. Grimes](http://codesymphony.co/) for reporting them.

= 2.4.1 =
* Bug fixes: see issues #100, #101, #102 on github
* Bump wordpress compatibility

= 2.4.0 =
* Bug fixes
* Use temporary file for editor image _(experimental)_
* Logging to file/chromephp

= 2.3.0 =
* Updated libraries
* (re-)Added Thickbox/Media Library Integration (#65)
* Set jpeg compression dynamically (#63)
* Add fit crop to thumbnail option (enabled by default), can be overridden in the "Options" menu #78
* Various bug fixes

= 2.2.2 =
* Unreleased version
* Updated libraries
* Added spinner to show loading
* Polish translation
* fix #76: PHP Strict Standards Error
* Save and crop stays in crop view

= 2.2.1 =
* fixed onLoad problem

= 2.2.0 =
* View the thumbnails you are planning on modifying from the crop tab
* New documentation at http://sewpafly.github.io/post-thumbnail-editor
* Resolved issue where users without capability could load the editor
* Added new custom `pte-edit` capability.
* Now use almondjs for the normal AMD loader
* Fixed some crop constraint bugs
* Removed requireJS domReady plugin
* Upgraded angularjs
* Improved startup

= 2.1.0 =
* Crop Constraints are visually available: green is good and red means there will be upscaling
* In the options panel change the size of the cropping image
* Bug fix: 3.6 compatibility
* Added thumbnail metadata to table

= 2.0.1 =
* IE fix with the jcrop api
* Now go into the view mode after cropping
* Added option to crop and save without verifying
* Featured images now have link to launch the Post Thumbnail Editor
* Updated coffee-script to 1.6.2
* Updated French and Spanish translations
* Made the cache buster an option

= 2.0.0 =
* New UI based off angularjs -- awesome framework btw -- same backend
* In view tab, click the pictures to select (double-click switches to crop view).
* Works on iPhone/iPad.

= 1.0.7 =
* Updated for Wordpress 3.5 (introduces backwards incompatible changes)
* Other bug fixes

= 1.0.5 =
* Fix custom sizes with either height or width set to '0'
* Added German translation

= 1.0.4 =
* Added full paths to php includes
* Fix handling for thumbnail names with spaces
* Fix unlink/deletion issue
* Added Portuguese translation

= 1.0.3 =
* Fixed some javascript issues
* Added Italian translation

= 1.0.2 =
* Problem with SVN commit of 1.0.1

= 1.0.1 =
* Fixed compatibility with other thickbox enabled plugins that called the wordpress media scripts.
* Added Options screen (Settings -> Post Thumbnail Editor) to configure thickbox dimensions and enable/disable debugging.
* Added Translation support & French translation (thanks to Li-An)

= 1.0.0 =
* Total redesign of PHP/HTML interface
* Allows editing multiple images
* Switch from fancybox to wordpress' included thickbox

= 0.2 =
* Added support to change thumbnails aspect ratio. Previously a square image was generated. (Only works for "medium" or "large" thumbnails by default. Will work for "thumbnail" size if the crop checkbox isn't checked under Media Settings).
* Thumbnails are appended with "-pte" to differentiate images created with this plugin

= 0.1.1 =
* Fixed IE8/firefox javascript errors

= 0.1 =
* Initial cut

== Upgrade Notice ==

= 2.4.6 =
* Updated polish translation
* Fixed post media library bug
