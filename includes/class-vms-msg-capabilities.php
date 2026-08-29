<?php
/**
 * Custom capability helpers.
 *
 * @package VMS_MSG
 */

defined( 'ABSPATH' ) || exit;

/**
 * Grants plugin access without requiring full manage_options for every role.
 */
class VMS_MSG_Capabilities {

	const CAP = 'manage_vms_msg';

	/**
	 * Whether the current user can manage Multi Mailer.
	 *
	 * @return bool
	 */
	public static function current_user_can() {
		return current_user_can( self::CAP );
	}

	/**
	 * Add capability to Administrator on activate / upgrade.
	 *
	 * @return void
	 */
	public static function install() {
		$role = get_role( 'administrator' );
		if ( $role && ! $role->has_cap( self::CAP ) ) {
			$role->add_cap( self::CAP );
		}
	}

	/**
	 * Remove capability from known roles on uninstall.
	 *
	 * @return void
	 */
	public static function uninstall() {
		foreach ( array( 'administrator', 'editor', 'author' ) as $slug ) {
			$role = get_role( $slug );
			if ( $role && $role->has_cap( self::CAP ) ) {
				$role->remove_cap( self::CAP );
			}
		}
	}
}
