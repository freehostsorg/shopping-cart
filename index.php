<?php
/*
Script Name: WP Quick Install
Author: Jonathan Buttigieg
Contributors: Julio Potier
Script URI: http://wp-quick-install.com
Version: 1.4.1
Licence: GPLv3
Last Update: 08 jan 15
*/
/*
Changes: 02.03.2017 Len Johnson <https://radixmo.com>
- modified to fix issue #30 - now installs plugins
- @see: https://github.com/GeekPress/WP-Quick-Install/issues/30

- functionality simplified to only download WordPress and selected plugins
*/

@set_time_limit( 0 );
// https://codex.wordpress.org/WordPress.org_API#Version_Check
define( 'WP_API_CORE'				, 'http://api.wordpress.org/core/version-check/1.7/?locale=' );
define( 'WPQI_CACHE_PATH'			, 'cache/' );
define( 'WPQI_CACHE_CORE_PATH'		, WPQI_CACHE_PATH . 'core/' );
define( 'WPQI_CACHE_PLUGINS_PATH'	, WPQI_CACHE_PATH . 'plugins/' );

require( 'inc/functions.php' );

// Force URL with index.php
if ( empty( $_GET ) && end( ( explode( '/' , trim($_SERVER['REQUEST_URI'], '/') ) ) ) == 'shopping-cart' ) {
	header( 'Location: index.php' );
	die();
}

// Create cache directories
if ( ! is_dir( WPQI_CACHE_PATH ) ) {
	mkdir( WPQI_CACHE_PATH );
}
if ( ! is_dir( WPQI_CACHE_CORE_PATH ) ) {
	mkdir( WPQI_CACHE_CORE_PATH );
}
if ( ! is_dir( WPQI_CACHE_PLUGINS_PATH ) ) {
	mkdir( WPQI_CACHE_PLUGINS_PATH );
}

// We verify if there is a preconfig file
$data = array();
if ( file_exists( 'data.ini' ) ) {
	$data = json_encode( parse_ini_file( 'data.ini' ) );
}

// We add  ../ to directory
$directory = ! empty( $_POST['directory'] ) ? '../' . $_POST['directory'] . '/' : '../';

