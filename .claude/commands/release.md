# Release

Release `anilcancakir/magic-starter-laravel` X.Y.Z to Packagist and GitHub Releases. The release lands through a `release: X.Y.Z` pull request; the bare tag on its merge commit starts `.github/workflows/publish.yml`, which does the rest.

**Usage**: `/release X.Y.Z` (e.g. `/release 1.0.1`). The version is bare: no `v`.

## 1. Pre-flight

1. `git checkout master && git pull --ff-only origin master`; `git status --porcelain` must be empty.
2. `composer test`, `composer lint` and `composer analyse` must pass. Stop and fix on a failure; never skip one.
3. `CHANGELOG.md` must have entries under `## [Unreleased]`. An empty section means there is nothing to release.

## 2. The release pull request

1. `git checkout -b release/X.Y.Z`.
2. Stamp the two version files, which `publish.yml` checks against the tag:
   - `CHANGELOG.md`: rename `## [Unreleased]` to `## [X.Y.Z] - YYYY-MM-DD` (today's date) and add a fresh empty `## [Unreleased]` above it. The section's body becomes the GitHub Release notes.
   - `resources/boost/skills/magic-starter-development/SKILL.blade.php`: set the top-level `version: X.Y.Z` line in the frontmatter.
3. `git commit -am "release: X.Y.Z"`, `git push -u origin release/X.Y.Z`, then `gh pr create --base master --title "release: X.Y.Z" --body "Release X.Y.Z."`.
4. Wait for every required check to pass, then `gh pr merge <number> --merge`. Never `--squash` and never `--rebase`: the tag has to sit on the merge commit of a reviewed history.

## 3. Tag the merge commit

1. `git checkout master && git pull --ff-only origin master && git fetch origin`.
2. `sha=$(gh pr view <number> --json mergeCommit --jq .mergeCommit.oid)`, then confirm `git rev-parse origin/master` prints the same SHA. Stop if it does not: something else merged, and the tag would carry it.
3. `git tag X.Y.Z "$sha" && git push origin X.Y.Z`.

## 4. What publish.yml does

The tag runs three jobs in order; each starts only when the one before it passed.

| Job | Does |
|---|---|
| `Validate` | Refuses a tag that is not on `master`, a skill `version:` that differs from the tag, and a tag without a `## [X.Y.Z]` CHANGELOG section; runs PHPUnit, Pint and PHPStan |
| `publish` | Asks Packagist to update `anilcancakir/magic-starter-laravel` |
| `GitHub Release` | Creates the release `X.Y.Z` with the CHANGELOG section as its notes |

Watch the run of this commit, not whichever run is newest: `gh run watch $(gh run list --workflow=publish.yml --commit "$(git rev-parse 'X.Y.Z^{commit}')" --limit=1 --json databaseId --jq '.[0].databaseId')`. Retry once if the list is empty, since a new run takes a moment to appear.

A `Validate` failure stops the `publish` job, but the repository's Packagist webhook reacts to the pushed tag on its own, so check https://packagist.org/packages/anilcancakir/magic-starter-laravel first: a version it lists is final, fix forward with the next version. If it does not list the tag, a re-run builds the original commit and cannot pick up a fix: fix the cause through a pull request, then ask before deleting the pushed tag (`git push --delete origin X.Y.Z` and `git tag -d X.Y.Z`; a remote delete is not yours to decide), check Packagist once more right before the delete, and tag the new merge commit. A transient failure (a runner or network error) is the only case for **Re-run failed jobs**.

When `publish` succeeded and only `GitHub Release` failed, use **Re-run failed jobs**, never **Re-run all jobs**: re-running everything repeats the Packagist update, and `gh release create` refuses a release that already exists. Or create the release by hand: `gh release create X.Y.Z --title X.Y.Z --latest --verify-tag --notes-file <file>`. A version Packagist has accepted is final: fix forward with the next version.

## 5. Verify

- `gh release view X.Y.Z` shows the notes from the CHANGELOG section.
- https://packagist.org/packages/anilcancakir/magic-starter-laravel lists X.Y.Z.
