<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class Shark_Cron_Manager {

	//const CRON_HOOK_MINUTE = 'shark_send_minute';
	const CRON_HOOK_WEEKLY = 'shark_send_weekly';
	const CRON_HOOK_MONTHLY = 'shark_send_monthly';

	/**
	 * Remove the Cron
	 *
	 * @access public
	 * @since  1.0.0
	 */
	public function remove_cron() {
		wp_clear_scheduled_hook( self::CRON_HOOK_WEEKLY );
		wp_clear_scheduled_hook( self::CRON_HOOK_MONTHLY );
	}

	/**
	 * Setup the Cron.
	 * @access public
	 * @since  1.0.0
	 */
	public function setup_cron() {
		// Create a Date Time object when the cron should run for the first time.
		$first_cron = new DateTime( date( 'Y-m-d' ) . ' 09:00');
		
		$first_cron->modify( '+1 day' );

/* 		if ( ! wp_next_scheduled( self::CRON_HOOK_MINUTE ) ) {
			$first_cron = new DateTime('now');
	  
			// Modify the date it contains
			$first_cron->modify('+ 2 minutes');
			wp_schedule_event( $first_cron->format( 'U' ), 'every_minute', self::CRON_HOOK_MINUTE );
		}
 */
		if ( ! wp_next_scheduled( self::CRON_HOOK_WEEKLY ) ) {
			// Create a new DateTime object
			$first_cron = new DateTime();
	  
			// Modify the date it contains
			$first_cron->modify('next monday');
			wp_schedule_event( $first_cron->format( 'U' ), 'weekly', self::CRON_HOOK_WEEKLY );
		}
	  
		if ( ! wp_next_scheduled( self::CRON_HOOK_MONTHLY ) ) {      
			// Create a Date Time object when the cron should run for the first time.
			$first_cron = new DateTime();
	  
			// Modify the date it contains
			$first_cron->modify('first day of next month');
	  
			wp_schedule_event( $first_cron->format( 'U' ), 'daily', self::CRON_HOOK_MONTHLY );
		}
	}
}