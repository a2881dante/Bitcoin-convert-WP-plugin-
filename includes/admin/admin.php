<?php

    add_action( 'admin_menu', 'action_create_admin_menu' );

    function action_create_admin_menu(){

        $page_title = 'List of operation';
        $menu_title = 'Bitcoin Convert';
        $capability = 'manage_options';
        $menu_slag = 'bitcoin-convert';
        add_menu_page($page_title, $menu_title, 8, $menu_slag
            , 'bctc_bitcoin_convert_setting', plugin_dir_url( __FILE__ ).'admin-icon.png', 27.333);
        add_submenu_page($menu_slag, 'Setting', 'Setting', 8, __FILE__, 'bctc_bitcoin_convert_list');

    }

    function bctc_bitcoin_convert_list(){

    }


    function bctc_bitcoin_convert_setting(){
        ?>
        <div class="wrap">
            <h2><?php echo get_admin_page_title() ?></h2>

            <form action="admin.php" method="POST">
                <?php
                settings_fields("opt_group");
                do_settings_sections("opt_page"); ?>

                    <br><br>
                    APP ID      <br><input type="text" name="bctc_app_id" placeholder="0000" size="4"
                                           value="<?php if(get_option('bctc_app_id')){echo get_option('bctc_app_id');} ?>">
                    <br><br>
                    API Key     <br><input type="text" name="bctc_api_key" placeholder="API Key" size="25"
                                           value="<?php if(get_option('bctc_api_key')){echo get_option('bctc_api_key');} ?>">
                    <br><br>
                    API Secret  <br><input type="text" name="bctc_api_secret" placeholder="API Secret" size="50"
                                           value="<?php if(get_option('bctc_api_secret')){echo get_option('bctc_api_secret');} ?>">
                    <br><br>
                    email       <br><input type="email" name="bctc_email" placeholder="email@email.com" size="50"
                                           value="<?php if(get_option('bctc_email')){echo get_option('bctc_email');} ?>">

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

get_option('bctc_app_id');
get_option('bctc_api_key');
get_option('bctc_api_secret');
get_option('bctc_email');
