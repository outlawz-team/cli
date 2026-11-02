#!/usr/bin/env bash
# Releases the cli with git flow: builds the phar with the version, tags it and pushes master, develop
# and the tag. composer global require and outlawz self-update both take builds/outlawz from the tag,
# so the phar has to be built in the release itself.
#
#   scripts/release.sh 1.2.0
set -euo pipefail

version=${1:-}
if [[ ! $version =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
  echo "Usage: scripts/release.sh <version>, for example 1.2.0" >&2
  exit 1
fi

cd "$(dirname "$0")/.."

if [ "$(git branch --show-current)" != develop ]; then
  echo "Run it on develop." >&2
  exit 1
fi
if ! git diff --quiet || ! git diff --cached --quiet; then
  echo "Commit or stash your changes first." >&2
  exit 1
fi
if git rev-parse -q --verify "refs/tags/$version" > /dev/null; then
  echo "Tag $version already exists." >&2
  exit 1
fi

git pull --ff-only origin develop
git fetch origin master:master

git flow release start "$version"
php outlawz app:build outlawz --build-version="$version"
built=$(php builds/outlawz --version)
if [ "$built" != "Outlawz $version" ]; then
  echo "builds/outlawz says '$built', not 'Outlawz $version'." >&2
  exit 1
fi
git commit -m "build: outlawz $version" -- builds/outlawz
GIT_MERGE_AUTOEDIT=no git flow release finish -m "$version" "$version"

git push origin master develop "$version"
echo "Released $version: the workflow Release checks the phar and creates the release on GitHub."