if ( isset( $_GET['action'] ) ) {

	switch( $_GET['action'] ) {

		case "check_before_upload" :

			$data = array();

			/*--------------------------*/
			/*	We verify if we can connect to DB or WP is not installed yet
			/*--------------------------*/

			// WordPress test
			if ( file_exists( $directory . 'wp-config.php' ) ) {
				$data['wp'] = "error directory";
			}

			// We send the response
			echo json_encode( $data );

			break;

		case "download_wp" :

			// Get WordPress language
			$language = substr( $_POST['language'], 0, 6 );

			// Get WordPress data
			$wp = json_decode( file_get_contents( WP_API_CORE . $language ) )->offers[0];

			/*--------------------------*/
			/*	We download the latest version of WordPress
			/*--------------------------*/

			if ( ! file_exists( WPQI_CACHE_CORE_PATH . 'wordpress-' . $wp->version . '-' . $language  . '.zip' ) ) {
				file_put_contents( WPQI_CACHE_CORE_PATH . 'wordpress-' . $wp->version . '-' . $language  . '.zip', file_get_contents( $wp->download ) );
			}

			break;

		case "unzip_wp" :

			// Get WordPress language
			$language = substr( $_POST['language'], 0, 6 );

			// Get WordPress data
			$wp = json_decode( file_get_contents( WP_API_CORE . $language ) )->offers[0];

			/*--------------------------*/
			/*	We create the website folder with the files and the WordPress folder
			/*--------------------------*/

			// If we want to put WordPress in a subfolder we create it
			if ( ! empty( $directory ) ) {
				// Let's create the folder
				mkdir( $directory );

				// We set the good writing rights
				chmod( $directory , 0755 );
			}

			$zip = new ZipArchive;

			// We verify if we can use the archive
			if ( $zip->open( WPQI_CACHE_CORE_PATH . 'wordpress-' . $wp->version . '-' . $language  . '.zip' ) === true ) {

				// Let's unzip
				$zip->extractTo( '.' );
				$zip->close();

				// We scan the folder
				$files = scandir( 'wordpress' );

				// We remove the "." and ".." from the current folder and its parent
				$files = array_diff( $files, array( '.', '..' ) );

				// We move the files and folders
				foreach ( $files as $file ) {
					rename(  'wordpress/' . $file, $directory . '/' . $file );
				}

				rmdir( 'wordpress' ); // We remove WordPress folder
				unlink( $directory . '/license.txt' ); // We remove licence.txt
				unlink( $directory . '/readme.html' ); // We remove readme.html
				unlink( $directory . '/wp-content/plugins/hello.php' ); // We remove Hello Dolly plugin
			}

			break;

			case "install_plugins" :

				/*--------------------------*/
				/*	Let's retrieve the plugin folder
				/*--------------------------*/

				if ( ! empty( $_POST['plugins'] ) ) {

					$plugins     = explode( ";", $_POST['plugins'] );
					$plugins     = array_map( 'trim' , $plugins );
					$plugins_dir = $directory . 'wp-content/plugins/';

					foreach ( $plugins as $plugin ) {

						// We retrieve the plugin XML file to get the link to downlad it
					    $plugin_repo = file_get_contents( "http://api.wordpress.org/plugins/info/1.0/$plugin.json" );

					    if ( $plugin_repo && $plugin = json_decode( $plugin_repo ) ) {

							$plugin_path = WPQI_CACHE_PLUGINS_PATH . $plugin->slug . '-' . $plugin->version . '.zip';

							if ( ! file_exists( $plugin_path ) ) {
								// We download the lastest version
								if ( $download_link = file_get_contents( $plugin->download_link ) ) {
 									file_put_contents( $plugin_path, $download_link );
 								}							}

					    	// We unzip it
					    	$zip = new ZipArchive;
							if ( $zip->open( $plugin_path ) === true ) {
								$zip->extractTo( $plugins_dir );
								$zip->close();
							}
					    }
					}
				}

			break;

			case "success" :

				/*--------------------------*/
				/*	If we have installed WP and Woocommerce successfully - add link to the website for istallation
				/*--------------------------*/

				echo '<div id="errors" class="alert alert-danger"><p style="margin:0;"><strong>' . _('Warning') . '</strong>: Don\'t forget to delete the <code>shopping-cart</code> folder.</p></div>';

				echo '<a href="' . $directory . '" class="button" target="_blank">' . _('Start Installation') . '</a>';

				break;
	}
}
else { ?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8" />
		<title>Radixmo Shopping Cart Download Script</title>

		<meta name="robots" content="noindex, nofollow">
		<!-- CSS files -->
		<link rel="stylesheet" href="//fonts.googleapis.com/css?family=Open+Sans%3A300italic%2C400italic%2C600italic%2C300%2C400%2C600&#038;subset=latin%2Clatin-ext&#038;ver=3.9.1" />
		<link rel="stylesheet" href="assets/css/style.min.css" />
		<link rel="stylesheet" href="assets/css/buttons.min.css" />
		<link rel="stylesheet" href="assets/css/bootstrap.min.css" />
		<link href="https://fonts.googleapis.com/css?family=Raleway:400,800" rel="stylesheet" /> 

		<style>
		body {
		    margin: 40px auto 25px;
		    padding: 10px 20px 10px;
		}
		#radixmo-logo {
		    margin: 20px auto 20px auto;
		    border-bottom: 0;
		    text-align: center;
		}

		#radixmo-logo a {
		    text-transform: uppercase;
		    font-weight: 800;
			font-size: 2.2em;
			color: #d33;
		    text-shadow: 1px 1px 22px #dd3333;

		    font-family: 'Raleway';
		    font-weight: 800;

		}
		h1 { 
			margin: 0 0 20px 0;
		    text-align: center;
		    font-weight: 800;
		}
		h2 {
		    border-bottom: 1px solid #dedede;
		    clear: both;
		    color: #666;
		    font-size: 24px;
		    margin: 0;
		    padding: 0;
		    padding-bottom: 7px;
		    font-weight: 400;
		}
		code {
			font-weight: 500;
			color: #dd3333;
		}
		</style>
	</head>
	<body class="wp-core-ui">
		<div id="radixmo-logo"><a href="https://radixmo.com">Radixmo</a></div>
		<h1><?php echo _('Shopping Cart Download Script');?></h1>
		<?php

