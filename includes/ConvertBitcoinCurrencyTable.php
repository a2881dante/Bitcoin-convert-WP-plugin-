<?php

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class ConvertBitcoinCurrencyTable extends WP_List_Table
{

public $data;

function __construct(){

parent::__construct(array(
'singular' => 'currency',
'plural'   => 'currencies',
'ajax'     => false,
));

add_action('admin_head', array( &$this, 'admin_header' ) );

}

function admin_header() {
$page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
if( 'cbtc_bitcoin_convert_setting' != $page )
return;
echo '<style type="text/css">';
    echo '.wp-list-table .column-currencyName{ width: 40%; }';
    echo '.wp-list-table .column-currencyCode{ width: 20%; }';
    echo '.wp-list-table .column-fixedFee{ width: 20%; }';
    echo '.wp-list-table .column-percentageFee{ width: 20%; }';
    echo '</style>';
}

function get_columns(){

$columns = array(
'cb'            => '<input type="checkbox" />',
'currencyName'  => 'Currency',
'currencyCode'  => 'Currency code',
'fixedFee'      => 'Fixed fee',
'percentageFee' => 'Percentage fee',
);
return $columns;

}

function prepare_items() {

$columns = $this->get_columns();
$hidden = array();
$sortable = $this->get_sortable_columns();
$this->_column_headers = array($columns, $hidden, $sortable);
usort( $this->data, array( &$this, 'usort_reorder' ) );
$this->items = $this->data;

}

function column_default( $item, $column_name ) {

switch( $column_name ) {
case 'currencyName':
return get_iso_4217_array()[$item['currency']][0];
case 'currencyCode':
return $item['currency'];
case 'fixedFee':
return $item[ $column_name ]." ".$item['currency'];
case 'percentageFee':
return $item[ $column_name ]."%";
default:
return print_r( $item, true );
}

}

function get_sortable_columns(){
$sortable_columns = array(
'currencyName'  => array('currencyName',false),
'currencyCode'  => array('currencyCode',false),
'fixedFee'      => array('fixedFee',false),
'percentageFee' => array('percentageFee',false),
);
return $sortable_columns;
}

function column_currencyName($item) {
$actions = array(
'delete'    => sprintf('<a href="?page=%s&action=%s&currency=%s">Delete
</a>',$_REQUEST['page'], 'delete',$item['currency']),
'edit'    => sprintf('<a href="?page=%s&action=%s&currency=%s">Edit
</a>',$_REQUEST['page'], 'edit',$item['currency']),
);
return sprintf('%1$s %2$s', get_iso_4217_array()[$item['currency']][0], $this->row_actions($actions) );
}

function get_bulk_actions() {
$actions = array(
'delete'    => 'Delete'
);
return $actions;
}

function column_cb($item) {
return sprintf(
'<input type="checkbox" name="currency[]" value="%s" />', $item['currency']
);
}

function usort_reorder( $a, $b ) {
$orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'currencyName';
$order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';
$result = strcmp( $a[$orderby], $b[$orderby] );
return ( $order === 'asc' ) ? $result : -$result;
}

}