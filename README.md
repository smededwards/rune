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
