# Post Thumbnail Editor

A wordpress plugin for managing and editing [post thumbnails][pt].

[pt]: http://codex.wordpress.org/Post_Thumbnails

## Contents

As of version 3.0, PTE now follows conventions established by
<https://github.com/DevinVinson/WordPress-Plugin-Boilerplate> (@3d3f181) for
purposes of code clarity and a general cleanup of existing features. A broad
overview of the changes include moving the main code into the `trunk`
subdirectory, where you'll find the regular wordpress files (plugin.php,
README.txt, etc.), and the assets directory where the images used for the
wordpress plugin site are stored.

## Installation

PTE can be installed in one of two ways, both of which are documented below. Note that because of its directory structure, PTE cannot be installed “as-is.”

Instead, the options are:

### Copying a Directory

1. Copy the `trunk` directory into your `wp-content/plugins` directory. You may
   wish to rename this to something else.
2. In the WordPress dashboard, navigation to the *Plugins* page
   Locate the menu item that reads “Post Thumbnail Editor”.
3. Click on *Activate*.

### Creating a Symbolic Link

#### On Linux or OS X

1. Create a symbolic link between the `trunk` directory and the plugin. For
   example: `ln -s post-thumbnail-editor/trunk /path/to/wordpress/wp-content/plugins/post-thumbnail-editor`
2. In the WordPress dashboard, navigate to the *Plugins* page, and
   locate the menu item that reads “Post Thumbnail Editor”.
3. Click on *Activate*.

#### On Windows

1. Create a symbolic link between the `trunk` directory and the plugin. For
   example: `mklink /J path\to\wp-content\plugins \path\to\post-thumbnail-editor\trunk`

2. In the WordPress dashboard, navigation to the *Plugins* page
   Locate the menu item that reads “Post Thumbnail Editor”.

3. Click on *Activate*.

## Build Instructions

In order to build PTE, the wordpress internationalization tools are required for
building the translation files.  Additionally, any javascript code that you want
to run outside of `DEBUG` mode, should be compiled and minified using gulp.

### i18n Tools

* [Poedit](http://www.poedit.net/)
* [makepot](http://i18n.svn.wordpress.org/tools/trunk/)
* [i18n](https://github.com/grappler/i18n)

## Basic Structure

Starts in `wp-includes/class-post-thumbnail-editor.php`, which sets up the most
basic hooks to load the pages in the admin and media library.

`admin/class-pte-admin.php` has the code that runs when the hooks are called.

`admin/class-pte-options.php` has the code that runs when the options hooks
are called.  Additionally this class is added to the static `PTE` object so that
all the options can be found easily across the different code points.

`includes/class-pte-api.php` contains the foundational code for the plugin, with
the methods that need to be run externally (get thumbnail information, resize,
delete, etc.)

`client` is an almost standalone javascript client for PTE. `class-pte-client`
has some code for generating the HTML to start the client and for accessing the
URLs.

## Testing

1. *(optional)* Install [WP-CLI][cli].
2. Run `bash bin/install-wp-tests.sh test test test localhost latest` to
   download the core wordpress files, the wordpress unit test files and install
   a database `test` with the username/password of `test`/`test`.
3. Run `phpunit`

[cli]: http://wp-cli.org/

## TODO

* Build Unit tests
* Build functional tests
* Rebuild client
* Integrate unit tests with travisci
* Fix translations (see PTE_Service especially)
* Add nonces (see PTE_Service)

## License

The Post Thumbnail Editor plugin is licensed under the GPL v2.

> This program is free software; you can redistribute it and/or modify it under
> the terms of the GNU General Public License, version 2, as published by the
> Free Software Foundation.
>
> This program is distributed in the hope that it will be useful, but WITHOUT
> ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
> FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
>
> You should have received a copy of the GNU General Public License along with
> this program; if not, write to the Free Software Foundation, Inc., 51 Franklin
> St, Fifth Floor, Boston, MA 02110-1301 USA

A copy of the license is included in the root of the plugin’s directory. The file is named `LICENSE`.

# Credits

The WordPress Plugin Boilerplate was started in 2011 by [Tom McFarlin](http://twitter.com/tommcfarlin/) and has since included a number of great contributions. In March of 2015 the project was handed over by Tom to Devin Vinson.
