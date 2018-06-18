<?php

    function insertDB($data, $format){
        global $wpdb;
        return $wpdb->insert($wpdb->prefix.'cbtc_bitcoin_convert'
            , $data
            //, $format
        );
    }

    function getToken(){

        $c = uniqid (rand(), true);
        $md5c = md5($c);
        return $md5c;

    }

    function cbtc_action_function_convert_page($content){

        global $wp_query;
        global $wpdb;

        $post_obj = $wp_query->get_queried_object();
        $post_slug = $post_obj->post_name;

        $err_false_enter = false;
        $err_false_api = false;

        if(strpos($post_slug, 'bitcoin-convert') !== false) {

            if(isset($_POST['action'])){
                if((strlen(str_replace(" ", "", $_POST['cardNumber']))==16)
                        and is_numeric($_POST['amount'])
                        and is_numeric(str_replace(" ", "", $_POST['cardNumber']))){

                    require_once( $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php' );
                    require_once('coingate/init.php');

                    $token = getToken();
                    $cardNumber = str_replace(" ", "", $_POST['cardNumber']);
                    $amount = $_POST['amount'];
                    $currency = $_POST['currency'];
                    $submitDate = time();
                    $payDate = 0;
                    $paymentResult = "";
                    $successUrl = plugins_url("callback.php?token=$token", __FILE__ );

                    \CoinGate\CoinGate::config(array(
                        'environment' => get_option('bctc_q_type'), // sandbox OR live
                        'app_id'      => get_option('bctc_app_id'),
                        'api_key'     => get_option('bctc_api_key'),
                        'api_secret'  => get_option('bctc_api_secret')
                    ));

                    $table_name = $wpdb->prefix."cbtc_bitcoin_convert_currency";
                    $data = $wpdb->get_row("SELECT * FROM $table_name WHERE currency='"
                        .$_POST['currency']."'", ARRAY_A);
                    $totalAmount = $amount+($amount*$data["percentageFee"]/100)+$data["fixedFee"];

                    $post_params = array(
                        'order_id'          => $token,
                        'price'             => $totalAmount,
                        'currency'          => $_POST['currency'],
                        'receive_currency'  => 'EUR',
                        'callback_url'      => $successUrl,
                        'cancel_url'        => $successUrl,
                        'success_url'       => $successUrl,
                        'title'             => '',
                        'description'       => ''
                    );

                    $order = \CoinGate\Merchant\Order::create($post_params);

                    if ($order) {

                        $paymentResult = "{\"id\":".$order->id.", "
                            ."\"currency\":\"".$order->currency."\", "
                            ."\"bitcoin_uri\":\"".$order->bitcoin_uri."\", "
                            ."\"status\":\"".$order->status."\", "
                            ."\"created_at\":\"".$order->created_at."\", "
                            ."\"expire_at\":\"".$order->expire_at."\", "
                            ."\"bitcoin_address\":\"".$order->bitcoin_address."\", "
                            ."\"order_id\":\"".$order->order_id."\", "
                            ."\"price\":".$order->price.", "
                            ."\"btc_amount\":".$order->btc_amount.", "
                            ."\"payment_url\":\"".$order->payment_url."\"}";


                        insertDB(array(
                            "token" => $token,
                            "cardNumber" => $cardNumber,
                            "amount" => $amount,
                            "currency" => $currency,
                            "submitDate" => $submitDate,
                            "payDate" => $payDate,
                            "paymentResult" => $paymentResult
                        ), array('%s', '%s', '%f', '%s', '%d', '%d', '$s'));

                        $url = $order->payment_url;
                        echo "<script>document.location.href = '$url';</script>";

                    } else {

                        $err_false_api = true;

                    }

                }else{
                    $err_false_enter = true;
                }
            }

            if($err_false_api) {
                echo "<div class=\"alert alert-danger\" role=\"alert\">
                          Incorrect API`s keys!
                        </div>";
            }
            if($err_false_enter) {
                echo "<div class=\"alert alert-danger\" role=\"alert\">
                          Enter the correct information!
                        </div>";
            }
            echo "<div class='container'><div class='row justify-content-center'>
                    <div class='col-12 col-sm-10'>
                        <form id='convert-form' class='container' method='POST'>
                
                            <div class='form-group row'>
                                <label for='cardNumber' class='col-sm-4 col-form-label'>Card Number</label>
                                <div class='col-sm-8'>
                                    <input type='text' class='form-control' name='cardNumber' id='cardNumber' placeholder='XXXX XXXX XXXX XXXX' 
                                           data-mask='0000 0000 0000 0000'>
                                </div>
                            </div>
                
                            <div class='form-group row'>
                                <label for='amount' class='col-sm-4 col-form-label'>Amount</label>
                                <div class='col-sm-8 input-group'>
                                    <input type='text' class='col-7 form-control' name='amount' id='amount' placeholder='0.00' data-mask='#0.00'
                                           data-mask-reverse='true'>
                                    <select class='col-5 form-control' name='currency' id='currency' style='height:auto'>";
            global $wpdb;
            $data = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix.'cbtc_bitcoin_convert_currency', ARRAY_A);
            foreach ($data as $currency){
                $code = $currency['currency'];
                echo "<option value='$code'>$code</option>";
            }
            echo "</select>
                                </div>
                            </div>
                            
                            <input type=\"hidden\" name=\"action\" value=\"cbtc_convert_form\">
                
                            <button
                                    type='submit'
                                    class='btn btn-primary btn-block g-recaptcha'
                                    data-sitekey='6LeE-U4UAAAAAPeCLQz2kNRW_iRYAH9Es4fWJxN8'
                                    data-callback='onSubmit'>
                                <strong>Top Up</strong>
                            </button>
                        </form>
                    </div>
                </div>
                </div> ";
        }

    }

    function cbtc_convert_form(){

    }