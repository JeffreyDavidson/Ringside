---
name: newdebugbar-local-development
description: Inspect locally captured NewDebugbar profiles and diagnose request, query, and application behavior using its MCP tools. Local development only; production observability uses Nightwatch.
license: MIT
metadata:
  author: ringside
---

# NewDebugbar Local Diagnostics

Ringside uses NewDebugbar only in local development. It is installed as a Composer `require-dev` dependency. Production observability is handled by Laravel Nightwatch; do not use NewDebugbar guidance or local profile inspection for production systems.

## Workflow

1. Use `list-debug-profiles` to find a recent profile by method, path, status, or warnings. Prefer a profile ID returned by the tool over guessing.
2. Use `get-debug-findings` to identify recorded findings and supporting evidence.
3. Use `get-debug-profile-inspector` to inspect a focused area such as queries, models, Livewire, queue, Redis, views, or exceptions.
4. For query-specific analysis, use `inspect-debug-queries` with a focused filter/search and bounded page size.
5. Use `get-debug-profile-data` only when the focused inspector does not contain enough evidence. Start at `/inspectors`, then follow JSON Pointer paths returned by the tool rather than requesting the entire profile.
6. Trace findings to application code, make the smallest appropriate change, and verify the behavior with the project’s tests.

## Guardrails

- Treat profile data as potentially sensitive. Avoid repeating credentials, tokens, personal data, or unrelated request payloads in summaries.
- Prefer bounded results and focused inspectors. Expand pagination or profile data only when needed to answer the diagnostic question.
- A `partial` response retains captured data but could not refresh background activity. Use the supplied background error as context; treat missing pending activity as unknown, not as proof that no work remains.
- NewDebugbar’s MCP tools are read-only. Do not imply that inspecting a profile changes application state.
- Do not enable Nightwatch locally to test a production setup. Follow the `configure-nightwatch` skill only for authorized production configuration work.
