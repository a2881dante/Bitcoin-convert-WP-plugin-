<?php

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class ConvertBitcoinTable extends WP_List_Table
{

    public $data;
    private $found_data;

    function __construct(){

        parent::__construct(array(
            'singular' => 'order',
            'plural'   => 'orders',
            'ajax'     => false,
        ));

        add_action('admin_head', array( &$this, 'admin_header' ) );

    }

    function admin_header() {
        $page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
        if( 'bitcoin-convert' != $page )
            return;
        echo '<style type="text/css">';
        echo '.wp-list-table .column-token{ width: 24%; }';
        echo '.wp-list-table .column-cardNumber{ width: 16%; }';
        echo '.wp-list-table .column-amount{ width: 12%; }';
        echo '.wp-list-table .column-currency{ width: 8%; }';
        echo '.wp-list-table .column-submitDate{ width: 16%; }';
        echo '.wp-list-table .column-payDate{ width: 16%; }';
        echo '.wp-list-table .column-status{ width: 8%; }';
        echo '</style>';
    }

    function no_items() {
        _e( 'List of operations is empty!' );
    }

    function get_columns(){

        $columns = array(
            'cb'            => '<input type="checkbox" />',
            'token'         => 'Token',
            'cardNumber'    => 'Card number',
            'amount'        => 'Amount',
            'currency'      => 'Currency',
            'submitDate'    => 'Submit date',
            'payDate'       => 'Pay date',
            'status'        => 'Status'
        );
        return $columns;

    }

    function prepare_items() {

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        $per_page = 5;
        $current_page = $this->get_pagenum();
        $total_items = count($this->data);
        $this->found_data = array_slice($this->data,(($current_page-1)*$per_page),$per_page);
        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page
        ) );

        usort( $this->found_data, array( &$this, 'usort_reorder' ) );
        $this->items = $this->found_data;
    }

    function column_default( $item, $column_name ) {

        switch( $column_name ) {
            case 'token':
            case 'cardNumber':
            case 'amount':
            case 'currency':
                return $item[ $column_name ];
            case 'submitDate':
            case 'payDate':
                return date('Y-m-d H:i:s', $item[ $column_name ]);
            case 'status':
                return $this->getStatus((json_decode($item['paymentResult'], true))['id'])->status;
            default:
                return print_r( $item, true );
        }

    }

    function get_sortable_columns(){
        $sortable_columns = array(
            'token'         => array('token',false),
            'cardNumber'    => array('cardNumber',false),
            'amount'        => array('amount',false),
            'currency'      => array('currency',false),
            'submitDate'    => array('submitDate',false),
            'payDate'       => array('payDate',false),
            'status'        => array('status',false)

        );
        return $sortable_columns;
    }

    function column_token($item) {
        $actions = array(
            'delete'    => sprintf('<a href="?page=%s&action=%s&token=%s">Delete
                                        </a>',$_REQUEST['page'], 'delete',$item['token']),
        );
        return sprintf('%1$s %2$s', $item['token'], $this->row_actions($actions) );
    }

    function get_bulk_actions() {
        $actions = array(
            'delete'    => 'Delete'
        );
        return $actions;
    }

    function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="token[]" value="%s" />', $item['token']
        );
    }

    function usort_reorder( $a, $b ) {
        $orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'submitDate';
        $order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';
        $result = strcmp( $a[$orderby], $b[$orderby] );
        return ( $order === 'asc' ) ? $result : -$result;
    }

    function getStatus($id){

        require_once('coingate/init.php');
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

}