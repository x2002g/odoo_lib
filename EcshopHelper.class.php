<?php
include_once './lib/Db.php';
include_once './OdooHelper.class.php';

class EcshopHelper{
    public function __construct() {
        $dsn    = 'mysql:dbname=ecshop;host=127.0.0.1;charset=UTF8';
        $user   = 'ecshop';
        $pass   = 'ecshop';
        $this->db   = new Db($dsn, $user, $pass, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "UTF8"'));
    }

    public function add_brand($brand_name) {
        $table  = 'ecs_brand';
        $where  = 'brand_name="'.addslashes(trim($brand_name)).'"';
        $data   = array(
            'brand_name'    => $brand_name,
            'brand_logo'    => '',
            'brand_desc'    => '',
            'site_url'      => '',
        );
        $sql    = 'select brand_id from '.$table.' where '.$where;
        $id     = $this->db->fetch_one($sql);
        if (empty($id)) {
            $id = $this->db->insert($table, $data);
        } else {
            $this->db->update($table, $data, $where);
        }
        return $id;
    }

    //---------------------------------------------------------------
    // import category
    /**
     *  array (
     *      'property_account_expense_categ' => array (
     *          0 => 46,
     *          1 => '140100 材料采购',
     *      ),
     *      'property_stock_journal' => array (
     *          0 => 11,
     *          1 => 'Stock Journal (CNY)',
     *      ),
     *      'parent_right' => 9,
     *      'name' => '配件',
     *      'sequence' => 0,
     *      'property_stock_account_input_categ' => false,
     *      'parent_id' => array (
     *          0 => 2,
     *          1 => '所有产品 / 可销售',
     *      ),
     *      'parent_left' => 8,
     *      'complete_name' => '所有产品 / 可销售 / 配件',
     *      'property_account_income_categ' => array (
     *          0 => 130,
     *          1 => '600100 主营业务收入',
     *      ),
     *      'child_id' => array (),
     *      'property_stock_valuation_account_id' => array (
     *          0 => 8,
     *          1 => 'X11001 Purchased Stocks - (test)',
     *      ),
     *      'type' => 'normal',
     *      'id' => 8,
     *      'property_stock_account_output_categ' => false,
     *  )
     */
    public function add_category($data) {
        $name   = trim($data['name']);
        $table  = 'ecs_category';
        $where  = 'cat_name="'.addslashes($name).'"';
        $cat_data   = array(
            'cat_name'  => $name,
            'keywords'  => '',
            'cat_desc'  => '',
            'template_file' => '',
            'measure_unit'  => '',
        );
        $sql    = 'select cat_id from '.$table.' where '.$where;
        $id     = $this->db->fetch_one($sql);
        if (empty($id)) {
            $id = $this->db->insert($table, $cat_data);
        } else {
            $this->db->update($table, $cat_data, $where);
        }
        return $id;
    }

    public function import_category() {
        $helper = new OdooHelper();
        $data   = $helper->get_all_category();
        return $this->_import_category_from_data($data);
    }

    private function _import_category_from_data(&$data) {
        foreach ($data as $key => $item) {
            $cat_id = $this->add_category($item);
            $data[$key]['ecs_id']   = $cat_id;
        }
        // update parent ID
        $count  = 0;
        $table  = 'ecs_category';
        foreach ($data as $key => $item) {
            if (empty($item['parent_id'][0])) continue;
            $parent_id  = $item['parent_id'][0];
            $parent_idx = $this->_parent_idx($parent_id, $data);

            if (empty($parent_idx)) continue;
            $parent_ecs_id  = $data[$parent_idx]['ecs_id'];
            $where  = 'cat_id='.$item['ecs_id'];
            $this->db->update($table, array('parent_id' => $parent_ecs_id), $where);

            $count++;
        }
        return $count;
    }

    private function _parent_idx($id, &$data) {
        foreach ($data as $key => $item) {
            if ($item['id'] == $id) {
                return $key;
            }
        }
        return false;
    }
    // end category
    //---------------------------------------------------------------


    //---------------------------------------------------------------
    // import products
    public function import_product() {
        // prepare image_dir
        $this->image_dir    = './img/';
        if (!file_exists($this->image_dir)) {
            mkdir($this->image_dir);
            chmod($this->image_dir, 0755);
        }

        $odoo   = new OdooHelper();

        $start  = 1;
        $limit  = 50;
        $fields = array('id', 'name', 'code', 'product_brand_id', 'list_price', 'image', 'image_medium', 'image_small', 'description', 'weight', 'qty_available', 'sale_ok');
        $count  = 0;
        while (true) {
            $ids    = array();
            for ($i = 0; $i < $limit; $i++) {
                $ids[]  = $start + $i;
            }
            $new_data   = $odoo->get_product_data_by_ids($ids, $fields);
            if (count($new_data) == 0) break;

            foreach ($new_data as $item) {
                try {
                    $product_id = $this->add_product($item);
                    echo $product_id."\n";
                    $count++;
                } catch (Exception $e) {
                    //TODO: do something here
                    var_dump($e->getMessage());
                }
            }

            $start  += $limit;
        }
        return $count;
    }

    /**
     * 
  array (
    'warranty' => 0,
    'ean13' => false,
    'supply_method' => 'buy',
    'uos_id' => false,
    'list_price' => 885,
    'seller_info_id' => array (
      0 => 21,
      1 => '23',
    ),
    'weight' => 0,
    'track_production' => false,
    'color' => 0,
    'incoming_qty' => 0,
    'image' => ...
    'image_medium' => ...
    'image_small' => ...
    'standard_price' => 876,
    'cost_method' => 'standard',
    'price_extra' => 0,
    'mes_type' => 'fixed',
    'uom_id' => array (
      0 => 1,
      1 => '件',
    ),
    'code' => 'CARD',
    'description_purchase' => false,
    'default_code' => 'CARD',
    'name_template' => '显卡',
    'property_account_income' => false,
    'lst_price' => 885,
    'price' => 0,
    'location_id' => array (),
    'id' => 25,
    'message_summary' => ' ',
    'uos_coeff' => 1,
    'procure_method' => 'make_to_stock',
    'delivery_count' => 0,
    'type' => 'product',
    'sale_ok' => true,
    'message_follower_ids' =>array (
      0 => 3,
    ),
    'qty_available' => 15,
    'rental' => false,
    'product_manager' => false,
    'track_outgoing' => false,
    'company_id' => array (
      0 => 1,
      1 => 'U3GO',
    ),
    'message_ids' => array (
      0 => 58,
    ),
    'product_tmpl_id' => array (
      0 => 25,
      1 => '显卡',
    ),
    'state' => false,
    'message_is_follower' => true,
    'loc_rack' => false,
    'uom_po_id' => array (
      0 => 1,
      1 => '件',
    ),
    'pricelist_id' => array (),
    'price_margin' => 1,
    'property_stock_account_input' => false,
    'virtual_available' => 15,
    'description' => '显卡，测试产品描述',
    'reception_count' => 0,
    'track_incoming' => false,
    'property_stock_production' => array (
      0 => 7,
      1 => '虚拟库位 / 生产',
    ),
    'seller_qty' => 1,
    'supplier_taxes_id' => array (),
    'volume' => 0,
    'sale_delay' => 7,
    'loc_row' => false,
    'seller_delay' => 3,
    'warehouse_id' => array (),
    'description_sale' => false,
    'active' => true,
    'property_stock_inventory' => array (
      0 => 5,
      1 => '虚拟库位 / 盘点盈亏',
    ),
    'variants' => false,
    'partner_ref' => '[CARD] 显卡',
    'valuation' => 'manual_periodic',
    'categ_id' => array (
      0 => 9,
      1 => '所有产品 / 可销售 / 部件',
    ),
    'loc_case' => false,
    'weight_net' => 0,
    'packaging' => array (),
    'seller_id' => array (
      0 => 23,
      1 => 'Seagate',
    ),
    'name' => '显卡',
    'property_stock_account_output' => false,
    'property_account_expense' => false,
    'orderpoint_ids' => array (),
    'property_stock_procurement' => array (
      0 => 6,
      1 => '物理库位 / 您的公司 / 库存 / 货架 1 / 需求',
    ),
    'outgoing_qty' => 0,
    'taxes_id' => array (),
    'produce_delay' => 1,
    'seller_ids' => array (
      0 => 21,
    ),
    'product_brand_id' => array (
      0 => 5,
      1 => '测试品牌1',
    ),
    'message_unread' => false,
     */

    public function add_product(&$item) {
        $code   = $item['code'];
        $code   = trim(str_replace('/', '-', $code));
        if (empty($code)) {
            throw new Exception('Empty code for product "'.$item['name'].'"');
        }

        $img_path       = $this->image_dir.$code;
        file_put_contents($img_path, base64_decode($item['image']));

        $img_path_md    = $this->image_dir.$code.'_md.png';
        file_put_contents($img_path_md, base64_decode($item['image_medium']));
        $img_path_sm    = $this->image_dir.$code.'_sm.png';
        file_put_contents($img_path_sm, base64_decode($item['image_small']));

        $brand_id   = 0;
        if (!empty($item['product_brand_id'][1])) {
            $brand_id   = $this->add_brand($item['product_brand_id'][1]);
        }

        $dir            = 'images/products/';
        $data   = array(
            'cat_id'    => 0,
            'goods_sn'  => $code,
            'goods_name'=> trim($item['name']),
            'brand_id'  => $brand_id,
            'provider_name' => '',
            'goods_number'  => $item['qty_available'],
            'goods_weight'  => $item['weight'],
            'market_price'  => $item['list_price'],
            'shop_price'    => $item['list_price'],
            'keywords'      => '',
            'goods_brief'   => $item['description'],
            'goods_desc'    => $item['description'],
            'goods_thumb'   => $dir.$code.'_sm.png',
            'goods_img'     => $dir.$code.'_md.png',
            'original_img'  => $dir.$code,
            'is_real'       => 1,
            'is_on_sale'    => $item['sale_ok'] ? 1 : 0,
            // this field is used to record ID in openerp
            'extension_code'=> $item['id'],
            'last_update'   => time(),
        );

        $table  = 'ecs_goods';
        $where  = 'goods_sn="'.addslashes($code).'"';
        $sql    = 'select goods_id from '.$table.' where '.$where;
        $id     = $this->db->fetch_one($sql);
        if (empty($id)) {
            $data['add_time']   = time();
            $id = $this->db->insert($table, $data);
        } else {
            $this->db->update($table, $data, $where);
        }
        return $id;
    }
    //---------------------------------------------------------------
}
