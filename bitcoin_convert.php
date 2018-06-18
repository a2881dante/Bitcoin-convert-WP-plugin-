<?php
/*
Plugin Name: Bitcoin convert to currency
Description: The plugin adds a page for converting bitcoins into another currency. The plugin works with the CoinGate system, processes the callback, sends out email based on payment results.
Version: 1.2
Author: Europe Smart Solutions LTD
Author URI: https://www.europe-smart-solutions.co.uk
*/

    ob_clean(); ob_start();

    define ("PLUGIN_PREFIX", "cbtc_");
    define ("DIR_ASSETS", "assets/");
    define ("DIR_INCLUDES", "includes/");

    require_once ( $_SERVER['DOCUMENT_ROOT'] . '/wp-load.php' );
    require_once ( DIR_INCLUDES."setting.php");
    require_once ( DIR_INCLUDES."iso_4217.php");
    require_once ( DIR_INCLUDES."ConvertBitcoinTable.php");
    require_once ( DIR_INCLUDES."ConvertBitcoinCurrencyTable.php");
    //require_once("includes/admin.php");

    register_activation_hook( __FILE__ , PLUGIN_PREFIX.'on_activate');
    register_deactivation_hook( __FILE__, PLUGIN_PREFIX.'on_deactivate');
    register_uninstall_hook( __FILE__ , PLUGIN_PREFIX.'on_uninstall');

    add_action( 'the_content', PLUGIN_PREFIX.'action_function_convert_page' );
    add_action( 'wp_enqueue_scripts', PLUGIN_PREFIX.'script_style_init' );
    add_action( 'admin_post_nopriv_cbtc_convert_form', PLUGIN_PREFIX.'convert_form' );
    add_action( 'admin_post_cbtc_convert_form', PLUGIN_PREFIX.'convert_form' );

    add_action( 'admin_menu', PLUGIN_PREFIX.'action_create_admin_menu' );

    function cbtc_action_create_admin_menu(){

        $page_title = 'List of operation';
        $menu_title = 'Bitcoin Convert';
        $menu_slag = 'bitcoin-convert';
        $hookMain = add_menu_page($page_title, $menu_title, 8, $menu_slag
            , 'cbtc_bitcoin_convert_list', plugin_dir_url( __FILE__ ).'plugin-icon.png', 27.333);
        $hookSetting = add_submenu_page($menu_slag, 'Setting', 'Setting', 8, __FILE__, PLUGIN_PREFIX.'bitcoin_convert_setting');

        add_action( "load-$hookMain", PLUGIN_PREFIX.'bitcoin_convert_add_options' );

    }

    function cbtc_bitcoin_convert_add_options() {
        global $myListTable;
        $option = 'per_page';
        $args = array(
            'label' => 'Orders',
            'default' => 10,
            'option' => 'orders_per_page'
        );
        add_screen_option( $option, $args );
        $myListTable = new ConvertBitcoinTable();
    }

    function cbtc_bitcoin_convert_list(){

        global $wpdb;

        $wpdb->query( 
            $wpdb->prepare( 
                "DELETE FROM ".$wpdb->prefix."cbtc_bitcoin_convert
                 WHERE submitDate < %d
                ",
                    time() - get_option("bctc_data_lifetime")
                )
        );

        $wpdb->delete( $wpdb->prefix.'cbtc_bitcoin_convert', array( 'token' => $token ) );

        if(isset($_GET['action'])){
            if($_GET['action'] == 'delete'){
                $wpdb->delete($wpdb->prefix.'cbtc_bitcoin_convert', array('token' => $_GET['token']));
            }
        }

        echo '<div class="wrap"><h2>Bitcoin Convert operation</h2>';

        $data = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix.'cbtc_bitcoin_convert', ARRAY_A);
        $listTable = new ConvertBitcoinTable();
        $listTable->data = $data;
        $listTable->prepare_items();
        ?><form method="post">
            <input type="hidden" namse="page" value="ttest_list_table">
            <?php
            $listTable->search_box( 'search', 'search_id' );
            $listTable->display();
        echo '</form></div>';

    }


    function cbtc_bitcoin_convert_setting(){

        global $wpdb;

        if(isset($_GET['action'])){
            if($_GET['action'] == 'delete'){
                $wpdb->delete($wpdb->prefix.'cbtc_bitcoin_convert_currency', array('currency' => $_GET['currency']));
            }
        }

        if(isset($_POST['edit_cancel'])){
            $page_url = remove_query_arg(
                array( 'action', 'currency' )
            );
            echo "<script>document.location.href = '$page_url';</script>";
        }

        if(isset($_POST["submit_api_setting"])){
            update_option("bctc_save_api_keys", 0);
            if(isset($_POST["bctc_q_type"])){
                update_option("bctc_q_type", $_POST['bctc_q_type']);
            }
            if(isset($_POST["bctc_app_id"])){
                update_option("bctc_app_id", $_POST['bctc_app_id']);
            }
            if(isset($_POST["bctc_api_key"])){
                update_option("bctc_api_key", $_POST['bctc_api_key']);
            }
            if(isset($_POST["bctc_api_secret"])){
                update_option("bctc_api_secret", $_POST['bctc_api_secret']);
            }
            if(isset($_POST["bctc_save_api_keys"])){
                if($_POST["bctc_save_api_keys"] == "save"){
                    update_option("bctc_save_api_keys", 1);
                }
            }
        }

        if(isset($_POST['add_new_currency'])){
            $fixedFee = 0;
            $percentageFee = 0;
            $currency = "";
            if(isset($_POST['currency'])){
                $currency=$_POST['currency'];
            }
            if(isset($_POST['fixed_fee'])){
                $fixedFee = $_POST['fixed_fee'];
            }
            if(isset($_POST['pecentage_fee'])){
                $percentageFee = $_POST['percentage_fee'];
            }
            $wpdb->insert($wpdb->prefix.'cbtc_bitcoin_convert_currency'
                , array(
                    'currency'      => $currency,
                    'fixedFee'      => $fixedFee,
                    'percentageFee' => $percentageFee
                )
            );
        }
        if(isset($_POST['edit_currency'])){
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix.'cbtc_bitcoin_convert_currency',
                array(
                    'currency'      => $_POST['currency'],
                    'fixedFee'      => $_POST['fixed_fee'],
                    'percentageFee' => $_POST['percentage_fee']
                ),
                array(
                    'currency' => $_POST['old_currency']
                )
            );
            $page_url = remove_query_arg(
                array( 'action', 'currency' )
            );
            echo "<script>document.location.href = '$page_url';</script>";
        }

        if(isset($_POST["submit_email_setting"])){
            if(isset($_POST["bctc_email"])){
                update_option("bctc_email", $_POST['bctc_email']);
            }
        }

        if(isset($_POST["submit_data_setting"])){
            update_option("bctc_save_operations_data", 0);
            if(isset($_POST["bctc_lifetime"])){
                update_option("bctc_data_lifetime", $_POST['bctc_lifetime']);
            }
            if(isset($_POST["bctc_save_operations_data"])){
                if($_POST["bctc_save_operations_data"] == "save"){
                    update_option("bctc_save_operations_data", 1);
                }
            }
        }

        ?>

        <div class="wrap">
            <h2><?php echo get_admin_page_title() ?></h2>

            <h3>API settings</h3>
                <form method="post">
                    <table cellspacing="12">
                        <tr>
                            <td>Environment (Live/Sandbox)</td>
                            <td>
                                <select name="bctc_q_type">
                                    <option value="live"
                                        <?php if(get_option('bctc_q_type')=="live") echo "selected"?>>Live</option>
                                    <option value="sandbox"
                                        <?php if(get_option('bctc_q_type')=="sandbox") echo "selected"?>>Sandbox</option>
                                </select>
                                <br><i>Sandbox environment, used only for testing CoinGate services
                                        – all invoices and payments created in this sandbox environment
                                    use testnet bitcoins.</i>
                            </td>
                        </tr>
                        <tr>
                            <td>APP ID</td>
                            <td><input type="text" name="bctc_app_id" placeholder="0000" size="4"
                                       value="<?php if(get_option('bctc_app_id')){echo get_option('bctc_app_id');} ?>">
                                <br><i>For each application you must register your own set of keys.</i>
                            </td>
                        </tr>
                        <tr>
                            <td>API Key</td>
                            <td><input type="text" name="bctc_api_key" placeholder="API Key" size="25"
                                       value="<?php if(get_option('bctc_api_key')){echo get_option('bctc_api_key');} ?>">
                                <br><i>You can generate your API Key in account area. From main account navigation
                                    go to Apps and create new app.</i>
                            </td>
                        </tr>
                        <tr>
                            <td>API Secret</td>
                            <td><input type="text" name="bctc_api_secret" placeholder="API Secret" size="50"
                                       value="<?php if(get_option('bctc_api_secret')){echo get_option('bctc_api_secret');} ?>">
                                <br><i>Each app has its own secret key.</i>
                            </td>
                        </tr>
                        <tr>
                            <td>Data save</td>
                            <td><input type="checkbox" name="bctc_save_api_keys" value="save" 
                                    <?php if(get_option('bctc_save_api_keys') == 1){echo "checked";} ?>>
                                <br><i>Would you like to save API`s keys in the system after removing the plugin? Data can not be viewed, but when reinstalling the plugin, the data on operations will be restored.</i>
                            </td>
                        </tr>

                    </table>
                <br><input type="submit" name="submit_api_setting" value="Save API Setting">
            </form>

            <br><hr>

            <h3>Currencies and fee settings</h3>
                <br>
                <form method="post">
                    <?php if(isset($_GET['action']) AND $_GET['action'] == 'edit'){
                        echo "<input type='hidden' name='old_currency' value='".$_GET['currency']."'>";
                    }?>
                    Currency:
                    <select name="currency">
                        <?php
                            $currencies = get_iso_4217_array();
                            foreach ($currencies as $key => $value){
                                echo "<option value='$key'";
                                if(isset($_GET['action']) AND $_GET['action'] == 'edit'){
                                    if(isset($_GET['currency'])){
                                        if($_GET['currency'] == $key){
                                            echo " selected";
                                        }
                                    }
                                }
                                echo">$value[0] ($key)</option>>";
                            }
                        ?>
                    </select>
                    Fixed fee:
                    <input type="number" name="fixed_fee" placeholder="0.00"
                        <?php if(isset($_GET['action']) AND $_GET['action'] == 'edit'){
                            $table_name = $wpdb->prefix."cbtc_bitcoin_convert_currency";
                            $data = $wpdb->get_row("SELECT * FROM $table_name WHERE currency='".$_GET['currency']."'", ARRAY_A);
                            echo " value='".$data['fixedFee']."'";
                        } ?>>
                    Percentage fee:
                    <input type="number" name="percentage_fee" placeholder="0%" min="0" max="100"
                        <?php if(isset($_GET['action']) AND $_GET['action'] == 'edit'){
                            $table_name = $wpdb->prefix."cbtc_bitcoin_convert_currency";
                            $data = $wpdb->get_row("SELECT * FROM $table_name WHERE currency='".$_GET['currency']."'", ARRAY_A);
                            echo " value='".$data['percentageFee']."'";
                        } ?>>
                        <?php if(isset($_GET['action']) AND $_GET['action'] == 'edit'){
                            echo "<input type='submit' name='edit_currency' value='Edit currency'>";
                            echo "<input type='submit' name='edit_cancel' value='Cancel'>";
                        }else{
                            echo "<input type='submit' name='add_new_currency' value='Add new currency'>";
                        }  ?>
                </form>
                <?php

                    global $wpdb;
                    $data = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix.'cbtc_bitcoin_convert_currency', ARRAY_A);
                    $listTable = new ConvertBitcoinCurrencyTable();
                    $listTable->data = $data;
                    $listTable->prepare_items();
                    $listTable->display();

                ?>

            <br><hr>

            <h3>e-mail settings</h3>

            <form method="post">
                <table cellspacing="12">
                    <tr>
                      <td>email</td>
                      <td><input type="email" name="bctc_email" placeholder="email@email.com" size="50"
                                 value="<?php if(get_option('bctc_email')){echo get_option('bctc_email');} ?>">
                        <br><i>Mail which will receive messages about the operations.</i>
                      </td>
                    </tr>
                </table>
                <br><input type="submit" name="submit_email_setting" value="Save e-mail Setting"><br>
            </form>

            <h3>Data settings</h3>

            <form method="post">
                <table cellspacing="12">
                    <tr>
                      <td>Data Lifetime</td>
                      <td>
                        <select name="bctc_lifetime">
                            <option value="<?php echo second_time(0); ?>" 
                                <?php if(get_option('bctc_data_lifetime') == second_time(0)){echo "selected=''";} ?>>Delete after accept</option>
                            <option value="<?php echo second_time(1); ?>" 
                                <?php if(get_option('bctc_data_lifetime') == second_time(1)){echo "selected=''";} ?>>1 day</option>
                            <option value="<?php echo second_time(3); ?>" 
                                <?php if(get_option('bctc_data_lifetime') == second_time(3)){echo "selected=''";} ?>>3 days</option>
                            <option value="<?php echo second_time(7); ?>" 
                                <?php if(get_option('bctc_data_lifetime') == second_time(7)){echo "selected=''";} ?>>1 week</option>
                            <option value="<?php echo second_time(30); ?>" 
                                <?php if(get_option('bctc_data_lifetime') == second_time(30)){echo "selected=''";} ?>>1 month</option>
                            <option value="<?php echo second_time(90); ?>" 
                                <?php if(get_option('bctc_data_lifetime') == second_time(90)){echo "selected=''";} ?>>3 month</option>
                            <option value="<?php echo second_time(180); ?>" 
                                <?php if(get_option('bctc_data_lifetime') == second_time(180)){echo "selected=''";} ?>>6 month</option>
                            <option value="<?php echo second_time(365); ?>" 
                                <?php if(get_option('bctc_data_lifetime') == second_time(365)){echo "selected=''";} ?>>1 year</option>
                        </select>
                        <br><i>How long will the data live last after confirming the operation? <br>Note that the presence of more than 10,000 entries can significantly reduce the speed of working with the table in the admin panel!</i>
                      </td>
                    </tr>
                    <tr>
                      <td>Data save</td>
                      <td><input type="checkbox" name="bctc_save_operations_data" value="save" 
                                    <?php if(get_option('bctc_save_operations_data') == 1){echo "checked";} ?>>
                        <br><i>Would you like to save the data in the system after removing the plugin? Data can not be viewed, but when reinstalling the plugin, the data on operations will be restored.</i>
                      </td>
                    </tr>
                </table>
                <br><input type="submit" name="submit_data_setting" value="Save data settings"><br>
            </form>


        </div>
        <?php
    }

    if( ! class_exists( 'WP_List_Table' ) ) {
        require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
    }