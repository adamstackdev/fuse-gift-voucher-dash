<?php 


class GiftVoucherDashboard
{

    public $report_name = "Gift Voucher Dashboard";
    public $report_slug = "gift_voucher_dashboard";
    public $report_type = 'report'; //NEW - Change to either 'report' or 'tool'
    public $period;
    public $date_format;

    function __construct(){

        $fusedb = new FuseDatabase();

        $this->auto_enable_report($fusedb);
        
        add_action( 'admin_init', array($this, 'add_ajax_actions'), 100 );

        if(isset($_SESSION[$this->report_slug.'_registered'])){
            //Don't re register
            do_action( 'qm/debug', 'Report already registered: '.$this->report_name );

        }
        else{
            $this->register_report();
        }

    }


    function hide_from_master( $reports ) {

        $reports = array_filter($reports, function($v, $k) {
            return $v->reportslug !== $this->report_slug;
        }, ARRAY_FILTER_USE_BOTH);

        return $reports;
    }


    function register_report(){
        $templatedir = WP_PLUGIN_DIR . '/fuse-reports/reports/gift-voucher-dashboard/template.php';
        $fusedb = new FuseDatabase();

        do_action( 'qm/debug', 'Register Report: '.$this->report_name );
        $fusedb->add_report($this->report_name, $this->report_slug, 'edit_posts', false, $templatedir, $this->report_type);
        $_SESSION[$this->report_slug.'_registered'] = true;
        //Auto enable this report
        
    }

    function add_ajax_actions(){
        add_action('wp_ajax_dashboard_get_data', array($this, 'get_data'));
        add_action('wp_ajax_reduce_date_filters', array($this, 'reduce_date_filters'));
        add_filter( 'fuse-reports-extra-reports-mainpage', array($this, 'hide_from_master'), 1, 1 );
    }

    function auto_enable_report($fusedb){
        // return;//switch off auto_enable_report

        
        $report = $fusedb->get_report($this->report_slug)[0];
        $fusedb->update_report($report->id, $report->reportname, 1);

        //Enable it for every user
        $report_user_args = array(
            'role__in'  =>  array('fuse_reports_only','shop_manager','shop_orders_only','fuse_user')
        );
        
        $report_users = get_users($report_user_args);     



        foreach($report_users as $user){
            $reports = get_user_meta($user->ID, 'fuse_report_access', true);

            //if user doesn't have this report or an array saved in the user meta, create a new array to load this report in to
            if(!is_array($reports) && $reports == ''){
                $reports = array();
            }
            do_action( 'qm/debug', print_r($reports, true) );
            //print("<pre>".var_dump($reports)."</pre>");
            
            
            if(!in_array($report->id, $reports)){ //If not already enabled for this user, add it.
                $reports[] = $report->id;
                update_user_meta($user->ID, 'fuse_report_access', $reports);
            }
            

            
        }

    }

    /**Get sales data */

    function get_data(){

        $sales = $this->get_sales();
        $vouchers = $this->dashboard_get_vouchers();

        wp_send_json(array('vouchers' => $vouchers, 'sales' => $sales));
    }