if ($_POST) { print_r($_POST); exit;}

		$parent_dir = realpath( dirname ( dirname( __FILE__ ) ) );
		if ( is_writable( $parent_dir ) ) { ?>

			<div id="response"></div>
			<div class="progress" style="display:none;">
				<div class="progress-bar progress-bar-striped active" style="width: 0%;"></div>
			</div>
			<div id="success" style="display:none; margin: 10px 0;">
				<h2 style="margin: 0; text-align: center;"><?php echo _('Download complete!') ;?></h2>
				<p><?php echo _('WordPress and selected plugins have been downloaded. and are ready to be installed.') ;?></p>
			</div>
			<form method="post" action="">

				<div id="errors" class="alert alert-danger" style="display:none;">
					<strong><?php echo _('Warning');?></strong>
				</div>

				<p><?php echo _('This script downloads WordPress and selected plugins then unzips the packages on your web server - fast!');?></p>

				<h2><?php echo _('WordPress Version');?></h2>

				<table class="form-table">
					<tr>
						<th scope="row"><label for="language"><?php echo _('Language');?></label></th>
						<td>
							<select id="language" name="language">
								<option value="en_US">English (United States)</option>
								<?php
								// Get all available languages
								$languages = json_decode( file_get_contents( 'http://api.wordpress.org/translations/core/1.0/?version=4.0' ) )->translations;

								foreach ( $languages as $language ) {
									echo '<option value="' . $language->language . '">' . $language->native_name . '</option>';
								}
								?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="directory"><?php echo _('Installation Folder');?></label>
						<td>
							<input name="directory" type="text" id="directory" size="25" value="" />
							<p><?php echo _('Leave blank to install on the root folder');?></p>
						</th>
						</td>
					</tr>
				</table>

				<h2><?php echo _('Free Plugins');?></h2>
				<p><?php echo _('This script downloads Woocommerce by default. Download additional free plugins by adding their slugs below. For example: the slug for http://wordpress.org/extend/plugins/<strong>wordpress-seo</strong> is <code>wordpress-seo</code>.');?></p>
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="plugins"><?php echo _('Plugin slugs');?></label>
						</th>
						<td>
							<input name="plugins" type="text" id="plugins" size="50" value="woocomerce" />
							<p><?php echo _('Additional plugin slugs must separated by a semicolon (;).<br> For example: <code>woocommerce;wordpress-seo</code>');?></p>
						</td>
					</tr>
				</table>

				<p class="step"><span id="submit" class="button button-large"><?php echo _('Download and extract WordPress and selected plugins');?></span></p>

			</form>

			<script src="assets/js/jquery-1.8.3.min.js"></script>
			<script>var data = <?php echo $data; ?>;</script>
			<script src="assets/js/script.js"></script>
		<?php
		} else { ?>

			<div class="alert alert-error" style="margin-bottom: 0px;">
				<strong><?php echo _('Warning !');?></strong>
				<p style="margin-bottom:0px;"><?php echo _('You don\'t have the correct permissions set on ') . basename( $parent_dir ) . _('. Set the correct file permissions.') ;?></p>
			</div>

		<?php
		}
		?>
	</body>
</html>
<?php
}
