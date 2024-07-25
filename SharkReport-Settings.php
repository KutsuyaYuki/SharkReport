<?php
class SharkReport_Settings
{
    public function setup(): void
    {
        add_action('admin_menu', [$this, 'shark_report_add_plugin_page']);
        add_action('admin_init', [$this, 'shark_report_page_init']);
        add_action('admin_notices', [$this, 'shark_admin_notice']);
    }

    public function shark_admin_notice(): void
    {
        $screen = get_current_screen();

        if ($screen->id === 'toplevel_page_shark-report' && isset($_GET['success'])) {
            $this->display_admin_notice($_GET['success']);
        }
    }

    private function display_admin_notice(string $success): void
    {
        $message = $success === '1' ? 'Success!' : 'Something went wrong :c.';
        $class = $success === '1' ? 'notice-success' : 'notice-error';
?>
        <div class="notice <?php echo esc_attr($class); ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php
    }

    public function shark_report_add_plugin_page(): void
    {
        add_menu_page(
            'Shark Report',
            'Shark Report',
            'manage_options',
            'shark-report',
            [$this, 'shark_report_create_admin_page'],
            'dashicons-redo',
            2
        );

        $this->register_settings();
    }

    private function register_settings(): void
    {
        register_setting('sharkreport-settings', 'report-emails-weekly-name');
        register_setting('sharkreport-settings', 'report-emails-monthly-name');
    }

    private function get_category_list(): array
    {
        $category_tree = [];

        $parent_categories = get_terms([
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'parent' => 0
        ]);

        foreach ($parent_categories as $parent_category) {
            $child_categories = get_terms([
                'taxonomy' => 'product_cat',
                'hide_empty' => false,
                'parent' => $parent_category->term_id
            ]);

            $children = array_map(function ($term) {
                return ['id' => $term->term_id, 'name' => $term->name];
            }, $child_categories);

            $category_tree[] = [
                "id"     => $parent_category->term_id,
                "name"   => $parent_category->name,
                "slug"   => $parent_category->slug,
                "children" => $children
            ];
        }

        return $category_tree;
    }

    public function display_category_list(): void
    {
        $category_tree = $this->get_category_list();

        echo '<table>';
        echo '<tr><th>ID</th><th>Name</th><th>Slug</th></tr>';

        foreach ($category_tree as $parent_category) {
            echo '<tr>';
            echo '<td>' . esc_html($parent_category['id']) . '</td>';
            echo '<td>' . esc_html($parent_category['name']) . '</td>';
            echo '<td>' . esc_html($parent_category['slug']) . '</td>';
            echo '</tr>';
        }

        echo '</table>';
    }

    private function print_category_hierarchy(array $categories): void
    {
        echo '<ul>';
        foreach ($categories as $category) {
            echo '<li>' . esc_html(ucfirst(strtolower($category['name'])));
            if (!empty($category['children'])) {
                $this->print_category_hierarchy($category['children']);
            }
        }
        echo '</ul></li>';
    }

    public function shark_report_create_admin_page(): void
    {
        $this->shark_report_options = get_option('shark_report_option_name');
    ?>
        <div class="wrap">
            <h2>Shark Report Settings</h2>
            <p>
            <h3>Settings for Shark Report</h3>
            </p>
            <?php settings_errors(); ?>

            <form method="post" action="options.php">
                <?php
                settings_fields('sharkreport-settings');
                $this->display_settings_fields();
                submit_button();
                ?>
            </form>

            <h3>Aangepaste uitdraai</h3>

            <?php
            echo "<section id='shark-report-category-list'>";
            echo "<h2>Select Category</h2>";
            $this->display_category_list();
            echo "</section>";

            $this->display_custom_report_form();
            $this->display_action_buttons();
            $this->display_test_functions();
            ?>
        </div>
    <?php
    }

    private function display_settings_fields(): void
    {
    ?>
        <table class="form-table">
            <?php $this->display_email_field('Weekly', 'report-emails-weekly-name'); ?>
            <?php $this->display_email_field('Monthly', 'report-emails-monthly-name'); ?>
        </table>
    <?php
    }

    private function display_email_field(string $label, string $option_name): void
    {
    ?>
        <tr>
            <td><label for="<?php echo esc_attr($option_name); ?>">Emails <?php echo esc_html($label); ?>:</label></td>
            <td>
                <input type="text" class="regular-text" id="<?php echo esc_attr($option_name); ?>" name="<?php echo esc_attr($option_name); ?>" value="<?php echo esc_attr(get_option($option_name)); ?>">
            </td>
        </tr>
    <?php
    }

    private function display_custom_report_form(): void
    {
    ?>
        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="shark_calc_all">
            <table class="form-table">
                <?php $this->display_email_field('Aangepast', 'report-emails-aangepast-name'); ?>
                <?php $this->display_date_field('Begin', 'date-start'); ?>
                <?php $this->display_date_field('Eind', 'date-end'); ?>
                <tr>
                    <td><?php submit_button('Send'); ?></td>
                </tr>
            </table>
        </form>
    <?php
    }

    private function display_date_field(string $label, string $field_name): void
    {
    ?>
        <tr>
            <td><label for="<?php echo esc_attr($field_name); ?>"><?php echo esc_html($label); ?>:</label></td>
            <td><input type="date" id="<?php echo esc_attr($field_name); ?>" name="<?php echo esc_attr($field_name); ?>"></td>
        </tr>
    <?php
    }

    private function display_action_buttons(): void
    {
    ?>
        <table>
            <tbody>
                <tr>
                    <td>
                        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <input type="hidden" name="action" value="shark_calc_weekly_now">
                            <?php submit_button('Week'); ?>
                        </form>
                    </td>
                    <td>
                        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                            <input type="hidden" name="action" value="shark_calc_monthly_now">
                            <?php submit_button('Maand'); ?>
                        </form>
                    </td>
                </tr>
            </tbody>
        </table>
    <?php
    }

    private function display_test_functions(): void
    {
    ?>
        <h3>Test functions</h3>
        <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="shark_calc_get_all_coupons">
            <table class="form-table">
                <?php $this->display_date_field('Begin', 'date-start'); ?>
                <?php $this->display_date_field('Eind', 'date-end'); ?>
                <tr>
                    <td><?php submit_button('Send'); ?></td>
                </tr>
            </table>
        </form>
<?php
    }

    public function shark_report_page_init(): void
    {
        // This method is intentionally left empty
    }
}
