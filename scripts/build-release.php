<?php
/**
 * Build GitHub Release zip for ART Portfolio.
 *
 * Usage: php scripts/build-release.php [output-path]
 *
 * @package Art_Portfolio
 */

if ( 'cli' === PHP_SAPI && ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ );
}

defined( 'ABSPATH' ) || exit;

/**
 * Write a message to STDERR in CLI mode.
 *
 * @param string $message Message text.
 */
function art_portfolio_build_release_stderr( $message ) {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI build script only.
	fwrite( STDERR, $message );
}

/**
 * Build release zip archive.
 *
 * @param array<int, string> $argv_list CLI arguments.
 * @return int Exit code.
 */
function art_portfolio_build_release( array $argv_list ) {
	if ( ! class_exists( 'ZipArchive' ) ) {
		art_portfolio_build_release_stderr( "ZipArchive is required.\n" );
		return 1;
	}

	$plugin_dir = dirname( __DIR__ );
	$slug       = basename( $plugin_dir );
	$output     = isset( $argv_list[1] ) ? $argv_list[1] : ( sys_get_temp_dir() . DIRECTORY_SEPARATOR . $slug . '.zip' );

	$exclude_dirs          = array( '.git', '.cursor', '.idea', '.vscode', 'node_modules', 'scripts', 'src' );
	$exclude_file_patterns = array(
		'*.zip',
		'*.log',
		'tmp-*.php',
		'local-*.php',
		'package.json',
		'package-lock.json',
	);

	$should_exclude = static function ( $relative_path ) use ( $exclude_dirs, $exclude_file_patterns ) {
		$relative_path = str_replace( '\\', '/', $relative_path );
		$parts         = explode( '/', $relative_path );

		foreach ( $parts as $part ) {
			if ( in_array( $part, $exclude_dirs, true ) ) {
				return true;
			}
		}

		$basename = basename( $relative_path );
		foreach ( $exclude_file_patterns as $pattern ) {
			if ( fnmatch( $pattern, $basename ) ) {
				return true;
			}
		}

		return false;
	};

	$zip    = new ZipArchive();
	$opened = $zip->open( $output, ZipArchive::OVERWRITE | ZipArchive::CREATE );

	if ( true !== $opened ) {
		art_portfolio_build_release_stderr( 'Cannot create zip: ' . $output . "\n" );
		return 1;
	}

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $plugin_dir, RecursiveDirectoryIterator::SKIP_DOTS )
	);

	foreach ( $iterator as $file_info ) {
		/**
		 * SplFileInfo instance for the current archive entry.
		 *
		 * @var SplFileInfo $file_info
		 */
		$absolute_path = $file_info->getPathname();
		$relative_path = substr( $absolute_path, strlen( $plugin_dir ) + 1 );

		if ( $should_exclude( $relative_path ) ) {
			continue;
		}

		$zip_path = $slug . '/' . str_replace( '\\', '/', $relative_path );

		if ( $file_info->isDir() ) {
			$zip->addEmptyDir( rtrim( $zip_path, '/' ) );
			continue;
		}

		$zip->addFile( $absolute_path, $zip_path );
	}

	$zip->close();

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI outputs a local filesystem path.
	echo $output, PHP_EOL;

	return 0;
}

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI exit code, not rendered output.
exit( art_portfolio_build_release( $argv ) );
