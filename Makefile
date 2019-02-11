.PHONY: release

analyze:
	vendor/bin/psalm --show-info=false

report:
	vendor/bin/psalm --report=debug/psalm.xml
	sensible-browser debug/psalm.xml

test:
	vendor/bin/phpunit

release:
	/usr/bin/env php release