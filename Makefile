### ——————————————————————————————————————————————————————————————————
### —— Local Makefile
### ——————————————————————————————————————————————————————————————————

include splash/vendor/badpixxel/php-sdk/make/sdk.mk

# Build Slash Module
.PHONY: module
module:
	php splash/vendor/bin/grumphp run --tasks=build-module
