# Product Direction and Roadmap

## Purpose and audience

Ringside helps wrestling promoters manage rosters, events, matches, and
championships while tracking eligibility and historical changes. Its value is
reducing administrative work so promoters can focus on their shows.

Primary audiences are independent promoters, regional promotion owners,
operations managers, and wrestling academies. Talent self-service and public
fan/media access are longer-term product ideas, not promises of current access.

The intended workflows are roster setup, reviewing availability, building an
event card, assigning competitors and officials, recording results, and
maintaining championship history.

## Status and sources of truth

This document preserves product intent from the retired Agent OS plans. It is
not a release checklist or evidence that a feature is implemented. The old
completion checkboxes, performance claims, and coverage claims were not carried
forward as verified facts.

- Current domain behavior belongs in [architecture documentation](architecture/core-capabilities.md).
- Architecture improvement candidates belong in the [refactoring backlog](architecture/refactoring-backlog.md).
- Executable work and progress belong in the Ringside Hermes Kanban board.
- Framework versions and quality commands come from the dependency manifests
  and [development commands](development/commands.md).

Before scheduling any item below, inspect the current implementation and create
a bounded Kanban task with acceptance criteria. Historical priorities need
reconfirmation; this migration does not authorize implementation.

## Retained near-term direction

The historical sequence was a custom admin interface, followed by promotion
ownership and data isolation. Preserve that context without treating the old
sequence as a current delivery commitment.

1. [Admin interface direction](guides/admin-interface-direction.md): consistent
   components, responsive shell, authentication, dashboard, and entity pages.
2. [Promotion management](guides/promotion-management-plan.md): promotion
   ownership, switching, settings, and isolation across roster and event data.
3. Dashboard and analytics: roster availability and career statistics,
   championship reigns, match performance, and event/venue reporting.

## Longer-term candidates

| Area | Retained ideas |
| --- | --- |
| API and integrations | Documented API, rate limits, webhooks, calendar export, social publishing, email marketing, ticketing integrations |
| Creative planning | Storylines, feuds, alliances, angles, character development, booking assistance |
| Business operations | Contracts, payroll, event revenue and expenses, budgets, forecasts |
| Cross-promotion work | Shared events, talent loans/trades, working agreements, comparative analytics |
| Team operations | Multi-user access, approval workflows, audit trails, import/export |
| Fan and media tools | Public promotion information, statistics, voting, comments, press releases, media assets, commentary and broadcast tools |
| Operational readiness | Deployment improvements, monitoring, backup/recovery, security and privacy reviews; evaluate containerization only if needed |

Potential measures include time spent on administration, booking errors,
data accuracy, usability on mobile, and dashboard usefulness. Establish baselines
before setting targets; the old numerical targets were aspirations, not results.

## Historical engineering checklist

The old entity specs also proposed relationship naming changes, builder naming,
bookability consistency, match pivot/competitor creation fixes, side assignment,
event eligibility, double-booking prevention, competitor limits, stable member
counting and manager relationships, lifecycle terminology, and title eligibility.
These are historical review topics, not newly reported bugs. Reconcile them with
current code, tests, and the architecture backlog before opening work; some may
already be resolved or superseded.

## Retired source material

The 79 files formerly under `.agent-os` are preserved in Git at commit
`82104235255ac6eb6a16b9812dd4c19590b5a772`. For example:

```sh
git show 82104235255ac6eb6a16b9812dd4c19590b5a772:.agent-os/product/prd.md
git ls-tree -r --name-only 82104235255ac6eb6a16b9812dd4c19590b5a772 -- .agent-os
```

Product intent was consolidated here and into the two linked plans. Historical
entity specs and task lists remain available through Git. Generic standards,
execution templates, duplicate summaries, and the completed login-page and PRD
consolidation plans were retired. Use `AGENTS.md`, `.ai/rules`, and current
project skills for development instructions.
