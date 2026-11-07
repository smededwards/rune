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
		'experience-years': path.resolve(
			__dirname,
			'blocks/experience-years/index.js'
		),
		'project-details': path.resolve( __dirname, 'src/project-details.js' ),
		augur: path.resolve( __dirname, 'src/augur.js' ),
	},
};
