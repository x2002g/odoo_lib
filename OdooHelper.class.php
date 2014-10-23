<?php
set_include_path(get_include_path() . PATH_SEPARATOR . './lib/phpxmlrpc/lib/');
require_once( 'xmlrpc.inc' );
require_once( 'xmlrpcs.inc' );
require_once( 'xmlrpc_wrappers.inc' );

class OdooHelper {
    public function __construct() {
        $this->user     = 'admin';
        $this->password = 'u3go';
        $this->dbname   = 'u3go_test';
        $this->server_url = 'http://odoo.u3go.biz/xmlrpc/';
        $this->uid      = 1;

        $this->image_dir    = './img';
        if (!file_exists($this->image_dir)) {
            mkdir($this->image_dir);
            chmod($this->image_dir, 0755);
        }

        # socket for object
        $server_url = $this->server_url.'object';
        $this->sock   = new xmlrpc_client($server_url);
    }

    public function connect() {
        $server_url = $this->server_url.'common';

        $msg = new xmlrpcmsg('login');
        $msg->addParam(new xmlrpcval($this->dbname, "string"));
        $msg->addParam(new xmlrpcval($this->user, "string"));
        $msg->addParam(new xmlrpcval($this->password, "string"));

        $sock = new xmlrpc_client($server_url);
        $resp =  $sock->send($msg);
        $val = $resp->value();
        $id = $val->scalarval();
        
        return ($id > 0) ? $id : -1;
    }

    /**
     * @param   mixed   $ids    An ID or an array of product ids.
     */
    public function get_qty($ids) {
        if (!is_array($ids))
            $ids    = array(intval($ids));

        $msg = new xmlrpcmsg('execute');
        $msg->addParam(new xmlrpcval($this->dbname, "string"));
        $msg->addParam(new xmlrpcval($this->uid, "int"));
        $msg->addParam(new xmlrpcval($this->password, "string"));

        $msg->addParam(new xmlrpcval('product.product', "string"));
        $msg->addParam(new xmlrpcval('get_product_available_rpc', "string"));
        $msg->addParam($this->_format($ids));

        $context= array(
            'lang'      => 'zh_CN',
            'bin_size'  => true,
            'states'    => array('confirmed', 'waiting', 'assigned', 'done'),
            'tz'        => 'Asiz/Shanghai',
            'uid'       => $this->uid,
            'what'      => array('in', 'out'),
        );
        $msg->addParam($this->_format($context));

        $resp   = $this->sock->send($msg);
        $val    = $resp->value();
        #$result = $val->scalarval();
        #return $result;
        return $this->_parse($val);
    }

    public function create_order() {
        $context = array(
            'lang'  => 'zh_CN.utf8',
            'tz'    => 'Asia/Shanghai',
            'uid'   => $this->uid
        );
        $products = array();
        $products[] = array(0, false, array(
            'delay'         => 7,
            'discount'      => 0,
            'name'          => '[CARD] 显卡1',
            'price_unit'    => 886,
            'product_id'    => 25,
            'product_packaging' => false,
            'product_uom'   => 1,
            'product_uom_qty'   => 2,   // qty of products
            'product_uos'   => false,
            'product_uos_qty'   => 2,   // qty of products
            'tax_id'        => array(array(6, false, array())),
            'th_weight'     => 0,
            'type'          => 'make_to_stock'
        ));
        $order  = array(
            'date_order'        => date('Y-m-d'),
            'invoice_quantity'  => 'order',
            'order_policy'      => 'manual',
            'partner_id'        => 6,           // partner ID
            'partner_invoice_id'=> 6,
            'partner_shipping_id'   => 6,
            'payment_term'      => 3,
            'picking_policy'    => 'direct',
            'pricelist_id'      => 1,
            'shop_id'           => 1,
            'user_id'           => 1,
            'order_line'        => $products,

            'client_order_ref'  => 'E00001',    // client order ID

            'fiscal_position'   => false,
            'incoterm'          => false,
            'message_follower_ids'  => false,
            'message_ids'       => false,
            'note'              => false,
            'origin'            => false,
            'project_id'        => false,
        );

        # generate message
        $msg = new xmlrpcmsg('execute');
        $msg->addParam(new xmlrpcval($this->dbname, "string"));
        $msg->addParam(new xmlrpcval($this->uid, "int"));
        $msg->addParam(new xmlrpcval($this->password, "string"));

        $msg->addParam(new xmlrpcval('sale.order', "string"));
        $msg->addParam(new xmlrpcval('create', "string"));
        $msg->addParam($this->_format($order));
        $msg->addParam($this->_format($context));

        $resp   = $this->sock->send($msg);
        $val    = $resp->value();
        $data   = $this->_parse($val);
        return $data;
    }

