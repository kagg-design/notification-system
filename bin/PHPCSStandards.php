<?php
/**
 * @author OnTheGo Systems
 */

namespace OTGS\Composer\Scripts;

class PHPCSStandards {
	const SQUIZLABS_CODESNIFFER_STANDARDS = '/squizlabs/php_codesniffer/CodeSniffer/Standards';
	private $specs;
	private $target_standards_path;
	private $vendor_location;

	/**
	 * PHPCSStandards constructor.
	 *
	 * @param $vendor_location
	 */
	public function __construct( $vendor_location ) {
		$this->vendor_location = $vendor_location;

		$this->target_standards_path = realpath( $this->vendor_location . self::SQUIZLABS_CODESNIFFER_STANDARDS );

		if ( ! $this->target_standards_path ) {
			echo 'I could not find `' . $this->vendor_location . self::SQUIZLABS_CODESNIFFER_STANDARDS . '`.' . PHP_EOL;
			exit( 0 );
		}

		$this->specs = array(
			'PHPCompatibility' => 'wimg/php-compatibility',
			'WordPress'        => 'wp-coding-standards/wpcs/WordPress',
			'WordPress-Core'   => 'wp-coding-standards/wpcs/WordPress-Core',
			'WordPress-Docs'   => 'wp-coding-standards/wpcs/WordPress-Docs',
			'WordPress-Extra'  => 'wp-coding-standards/wpcs/WordPress-Extra',
			'WordPress-VIP'    => 'wp-coding-standards/wpcs/WordPress-VIP',
		);
	}

	public function run() {
		foreach ( $this->specs as $spec => $source ) {
			$source_path = realpath( $this->vendor_location . DIRECTORY_SEPARATOR . $source );
			$target_path = $this->target_standards_path . DIRECTORY_SEPARATOR . $spec;

			if ( $source_path ) {
				echo 'Installing ' . $source . '... ';
				if ( realpath( $target_path ) ) {
					$command = $this->get_remove_dir_command( $target_path );
					$this->exec_shell_command( $command );
				}

				$command = $this->get_copy_dir_command( $source_path, $target_path );
				if ( $this->exec_shell_command( $command ) === 0 ) {
					echo 'Done.' . PHP_EOL;
				} else {
					echo 'Failed.' . PHP_EOL;
				}
			}
		}
	}

	private function get_remove_dir_command( $path ) {
		if ( $this->is_windows() ) {
			return 'rmdir /s /q ' . escapeshellarg( $path );
		}

		return 'rm -rf ' . escapeshellarg( $path );
	}

	private function exec_shell_command( $command ) {
		exec( $command, $output, $return_var );

		if ( $output ) {
			echo implode( PHP_EOL, $output );
		}

		return $return_var;
	}

	private function get_copy_dir_command( $source_path, $target_path ) {
		if ( $this->is_windows() ) {
			return 'xcopy ' . escapeshellarg( $source_path ) . ' ' . escapeshellarg( $target_path ) . ' /seiqy';
		}

		return 'cp -rp ' . escapeshellarg( $source_path ) . ' ' . escapeshellarg( $target_path );
	}

	private function is_windows() {
		return 0 === stripos( PHP_OS, 'WIN' );
	}

}
