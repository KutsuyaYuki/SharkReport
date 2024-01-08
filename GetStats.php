<?php

/**
 * Class for handling settings and functionality related to Shark Reports in WordPress admin.
 *
 * Provides options and actions for generating sales reports in WooCommerce. 
 * Also includes methods for adding cron jobs, registering settings, and more.
 *
 */
class SharkGetStats
{

    public function __construct()
    {
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
    public function shark_calc_all_action_weekly()
    {
        // Supposed to prevent the function from being executed twice in 60 seconds.
        if (get_transient('shark_calc_all_action_weekly_semaphore')) return;
        set_transient('shark_calc_all_action_weekly_semaphore', true, 60);

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
    public function shark_calc_all_action_weekly_now()
    {
        // Supposed to prevent the function from being executed twice in 60 seconds.
        if (get_transient('shark_calc_all_action_weekly_semaphore')) return;
        set_transient('shark_calc_all_action_weekly_semaphore', true, 60);

        $previous_week = strtotime("-1 week +1 day");

        $start_week = strtotime("last monday midnight", $previous_week);
        $end_week = strtotime("next sunday", $start_week);

        $start_week = date("Y-m-d", $start_week);
        $end_week = date("Y-m-d", $end_week);

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
    public function shark_calc_all_action_monthly()
    {
        $now = strtotime("now");
        // Get current day in int
        $this_day = date('j');
        if ($this_day == 1) {
            // Supposed to prevent the function from being executed twice in 60 seconds.
            if (get_transient('shark_calc_all_action_monthly_semaphore')) return;
            set_transient('shark_calc_all_action_monthly_semaphore', true, 60);

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
    public function shark_calc_all_action_monthly_now()
    {
        $now = strtotime("now");    // Get current day in int
        $this_day = date('j');
        // Supposed to prevent the function from being executed twice in 60 seconds.
        if (get_transient('shark_calc_all_action_monthly_semaphore')) return;
        set_transient('shark_calc_all_action_monthly_semaphore', true, 60);

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
    public function shark_calc_all_action()
    {
        if (isset($_GET['date-start'])) {
            $report_emails = explode(",", $_GET['report-emails-aangepast-name']);
            $this->Shark_getOrders($_GET['date-start'], $_GET['date-end'], $report_emails);
            $url = admin_url('admin.php?page=shark-report');

            exit();
        }
    }

    public function Shark_listAllProductsAndCategories()
    {
        // Retrieve all published products.
        $args = array(
            'status' => 'publish',
            'limit'  => -1,
            'return' => 'objects',
        );

        $products = wc_get_products($args);
        $category_tree = [];

        // First, build a category tree.
        foreach ($products as $product) {
            // Get product ID, name and its categories.
            $product_name = $product->get_name();
            $categories_terms = wp_get_post_terms($product->get_id(), 'product_cat', array('fields' => 'all'));

            foreach ($categories_terms as $term) {
                if (!array_key_exists($term->name, $category_tree)) {
                    $category_tree[$term->name] = [];
                }

                // Avoid duplicating product names within the same category.
                if (!in_array($product_name, $category_tree[$term->name])) {
                    array_push($category_tree[$term->name], htmlspecialchars_decode($product_name));
                }

                // Additionally handle parent-child relationships if any sub-categories exist.
                while ($term->parent != 0 && ($parent_term = get_term_by('id', $term->parent, 'product_cat'))) {
                    if (!isset($category_tree[$parent_term->name][$term->name])) {
                        // Initialize sub-category with empty array or keep existing products
                        $category_tree[$parent_term->name][$term->name] = &$category_tree[$term->name];
                    }
                    $term = &$parent_term;
                }
            }
        }

        // Now format this data into desired string representation.

        ob_start();  // Start capturing the echoed output

        echo PHP_EOL;   // Prepend new line for better readability

        foreach ($category_tree as $cat_name => &$subcats_or_products) {

            echo "{$cat_name}" . PHP_EOL;

            if (is_array($subcats_or_products)) {

                foreach ($subcats_or_products as $_subCatName => $_productsOrSubcatArray) {

                    if (is_array($_productsOrSubcatArray)) {

                        echo "— {$_subCatName}" . PHP_EOL;

                        foreach ($_productsOrSubcatArray as &$_productName) {
                            echo "—— {$_productName}" . PHP_EOL;

                            unset($_productName);  // Unset reference after usage to avoid potential issues on next iteration
                        }
                    } else {
                        echo "— {$_productsOrSubcatArray}" . PHP_EOL;
                    }

                    unset($_productsOrSubcatArray);  // Unset reference after usage to avoid potential issues on next iteration

                }
            } else {

                echo "— {$subcats_or_products}" . PHP_EOL;
            }

            unset($subcats_or_products);   // Unset reference after usage to avoid potential issues on next iteration

        }

        return ob_get_clean();   // Return captured content and stop capturing
    }


    public function Shark_dumpAllProductCategories()
    {
        // Retrieve all terms in the 'product_cat' taxonomy including empty.
        $all_categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
            'parent'   => 0
        ));

        // Function to recursively show category and its children.
        $print_category_tree = function ($categories, $depth = 0) use (&$print_category_tree) {
            foreach ($categories as $category) {
                // Print category name with indentation based on depth.
                echo str_repeat('— ', $depth) . $category->name . PHP_EOL;

                // Get child categories.
                $child_categories = get_terms(array(
                    'taxonomy' => 'product_cat',
                    'hide_empty' => false,
                    'parent'   => $category->term_id
                ));

                if (!empty($child_categories)) {
                    // If child categories are found, recurse into them and increase depth for indentation.
                    $print_category_tree($child_categories, $depth + 1);
                }
            }
        };

        ob_start();  // Start buffering output
        echo '<pre>';

        // Call recursive print with parent categories only (top level).
        $print_category_tree($all_categories);

        echo '</pre>';

        // Get buffered content and clean buffer
        $output = ob_get_clean();

        var_dump($output);  // Dump output which includes both parents and their children properly indented.
    }





    public function Shark_getOrders($dateStart, $dateEnd, $reportEmails)
    {
        // Initiate product categories dump
        $this->Shark_dumpAllProductCategories();

        // List all products and categories
        $this->Shark_listAllProductsAndCategories();

        // Check if WooCommerce is active
        if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            // Get all orders from WooCommerce between date period
            $orders = wc_get_orders(array(
                'limit' => -1,
                'date_created' => "$dateStart...$dateEnd",
                'status' => array('wc-processing', 'wc-completed', 'wc-on-hold', 'wc-pending'),
            ));

            // Define variables to collect order data
            $orderLinesData = array();
            $totalShipping = 0;
            $quantityAll = 0;
            $totalExBtw = 0;
            $totalBtw = 0;
            $totalInclBtw = 0;
            $coupons = array();


            // Added: Initialize these variables before using them
            $result = array();
            $quantity_all = 0;
            $total_ex_btw = 0;
            $total_btw = 0;
            $total_incl_btw = 0;
            var_dump(count($orders));

            foreach ($orders as $orderId) {
                $order = wc_get_order($orderId); // Get the order by id
                $totalShipping = $order->get_shipping_total() + $totalShipping;
                $orderDate = $order->get_date_created();
                if ($order->get_coupons()) {
                    $coupons = array_merge($coupons, $order->get_coupons());
                }
                // Get the data per order line
                foreach ($order->get_items() as $orderLine) {
                    $itemData = $orderLine->get_data();
                    $productName = $itemData['name'];
                    $quantity = $itemData['quantity'];
                    $lineTotal = $orderLine->get_subtotal();
                    $lineTotalBtw = $orderLine->get_subtotal_tax();

                    // Instance of the WC_Product object
                    $product = $orderLine->get_product();

                    if ($product) {
                        $afdeling = $product->get_attribute("Afdeling");

                        // Fetch product categories
                        $categories = get_the_terms($orderLine->get_product_id(), 'product_cat');

                        // Fetch product description
                        $description = $product ? $product->get_description() : "";

                        // If product belongs to one or more categories, take the first one
                        if (count($categories) >= 1) {
                            $separateCategory = $categories[0];

                            // Information of item category-wise
                            $itemCat = array(
                                "Category" => $separateCategory->name,
                                "CategoryID" => $separateCategory->term_id,
                                "Afdeling" => $afdeling,
                                "Name" => $productName,
                                "Description" => $description,
                                "Quantity" => $quantity,
                                "Total" => $lineTotal,
                                "Total_btw" => $lineTotalBtw
                            );
                            array_push($orderLinesData, $itemCat);
                        }
                    } else {
                        // In case product has been deleted
                        array_push($orderLinesData, [
                            "Category" => "Product verwijderd",
                            "CategoryID" => "Product verwijderd",
                            "Afdeling" => "Product verwijderd",
                            "Name" => $productName,
                            "Description" => "Product verwijderd",
                            "Quantity" => $quantity,
                            "Total" => $lineTotal,
                            "Total_btw" => $lineTotalBtw
                        ]);
                    }
                }
            }


            // Prepare base structure for the excel
            $basicArray = [
                ['name' => '<b>Verkopen per product Webshop</b>'],
                ['name' => '', 'description' => ''],
                ['name' => '<b>Bedrijfsnaam</b>', 'description' => 'Kringloopbedrijf Het Warenhuis'],
                ['name' => '<b>KvK-nummer</b>', 'description' => '64643069'],
                ['name' => '<b>Periode:</b>', 'description' => (new DateTime($dateStart))->format('d-m-Y') . ' - ' . (new DateTime($dateEnd))->format('d-m-Y')],
                ['name' => '', 'description' => ''],
                [
                    'name' => '<b>Artikel</b>', 'description' => '<b>Variant</b>', 'category' => '<b>Afdeling</b>',
                    'quantity' => '<b>Hoeveelheid</b>',
                    'total' => '<b>Totaal (ex BTW)</b>',
                    'total_btw' => '<b>BTW</b>',
                    'total_all' => '<b>Totaal (incl BTW)</b>'
                ],
            ];

            $result = array_merge($result, $basicArray);

            var_dump($orderLinesData);
            // Loop through the order lines data
            foreach ($orderLinesData as $orderLineData) {
                $categoryID = $orderLineData['CategoryID'];
                $categoryName = $orderLineData['Category'];
                $afdeling = $orderLineData['Afdeling'];
                $productName = $orderLineData['Name'];
                $orderLineQuantity = $orderLineData['Quantity'];
                $orderLineTotal = $orderLineData['Total'];
                $orderLineTotalBtw = $orderLineData['Total_btw'];
                $orderLineDescription = $orderLineData['Description'];

                $total_all = $orderLineTotal + $orderLineTotalBtw;
                $quantity_all = $quantity_all + $orderLineQuantity;
                $total_ex_btw = $total_ex_btw + $orderLineTotal;
                $total_btw = $total_btw + $orderLineTotalBtw;
                $total_incl_btw = $total_incl_btw + $total_all;


                // Use product name map instead of costly array search
                $rowKey = array_search($productName, array_column($result, 'name'));
                if ($rowKey !== false) {
                    $row = $result[$rowKey];
                    // Sum up the quantity and total of what's already in the array and what we currently have in $order_line_data
                    $row['quantity'] += $orderLineQuantity;
                    $row['total'] = number_format($row['total'] + $orderLineTotal, 2);
                    $row['total_btw'] = number_format($row['total_btw'] + $orderLineTotalBtw, 2);
                    $row['total_all'] = number_format($row['total_all'] + $total_all, 2);

                    // Add the new totals back to the resultset
                    $result[$rowKey] = $row;
                } else {
                    // Assume get_ancestors() returns an array; get the last category parent
                    $categoryParents = get_ancestors($categoryID, 'product_cat');
                    $parentCategory = end($categoryParents);

                    // Push new row to result array
                    $result[] = [
                        //'date' => $order_date,
                        'name' => $productName,
                        'category' => $categoryName,
                        'afdeling' => $afdeling,
                        //'description' => $this->get_product_category_by_id($parentCategory),
                        'quantity' => $orderLineQuantity,
                        'total' => number_format($orderLineTotal, 2),
                        'total_btw' => number_format($orderLineTotalBtw, 2),
                        'total_all' => number_format($total_all, 2),
                    ];
                }
            }

            // Function to add an element to result array
            function addToResult(&$resultArray, $element)
            {
                $resultArray[] = $element;
            }

            // Add each new element to the result array
            addToResult($result, []);
            addToResult($result, [
                'name' => '',
                'description' => '',
                'afdeling' => '',
                'quantity' => number_format($quantity_all, 0),
                'total' => number_format($total_ex_btw, 2),
                'total_btw' => number_format($total_btw, 2),
                'total_all' => number_format($total_incl_btw, 2)
            ]);
            addToResult($result, ['name' => '<hr>']);
            addToResult($result, ['name' => '<b>Verzendkosten</b>', 'description' => $totalShipping]);
            addToResult($result, ['name' => '<hr>']);
            addToResult($result, ['name' => '<b>Cadeaubonnen</b>']);
            addToResult($result, [
                'name' => '<b>Naam</b>',
                'description' => '<b>Datum</b>',
                'afdeling' => '',
                'quantity' => '',
                'total' => '<b>Totaal (ex BTW)</b>',
                'total_btw' => '<b>BTW</b>',
                'total_all' => '<b>Totaal (incl BTW)</b>'
            ]);

            foreach ($coupons as $coupon) {
                addToResult($result, [
                    'name' => $coupon->get_code(),
                    'description' => $coupon->get_order()->get_date_created(),
                    'afdeling' => '',
                    'quantity' => '',
                    'total' => $coupon->get_discount(),
                    'total_btw' => $coupon->get_discount_tax(),
                    'total_all' => $coupon->get_discount() + $coupon->get_discount_tax()
                ]);
            }


            // Save the excel file
            $path = getcwd() . "/Omzet CW " . $dateStart . ' - ' . $dateEnd . ".xlsx";
            SimpleXLSXGen::fromArray($result)->saveAs($path);

            // Retrieve the emails
            $multiple_recipients_weekly = explode(",", get_option('report-emails-weekly-name'));
            $multiple_recipients_monthly = explode(",", get_option('report-emails-monthly-name'));

            // Send the email
            wp_mail(
                $reportEmails,
                'Omzet CW Webshop: ' . $dateStart . ' - ' . $dateEnd,
                'Omzet CW Webshop: ' . $dateStart . ' - ' . $dateEnd,
                '',
                [$path]
            );

            // Save the file again
            SimpleXLSXGen::fromArray($result)->saveAs(getcwd() . "/Omzet CW " . $dateStart . ' - ' . $dateEnd . ".xlsx");

            exit();


            return createExcelReport($result);
        }
    }

    // function get_product_category_by_id( $category_id ) {
    //     $term_list = wp_get_post_terms($category_id, 'product_cat', array('fields' => 'ids'));
    //     return $term_list;
    //   }
    function get_product_category_by_id($category_id)
    {
        //echo("get_product_category_by_id got called");
        $term = get_term_by('id', $category_id, 'product_cat', 'ARRAY_A');
        if ($term == false) {
            return '';
        }
        //echo("get_product_category_by_id got called");
        return $term['name'];
    }
    public function shark_calc_get_all_coupons_action()
    {
        $order = wc_get_order(9276);

        // Get coupons by orderimage.png
        foreach ($order->get_items('coupon') as $_ => $coupon_item) {

            $coupon = new WC_Coupon($coupon_item['name']);

            $coupon_post = get_post((WC()->version < '2.7.0') ? $coupon->id : $coupon->get_id());
            $discount_amount = !empty($coupon_item['discount_amount']) ? $coupon_item['discount_amount'] : 0;
            $coupon_items[] = implode('|', array(
                'code:' . $coupon_item['name'],
                'description:' . (is_object($coupon_post) ? $coupon_post->post_excerpt : ''),
                'amount:' . wc_format_decimal($discount_amount, 2),
            ));

            var_dump($coupon);
        }
    }
}
