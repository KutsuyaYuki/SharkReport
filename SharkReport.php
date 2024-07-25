<?php
/*
   Plugin Name: Shark Report
   Plugin URI: http://www.blahaj.nl
   description: Yuki's plugin
   Version: 0.1
   Author: Yuki Schoenmaker
   Author URI: http://www.blahaj.nl
   License: GPL2
   */
include_once("GetStats.php");
include_once("SharkReport-Settings.php");
include_once("shark-cron-manager.php");
include_once("SimpleXLSXGen.php");

class SharkReport
{

	/**
	 * This method will be run on plugin activation.
	 *
	 * @since  1.0.0
	 */
	public static function activation()
	{
		// Setup Cron.
		$cron_manager = new Shark_Cron_Manager();
		$cron_manager->setup_cron();
	}

	/**
	 * This method wil run on plugin deactivation.
	 *
	 * @since  1.0.0
	 */
	public static function deactivation()
	{
		// Remove Cron.
		$cron_manager = new Shark_Cron_Manager();
		$cron_manager->remove_cron();
	}

	/**
	 * Constructor.
	 *
	 * @since  1.0.0
	 */
	public function __construct()
	{
		// Check if WC is activated.
		if ($this->is_wc_active()) {
			$this->init();
		}
	}

	/**
	 * Check if WooCommerce is active.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	private function is_wc_active()
	{

		$is_active = class_exists('WooCommerce');

		// Do the WC active check.
		if (false === $is_active) {
			add_action('admin_notices', array($this, 'notice_activate_wc'));
		}

		return $is_active;
	}

	/**
	 * Initialize the plugin.
	 *
	 * @since  1.0.0
	 */
	private function init()
	{

		$shark_getstats = new SharkGetStats();

		add_action('admin_post_shark_calc_all', array($shark_getstats, 'shark_calc_all_action'));
		add_action('admin_post_shark_calc_weekly_now', array($shark_getstats, 'shark_calc_all_action_weekly_now'));
		add_action('admin_post_shark_calc_monthly_now', array($shark_getstats, 'shark_calc_all_action_monthly_now'));
		// add_action('admin_post_shark_calc_get_all_coupons', array($shark_getstats, 'shark_calc_get_all_coupons_action'));

		// Only load in admin.
		if (is_admin()) {

			// Setup the settings.
			$settings = new SharkReport_Settings();
			$settings->setup();
		}

		// Cron hook.
		//add_action( Shark_Cron_Manager::CRON_HOOK_MINUTE, array( $shark_getstats, 'shark_calc_all_action_weekly' ) );
		add_action(Shark_Cron_Manager::CRON_HOOK_WEEKLY, array($shark_getstats, 'shark_calc_all_action_weekly'));
		add_action(Shark_Cron_Manager::CRON_HOOK_MONTHLY, array($shark_getstats, 'shark_calc_all_action_monthly'));
	}
}

/**
 * Initialize plugin.
 */
function SharkReport_main_init()
{
	new SharkReport();
}

// Create object - Plugin init.
add_action('plugins_loaded', 'SharkReport_main_init');

// Activation hook.
register_activation_hook(__FILE__, array('SharkReport', 'activation'));

// Deactivation hook.
register_deactivation_hook(__FILE__, array('SharkReport', 'deactivation'));
