---
paths:
  - 'app/Auth/**'
---

# Auth

## Custom authentication infrastructure only
app/Auth holds custom authentication infrastructure: user providers, guards and password-broker pieces. Build each on Laravel's auth contracts or framework classes and register it in a service provider (AppServiceProvider registers the eloquent-email provider). Do not put other business logic here.

## Mirror the folder in Integration tests
Test every class under app/Auth in tests/Integration/Auth at the mirrored path, through the registered provider or guard rather than the class constructed by hand.

## Treat credentials as hostile input
Authentication code marks credential parameters `#[\SensitiveParameter]`, normalizes the email through `EmailAddress::normalize()`, and never lets user input widen a lookup. `CaseInsensitiveEmailUserProvider` uses `whereLike` only to narrow candidates, then compares the exact lowercase address in PHP, so `%` and `_` in the input cannot match other accounts. Fall back to the parent provider for credentials without a string email.