    /**
     * Get product data by product ids in OpenERP
     * IDs are usually numbers like 1, 2, 3...
     */
    public function get_product_data_by_ids($ids, $fields = null) {
        # if $ids is integer, transfer to Array
        if (!is_array($ids)) {
            $ids    = array(intval($ids));
        }

        $msg = new xmlrpcmsg('execute');
        $msg->addParam(new xmlrpcval($this->dbname, "string"));
        $msg->addParam(new xmlrpcval($this->uid, "int"));
        $msg->addParam(new xmlrpcval($this->password, "string"));

        $msg->addParam(new xmlrpcval('product.product', "string"));
        $msg->addParam(new xmlrpcval('read', "string"));

        # product ids
        //$ids    = array(25);
        $msg->addParam($this->_format($ids));

        # fields
        if (empty($fields)) {
            $msg->addParam(new xmlrpcval(null, "null"));
        } else {
            //$fields = array('name', 'code', 'product_brand_id', 'list_price', 'image', 'image_medium', 'image_small', 'description', 'weight', 'qty_available', 'sale_ok');
            //$fields = array('warranty', 'ean13', 'supply_method', 'uos_id', 'list_price', 'seller_info_id', 'weight', 'track_production', 'color', 'incoming_qty', 'image', 'standard_price', 'cost_method', 'price_extra', 'mes_type', 'uom_id', 'code', 'description_purchase', 'default_code', 'name_template', 'property_account_income', 'lst_price', 'price', 'location_id', 'id', 'message_summary', 'uos_coeff', 'delivery_count', 'procure_method', 'sale_ok', 'message_follower_ids', 'qty_available', 'categ_id', 'product_manager', 'property_stock_account_output', 'track_outgoing', 'company_id', 'message_ids', 'product_tmpl_id', 'state', 'message_is_follower', 'loc_rack', 'uom_po_id', 'pricelist_id', 'price_margin', 'property_stock_account_input', 'virtual_available', 'description', 'reception_count', 'track_incoming', 'property_stock_production', 'seller_qty', 'supplier_taxes_id', 'volume', 'sale_delay', 'loc_row', 'seller_delay', 'warehouse_id', 'description_sale', 'active', 'property_stock_inventory', 'variants', 'partner_ref', 'valuation', 'loc_case', 'weight_net', 'packaging', 'seller_id', 'name', 'type', 'property_account_expense', 'orderpoint_ids', 'property_stock_procurement', 'outgoing_qty', 'taxes_id', 'produce_delay', 'seller_ids', 'rental', 'message_unread');
            $msg->addParam($this->_format($fields));
        }

        $context= array(
            'lang'      => 'zh_CN',
            'bin_size'  => true,
            'tz'        => 'Asiz/Shanghai',
            'uid'       => $this->uid,
        );
        $msg->addParam($this->_format($context));

        $resp   = $this->sock->send($msg);
        $val    = $resp->value();
        $data   = $this->_parse($val);
        return $data;
    }

    public function gen_datafeed($path) {
        $data   = array();
        $start  = 1;
        $limit  = 50;

         $fields = array('name', 'code', 'product_brand_id', 'list_price', 'image', 'image_medium', 'image_small', 'description', 'weight', 'qty_available', 'sale_ok');

        $count  = 0;
        $file   = fopen($path, 'wb');
        fputcsv($file, array('Name','NO.','Brand','Market price','Shop price','Points limit for buying','Original picture','Picture','Thumbnail','Keywords','Brief','Details','Weight(kg)','Stock quantity','Stock warning quantity','Best','New','Hot','On sale','Can be a common product sale?','Entity'));
        while (true) {
            $ids    = array();
            for ($i = 0; $i < $limit; $i++) {
                $ids[]  = $start + $i;
            }
            $new_data   = $this->get_product_data_by_ids($ids, $fields);
            if (count($new_data) == 0) break;
            //var_export($new_data[0]);
            //exit;

            //$data   = array_merge($data, $new_data);
            foreach ($new_data as $item) {
                // images
                $code   = str_replace('/', '-', $item['code']);
                $img_path       = $this->image_dir.'/'.$code.'.png';
                file_put_contents($img_path, base64_decode($item['image_medium']));
                $img_path_md    = $this->image_dir.'/'.$code.'_md.png';
                file_put_contents($img_path_md, base64_decode($item['image_medium']));
                $img_path_sm    = $this->image_dir.'/'.$code.'_sm.png';
                file_put_contents($img_path_sm, base64_decode($item['image_small']));

                $brand  = !empty($item['product_brand_id'][1]) ? $item['product_brand_id'][1]: '';

                $row    = array(
                    $item['name'],
                    $item['code'],  // 商品货号
                    $brand,         // 商品品牌
                    $item['list_price'], // 市场售价
                    $item['list_price'], // 本店售价
                    '',// 积分购买额度
                    $img_path,      // 商品原图
                    $img_path_md,   // 商品图片
                    $img_path_sm,   // 商品缩略图
                    '', // 商品关键词
                    $item['description'], // 简单描述
                    $item['description'], // 详细描述
                    floatval($item['weight']), // 商品重量
                    intval($item['qty_available']), // 库存数量
                    0, // 库存警告数量
                    1, // 是否精品
                    1, // 是否新品
                    0, // 是否热销
                    intval($item['sale_ok']), // 是否上架
                    1, // 能否作为普通商品销售
                    1  // 是否实体商品
                );
                fputcsv($file, $row);
                $count++;
            }

            $start  += $limit;
        }
        fclose($file);
        //return $data;
        return $count;
    }

    //----------------------------------------------------------------
    // private methods
    
    private function _format($val) {
        if (is_bool($val)) {
            return new xmlrpcval($val, 'boolean');
        } elseif (is_int($val)) {
            return new xmlrpcval($val, 'int');
        } elseif (is_array($val)) {
            $val2   = array();
            foreach ($val as $k => $v) {
                $val2[$k]   = $this->_format($v);
            }
            $type   = isset($val[0]) ? 'array' : 'struct';
            return new xmlrpcval($val2, $type);
        } else {
            // default as string
            return new xmlrpcval($val, 'string');
        }
    }

    private function _parse($val) {
        if (is_array($val)) {
            foreach ($val as $k => $v) {
                $val[$k]    = $this->_parse($v);
            }
            return $val;
        } elseif (!($val instanceof xmlrpcval)) {
            return $val;
        } elseif ($val->kindof() == 'array' || $val->kindof() == 'struct') {
            $val2   = $val->scalarval();
            foreach ($val2 as $k => $v) {
                $val2[$k]   = $this->_parse($v);
            }
            return $val2;
        } elseif ($val->kindof() == 'scalar') {
            return $val->scalarval();
        }
    }
}

