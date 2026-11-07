#!/bin/sh
# Check project metadata registration and the Block Bindings API source.
set -eu
: "${WP_PATH:?Set WP_PATH to the WordPress installation}"
repo_dir=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
wp --path="$WP_PATH" eval-file "$repo_dir/tests/project-details.php"
