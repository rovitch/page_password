.PHONY: help
help: ## Displays this list of targets with descriptions
	@echo "The following commands are available:\n"
	@grep -E '^[a-zA-Z0-9_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}'

.PHONY: install
install: ## Run composer update
	Build/Scripts/runTests.sh -s composerUpdate

.PHONY: rector
rector: ## Run rector
	Build/Scripts/runTests.sh -s rector

.PHONY: fix-cs
fix-cs: ## Fix PHP coding styles
	Build/Scripts/runTests.sh -s cgl

.PHONY: fix
fix: rector fix-cs## Run rector and cgl fixes

.PHONY: phpstan
phpstan: ## Run phpstan tests
	Build/Scripts/runTests.sh -s phpstan

.PHONY: phpstan-baseline
phpstan-baseline: ## Update the phpstan baseline
	Build/Scripts/runTests.sh -s phpstanBaseline

.PHONY: test
test: fix-cs phpstan test-unit test-functional## Run all tests

.PHONY: test-unit
test-unit: ## Run unit tests
	Build/Scripts/runTests.sh -s unit -z --coverage-php=.Build/.cache/coverage/unit.cov Tests/Unit/

.PHONY: test-functional
test-functional: ## Run functional tests
	Build/Scripts/runTests.sh -s functional -d mysql -z --coverage-php=.Build/.cache/coverage/functional.cov Tests/Functional/

.PHONY: coverage-report
phpcov:
	Build/Scripts/runTests.sh -s phpcov

.PHONY: coverage
coverage: test-unit test-functional phpcov
