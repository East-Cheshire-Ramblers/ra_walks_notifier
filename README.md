# Walks Manager Watch

Walks Manager Watch monitors booking CSV exports from Walks Manager and keeps the existing v4.2 booking parser and notification behaviour under test.

This repository starts from the stable v4.2 baseline. The v4.3 branch is reserved for the background-agent refactor. Microsoft 365 OAuth is intentionally out of scope for this baseline.

## Baseline Scope

- Parse Walks Manager booking CSV rows using the v4.2 rules.
- Preserve notification message rendering for booker acknowledgements and organiser updates.
- Provide parser regression tests that can run without Joomla, Composer, or external services.

## CSV Rules Preserved From v4.2

- Row 1 is treated as the header and ignored.
- Rows whose first column starts with `#` are treated as comments and ignored.
- Column 1 is the group code and is required.
- Column 2 is the name and is required.
- Column 3 is the email address and is required.
- Column 4 is the optional partner/details field.

## Running Tests

```sh
php tests/run.php
```

The tests use `tests/fixtures/bookings.csv` and assert the parser output plus validation errors.

## Branches

- `main`: stable v4.2 baseline.
- `v4.3-background-agent-refactor`: branch for the upcoming background-agent refactor.

Do not add Microsoft 365 OAuth on the v4.2 baseline. OAuth work should be designed separately after the background-agent structure is agreed.
