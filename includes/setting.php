<?php

    require_once("core.php");
    //require_once("admin.php");

    function cbtc_on_activate(){

        global $wpdb;

        $table_name_orders = $wpdb->prefix . "cbtc_bitcoin_convert";
        $table_name_currency = $wpdb->prefix . "cbtc_bitcoin_convert_currency";
        $charset_collate = $wpdb->get_charset_collate();

        update_option("bctc_app_id", '');
        update_option("bctc_api_key", '');
        update_option("bctc_api_secret", '');
        update_option("bctc_email", '');
        update_option("bctc_q_type", 'live');
        update_option("bctc_save_api_keys", 1);
        update_option("bctc_save_operations_data", 1);
        update_option("bctc_data_lifetime", second_time(30));

        $sql = "CREATE TABLE $table_name_orders (
                    id int(11) NOT NULL,
                    token varchar(32) NOT NULL,
                    cardNumber varchar(16) NOT NULL,
                    amount float NOT NULL,
                    currency varchar(3) NOT NULL DEFAULT 'EUR',
                    submitDate bigint(20) NOT NULL,
                    payDate bigint(20) NOT NULL,
                    paymentResult text NOT NULL
                ) $charset_collate;
                CREATE TABLE $table_name_currency (
                    id int(11) NOT NULL,
                    currency varchar(3) NOT NULL,
                    fixedFee float NOT NULL,
                    percentageFee float NOT NULL
                ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        $wpdb->insert($wpdb->prefix.'cbtc_bitcoin_convert_currency'
            , array(
                'currency'      => 'EUR',
                'fixedFee'      => 5.0,
                'percentageFee' => 1
            )
        );
        $wpdb->insert($wpdb->prefix.'cbtc_bitcoin_convert_currency'
            , array(
                'currency'      => 'USD',
                'fixedFee'      => 5.5,
                'percentageFee' => 1
            )
        );

        global $user_ID;

        $idPost = wp_insert_post(array(
            'post_type'         => 'page',
            'post_title'        => 'Bitcoin convert',
            'post_content'      => file_get_contents(__DIR__ . '/convert_page.php'),
            'comment_status'    => 'closed',
            'post_status'       => 'publish',
            'ping_status'       => 'closed',
            'post_password'     => null,
            'post_author'       => $user_ID,
            'post_name'         => 'bitcoin-convert',
            'post_password'     => time(),
        ));
        update_option("bctc_convert_page_link", get_page_link($idPost));
        update_option("bctc_is_anable", 1);

    }

    function cbtc_on_deactivate(){

        $posts = get_pages();
        foreach ($posts as $post){
            if(strpos($post->post_name, 'bitcoin-convert') !== false){
                $id = $post->ID;
                wp_delete_post($id, true);
            }
        }

    }

    function cbtc_on_uninstall(){

        global $wpdb;

        if(get_option("bctc_save_api_keys") == 0){
            delete_option('bctc_app_id');
            delete_option('bctc_api_key');
            delete_option('bctc_api_secret');
            delete_option("bctc_save_api_keys");
        }
        delete_option('bctc_email');
        delete_option('bctc_q_type');
        delete_option("bctc_data_lifetime");

        if(get_option("bctc_save_operations_data") == 0){
            delete_option("bctc_save_operations_data");
            $wpdb->query( sprintf( "DROP TABLE IF EXISTS %s",
                $wpdb->prefix . 'cbtc_bitcoin_convert' ) );
        }
        $wpdb->query( sprintf( "DROP TABLE IF EXISTS %s",
                $wpdb->prefix . 'cbtc_bitcoin_convert_currency' ) );


    }

    function cbtc_script_style_init(){

        global $wp_query;
        $post_obj = $wp_query->get_queried_object();
        $post_slug = $post_obj->post_name;
        if(strpos($post_slug, 'bitcoin-convert') !== false) {
            wp_deregister_script('jquery');
            wp_register_script('jquery', "https://ajax.googleapis.com/ajax/libs/jquery/1.12.2/jquery.min.js", false, '1.12.2');
            wp_enqueue_script('jquery');
            wp_enqueue_script('bootstrap-js', plugins_url('cbtc_convert_bitcoin/assets/bootstrap/dist/js/bootstrap.min.js')
                , array('jquery'), '');
            wp_enqueue_script( 'custom-script-popper', plugins_url( 'cbtc_convert_bitcoin/assets/bootstrap/assets/js/vendor/popper.min.js')
                , array('jquery'), null, true);
            wp_enqueue_script( 'custom-script-recapcha', plugins_url( 'https://www.google.com/recaptcha/api.js')
                , array('jquery'), null, false );
            wp_enqueue_script('custom-script-mask', plugins_url('cbtc_convert_bitcoin/assets/js/jquery.mask.min.js')
                , array('jquery'), null, true);
            wp_enqueue_style('cbtc-bootstrap-style', plugins_url('cbtc_convert_bitcoin/assets/bootstrap/dist/css/bootstrap.min.css'));
            wp_enqueue_style('cbtc-custom-style', plugins_url('cbtc_convert_bitcoin/assets/css/app.css'));
        }

    }

    function second_time($days){
        return 60*60*24*$days;
    }