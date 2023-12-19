<?php
/**
 * Class for handling settings and functionality related to Shark Reports in WordPress admin.
 *
 * Provides options and actions for generating sales reports in WooCommerce. 
 * Also includes methods for adding cron jobs, registering settings, and more.
 *
 */
class SharkGetStats {
    
    public function __construct(){
    }

    /**
     * Calculate all actions for the shark on a weekly basis.
     *
     * This function retrieves the current day and prevents the function from being executed twice within 60 seconds.
     * It then calculates the start and end dates for the previous week and retrieves the report emails.
     * Finally, it calls the `Shark_getOrders` method to perform the necessary calculations and exits.
     *
     * @throws Some_Exception_Class This function does not throw any exceptions.
     */
    public function shark_calc_all_action_weekly() {
        // Supposed to prevent the function from being executed twice in 60 seconds.
        if ( get_transient( 'shark_calc_all_action_weekly_semaphore' ) ) return;
        set_transient( 'shark_calc_all_action_weekly_semaphore', true, 60);

        $date_start = date('Y-m-d', strtotime('last week Monday'));
        $date_end = date('Y-m-d', strtotime('last week Sunday'));
        
        $report_emails = explode(",", get_option('report-emails-weekly-name'));

        $this->Shark_getOrders($date_start, $date_end, $report_emails);
            
        exit();
    }

    /**
     * Calculate all actions for the shark on a weekly basis.
     * 
     * This function retrieves the current day and prevents the function from being executed twice within 60 seconds.
     * It then calculates the start and end dates for the previous week and retrieves the report emails.
     * Finally, it calls the `Shark_getOrders` method to perform the necessary calculations and exits.
     * 
     */
    public function shark_calc_all_action_weekly_now() {
        // Supposed to prevent the function from being executed twice in 60 seconds.
        if ( get_transient( 'shark_calc_all_action_weekly_semaphore' ) ) return;
        set_transient( 'shark_calc_all_action_weekly_semaphore', true, 60);

        $previous_week = strtotime("-1 week +1 day");

        $start_week = strtotime("last monday midnight",$previous_week);
        $end_week = strtotime("next sunday",$start_week);

        $start_week = date("Y-m-d",$start_week);
        $end_week = date("Y-m-d",$end_week);

        $date_start = date('Y-m-d', strtotime('last week Monday'));
        $date_end = date('Y-m-d', strtotime('last week Sunday'));
        
        $report_emails = explode(",", get_option('report-emails-weekly-name'));

        $this->Shark_getOrders($start_week, $end_week, $report_emails);
            
        exit();
    }

    /**
     * Calculate shark action monthly.
     *
     * This function calculates the shark action monthly. It is triggered on the first day of each month.
     * It prevents the function from being executed twice in 60 seconds by using a transient semaphore.
     * The function retrieves the start and end date of the last month and the report emails from the options.
     * It then calls the Shark_getOrders method to perform some calculations based on the given dates and emails.
     * Finally, it exits the current script execution.
     *
     * @return void
     */
    public function shark_calc_all_action_monthly() {
        $now = strtotime("now");
        // Get current day in int
        $this_day = date('j');
        if ($this_day == 1) {
            // Supposed to prevent the function from being executed twice in 60 seconds.
            if ( get_transient( 'shark_calc_all_action_monthly_semaphore' ) ) return;
            set_transient( 'shark_calc_all_action_monthly_semaphore', true, 60);

            $date_start = date('Y-m-d', strtotime('first day of last month'));
            $date_end = date('Y-m-d', strtotime('last day of last month'));
            
            $report_emails = explode(",", get_option('report-emails-monthly-name'));

            $this->Shark_getOrders($date_start, $date_end, $report_emails);
            
            exit();
	    }
    }

