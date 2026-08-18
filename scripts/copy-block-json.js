const fs = require( 'fs' );
const path = require( 'path' );

const source = path.join( __dirname, '..', 'src', 'gallery', 'block.json' );
const targetDir = path.join( __dirname, '..', 'build', 'gallery' );
const target = path.join( targetDir, 'block.json' );

fs.mkdirSync( targetDir, { recursive: true } );
fs.copyFileSync( source, target );

const nestedCopy = path.join( targetDir, 'gallery' );
if ( fs.existsSync( nestedCopy ) ) {
	fs.rmSync( nestedCopy, { recursive: true, force: true } );
}

const silence = [
	'<?php',
	'/**',
	' * Silence is golden.',
	' *',
	' * @package Art_Portfolio',
	' */',
	'',
	"defined( 'ABSPATH' ) || exit;",
	'',
].join( '\n' );

fs.writeFileSync( path.join( targetDir, 'index.php' ), silence );
