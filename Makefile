.PHONY: release

analyze:
	vendor/bin/psalm --show-info=false

report:
	vendor/bin/psalm --report=build/logs/psalm.xml
	sensible-browser build/logs/psalm.xml

test:
	vendor/bin/phpunit

release:
	/usr/bin/env php release