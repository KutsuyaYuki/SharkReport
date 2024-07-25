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

require_once("GetStats.php");
require_once("SharkReport-Settings.php");
require_once("shark-cron-manager.php");
require_once("SimpleXLSXGen.php");

class SharkReport
{
	private $shark_getstats;
	private $settings;
	private $cron_manager;

	/**
	 * This method will be run on plugin activation.
	 *
	 * @since  1.0.0
	 */
	public static function activation(): void
	{
		$cron_manager = new Shark_Cron_Manager();
		$cron_manager->setup_cron();
	}

	/**
	 * This method will run on plugin deactivation.
	 *
	 * @since  1.0.0
	 */
	public static function deactivation(): void
	{
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
	private function is_wc_active(): bool
	{
		$is_active = class_exists('WooCommerce');

		if (!$is_active) {
			add_action('admin_notices', [$this, 'notice_activate_wc']);
		}

		return $is_active;
	}

	/**
	 * Display notice to activate WooCommerce.
	 */
	public function notice_activate_wc(): void
	{
?>
		<div class="notice notice-error">
			<p><?php _e('Shark Report requires WooCommerce to be active.', 'shark-report'); ?></p>
		</div>
<?php
	}

	/**
	 * Initialize the plugin.
	 *
	 * @since  1.0.0
	 */
	private function init(): void
	{
		$this->shark_getstats = new SharkGetStats();
		$this->cron_manager = new Shark_Cron_Manager();

		$this->add_admin_actions();

		if (is_admin()) {
			$this->settings = new SharkReport_Settings();
			$this->settings->setup();
		}

		$this->add_cron_actions();
	}

	/**
	 * Add admin actions.
	 */
	private function add_admin_actions(): void
	{
		add_action('admin_post_shark_calc_all', [$this->shark_getstats, 'shark_calc_all_action']);
		add_action('admin_post_shark_calc_weekly_now', [$this->shark_getstats, 'shark_calc_all_action_weekly_now']);
		add_action('admin_post_shark_calc_monthly_now', [$this->shark_getstats, 'shark_calc_all_action_monthly_now']);
	}

	/**
	 * Add cron actions.
	 */
	private function add_cron_actions(): void
	{
		add_action(Shark_Cron_Manager::CRON_HOOK_WEEKLY, [$this->shark_getstats, 'shark_calc_all_action_weekly']);
		add_action(Shark_Cron_Manager::CRON_HOOK_MONTHLY, [$this->shark_getstats, 'shark_calc_all_action_monthly']);
	}
}

/**
 * Initialize plugin.
 */
function SharkReport_main_init(): void
{
	new SharkReport();
}

// Create object - Plugin init.
add_action('plugins_loaded', 'SharkReport_main_init');

// Activation hook.
register_activation_hook(__FILE__, [SharkReport::class, 'activation']);

// Deactivation hook.
register_deactivation_hook(__FILE__, [SharkReport::class, 'deactivation']);
