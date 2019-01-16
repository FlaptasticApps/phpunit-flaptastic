.PHONY: php_version
php_version:
	php --version

.PHONY: phpunit_version
phpunit_version:
	composer show phpunit/phpunit
