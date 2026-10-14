# Releases

This page holds Ringside's release profile and checklist. The `release` agent skill reads the profile and carries the step-by-step mechanics. Any agent or person without the skill follows the checklist below. Branch and commit rules come from `AGENTS.md` ("Git workflow and Conventional Commits").

## Release profile

```yaml
version_scheme: semver
version_policy: every release bumps the patch (v0.7.x), including releases with features; bump the minor only when the owner calls a release a milestone
tag_format: vX.Y.Z               # annotated tags; see the known exceptions below for v0.4.0
integration_branch: develop
release_base: develop
release_branch: release/vX.Y.Z   # the release PR title and tag use the same version
release_commit: none             # no version file or changelog: the release branch is exactly the tip of develop
release_pr_merge: merge          # regular merge commit into main; hotfix/ branches are squash-merged into main
github_release: yes              # titled "Ringside vX.Y.Z", body starts with Highlights, public-facing (no internal checklist)
breaking_changes: ask            # a `!` commit or BREAKING CHANGE footer: the owner decides the bump
staging: none
promote: automatic               # Forge deploys main when the release PR merges: merging IS the production deploy
verify: read-only production checks (see the checklist)
backup_gate: manual              # take a database backup before merging a release that contains migrations
sync_integration: fast-forward   # never a sync merge commit or a merge-back PR; if it isn't possible, stop and explain
```

## Release checklist

Each stage runs only when the owner gives its command. Releases are batched: a release ships several finished changes, not one change at a time, unless it fixes a production bug.

1. **"Prepare the vX.Y.Z release"**
   - Confirm `develop` is clean, its CI is green and nothing is open that should ship with it.
   - Check for migrations: `git diff --name-only origin/main..origin/develop -- database/migrations`.
   - Cut `release/vX.Y.Z` at the exact tip of `develop`, push it and open a **draft** PR into `main` titled `chore(release): release vX.Y.Z`. List the commits, the user-visible changes, migrations and environment changes.
   - Draft the GitHub release notes.
   - From now until step 3, merge nothing into `develop` (including other sessions and Dependabot). An extra commit there blocks the fast-forward.
   - If CI fails because a runner timed out or got stuck, rerun the failed jobs using the retry guard below. If it is a real failure, fix it with a small PR into `develop`, then fast-forward the release branch to the new `develop` tip. Never recreate the branch or the PR.
2. **"Merge it"**
   - If the release has migrations, take a database backup first.
   - Mark the PR ready, verify base `main`, head `release/vX.Y.Z`, head SHA and green checks, then merge with a regular merge commit pinned to the head SHA, subject `chore(release): release vX.Y.Z (#N)`.
   - Forge deploys `main`. Once the live revision equals the full merge-commit SHA, run the read-only production checks ([production-operations.md](production-operations.md)): live revision, no pending migrations, Nightwatch running, scheduler running, `/up` returns 200.
3. **"Tag it and sync develop"**
   - Create an annotated tag `vX.Y.Z` on the deployed merge commit (message: the SHA and the Forge release) and push it. Never move or reuse a tag.
   - Fast-forward `develop` to `main`: `git merge-base --is-ancestor origin/develop origin/main`, then `git switch develop && git merge --ff-only origin/main && git push origin develop`.
   - If the fast-forward isn't possible, stop and change nothing. Never create a sync merge commit or a merge-back PR. Report which commits are on `develop` but not on `main` (`git log --oneline origin/main..origin/develop`), their PRs, and how they got there. The owner decides what happens next.
   - Merges into `develop` can resume.
4. **"Create the GitHub release"**
   - `gh release create vX.Y.Z --verify-tag --title "Ringside vX.Y.Z" --notes-file <notes>`, then confirm it is the latest release.

### Wait for a CI retry

Before requesting a retry, verify the parent CI run's `head_sha` equals the full
candidate SHA and capture `run_attempt + 1`. Request the retry once, then use the
shared bounded waiter with that recorded attempt. A SHA-only green result can
still describe older checks while the new attempt is registering.

Replace the quoted placeholders with the selected CI run ID and full candidate
SHA. The commands stop if the run belongs to a different SHA or the retry request
fails:

```bash
repo=JeffreyDavidson/Ringside
run_id='<CI-run-id>'
sha='<full-candidate-SHA>'
run=$(gh api "repos/$repo/actions/runs/$run_id") &&
jq -e --arg sha "$sha" '.head_sha == $sha' <<<"$run" &&
next_attempt=$(jq -er '.run_attempt + 1' <<<"$run") &&
gh run rerun "$run_id" --failed --repo "$repo" &&
~/.agents/skills/merge-pr/scripts/wait-for-checks.sh "$sha" auto 2400 "$repo" --require-run "$run_id:$next_attempt"
```

For a cancelled whole run, omit `--failed`; for a single job, replace it with
`--job <job-database-id>`. Capture the parent run's next attempt first in either
case. Never calculate it after rerunning. Repeat `--require-run RUN_ID:ATTEMPT`
when several CI runs must pass. The guard adds to the commit checks and waits
through delayed registration; a missing or pending attempt cannot count as green.
After success, freshly verify the PR head SHA, merge state and checks before merge.

### Hotfix

A hotfix is only for a production bug that can't wait for `develop`. Branch `hotfix/<name>` from the deployed tag, open a PR into `main` and squash-merge it, then tag it as the next patch. Afterwards bring the fix into `develop` with a normal PR. If `main` already matches `develop`, do a normal fix PR and a patch release instead.

### Notes

- **Pre-push hook:** it runs the full suite on branch pushes. A timing flake in a browser test can block the push; retry the push and never bypass the hook. Pushes that carry only tags or branch deletions skip the checks.
- **Merged branches:** GitHub deletes them automatically. Delete the local release branch with `git branch -d`.
- **Branch protection:** `main` is protected and takes changes only through PRs with green checks. A GitHub ruleset on `develop` blocks force-pushes and deletion. Normal pushes and the post-release fast-forward are still allowed.

## Known exceptions in the release history

Releases before v0.5.0 predate parts of this workflow. Pushed history and existing tags are never rewritten, so these stay as they are:

- **v0.2.0** was squash-merged into `main` (#1604) instead of merged with a merge commit.
- **v0.2.1** (#1606), **v0.2.2** (#1610) and **v0.4.0** (#1705) kept GitHub's default "Merge pull request …" subject instead of `chore(release): release vX.Y.Z`.
- **v0.2.2** was merged from `develop` straight into `main` without a `release/` branch.
- **v0.4.0**'s tag is lightweight. Every tag from v0.5.0 on is annotated.
- **v0.2.0, v0.2.1 and v0.3.0** were not tagged when they were released. Their annotated tags and GitHub releases, and the v0.1.0 GitHub release, were added on 2026-10-08 on the original release commits. Their tag messages say so.
- Commits from before 2026-09-20, when the Conventional Commits rule was added, don't all follow it.
