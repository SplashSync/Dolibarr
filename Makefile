### ——————————————————————————————————————————————————————————————————
### —— Local Makefile
### ——————————————————————————————————————————————————————————————————

include splash/vendor/badpixxel/php-sdk/make/sdk.mk

################################################################################
# Docker Compose Container we want to check
CONTAINERS="dol-22,dol-21,dol-20,dol-18,dol-17,dol-16"

COMMAND ?= echo "Aucune commande spécifiée"
COLOR_CYAN := $(shell tput setaf 6)
COLOR_RESET := $(shell tput sgr0)

.PHONY: 	serve
serve: 		## Execute Functional Test
	symfony serve --no-tls

.PHONY: 	upgrade
upgrade: 	## Update Composer Packages
	composer update -q || composer update

.PHONY: 	verify
verify:		## Verify Code in All Containers
	$(MAKE) up
	$(MAKE) upgrade
	$(MAKE) all-checked COMMAND="php splash/vendor/bin/grumphp run --testsuite=travis"
	$(MAKE) all-checked COMMAND="php splash/vendor/bin/grumphp run --testsuite=csfixer"
	$(MAKE) all-checked COMMAND="php splash/vendor/bin/grumphp run --testsuite=phpstan"

.PHONY: 	phpstan
phpstan:	## Execute Php Stan in All Containers
	$(MAKE) all-checked COMMAND="php splash/vendor/bin/grumphp run --testsuite=phpstan"

.PHONY: 	test
test: 		## Execute Functional Test in All Containers
	$(MAKE) up
	$(MAKE) all-checked COMMAND="php splash/vendor/bin/phpunit --testdox"


.PHONY: 	module
module: 	## Build Slash Module
	php splash/vendor/bin/grumphp run --tasks=build-module

.PHONY: 	all
all: 		## Execute a Command in All Containers
	@$(foreach service,$(shell docker compose config --services | sort), \
		set -e; \
		echo "$(COLOR_CYAN) >> Executing '$(COMMAND)' in container: $(service) $(COLOR_RESET)"; \
		docker compose exec $(service) bash -c "$(COMMAND)"; \
	)

.PHONY: 		all-checked
all-checked: 	## Execute a Command in All Checked Containers
	@$(foreach service,$(shell echo $(CONTAINERS) | tr "," "\n"), \
		set -e; \
		echo "$(COLOR_CYAN) >> Executing '$(COMMAND)' in container: $(service) $(COLOR_RESET)"; \
		docker compose exec $(service) bash -c "$(COMMAND)"; \
	)