    /**
     * Calculate all actions for the shark on a monthly basis.
     *
     * This function retrieves the current day and prevents the function from being executed twice within 60 seconds.
     * It then calculates the start and end dates for the previous month and retrieves the report emails.
     * Finally, it calls the `Shark_getOrders` method to perform the necessary calculations and exits.
     *
     * @throws Some_Exception_Class This function does not throw any exceptions.
     */
    public function shark_calc_all_action_monthly_now() {
    $now = strtotime("now");    // Get current day in int
    $this_day = date('j');
        // Supposed to prevent the function from being executed twice in 60 seconds.
        if ( get_transient( 'shark_calc_all_action_monthly_semaphore' ) ) return;
        set_transient( 'shark_calc_all_action_monthly_semaphore', true, 60);

        $date_start = date('Y-m-d', strtotime('first day of last month'));
        $date_end = date('Y-m-d', strtotime('last day of last month'));
        
        $report_emails = explode(",", get_option('report-emails-monthly-name'));

        $this->Shark_getOrders($date_start, $date_end, $report_emails);
        
        exit();
    }

    /**
     * Calculate all actions for the shark.
     * 
     * This function calls the `Shark_getOrders` method to perform the necessary calculations and exits.
     * 
     */
    public function shark_calc_all_action() {
        if ( isset ( $_GET['date-start'] ) ){
            $report_emails = explode(",", $_GET['report-emails-aangepast-name']);
            $this->Shark_getOrders($_GET['date-start'], $_GET['date-end'], $report_emails);
            $url = admin_url('admin.php?page=shark-report');
            
            exit();    
        }
    }