    function get_sales(){

        $this->date_format = 'd-m-Y';

        $reduce_func = function ($result, $item) use($date_format){
            
            if(!$item['order_date']){
                // $order = wc_get_order($item['order number']);
                // if(!$order){
                //     return null;
                // }
                // $item['order_date'] = date($this->date_format, strtotime($order->get_date_paid()->format($this->date_format)));
                // wp_send_json( $item['order number'] );
 
            } else {
                //strtotime assumes a UK date if - is used. If the year is first, replace with /
                if(strlen(explode('-', $item['order_date'])[0]) == 4){
                    $item['order_date'] = str_replace('-', '/', $item['order_date']);
                }
                $date = date($this->date_format, strtotime($item['order_date']));


                //separate sales into their categories
                $type = $item['type'];
                if($type == 'event_ticket'){
                    //Need to check whether it's an event ticket OR an event voucher
                    // $order_meta = get_post_meta($item['order number']);
                    // $type = $order_meta['order_type'][0];
                    $order = wc_get_order($item['order number']);
                    foreach($order->get_items() as $order_item){
                        $type = $order_item->get_meta('Type', true);
                        $gift_card_type = $order_item->get_meta('Gift Card Type', true);
                        if($type == 'Event Ticket' && $gift_card_type == 'digital'){
                            $type = "event_voucher";
                        }
                        $prev_amount = $result[$type][$date] ?? 0;
                        $result[ucfirst(str_replace('_',' ',$type))][$date] = $item['item_amount'] += $prev_amount;
                    }
                } else {
                    $prev_amount = $result[$type][$date] ?? 0;
                    $result[ucfirst(str_replace('_',' ',$type))][$date] = $item['item_amount'] += $prev_amount;
                }
            }

            return $result;
        }; 

        $sort_sales_dates = function ($dt1, $dt2) {
            return strtotime($dt1) - strtotime($dt2);
        };

        $sales = new WP_Query(array( 
            'post_type' =>  'invoices',
            'posts_per_page'    =>  -1,
        ));

        if($sales->have_posts()){
            $first_invoice = $sales->posts[0];
            $after_date = get_the_date('01-m-Y', $first_invoice);
            $last_invoice = $sales->posts[$sales->found_posts];
            $before_date = get_the_date('t-m-Y', $last_invoice);
        } else {
            //If there aren't any posts - we can't get a date range. So just do 12 months (otherwise filling in 0's below wont work).
            $after_date = date('d-m-Y', strtotime(date('d-m-Y'). '- 12 months'));
            $after_date = date('d-m-Y');
        }

        $period = new DatePeriod(
            new DateTime(date('d-m-Y', strtotime(date('d-m-Y').' - 2 years'))),
            new DateInterval('P1D'),
            new DateTime(date('d-m-Y'))
        );

        $this->period = iterator_to_array($period);


        $invoice_json = array();

        foreach($sales->posts as $sale){
            $invoices_json_array[] = get_post_meta($sale->ID, 'item_json', true); //decode invoice json
        }


        foreach($invoices_json_array as $json_string){
            $json_array = json_decode($json_string, true);
            if($json_array){
                $invoice_json = array_merge($invoice_json, $json_array);
            }
        }
      
        $sales_array = array_reduce( $invoice_json, $reduce_func) ?? array();
        
        //If there are no sales, fill with zeros instead. (if it's empty it will use a 3 month date range as per above).
        if(empty($sales_array)){
            foreach($this->period as $key => $date){
                $sales_array['Sales'][$date->format($this->date_format)] = 0;
            }
        } else {
            foreach($sales_array as $category => $sales_data){
                foreach($this->period as $key => $date){
                    if(!isset($sales_array[$category][$date->format($this->date_format)]) && is_array($sales_array[$category])){
                        $sales_array[$category][$date->format($this->date_format)] = 0;
                    }
                }
            }
        }
        
        foreach($sales_array as $category => $sales_data){
            if(is_array($sales_data)){
                uksort($sales_data, $sort_sales_dates);
                $sales_array[$category] = $sales_data;
            }
        }

        return $sales_array;

    }


    /** Get voucher data */

    function dashboard_get_vouchers(){

        $sort_sales_dates = function ($dt1, $dt2) {
            return strtotime($dt1) - strtotime($dt2);
        };

        $args = array(
            'post_type' =>  'voucher_codes',
            'post_status'   =>  'publish',
            'posts_per_page'    =>  -1,
        );

        $voucher_codes = new WP_Query( $args );

        if(!empty($voucher_codes->posts)){
            $reduced_vouchers = array_reduce($voucher_codes->posts, array($this, 'reduce_vouchers'));
            $balance = array_reduce($reduced_vouchers, array($this, 'reduce_amounts'));
            $breakage = array_reduce($reduced_vouchers, array($this, 'reduce_breakage'));
        } else {
            $reduced_vouchers = array(
                '1' => array(
                    'amount' => 0,
                    'expiry_date' => '01/01/2999',
                    'code_type' => 'Gift Card',
                    'order_date' => '01/01/2999'
                )
            );
            $balance = 0;
            $breakage = 0;
        }



        $redemptions = array_reduce($reduced_vouchers, array($this, 'reduce_redemptions'));
        if(empty($redemptions)){ //Populate with sample data (0's).
            foreach($this->period as $key => $date){
                if(!isset($redemptions['Redemptions'][$date->format($this->date_format)])){
                    $redemptions['Redemptions'][$date->format($this->date_format)] = 0;
                }
            }   
        } else {
            foreach($redemptions as $type => $data){
                foreach($this->period as $key => $date){
                    if(!isset($redemptions[$type][$date->format($this->date_format)])){
                        $redemptions[$type][$date->format($this->date_format)] = 0;
                    }
                } 
            }
        }

        foreach($redemptions as $type => $redemptions_data){
            if(is_array($redemptions_data)){
                uksort($redemptions_data, $sort_sales_dates);
                $redemptions[$type] = $redemptions_data;
            }
        }

        return array('sales' => $reduced_vouchers, 'redemptions' => $redemptions, 'balance' => $balance, 'breakage' => $breakage);
    }

    /** Reduce Functions */

