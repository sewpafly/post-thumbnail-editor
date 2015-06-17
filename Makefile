.PHONY: js
JS_OUTPUT = js-build

# local.mk is not tracked in git project
# USED FOR i18n functions
#    I18N_ENV   = $(wildcard /home/.../wordpress-i18n/makepot.php)
#    I18N        = $(if $(I18N_ENV),$(I18N_ENV),$(shell cygpath -u -a ~/build/wordpress_i18n/makepot.php))
include $(wildcard local.mk)

# A simple make will compile the js/css and minify them
all: gzip-js trans

# Build javascript
js:
	@echo "Building javascript"
	r.js -o build.js
	chmod -R 777 $(JS_OUTPUT)

$(JS_OUTPUT): js
gzip-js: $(JS_OUTPUT)
	gzip - > $(JS_OUTPUT)/main.js.gz < $(JS_OUTPUT)/main.js

#  i18n - Defined in local.mk to point to wordpress makepot.php script
trans:
	@echo "Creating Internationalization Template"
ifdef I18N
	cd i18n; \
	php '$(I18N)' wp-plugin ../
endif
# To translate the .po to .mo files
# for file in `find . -name "*.po"` ; do msgfmt -o ${file/.po/.mo} $file ; done
# msgfmt -o filename.mo filename.po

# Clean
clean:
	@echo "Cleaning up"
	rm -rf $(JS_OUTPUT)

# vi: ts=3
