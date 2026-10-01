# Releases

The `release` agent skill reads the profile below before it runs a release. The branch and merge rules come from `AGENTS.md` ("Git workflow and Conventional Commits"); this page summarizes them in one place.

## Release profile

```yaml
version_scheme: semver
tag_format: vX.Y.Z               # currently 0.y.z; use annotated tags (v0.4.0 was created lightweight)
integration_branch: develop
release_base: develop
release_branch: release/vX.Y.Z   # the release PR title and tag use the same version
release_pr_merge: merge          # regular merge commit into main; hotfix/ branches are squash-merged into main
github_release: yes              # titled "Ringside vX.Y.Z", body starts with Highlights
breaking_changes: ask            # decide the bump per release when a commit carries `!` or a BREAKING CHANGE footer
```

Not recorded here, so the skill asks before acting: whether a `chore(release): ...` commit is made on the release branch, how staging and production are deployed, and how `develop` is synchronized after a release.
