#!/usr/bin/env bash
# Creates a basic local Kirby 6 site in this folder with the kirby-emojis plugin linked.
#
# Usage: ./setup.sh [--local | --ddev] [--no-seed]
#   --local    use the PHP and Composer installed on this machine
#   --ddev     run everything inside a DDEV project (https://ddev.com)
#   --no-seed  skip copying the test content from seed/ (blank Plainkit site)
# Without --local/--ddev you are asked interactively (non-interactive default: --local).
set -euo pipefail
cd "$(dirname "$0")"

PACKAGE="jeslage/kirby-emojis"
KIT="getkirby/plainkit:^6.0.0-beta.1"
DDEV_PROJECT="kirby-emojis"
DDEV_URL="https://${DDEV_PROJECT}.ddev.site"
LOCAL_URL="http://localhost:8000"
# Name of the plugin's folder (the parent of this playground), used for the DDEV mount.
PLUGIN_DIR="$(basename "$(cd .. && pwd)")"

mode=""
seed=1
for arg in "$@"; do
  case "$arg" in
    --local) mode="local" ;;
    --ddev) mode="ddev" ;;
    --no-seed) seed=0 ;;
    -h|--help)
      sed -n '2,8p' "$(basename "$0")" | sed 's/^# \{0,1\}//'
      exit 0
      ;;
    *)
      echo "Unknown option: $arg (use --local, --ddev or --no-seed)" >&2
      exit 1
      ;;
  esac
done

if [ -d kirby ] || [ -f vendor/getkirby/cms/bootstrap.php ]; then
  echo "Kirby is already installed here." >&2
  exit 0
fi

if [ -z "$mode" ]; then
  if [ -t 0 ]; then
    read -r -p "Set up with [1] local PHP or [2] DDEV? [1] " choice
    case "${choice:-1}" in
      1|local) mode="local" ;;
      2|ddev) mode="ddev" ;;
      *) echo "Invalid choice: $choice" >&2; exit 1 ;;
    esac
  else
    mode="local"
  fi
fi

require() {
  for cmd in "$@"; do
    if ! command -v "$cmd" >/dev/null 2>&1; then
      echo "'$cmd' is required for the $mode setup but was not found." >&2
      exit 1
    fi
  done
}

if [ "$mode" = "ddev" ]; then
  require ddev
  run_composer() { ddev composer "$@"; }
  # `ddev composer create-project` refuses subdirectories, so bypass the wrapper.
  run_create_project() { ddev exec composer create-project "$@"; }
  run_bash() { ddev exec bash "$@"; }
  # With Mutagen (default on macOS) host and container are synced asynchronously.
  # Flush after host-side writes the container needs and vice versa. No-op otherwise.
  sync_files() { ddev mutagen sync >/dev/null 2>&1 || true; }
else
  require php composer
  run_composer() { composer "$@"; }
  run_create_project() { composer create-project "$@"; }
  run_bash() { bash "$@"; }
  sync_files() { :; }
fi

echo "Setting up the Kirby playground ($mode)..."

if [ "$mode" = "ddev" ]; then
  if [ ! -f .ddev/config.yaml ]; then
    ddev config \
      --project-name="$DDEV_PROJECT" \
      --project-type=php \
      --docroot=. \
      --php-version=8.4
  fi
  # The plugin is this folder's parent, outside the DDEV project root. Mount it at
  # /var/<plugin-dir>: the container's project root is /var/www/html, so the Composer path
  # repository ../../<plugin-dir> resolves on the host and in the container alike.
  cat > .ddev/docker-compose.plugin.yaml <<YAML
services:
  web:
    volumes:
      - \${DDEV_APPROOT}/..:/var/${PLUGIN_DIR}
YAML
  ddev start
fi

# Install into a temp dir inside this folder (visible to the DDEV container), then move
# the files next to README.md / setup.sh without overwriting them. The move runs where
# Composer ran (container or host), so Mutagen doesn't have to shuffle vendor/ around.
tmp=".install"
rm -rf "$tmp"
trap 'rm -rf "$tmp"' EXIT
mkdir -p "$tmp"
cat > "$tmp/move.sh" <<'SH'
set -euo pipefail
shopt -s dotglob
for f in .install/site/*; do
  name="$(basename "$f")"
  case "$name" in README.md|setup.sh|seed|.ddev|.gitignore|.git) continue ;; esac
  rm -rf "./$name"
  mv "$f" .
done
rm -rf .install/site
SH
sync_files
run_create_project "$KIT" "$tmp/site" --prefer-dist --no-interaction
run_bash "$tmp/move.sh"
sync_files
rm -rf "$tmp"

if [ ! -f kirby/bootstrap.php ]; then
  echo "Kirby install failed: no kirby/bootstrap.php in $(pwd)." >&2
  exit 1
fi

# Link the plugin via a path repository. getkirby/composer-installer puts it into
# site/plugins/kirby-emojis as a symlink, so edits apply immediately.
run_composer config repositories.kirby-emojis path "../../${PLUGIN_DIR}"
run_composer config minimum-stability dev
run_composer config prefer-stable true
run_composer require "${PACKAGE}:@dev" --no-interaction

if [ "$seed" = 1 ]; then
  # Blueprints, templates, content and custom emojis covering the plugin's features.
  echo "Copying test content from seed/..."
  cp -R seed/. .
  sync_files
fi

echo
if [ "$mode" = "ddev" ]; then
  echo "Done. Open ${DDEV_URL}/panel (ddev launch /panel) and create the first user."
else
  echo "Done. Run: composer start, then open ${LOCAL_URL}/panel and create the first user."
fi
echo "Panel assets: run 'npm run dev' in the plugin root (see README.md)."
