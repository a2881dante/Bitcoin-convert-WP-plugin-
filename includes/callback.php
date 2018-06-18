<?php

    if($_GET['token']) {

        require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');
        require('coingate/init.php');

        $time = time();
        $token = $_GET['token'];

        global $wpdb;
        $table_name = $wpdb->prefix."cbtc_bitcoin_convert";
        $data = $wpdb->get_row("SELECT * FROM $table_name WHERE token='$token'", ARRAY_A);
        $wpdb->update(
            $wpdb->prefix.'cbtc_bitcoin_convert',
            array(
                "payDate" => $time
            ),
            array(
                "token" => $token
            )
        );

        if(get_option("bctc_data_lifetime") == 0){
            $wpdb->delete( $wpdb->prefix.'cbtc_bitcoin_convert', array( 'token' => $token ) );
        }

        if($data != null){

            $paymentResult = json_decode($data['paymentResult'], true);
            $mailText = getMailText(getStatus($paymentResult['id']), $data);
            $headers  = 'MIME-Version: 1.0' . "\r\n";
            $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
            wp_mail(get_option('bctc_email'), 'Convert Bitcoins', $mailText, $headers, '');

        }

    }

    function getStatus($id){

        \CoinGate\CoinGate::config(array(
            'environment' => get_option('bctc_q_type'), // sandbox OR live
            'app_id'      => get_option('bctc_app_id'),
            'api_key'     => get_option('bctc_api_key'),
            'api_secret'  => get_option('bctc_api_secret')
        ));
        try {
            $order = \CoinGate\Merchant\Order::find(intval($id));
            if ($order) {
                return $order;
            }
            else {
                echo 'Order not found';
                return false;
            }
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }

    function getMailText($statusResult, $row){
        $card = $row['cardNumber'];
        $str = "<html><body><h1 style='text-align: center'>Bitcoin convert</h1>
            <table style=\"font-size: medium; width: 100%; margin: 20px\">
                <tr>
                    <td>Order id</td>
                    <td><b>$statusResult->order_id</b></td>
                </tr>
                <tr>
                    <td>Card number</td>
                    <td><b>$card</b></td>
                </tr>
                <tr>
                    <td>Amount</td>
                    <td><b>$statusResult->price $statusResult->currency</b></td>
                </tr>
                <tr>
                    <td>Status</td>
                    <td><b>$statusResult->status</b></td>
                </tr>
                <tr>
                    <td>Date</td>
                    <td><b>$statusResult->expire_at</b></td>
                </tr>
                <tr>
                    <td>Bitcoin uri</td>
                    <td><b>$statusResult->bitcoin_uri</b></td>
                </tr>
            
            </table>
                </body></html>";
        return $str;

    }

?>


