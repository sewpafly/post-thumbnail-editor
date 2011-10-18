=== Post Thumbnail Editor ===
Contributors: sewpafly
Donate link: https://www.wepay.com/donate/34543
Tags: post-thumbnail, post thumbnail, featured image, featured, editor, image, awesome
Requires at least: 3.2
Tested up to: 3.2.1
Stable tag: trunk

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
	1. Select the thumbnails you want to edit. If a thumbnail defines a specific aspect ratio it will be applied to the editor.  If you select thumbnails with more than 1 different aspect ratios, this feature is disabled.  Be careful or you might make some of your pictures look funny.
	2. Select the cropped/scaled area, by clicking and dragging on the left-hand image. 
	3. Click "Create Thumbnails" -- this button is only enabled if you have thumbnails selected and a crop area defined.
	4. Thumbnail Preview: select all the thumbnails you want to keep and click "Okay, these look good".
4. It's possible that you might have to refresh the cache (ctrl+f5 on the page) to see changes, but they should be there.

= Did you even test this? =

Yes. No. Sort of. Thanks for asking. But [let me know if you're having problems](https://github.com/sewpafly/post-thumbnail-editor/issues) and I'll see what I can do.

= Is there a way to regenerate images created by this plugin? =

Do you really want this?  I haven't gotten any feedback that this is desirable... So imma let it go for now.

= What version of PHP do I need? =

Using a version with [json_encode](http://www.php.net/manual/en/function.json-encode.php) enabled would be nice...

== Screenshots ==

1. Before/After
2. To edit from Media Library click "Thumbnail" in the rollover options for the row.
3. In the Edit subpage for media locate the box titled "Thumbnail Settings", and click the link to "Post Thumbnail Editor".
4. Crop the image as you see fit and click "Create Thumbnails".
5. Verify you want to keep the resized/recropped images by clicking "Okay, these look good..."
6. Shortcut to edit the thumbnails from the picture metadata screen.

== Changelog ==

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

= 1.0.4 =
Bugfixes & portuguese translation

= 1.0.3 =
Fixed javascript problems & added italian translation

= 1.0.2 =
Problem with SVN commit of 1.0.1

= 1.0.1 =
Added translation support, french language, options menu and fixed incompatibility with other thickbox enabled plugins.

= 1.0.0 =
Now with more awesome.  Redesigned interface will challenge your perceptions of the universe.

= 0.2.2 =
Fixed version information
Fixed plugin specific defines

= 0.2.1 =
Fixed PHP round() issue.

= 0.2 =
Change thumbnails aspect ratio (only works for medium/large. Will work for thumbnail if the crop checkbox isn't checked under Media Settings)

= 0.1.1 =
This version fixes a IE8/firefox javascript error.

