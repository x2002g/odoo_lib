<?php
include './OdooHelper.class.php';

function test_connect() {
    $helper = new OdooHelper();
    $id     = $helper->connect();
    var_dump($id);
}
//test_connect();

function test_get_qty() {
    $helper = new OdooHelper();
    $ids    = 25;
    $ids    = array(25);
    $qtys   = $helper->get_qty($ids);
    var_dump($qtys);
}
//test_get_qty();

function test_get_product_data_by_ids() {
    $helper = new OdooHelper();
    $ids    = array(1, 2, 25);
    // specified fields
    //$fields = array('name', 'code', 'product_brand_id', 'list_price', 'image', 'image_medium', 'image_small', 'description', 'weight', 'qty_available', 'sale_ok');
    $fields = array('name', 'code', 'product_brand_id', 'list_price', 'description', 'weight', 'qty_available', 'sale_ok');
    $result = $helper->get_product_data_by_ids($ids, $fields);
    // all fields
    //$result = $helper->get_product_data_by_ids($ids);
    var_dump($result);
}
//test_get_product_data_by_ids();

function test_gen_datafeed() {
    $helper = new OdooHelper();
    $path   = './data.csv';
    $result = $helper->gen_datafeed($path);
    var_dump($result);
}
//test_gen_datafeed();

function test_create_order() {
    $helper = new OdooHelper();
    $result = $helper->create_order();
    var_dump($result);
}
//test_create_order();

