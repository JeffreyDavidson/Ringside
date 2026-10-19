---
paths:
  - 'app/Console/**'
---

# Console

## Commands are thin entry points to an Action
A command parses its arguments, calls the Action that owns the work, and reports. It holds no business rules and does not touch models directly beyond finding the argument records. Declare the name and options with the `#[Signature]` attribute and the summary with `#[Description]`.

## Data-changing commands preview first
A command that writes data takes `--dry-run` (report what would change, change nothing) and requires `--force` to apply (`promotions:backfill-roster-ownership`, `promotions:backfill-event-title-ownership`). Without either it refuses with an error and a failure exit code. Reject an unknown argument record before doing anything, and return `self::SUCCESS` or `self::FAILURE`, not bare integers.

## Test the preview and the apply
Test each command beside the behaviour it changes in an Integration test: the dry run changes nothing and reports the count, no flag refuses, `--force` applies, and a missing record fails. Console code is part of the 100% coverage gate.
