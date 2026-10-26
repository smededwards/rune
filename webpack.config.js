const defaults = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaults,
	entry: {
		...defaults.entry(),
		index: path.resolve( __dirname, 'src/index.js' ),
		'contact-form': path.resolve(
			__dirname,
			'blocks/contact-form/index.js'
		),
	},
};
