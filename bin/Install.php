<?php
/**
 * @author OnTheGo Systems
 */

namespace OTGS\Composer\Scripts;

class Install {
	public static function PHPCSStandards() {
		$vendor_locations = array(
			dirname( getcwd() . '/vendor/autoload.php' ),
			dirname( __DIR__ . '/../vendor/autoload.php' ),
			dirname( __DIR__ . '/../../../../../autoload.php' ),
		);

		$vendor_location = null;

		foreach ( $vendor_locations as $vendor_location ) {
			if ( realpath( $vendor_location ) ) {
				break;
			}
		}

		if ( ! $vendor_location ) {
			echo 'I could not find the `autoload.php`.' . PHP_EOL;
			exit( 0 );
		}

		$phpcs_standards = new PHPCSStandards( $vendor_location );
		$phpcs_standards->run();
	}
}