    function reduce_vouchers($carry, $item){
        $amount = get_post_meta($item->ID, 'amount', true);
        $order_id = get_post_meta($item->ID, 'order_id', true);

        if(isset($carry[$order_id])){
            $amount = $carry[$order_id]['amount'] += $amount;
        }

        $redemptions = array();
            $order = wc_get_order($order_id);

            if($order){
                $expiry_date = get_post_meta($item->ID, 'expiry_date', true);
                $order_date = date('d-m-Y', strtotime($order->get_date_paid()->format('d-m-Y')));
                $code_type = (get_field('code_type', $item->ID) == '') ? 'Gift Card' : 'Experience Voucher';
                if(!$expiry_date){
                    $expiry_date = date('d-m-Y', strtotime($order_date.' + 1 year'));
                }

                foreach(get_post_meta($item->ID) as $key => $value){
                    if(str_contains( $key, 'redemption' )){
                        $redemptions[$key] = $value;
                    }
                }

                $carry[$order_id] = array(
                    'amount' => $amount,
                    'expiry_date'   =>  $expiry_date,
                    'code_type'     =>  $code_type,
                    'redemptions' => $redemptions,
                    'order_date' => $order_date
                );
            }

        return $carry;
    }

    function reduce_redemptions($carry, $item){
        if(!empty($item['redemptions'])){
            $redemption_amount = 0;
            foreach($item['redemptions'] as $key => $redemption){
                if(str_contains( $key, 'amount' )){
                    $redemption_date = date('d-m-Y', strtotime($item['redemptions']['redemption_date_'.substr($key, -1)][0]));
                    $redemption_amount += $item['redemptions']['redemption_amount_'.substr($key, -1)][0];
                }
            }
            $prev_amount = $carry[$redemption_date] ?? 0;
            $carry[$item['code_type']][$redemption_date] = $redemption_amount + $carry[$redemption_date];
        }
        return $carry;
    }

    function reduce_amounts($carry, $item){
        if(strtotime($item['expiry_date']) > strtotime(date('Y-m-d'))){ //Balance of non expired gift vouchers
            $carry += (float)$item['amount'];
        }
        return $carry;
    }


    function reduce_breakage($carry, $item){
        if(strtotime($item['expiry_date']) < strtotime(date('Y-m-d'))){ //Balance of expired gift vouchers
            $carry += (float)$item['amount'];
        }

        return $carry;
    }


    /**Reduce date filter callback */

    function reduce_date_filters(){

        //Change the date formats depending on which filter is chosen (1 month ranges need to be days / others need to be months).

        $from = $_POST['from'];
        $to = $_POST['to'];

        $earlier = new DateTime($from);
        $later = new DateTime($to);
        
        $abs_diff = $later->diff($earlier)->format("%a"); //3/ (60 * 60 * 24));

        if($abs_diff > 30){
            $date_format = 'M Y';
        } else {
            $date_format = 'd-m-Y';
        }

        //Vouchers
        $redemptions_temp = array();
        foreach($_POST['data']['vouchers']['redemptions'] as $type => $properties){

            $sales = array_filter($properties, function($v, $k) use($from, $to) {
                if(strtotime($k) >= strtotime($from) && strtotime($k) <= strtotime($to)){
                    return true;
                }
            }, ARRAY_FILTER_USE_BOTH );

            $redemptions_temp['vouchers']['redemptions'][$type] = $sales;
        }

        foreach($redemptions_temp['vouchers']['redemptions'] as $type => $sales){
            foreach($sales as $date => $redemptions){
                $return_array['vouchers']['redemptions'][$type][date($date_format, strtotime($date))] += $redemptions;
            }
        }

        

        //Voucher Sales (to calculate balance & breakage)

            $sales = array_filter($_POST['data']['vouchers']['sales'], function($v, $k) use($from, $to) {

                if(strtotime($v['order_date']) >= strtotime($from) && strtotime($v['order_date']) <= strtotime($to)){
                    return true;
                }
            }, ARRAY_FILTER_USE_BOTH );

            $return_array['vouchers']['sales'] = $sales;


        //Sales
        $sales_temp = array();
        foreach($_POST['data']['sales'] as $type => $properties){
            $_sales = array_filter($properties, function($v, $k) use($from, $to) {
                if(strtotime($k) >= strtotime($from) && strtotime($k) <= strtotime($to)){
                    return true;
                }
            }, ARRAY_FILTER_USE_BOTH );

            $sales_temp[$type] = $_sales;
        }   

        foreach($sales_temp as $type => $sales){
            foreach($sales as $date => $sales_count){
                $return_array['sales'][$type][date($date_format, strtotime($date))] += $sales_count;
            }
        }


        //Redemptions & Breakage

        $reduce_amt = function($carry, $item) use($from, $to){
            if(strtotime($item['expiry_date']) > strtotime(date($to))){ //Balance of non expired gift vouchers
                $carry += (float)$item['amount'];
            }
            return $carry;
        };
    
    
        $reduce_breakage = function($carry, $item) use($from, $to){
            if(strtotime($item['expiry_date']) < strtotime(date($to))){ //Balance of expired gift vouchers
                $carry += (float)$item['amount'];
            }
    
            return $carry;
        };

        $return_array['vouchers']['balance'] = array_reduce($_POST['data']['vouchers']['sales'], $reduce_amt);
        $return_array['vouchers']['breakage'] = array_reduce($_POST['data']['vouchers']['sales'], $reduce_breakage);

        wp_send_json($return_array);

    }

}
