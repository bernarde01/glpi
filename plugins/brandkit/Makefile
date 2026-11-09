# Plugin-local targets. From plugins/brandkit: make install / enable
include ../../PluginsMakefile.mk

.PHONY: test
test:
	cd ../.. && php vendor/bin/phpunit -c plugins/brandkit/phpunit.xml
