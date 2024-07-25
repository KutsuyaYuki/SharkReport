<?php

/**
 * Class for handling settings and functionality related to Shark Reports in WordPress admin.
 *
 * Provides options and actions for generating sales reports in WooCommerce.
 * Also includes methods for adding cron jobs, registering settings, and more.
 */
class SharkGetStats
{
    // Constants for frequently used values
    const TRANSIENT_WEEKLY = 'shark_calc_all_action_weekly_semaphore';
    const TRANSIENT_MONTHLY = 'shark_calc_all_action_monthly_semaphore';
    const TRANSIENT_TIMEOUT = 60;

    public function __construct()
    {
        // Constructor left empty intentionally
    }

    /**
     * Calculate all actions for the shark on a weekly basis.
     *
     * This function retrieves the current day and prevents the function from being executed twice within 60 seconds.
     * It then calculates the start and end dates for the previous week and retrieves the report emails.
     * Finally, it calls the `Shark_getOrders` method to perform the necessary calculations and exits.
     */
    public function shark_calc_all_action_weekly(): void
    {
        if ($this->isTransientSet(self::TRANSIENT_WEEKLY)) {
            return;
        }

        $dateStart = date('Y-m-d', strtotime('last week Monday'));
        $dateEnd = date('Y-m-d', strtotime('last week Sunday'));

        $reportEmails = $this->getReportEmails('report-emails-weekly-name');

        $this->Shark_getOrders($dateStart, $dateEnd, $reportEmails);

        exit();
    }

    /**
     * Calculate all actions for the shark on a weekly basis (now).
     */
    public function shark_calc_all_action_weekly_now(): void
    {
        if ($this->isTransientSet(self::TRANSIENT_WEEKLY)) {
            return;
        }

        $previousWeek = strtotime("-1 week +1 day");
        $startWeek = date("Y-m-d", strtotime("last monday midnight", $previousWeek));
        $endWeek = date("Y-m-d", strtotime("next sunday", strtotime($startWeek)));

        $reportEmails = $this->getReportEmails('report-emails-weekly-name');

        $this->Shark_getOrders($startWeek, $endWeek, $reportEmails);

        exit();
    }

    /**
     * Calculate shark action monthly.
     */
    public function shark_calc_all_action_monthly(): void
    {
        $thisDay = (int)date('j');
        if ($thisDay !== 1) {
            return;
        }

        if ($this->isTransientSet(self::TRANSIENT_MONTHLY)) {
            return;
        }

        $dateStart = date('Y-m-d', strtotime('first day of last month'));
        $dateEnd = date('Y-m-d', strtotime('last day of last month'));

        $reportEmails = $this->getReportEmails('report-emails-monthly-name');

        $this->Shark_getOrders($dateStart, $dateEnd, $reportEmails);

        exit();
    }

    /**
     * Calculate all actions for the shark on a monthly basis (now).
     */
    public function shark_calc_all_action_monthly_now(): void
    {
        if ($this->isTransientSet(self::TRANSIENT_MONTHLY)) {
            return;
        }

        $dateStart = date('Y-m-d', strtotime('first day of last month'));
        $dateEnd = date('Y-m-d', strtotime('last day of last month'));

        $reportEmails = $this->getReportEmails('report-emails-monthly-name');

        $this->Shark_getOrders($dateStart, $dateEnd, $reportEmails);

        exit();
    }

    /**
     * Calculate all actions for the shark.
     */
    public function shark_calc_all_action(): void
    {
        if (isset($_GET['date-start'])) {
            $reportEmails = explode(",", sanitize_text_field($_GET['report-emails-aangepast-name']));
            $dateStart = sanitize_text_field($_GET['date-start']);
            $dateEnd = sanitize_text_field($_GET['date-end']);
            $this->Shark_getOrders($dateStart, $dateEnd, $reportEmails);
            wp_safe_redirect(admin_url('admin.php?page=shark-report'));
            exit();
        }
    }

    // ... [rest of the methods]

    /**
     * Check if a transient is set
     */
    private function isTransientSet(string $transientName): bool
    {
        if (get_transient($transientName)) {
            return true;
        }
        set_transient($transientName, true, self::TRANSIENT_TIMEOUT);
        return false;
    }

    /**
     * Get report emails
     */
    private function getReportEmails(string $optionName): array
    {
        return explode(",", get_option($optionName));
    }
}
