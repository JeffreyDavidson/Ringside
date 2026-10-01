# Releases

The `release` agent skill reads the profile below before it runs a release. The branch and merge rules come from `AGENTS.md` ("Git workflow and Conventional Commits"); this page summarizes them in one place.

## Release profile

```yaml
version_scheme: semver
version_policy: pre-1.0 feature releases bump the minor (v0.4.0 and v0.5.0 did); patch is for fix-only releases
tag_format: vX.Y.Z               # annotated tags only; v0.4.0 was created lightweight, v0.5.0 onwards is annotated
integration_branch: develop
release_base: develop
release_branch: release/vX.Y.Z   # the release PR title and tag use the same version
release_commit: none             # no version file or changelog: the release branch is exactly the tip of develop
release_pr_merge: merge          # regular merge commit into main; hotfix/ branches are squash-merged into main
github_release: yes              # titled "Ringside vX.Y.Z", body starts with Highlights, public-facing (no internal checklist)
breaking_changes: ask            # decide the bump per release when a commit carries `!` or a BREAKING CHANGE footer
staging: none
promote: automatic               # Forge deploys main when the release PR merges: merging IS the production deploy
verify: manual                   # watch the Forge deployment; check that migrations ran (Forge dashboard)
backup_gate: manual              # take a database backup before merging a release that contains migrations
sync_integration: fast-forward   # develop fast-forwards to main after the release; no sync merge commit
```

## Running a release

1. Pause merges into `develop` (including from other sessions and Dependabot) from the moment the release branch is cut until `develop` has been fast-forwarded; an extra commit there makes the fast-forward impossible and forces a merge-back PR instead.
2. Cut `release/vX.Y.Z` at the exact tip of `develop`, open a draft PR titled `chore(release): release vX.Y.Z`, and wait for all required checks.
3. Before merging: take a database backup if the release has migrations (`git diff --name-only origin/main..origin/develop -- database/migrations`). A migration with a pre-flight (for example the single-current-tag-team-membership index) aborts with instructions instead of changing data, but leaves the deploy stopped until an operator fixes the data.
4. Merge with a regular merge commit pinned to the head SHA, then watch the Forge deployment.
5. Tag the merge commit with an annotated tag, push the tag, create the GitHub release, fast-forward `develop` to `main`, and delete the merged release branch.

Notes:

- The pre-push hook runs the full suite on tag and branch pushes too; a timing flake in a browser test can block the push. Retry the push; do not bypass the hook.
- Deleting a merged remote branch through the GitHub API (`gh api -X DELETE repos/<owner>/<repo>/git/refs/heads/<branch>`) avoids a full test run for a push that carries no code.
