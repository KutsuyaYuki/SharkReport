<?php
class SharkReport_Settings {
	public function setup() {

		add_action( 'admin_menu', array( $this, 'shark_report_add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'shark_report_page_init' ) );
        add_action( 'admin_notices', array( $this,'shark_admin_notice' ) );

	}

// display custom admin notice
function shark_admin_notice() {
	
	$screen = get_current_screen();
	
	if ($screen->id === 'toplevel_page_shark-report') {
		
		if (isset($_GET['success'])) {
			
			if ($_GET['success'] === '1') : ?>
				
				<div class="notice notice-success is-dismissible">
					<p><?php _e('Success!', 'bbb'); ?></p>
				</div>
				
			<?php else : ?>
				
				<div class="notice notice-error is-dismissible">
					<p><?php _e('Something went wrong :c.', 'bbb'); ?></p>
				</div>
				
			<?php endif;
			
		}
	}
}


	public function shark_report_add_plugin_page() {
		add_menu_page(
			'Shark Report', // page_title
			'Shark Report', // menu_title
			'manage_options', // capability
			'shark-report', // menu_slug
			array( $this, 'shark_report_create_admin_page' ), // function
			'dashicons-redo', // icon_url
			2 // position
		);

        register_setting('sharkreport-settings', 'report-emails-weekly-name');
        register_setting('sharkreport-settings', 'report-emails-monthly-name');

	}

	public function shark_report_create_admin_page() {
		$this->shark_report_options = get_option( 'shark_report_option_name' ); ?>

		<div class="wrap">
			<h2>Shark Report Settings</h2>
			<p><h3>Settings for Shark Report</h3></p>
			<?php settings_errors(); ?>

            <form method="post" action="options.php">
            
                <?php settings_fields('sharkreport-settings'); //Is where we save it in the DB ?>

                <table class="form-table">
                    <tr>
                        <td>
                            <label for="report-emails-id">Emails Weekly:</label>
                        </td>
                        <td>
                            <input type="textarea" required pattern="^(([^\@\.\,])+@{1}([^\@\,])+,?)+([^,])$" width="100px" height="100px" rows="10" class="regular-text" id="report-emails-weekly-id" name="report-emails-weekly-name" value="<?php echo get_option('report-emails-weekly-name'); //Gets setting from DB ?>">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="report-emails-id">Emails Monthly:</label>
                        </td>
                        <td>
                            <input type="textarea" required pattern="^(([^\@\.\,])+@{1}([^\@\,])+,?)+([^,])$" width="100px" height="100px" rows="10" class="regular-text" id="report-emails-monthly-id" name="report-emails-monthly-name" value="<?php echo get_option('report-emails-monthly-name'); //Gets setting from DB ?>">
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>

            </form>

			<p><h3>Aangepaste uitdraai</h3></p>

			<form action="<?php echo admin_url( 'admin-post.php' ); ?>">
			<input type="hidden" name="action" value="shark_calc_all">

			<table class="form-table">
				<tr>
					<td>
						<label for="report-emails-aangepast-id">Emails Aangepast:</label>
					</td>
					<td>
						<input type="textarea" required pattern="^(([^\@\.\,])+@{1}([^\@\,])+,?)+([^,])$" width="100px" height="100px" rows="10" class="regular-text" id="report-emails-aangepast-id" name="report-emails-aangepast-name" value="<?php echo get_option('report-emails-aangepast-name'); //Gets setting from DB ?>">
					</td>
				</tr>
                <tr>
                    <td>
                        <label for="report-emails-id">Begin:</label>
                    </td>
                    <td>
                        <input type="date" width="100px" height="100px" rows="10" id="date-start" name="date-start">
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="report-emails-id">Eind:</label>
                    </td>
                    <td>
                        <input type="date" width="100px" height="100px" rows="10" id="date-end" name="date-end">
                    </td>
                </tr>
                <tr>
                    <td>
							<?php submit_button( 'Send' ); ?>
                    </td>
                </tr>
				</table>
				</form>
				</div>
			
				<table>
					<tbody>
						<tr>
							<td>
								<form action="<?php echo admin_url( 'admin-post.php' ); ?>">
								<input type="hidden" name="action" value="shark_calc_weekly_now">
								<?php submit_button( 'Week' ); ?>
								</form>
							</td>
							<td>
								<form action="<?php echo admin_url( 'admin-post.php' ); ?>">
								<input type="hidden" name="action" value="shark_calc_monthly_now">
								<?php submit_button( 'Maand' ); ?>
								</form>
							</td>
						</tr>
					</tbody>
					</table>

                    <p><h3>Test functions</h3></p>

                    <form action="<?php echo admin_url( 'admin-post.php' ); ?>">
                    <input type="hidden" name="action" value="shark_calc_get_all_coupons">

                    <table class="form-table">
                <tr>
                    <td>
                        <label for="report-emails-id">Begin:</label>
                    </td>
                    <td>
                        <input type="date" width="100px" height="100px" rows="10" id="date-start" name="date-start">
                    </td>
                </tr>
                <tr>
                    <td>
                        <label for="report-emails-id">Eind:</label>
                    </td>
                    <td>
                        <input type="date" width="100px" height="100px" rows="10" id="date-end" name="date-end">
                    </td>
                </tr>
                        <tr>
                            <td>
                                    <?php submit_button( 'Send' ); ?>
                            </td>
                        </tr>
                        </table>
                        </form>
                        </div>
		
	<?php }

	public function shark_report_page_init() {
	}
}

?>