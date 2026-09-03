---
paths:
  - config/mail.php
---

# Config

## Track Resend email infrastructure opportunities
When revisiting email infrastructure, evaluate Resend’s verified-domain capacity for separate development, staging, and production domains, plus suppression-list management and the headless analytics API. Verify current plan/API behavior before implementation and keep credentials out of code, logs, artifacts, and caches. Resend MCP support and agent plugins are optional development tooling, not a runtime requirement.
