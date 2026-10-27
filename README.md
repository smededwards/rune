# Rune

Portfolio blocks plugin. The editable Contact form is its first custom block.

Develop alongside `portfolio`, `obel`, `aeon` and `rune`. Use the site Makefile to run all tools inside containers. This repository has its own Composer lock and checks.

Run `npm ci`, `npm run build`, `npm run start`, `npm run lint:js` and `npm run lint:scss` inside the Node container. Generated `build/` assets are assembled in site CI, not committed.

Run `composer install` and `composer lint` in the PHP tools container. CI also syntax-checks PHP. Contact-submission behaviour is tested in Aeon.

For release, publish this repository separately and create a semantic version tag after checks pass. The site must require a real tag and update its production lock. Do not deploy a local path dependency.

## Contribution conventions

Use Conventional Commit subjects and PR titles, for example `feat(theme): add navigation` or `fix(blocks): correct editor styles`. The repository's first commit is the explicit exception: `Initial Commit`. PR CI validates titles and every commit in the PR. See `AGENTS.md` for repository guidance.

## Contact form

Insert **Contact form** on a public Page. Select it and use **Block settings → Form content** to edit the name/email/message labels, placeholders, button text and success message. The three fields stay fixed for the MVP. Surrounding introduction and profile links remain ordinary native blocks. Only one instance is supported per page.

The block uses `blocks/contact-form/block.json`, a namespaced PHP renderer and a small editor-only script compiled to `build/contact-form.js`. It saves attributes rather than HTML. Its output is escaped at render time, with visible labels, required indicators, associated field errors and an error summary. It depends on Aeon’s contact handler and intentionally renders no public form if that handler is unavailable. Obel supplies the theme styling; no frontend JavaScript is required.

After installing Composer and npm dependencies, regenerate the classmap with `composer dump-autoload` and run `npm run build`. WordPress provides the editor packages at runtime; the build extracts their native script dependencies. Build assets and vendor dependencies remain outside Git.

## Editor canvas colour mode

A native SlotFill button named **Dark editor canvas** appears in the post/page editor, Site Editor editing toolbar and block widgets editor. It changes the content canvas only. The initial appearance follows the operating-system preference until a choice is made; explicit light/dark choices use WordPress's persisted preference store under `rune.canvasColourMode`. This preference is separate from the public website's colour toggle and does not edit block attributes, saved content or global styles.

`editor/class-colour-mode.php` enqueues the toolbar script through `enqueue_block_editor_assets` and the canvas stylesheet through the admin-only `enqueue_block_assets` hook. The generated assets do not load on the public frontend. Supporting code is under `src/js/editor-colour-mode/`; styling is under `src/scss/`. The build entry is `src/editor-colour-mode.js`.

The toolbar uses core's `Fill` component and the named `PinnedItems` slots. WordPress 7.1 uses the shared `core` scope for post and site editors; widgets uses `core/edit-widgets`. Older post/site scopes are kept in the small adapter in `control.js`. These named toolbar integration points should be checked when updating WordPress; this feature was verified against the installed WordPress 7.1.3. The button follows core's pinned-toolbar visibility settings rather than injecting DOM elements into the toolbar.

The canvas controller handles non-iframed editors, editor iframe loads, replacement documents and subsequently mounted canvases. Observers and load listeners are cleaned up when the control unmounts. It does not access unrelated or cross-origin frames. Native button keyboard behaviour, an accessible label and `aria-pressed` communicate the current state; no colour transition or animation is introduced.

Canvas colours are exposed as `--rune-canvas-background`, `--rune-canvas-text`, `--rune-canvas-muted`, `--rune-canvas-line`, `--rune-canvas-panel` and `--rune-canvas-accent`. Themes can use these in their editor styles to adapt custom surfaces without changing saved block colours. Obel maps its editor colour tokens to this contract, with its existing colours as fallbacks when Rune is inactive. Explicit block colour choices are preserved; arbitrary third-party theme surfaces are not rewritten.

Run `npm run test:editor` for the six canvas lifecycle checks; CI runs them with linting and the build. Manual verification should cover keyboard activation, persistence after reload, post/site/widgets toolbar availability and unchanged surrounding editor chrome. Obel itself does not expose a widgets area, so widgets verification uses a temporary local-only sidebar fixture that is removed afterwards without saving widgets.

References: [SlotFill](https://developer.wordpress.org/block-editor/reference-guides/components/slot-fill/), [editor asset loading](https://developer.wordpress.org/block-editor/how-to-guides/enqueueing-assets-in-the-editor/).
