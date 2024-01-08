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

    private function get_category_list() {
        $category_tree = array();
    
        // Retrieve all parent terms (top-level categories).
        $parent_categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'parent' => 0
        ));
    
        foreach ($parent_categories as $pcategory) {
            // Get child terms.
            $child_args = array(
                'taxonomy' => 'product_cat',
                'hide_empty' => false,
                'parent' => $pcategory->term_id
            );
    
            $child_categories = get_terms($child_args);
    
            // To store children with their names.
            $children = array_map(function($term){
                return ['id' => $term->term_id, 'name' => $term->name];
            },$child_categories);
    
             // Add Parent category along with its children to tree.
             $category_tree[] = [
                 "id"     =>$pcategory->term_id,
                 "name"   =>$pcategory->name,
                 "slug"   =>$pcategory->slug,
                 "children"=>$children
             ];
        }
    
        return  $category_tree;
    }

    public function display_category_list() {
        $categories = get_terms(array(
            'taxonomy'   => 'product_cat',
            'orderby'    => 'name', // You might want to change ordering if needed.
            'order'      => 'ASC',
            'hide_empty' => false,
        ));
    
        echo '<div>';
        echo '<h3>Select Category:</h3>';
    
        echo '<select name="shark_report_categories" id="shark_report_categories">';
        foreach ($categories as $category) {
              // Get parent name if there is one
              $parent_name = '';
              if($category->parent){
                $parent_term = get_term_by('id',$category->parent,'product_cat');
                $parent_name = esc_html($parent_term->name) . " > ";
              }
    
              echo '<option value="' . esc_attr($category->term_id) . '">' .
                   str_repeat(' ', $depth * 3).$parent_name . esc_html($category->name). '</option>';
         }
         echo '</select>';
    
         echo '</div>';
    }
        
    // A recursive function to print Category Hierarchy HTML
    private function print_category_hierarchy($categories){
        echo '<ul>';
        foreach ($categories as    $category) {
            echo '<li>' . esc_html(ucfirst(strtolower($category['name'])));
                if(!empty($category['children'])){
                    $this->print_category_hierarchy($category['children']); // If there are children go deeper
                }
    
        }
        echo '</ul></li>';
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
                            <input type="textarea" width="100px" height="100px" rows="10" class="regular-text" id="report-emails-weekly-id" name="report-emails-weekly-name" value="<?php echo get_option('report-emails-weekly-name'); //Gets setting from DB ?>">
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="report-emails-id">Emails Monthly:</label>
                        </td>
                        <td>
                            <input type="textarea" width="100px" height="100px" rows="10" class="regular-text" id="report-emails-monthly-id" name="report-emails-monthly-name" value="<?php echo get_option('report-emails-monthly-name'); //Gets setting from DB ?>">
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>

            </form>

			<p><h3>Aangepaste uitdraai</h3></p>

            <?php
    echo "<section id='shark-report-category-list'>";
    echo "<h2>Select Category</h2>";

    // Output Category dropdown
    $this->display_category_list();

    echo "</section>";
            ?>

			<form action="<?php echo admin_url( 'admin-post.php' ); ?>">
			<input type="hidden" name="action" value="shark_calc_all">

			<table class="form-table">
				<tr>
					<td>
						<label for="report-emails-aangepast-id">Emails Aangepast:</label>
					</td>
					<td>
						<input type="textarea" width="100px" height="100px" rows="10" class="regular-text" id="report-emails-aangepast-id" name="report-emails-aangepast-name" value="<?php echo get_option('report-emails-aangepast-name'); //Gets setting from DB ?>">
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