    public function Shark_getOrders($date_start, $date_end, $report_emails){
        //echo("Shark_getOrders got called");
        /**
        * Check if WooCommerce is active
        **/
        if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

            $shark_report_options = get_option( 'shark_report_option_name' );

            // Get all orders from woocommeSrce between date period
            $orders = wc_get_orders( array(
                'limit' => -1,
                'date_created' => $date_start.'...'.$date_end,
                'status' => array('wc-processing', 'wc-completed', 'wc-on-hold', 'wc-pending'),
            ) );

            // $args = array(
            //     'limit' => -1,
            //     'date_completed' => $date_start.'...'.$date_end,
            // );
            // $orders = wc_get_orders( $args );
            
            $order_lines_data = array();
            $total_shipping = 0;
            $quantity_all = 0;
            $total_ex_btw = 0;
            $total_btw = 0;
            $total_incl_btw = 0;
            $coupons = array();

            var_dump(count($orders));
            foreach ($orders as $order_id) { // Get all order ids
                $order = wc_get_order($order_id); // Get the order by id
                $total_shipping = $order->get_shipping_total() + $total_shipping;
                $order_date = $order->get_date_created();
                if ($order->get_coupons()) {
                    $coupons = array_merge($coupons, $order->get_coupons());
                    }
                // Get the data per order line
                
                foreach($order->get_items() as $order_line) {
                    $item_data = $order_line->get_data();
                    $product_name = $item_data['name'];
                    $quantity = $item_data['quantity'];
                    //$line_total = $item_data['total'];
                    $line_total = $order_line->get_subtotal();
                    $line_total_btw = $order_line->get_subtotal_tax();

                    // Get an instance of the WC_Product object (can be a product variation too)
                    $product = $order_line->get_product();
                    //var_dump($order_line);

                    if($product){
                        // Special code to get the categories of the product of the order line
                        $categories = get_the_terms($order_line->get_product_id(), 'product_cat');
    
                        // Get the product description (works for product variation too)
                        $description = "";
                        if ($product) {
                            $description = $product->get_description();
                        }
                        
                        // CHANGE ON 2 DECEMBER 2022, since adding the multiple categories adds up the Quantity,
                        // We will for now take the first category
                        if (count($categories) >= 1) {
                            $seperate_category = $categories[0];

                            $itemCat = array(
                                "Category" => $seperate_category->name,
                                "CategoryID" => $seperate_category->term_id,
                                "Name" => $product_name,
                                "Description" => $description,
                                "Quantity" => $quantity,
                                "Total" => $line_total,
                                "Total_btw" => $line_total_btw
                            );
                            array_push($order_lines_data, $itemCat);
                        }

                        // Since a product can be in multiple categories, add each one seperately to this
                        // foreach ($categories as $seperate_category) {
                        //     $itemCat = array(
                        //         "Category" => $seperate_category->name,
                        //         "CategoryID" => $seperate_category->term_id,
                        //         "Name" => $product_name,
                        //         "Description" => $description,
                        //         "Quantity" => $quantity,
                        //         "Total" => $line_total,
                        //         "Total_btw" => $line_total_btw
                        //     );
                        //     array_push($order_lines_data, $itemCat);
                        // }
                    }
                    else{
                        $itemCat = array(
                            "Category" => "Product verwijderd",
                            "CategoryID" => "Product verwijderd",
                            "Name" => $product_name,
                            "Description" => "Product verwijderd",
                            "Quantity" => $quantity,
                            "Total" => $line_total,
                            "Total_btw" => $line_total_btw
                        );
                        array_push($order_lines_data, $itemCat);
                    }
                }
            }

            // Create a basic array for the excel
            $result = array(array('name' => '<b>Verkopen per product Webshop</b>'));
            array_push($result, array('name' => '', 'description' => ''));
            array_push($result, array('name' => '<b>Bedrijfsnaam</b>', 'description' => 'Kringloopbedrijf Het Warenhuis'));
            array_push($result, array('name' => '<b>KvK-nummer</b>', 'description' => '64643069'));
            array_push($result, array('name' => '<b>Periode:</b>', 'description' => (new DateTime($date_start))->format('d-m-Y') . ' - ' . (new DateTime($date_end))->format('d-m-Y')));
            array_push($result, array('name' => '', 'description' => ''));
            array_push($result, array('name' => '<b>Artikel</b>', 'description' => '<b>Variant</b>','category' => '<b>Categorie</b>','quantity' => '<b>Hoeveelheid</b>', 'total' => '<b>Totaal (ex BTW)</b>', 'total_btw' => '<b>BTW</b>', 'total_all' => '<b>Totaal (incl BTW)</b>'));

            // Loop over the just gotten data to get it in the format the excel wants
            foreach ($order_lines_data as $order_line_data) {                
                $categoryID = $order_line_data['CategoryID'];
                $categoryName = $order_line_data['Category'];
                $productName = $order_line_data['Name'];
                $orderLineQuantity = $order_line_data['Quantity'];
                $orderLineTotal = $order_line_data['Total'];
                $orderLineTotalBtw = $order_line_data['Total_btw'];
                $orderLineDescription = $order_line_data['Description'];

                $total_all = $orderLineTotal + $orderLineTotalBtw;
                $quantity_all = $quantity_all + $orderLineQuantity;
                $total_ex_btw = $total_ex_btw + $orderLineTotal;
                $total_btw = $total_btw + $orderLineTotalBtw;
                $total_incl_btw = $total_incl_btw + $total_all;

                $row_key = array_search($productName, array_column($result, 'name'));
                if ($row_key !== false) {
                $row = $result[$row_key];
                    // Sum up the quantity and total of what's already in the array and what we currently have in $order_line_data
                    $row['quantity'] = $row['quantity'] + $orderLineQuantity;
                    $row['total'] = number_format($row['total'] + $orderLineTotal, 2);
                    $row['total_btw'] = number_format($row['total_btw'] + $orderLineTotalBtw, 2);
                    $row['total_all'] = number_format($row['total_all'] + $total_all, 2);

                    // Add the new totals back to the resultset
                    $result[$row_key] = $row;
                } else {
                    $parentcategory = "";
                    $categoryParents = get_ancestors($categoryID, 'product_cat');
                    foreach($categoryParents as $parentcat){
                        $parentcategory = $parentcat;
                    }
                    array_push($result, array(
                        //'date' => $order_date,
                        'name' => $productName,
                        'category' => $categoryName,
                        'description' => $this->get_product_category_by_id($parentcategory),
                        'quantity' => $orderLineQuantity,
                        'total' => number_format($orderLineTotal, 2),
                        'total_btw' => number_format($orderLineTotalBtw, 2),
                        'total_all' => number_format($total_all, 2),
                    )); 
                }
            }

            // Write the results to the array called results (our main array)
            array_push($result, array());
            array_push($result, array('name' => '', 'description' => '','category' => '','quantity' => number_format($quantity_all, 0), 'total' => number_format($total_ex_btw, 2), 'total_btw' => number_format($total_btw, 2), 'total_all' => number_format($total_incl_btw, 2)));
            array_push($result, array('name' => '<hr>'));
            array_push($result, array('name' => '<b>Verzendkosten</b>', 'description' => $total_shipping));
            array_push($result, array('name' => '<hr>'));
            array_push($result, array('name' => '<b>Cadeaubonnen</b>'));
            array_push($result, array('name' => '<b>Naam</b>', 'description' => '<b>Datum</b>','category' => '','quantity' => '', 'total' => '<b>Totaal (ex BTW)</b>', 'total_btw' => '<b>BTW</b>', 'total_all' => '<b>Totaal (incl BTW)</b>'));
            foreach ($coupons as $coupon) {
                //var_dump($coupon);
                //array_push($result, array('name' => $coupon->get_code(), 'description' => $coupon->get_discount()));
                array_push($result, array(
                    'name' => $coupon->get_code(),
                    'description' => $coupon->get_order()->get_date_created(),
                    'category' => '',
                    'quantity' => '',
                    'total' => $coupon->get_discount(),
                    'total_btw' => $coupon->get_discount_tax(),
                    'total_all' => $coupon->get_discount() + $coupon->get_discount_tax()
                    ));
                }
            
            //var_dump($result);

            // Save the excel to the filesystem
            $path = getcwd() ."/Omzet CW ".$date_start.' - '.$date_end.".xlsx";
            SimpleXLSXGen::fromArray($result)->saveAs($path);

            var_dump($path);

            // Retrieve the emails from the report-emails-name option
            $multiple_recipients_weekly = explode(",", get_option('report-emails-weekly-name'));

            // Retrieve the emails from the report-emails-name option
            $multiple_recipients_monthly = explode(",", get_option('report-emails-monthly-name'));

            // email the csv
            //$multiple_recipients = array(
            //    'r.bos@kringloopwarenhuis.nl',
            //    'y.schoenmaker@kringloopwarenhuis.nl'
            //);

            var_dump($report_emails);

            var_dump(wp_mail( 
            $report_emails,                                     // To
            'Omzet CW Webshop: '.$date_start.' - '.$date_end,   // Subject
            'Omzet CW Webshop: '.$date_start.' - '.$date_end,   // Body
            '',                                                 // Headers
            array($path)));                                     // Attachments
            
            // close temp file
            SimpleXLSXGen::fromArray($result)->saveAs(getcwd() . "/Omzet CW ".$date_start.' - '.$date_end.".xlsx"); // or downloadAs('books.xlsx') or $xlsx_content = (string) $xlsx 

            // Disable this to get log
            //-------------------------
            wp_safe_redirect(
                    // Retrieves the site url for the current site.
                    add_query_arg( 
                        array( 
                            'success' => '1'
                        ), 
                        site_url( '/wp-admin/admin.php?page=shark-report' )
                )
            );
            exit();
            
            die;
        }
    }

    // function get_product_category_by_id( $category_id ) {
    //     $term_list = wp_get_post_terms($category_id, 'product_cat', array('fields' => 'ids'));
    //     return $term_list;
    //   }
    function get_product_category_by_id( $category_id ) {
        //echo("get_product_category_by_id got called");
        $term = get_term_by( 'id', $category_id, 'product_cat', 'ARRAY_A' );
        if ($term == false) {
            return '';
          }
        //echo("get_product_category_by_id got called");
        return $term['name'];
      }
    public function shark_calc_get_all_coupons_action(){
        $order = wc_get_order(9276);

        // Get coupons by orderimage.png
        foreach ($order->get_items('coupon') as $_ => $coupon_item) {

            $coupon = new WC_Coupon($coupon_item['name']);

            $coupon_post = get_post((WC()->version < '2.7.0') ? $coupon->id : $coupon->get_id());
            $discount_amount = !empty($coupon_item['discount_amount']) ? $coupon_item['discount_amount'] : 0;
            $coupon_items[] = implode('|', array(
                    'code:' . $coupon_item['name'],
                    'description:' . ( is_object($coupon_post) ? $coupon_post->post_excerpt : '' ),
                    'amount:' . wc_format_decimal($discount_amount, 2),
            ));

            var_dump($coupon);
        }
    }

}
