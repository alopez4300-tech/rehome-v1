#!/usr/bin/make -f

# Backend development shortcuts
# Usage: make test, make lint, make fix

.PHONY: test lint fix rector pint typecheck qa help

# Run tests with strict PHPUnit configuration
test:
	composer test

# Run linting checks (Pint + Rector dry-run)
lint:
	composer rector
	composer lint

# Run Rector to apply code modernization
rector:
	composer rector:fix

# Run Pint to format code
pint:
	./vendor/bin/pint

# Run static analysis
typecheck:
	composer typecheck

# Apply all fixes (Rector + Pint)
fix:
	composer fix

# Run all quality assurance checks
qa: lint typecheck test

# Show available targets
help:
	@echo "Available targets:"
	@echo "  test      - Run PHPUnit tests (strict mode)"
	@echo "  lint      - Run Rector (dry-run) + Pint (test mode)"
	@echo "  rector    - Apply Rector code modernization"
	@echo "  pint      - Format code with Pint"
	@echo "  typecheck - Run PHPStan static analysis"
	@echo "  fix       - Apply all fixes (Rector + Pint)"
	@echo "  qa        - Run all QA checks (lint + typecheck + test)"
	@echo "  help      - Show this help message"
