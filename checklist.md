# Checklist

1. Update jquery-tmpl and any other apps
   * `cd apps/jquery-tmpl`
   * `git fetch upstream`
   * `git merge upstream/master`
   * `git push origin master`
2. Run build script: `make`
   * compiles & minifies css/js 
   * concatenates into one file
3. Update post-thumbnail-editor.php
   * Change the version information in 2 places
4. Modify README.txt
   * Update the requires/tested version information
   * Update the Upgrade Notice
   * Update the Changelog
   * Update screenshots (max-width: 532px)
   * [Test README](http://wordpress.org/extend/plugins/about/validator/)
5. Test on Firefox, Chrome, Safari, IE7/8/9 & Linux/Windows/Mac
   * Do the rows change color on selection?
   * Does the height get set correctly?
   * Does enabling the ad rotate plugin cause any problems?
	* Does the pastebin functionality work?
6. Tag the git release
   * `git commit -a -m "Commit msg"`
   * `git tag [-a -m 'annotated tag'] version`
   * `git push --tags`

