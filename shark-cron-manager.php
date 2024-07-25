<?php

if (!defined('ABSPATH')) {
	exit;
} // Exit if accessed directly

class Shark_Cron_Manager
{
	const CRON_HOOK_WEEKLY = 'shark_send_weekly';
	const CRON_HOOK_MONTHLY = 'shark_send_monthly';

	/**
	 * Remove the Cron jobs
	 *
	 * @access public
	 * @since  1.0.0
	 */
	public function remove_cron(): void
	{
		wp_clear_scheduled_hook(self::CRON_HOOK_WEEKLY);
		wp_clear_scheduled_hook(self::CRON_HOOK_MONTHLY);
	}

	/**
	 * Setup the Cron jobs
	 *
	 * @access public
	 * @since  1.0.0
	 */
	public function setup_cron(): void
	{
		$this->setup_weekly_cron();
		$this->setup_monthly_cron();
	}

	/**
	 * Setup weekly cron job
	 */
	private function setup_weekly_cron(): void
	{
		if (!wp_next_scheduled(self::CRON_HOOK_WEEKLY)) {
			$nextMonday = $this->get_next_monday();
			wp_schedule_event($nextMonday->getTimestamp(), 'weekly', self::CRON_HOOK_WEEKLY);
		}
	}

	/**
	 * Setup monthly cron job
	 */
	private function setup_monthly_cron(): void
	{
		if (!wp_next_scheduled(self::CRON_HOOK_MONTHLY)) {
			$firstDayNextMonth = $this->get_first_day_next_month();
			wp_schedule_event($firstDayNextMonth->getTimestamp(), 'daily', self::CRON_HOOK_MONTHLY);
		}
	}

	/**
	 * Get DateTime object for next Monday at 9:00 AM
	 */
	private function get_next_monday(): DateTime
	{
		$nextMonday = new DateTime('next monday 09:00');
		return $nextMonday;
	}

	/**
	 * Get DateTime object for first day of next month at 9:00 AM
	 */
	private function get_first_day_next_month(): DateTime
	{
		$firstDayNextMonth = new DateTime('first day of next month 09:00');
		return $firstDayNextMonth;
	}
}
