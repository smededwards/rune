# Rune

Rune is a WordPress blocks and editor plugin. It provides an editable Contact Form, editor controls and pattern settings.

## Name

Rune refers to symbolic writing. Individual marks can form larger expressions, much as reusable blocks combine into pages; the name speaks to composition without prescribing a particular visual style.

Develop Rune alongside `portfolio`, `obel` and `aeon`. The site Makefile runs tools in containers. Rune has its own Composer lock and checks.

Run `npm ci`, `npm run build`, `npm run start`, `npm run lint:js` and `npm run lint:scss` in the Node container. Site CI builds the generated assets; `build/` files are not committed.

Run `composer install` and `composer lint` in the PHP tools container. CI syntax-checks PHP. Aeon tests contact submissions.

After checks pass, publish Rune separately and create a semantic version tag. Update the site’s production lock to use that tag. Production must use tagged releases rather than local path dependencies.

## Contribution Conventions

Use Conventional Commit subjects and PR titles, such as `feat(theme): add navigation` or `fix(blocks): correct editor styles`. Name the first commit `Initial Commit`. PR CI checks titles and commits. See `AGENTS.md` for details.

## Contact Form

Add **Contact Form** to a public Page. Select the block and use **Block Settings → Form Content** to edit the name, email and message labels, placeholders, submit button and success message. The fields are fixed for the MVP. Build the introduction and profile links with native blocks. Use one Contact Form block per Page.

The block is defined in `blocks/contact-form/block.json`, rendered by namespaced PHP and configured with an editor script compiled to `build/contact-form.js`. It saves attributes and renders escaped markup with visible labels, required indicators, field errors and an error summary. Aeon handles submissions; without Aeon, Rune does not render the public form. Obel supplies the styles. The form needs no front-end JavaScript.

After installing Composer and npm dependencies, run `composer dump-autoload` to update the classmap and `npm run build` to compile assets. WordPress provides editor packages at runtime, and the build extracts their script dependencies. Generated assets and Composer dependencies are excluded from Git.

## Editor Controls

Under **Settings → Rune**, the **Block Editor** settings control the available blocks and Openverse media source. **Patterns** settings control the native Patterns shortcut, bundled WordPress patterns and remote pattern directory. **Augur** controls whether its editor action is available. These controls do not unregister blocks, alter saved content or remove saved patterns.

Rune copies existing editor preferences from Aeon into its own `rune_editor_features` option on first load. Existing selections are preserved, and Rune’s settings do not delete or modify Aeon’s saved option.

The controls are implemented in `editor/class-features.php`, `editor/class-patterns.php` and `editor/class-settings.php`. Obel remains responsible for theme-specific presentation and editor styles.

## Editor Canvas Colour Mode

The post and page editor, Site Editor and Widgets editor have a SlotFill toolbar button named **Dark Editor Canvas**. It changes the content canvas. Before the user chooses a mode, Rune follows the operating-system preference. It saves explicit choices in WordPress’s preference store under `rune.canvasColourMode`. The editor setting is separate from the public colour toggle and does not change block attributes, saved content or global styles.

`editor/class-colour-mode.php` loads the toolbar script through `enqueue_block_editor_assets` and the canvas stylesheet through the admin-only `enqueue_block_assets` hook. These assets are not loaded on the public site. The supporting code is in `src/js/editor-colour-mode/`, styles in `src/scss/`, and the build entry in `src/editor-colour-mode.js`.

The toolbar uses WordPress’s `Fill` component and named `PinnedItems` slots. In WordPress 7.1, post and site editors use the `core` scope; Widgets uses `core/edit-widgets`. `control.js` maps the older post and site editor scopes. Check these toolbar slots when updating WordPress. The feature was verified with WordPress 7.1.3. Core’s pinned-toolbar settings control button visibility.

The canvas controller supports iframed and non-iframed editors, including replacement documents and canvases mounted after load. It removes observers and load listeners when the control unmounts, and only accesses the editor’s own frame. The native button supports keyboard input; its accessible label and `aria-pressed` state announce the current mode. The toggle does not animate colour changes.

Rune exposes these canvas colour variables: `--rune-canvas-background`, `--rune-canvas-text`, `--rune-canvas-muted`, `--rune-canvas-line`, `--rune-canvas-panel` and `--rune-canvas-accent`. Themes can use them for editor surfaces. Obel maps its editor tokens to the variables and falls back to its own colours when Rune is inactive. Saved block colours and third-party theme surfaces are left unchanged.

Run `npm run test:editor` for six canvas lifecycle checks. CI runs them with linting and the build. Also check keyboard activation, saved preference after reload, toolbar availability in post, site and Widgets editors, and the surrounding editor interface. Obel has no Widgets area, so Widgets testing uses a temporary local sidebar fixture. Remove it without saving any Widgets.

References: [SlotFill](https://developer.wordpress.org/block-editor/reference-guides/components/slot-fill/), [editor asset loading](https://developer.wordpress.org/block-editor/how-to-guides/enqueueing-assets-in-the-editor/).

## Project Details Bindings

Rune registers Role, Year and Technologies metadata for Pages and exposes them through the `rune/project-meta` Block Bindings source. The **Project Details** panel saves the values through the normal editor REST workflow. Core Paragraph and Heading blocks can consume the values without becoming custom blocks.

For example, a bound Paragraph can use this saved markup:

```html
<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"rune/project-meta","args":{"field":"role"}}}}} -->
<p>Role</p>
<!-- /wp:paragraph -->
```

Rune owns the metadata and binding source; the active theme owns the layout and presentation.

## Code Block Copy Control

Rune enhances native **Code** blocks with an accessible **Copy** button on the front-end. The block keeps WordPress’s saved `core/code` markup and editor behaviour; Rune adds the control only when the block is rendered. The button uses the Clipboard API with a small fallback for browsers that do not expose it, and reports the result through a live status message.

The server-side wrapper lives in `frontend/class-code-copy.php`. Its front-end behaviour is built from `src/code-copy.js`, with presentation in `src/scss/code-copy.scss`. Existing code content is read from the native `<code>` element, so indentation and line breaks remain intact.

## Augur

Augur adds an **Improve Clarity** action to the block editor for selected paragraph and heading blocks. It sends only the selected block text to a server-side provider and shows the response for review before applying it. Saving the post uses WordPress's normal revision workflow.

Configure the provider outside the browser with `AUGUR_PROVIDER` (`openai` or `gemini`), `AUGUR_MODEL` and `AUGUR_API_KEY`, then enable Augur under **Settings → Rune → Augur**. Keep the key in the environment or server configuration; it is never exposed to editor JavaScript. The first version has no usage limiter and supports block-level rather than inline text selection.
