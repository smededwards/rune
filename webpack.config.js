const defaults = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaults,
	entry: {
		'editor-colour-mode': path.resolve(
			__dirname,
			'src/editor-colour-mode.js'
		),
		'contact-form': path.resolve(
			__dirname,
			'blocks/contact-form/index.js'
		),
	},
};
