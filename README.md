# Rune

Rune is a WordPress blocks plugin. Its first custom block is an editable Contact form.

## Name

Rune refers to symbolic writing. Individual marks can form larger expressions, much as reusable blocks combine into pages; the name speaks to composition without prescribing a particular visual style.

Develop Rune alongside `portfolio`, `obel` and `aeon`. The site Makefile runs tools in containers. Rune has its own Composer lock and checks.

Run `npm ci`, `npm run build`, `npm run start`, `npm run lint:js` and `npm run lint:scss` in the Node container. Site CI builds the generated assets; `build/` files are not committed.

Run `composer install` and `composer lint` in the PHP tools container. CI syntax-checks PHP. Aeon tests contact submissions.

After checks pass, publish Rune separately and create a semantic version tag. Update the site’s production lock to use that tag. Production must use tagged releases rather than local path dependencies.

## Contribution conventions

Use Conventional Commit subjects and PR titles, such as `feat(theme): add navigation` or `fix(blocks): correct editor styles`. Name the first commit `Initial Commit`. PR CI checks titles and commits. See `AGENTS.md` for details.

## Contact form

Add **Contact form** to a public Page. Select the block and use **Block settings → Form content** to edit the name, email and message labels, placeholders, submit button and success message. The fields are fixed for the MVP. Build the introduction and profile links with native blocks. Use one Contact form block per Page.

The block is defined in `blocks/contact-form/block.json`, rendered by namespaced PHP and configured with an editor script compiled to `build/contact-form.js`. It saves attributes and renders escaped markup with visible labels, required indicators, field errors and an error summary. Aeon handles submissions; without Aeon, Rune does not render the public form. Obel supplies the styles. The form needs no frontend JavaScript.

After installing Composer and npm dependencies, run `composer dump-autoload` to update the classmap and `npm run build` to compile assets. WordPress provides editor packages at runtime, and the build extracts their script dependencies. Generated assets and Composer dependencies are excluded from Git.

## Editor canvas colour mode

The post and page editor, Site Editor and Widgets editor have a SlotFill toolbar button named **Dark editor canvas**. It changes the content canvas. Before the user chooses a mode, Rune follows the operating-system preference. It saves explicit choices in WordPress’s preference store under `rune.canvasColourMode`. The editor setting is separate from the public colour toggle and does not change block attributes, saved content or global styles.

`editor/class-colour-mode.php` loads the toolbar script through `enqueue_block_editor_assets` and the canvas stylesheet through the admin-only `enqueue_block_assets` hook. These assets are not loaded on the public site. The supporting code is in `src/js/editor-colour-mode/`, styles in `src/scss/`, and the build entry in `src/editor-colour-mode.js`.

The toolbar uses WordPress’s `Fill` component and named `PinnedItems` slots. In WordPress 7.1, post and site editors use the `core` scope; Widgets uses `core/edit-widgets`. `control.js` maps the older post and site editor scopes. Check these toolbar slots when updating WordPress. The feature was verified with WordPress 7.1.3. Core’s pinned-toolbar settings control button visibility.

The canvas controller supports iframed and non-iframed editors, including replacement documents and canvases mounted after load. It removes observers and load listeners when the control unmounts, and only accesses the editor’s own frame. The native button supports keyboard input; its accessible label and `aria-pressed` state announce the current mode. The toggle does not animate colour changes.

Rune exposes these canvas colour variables: `--rune-canvas-background`, `--rune-canvas-text`, `--rune-canvas-muted`, `--rune-canvas-line`, `--rune-canvas-panel` and `--rune-canvas-accent`. Themes can use them for editor surfaces. Obel maps its editor tokens to the variables and falls back to its own colours when Rune is inactive. Saved block colours and third-party theme surfaces are left unchanged.

Run `npm run test:editor` for six canvas lifecycle checks. CI runs them with linting and the build. Also check keyboard activation, saved preference after reload, toolbar availability in post, site and Widgets editors, and the surrounding editor interface. Obel has no Widgets area, so Widgets testing uses a temporary local sidebar fixture. Remove it without saving any Widgets.

References: [SlotFill](https://developer.wordpress.org/block-editor/reference-guides/components/slot-fill/), [editor asset loading](https://developer.wordpress.org/block-editor/how-to-guides/enqueueing-assets-in-the-editor/).
