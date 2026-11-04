#!/bin/sh
# Check each combination of pattern settings on a dedicated WordPress site.
set -eu
: "${WP_PATH:?Set WP_PATH to the WordPress installation}"
wp_run() { wp --path="$WP_PATH" "$@"; }
repo_dir=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
if backup=$(wp_run option get rune_editor_features --format=json 2>/dev/null); then
	existed=1
else
	existed=0
	backup='{}'
fi
pattern_id=''
restore() {
	if [ "$existed" = 1 ]; then
		wp_run option update rune_editor_features "$backup" --format=json >/dev/null
	else
		wp_run option delete rune_editor_features >/dev/null 2>&1 || true
	fi
	if [ -n "$pattern_id" ]; then
		wp_run post delete "$pattern_id" --force >/dev/null
	fi
}
trap restore EXIT
pattern_id=$(wp_run post create --post_type=wp_block --post_status=publish --post_title='Rune pattern integration' --post_content='<!-- wp:paragraph --><p>Rune pattern verification.</p><!-- /wp:paragraph -->' --porcelain)
wp_run option update rune_editor_features '{"patterns_shortcut":true,"core_patterns":true,"remote_patterns":true}' --format=json >/dev/null
wp_run eval-file "$repo_dir/tests/patterns.php" default "$pattern_id"
for flags in 000 001 010 011 100 101 110 111; do
	shortcut=$(printf '%s' "$flags" | cut -c1)
	core=$(printf '%s' "$flags" | cut -c2)
	remote=$(printf '%s' "$flags" | cut -c3)
	values=$(printf '{"patterns_shortcut":%s,"core_patterns":%s,"remote_patterns":%s,"simplify_blocks":true,"hide_openverse":true}' "$shortcut" "$core" "$remote")
	wp_run option update rune_editor_features "$values" --format=json >/dev/null
	wp_run eval-file "$repo_dir/tests/patterns.php" "$flags" "$pattern_id"
done
