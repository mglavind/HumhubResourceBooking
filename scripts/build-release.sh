#!/usr/bin/env bash
#
# Builds the distributable module archive for the HumHub Marketplace.
#
# The archive contains a single top-level directory named after the module id,
# so it can be extracted directly into protected/modules/.
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

if ! command -v python3 >/dev/null 2>&1; then
    echo "python3 is required to read module.json" >&2
    exit 1
fi

read -r MODULE_ID VERSION <<<"$(python3 -c 'import json; m = json.load(open("module.json")); print(m["id"], m["version"])')"

if [ -z "$MODULE_ID" ] || [ -z "$VERSION" ]; then
    echo "Could not read id and version from module.json" >&2
    exit 1
fi

python3 - <<'PY'
import json
import os
import sys

metadata = json.load(open("module.json"))
required = [os.path.join("resources", "module_image.png")]
required += [os.path.join("resources", name) for name in metadata.get("screenshots", [])]

missing = [path for path in required if not os.path.isfile(path)]

if missing:
    sys.exit("Missing marketplace assets: " + ", ".join(missing))
PY

if grep -q "(Unreleased)" docs/CHANGELOG.md; then
    echo "Warning: docs/CHANGELOG.md still contains an (Unreleased) heading." >&2
fi

STAGE="$ROOT/dist/$MODULE_ID"
ARCHIVE="$ROOT/dist/$MODULE_ID-$VERSION.zip"

rm -rf "$STAGE" "$ARCHIVE"
mkdir -p "$STAGE"

rsync -a \
    --exclude '.git/' \
    --exclude '.github/' \
    --exclude '.phpactor/' \
    --exclude '.phpactor.json' \
    --exclude '.idea/' \
    --exclude '.vscode/' \
    --exclude '.DS_Store' \
    --exclude '.php-cs-fixer.cache' \
    --exclude '.phpunit.result.cache' \
    --exclude 'vendor/' \
    --exclude 'node_modules/' \
    --exclude 'composer.json' \
    --exclude 'composer.lock' \
    --exclude 'docker-compose.yml' \
    --exclude 'scripts/' \
    --exclude '.editorconfig' \
    --exclude '.gitignore' \
    --exclude 'tests/' \
    --exclude 'dist/' \
    --exclude '.tmp*' \
    ./ "$STAGE/"

(
    cd "$ROOT/dist"
    zip -qr "$MODULE_ID-$VERSION.zip" "$MODULE_ID"
)

rm -rf "$STAGE"

echo "Built dist/$MODULE_ID-$VERSION.zip"
