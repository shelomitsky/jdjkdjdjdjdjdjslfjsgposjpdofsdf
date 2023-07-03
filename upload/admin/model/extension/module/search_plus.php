<?php

class ModelExtensionModuleSearchPlus extends Model {

    public $client = null;
    private $categoryDescriptions = [];

    /*
     * load elasticsearch connection
     * from custom made library
     */

    public function getClient() {
        if (null == $this->client):
            $this->load->library('search_plus');
            $this->client = $this->search_plus->client;
        endif;
        return $this->client;
    }

    /*
     * process products attributes
     * for elasticsearch index mapping
     */

    public function getIndexMapping() {

        $analyzer = 'custom_analyzer';

        $columns = [
            "product_id" => [
                "type" => "integer"
            ],
            "model" => [
                "type" => "text",
                'analyzer' => $analyzer,

            ],
            "sku" => [
                "type" => "text",
                'analyzer' => $analyzer,
            ],
            "upc" => [
                "type" => "text",
                'analyzer' => $analyzer,
            ],
            "ean" => [
                "type" => "text",
                'analyzer' => $analyzer,
            ],
            "jan" => [
                "type" => "text",
                'analyzer' => $analyzer,
            ],
            "isbn" => [
                "type" => "text",
                'analyzer' => $analyzer,
            ],
            "mpn" => [
                "type" => "text",
                'analyzer' => $analyzer,
            ],
            "manufacturer" => [
                'properties' => [
                    'id' => [
                        'type' => 'integer'
                    ],
                    'name' => [
                        'type' => 'text',
                        'analyzer' => $analyzer,
                        'fields' => [
                            'keyword' => [
                                'type' => 'keyword'
                            ]
                        ]
                    ],
                ]
            ],
            "location" => [
                "type" => "text",
                'analyzer' => $analyzer,
                "fields" => [
                    "keyword" => [
                        "type" => "keyword"
                    ]
                ]
            ],
            "quantity" => [
                "type" => "integer"
            ],
            "stock_status_id" => [
                "type" => "integer"
            ],
            "manufacturer_id" => [
                "type" => "integer"
            ],
            "shipping" => [
                "type" => "integer"
            ],
            "price" => [
                "type" => "scaled_float",
                "scaling_factor" => 100
            ],
            "cost" => [
                "type" => "scaled_float",
                "scaling_factor" => 100
            ],
            // origginal price
            "price_original" => [
                "type" => "scaled_float",
                "scaling_factor" => 100
            ],
            // discount in percentge
            "discount_percent" => [
                "type" => "integer",
            ],
            // discount in amount
            "discount_amount" => [
                "type" => "scaled_float",
                "scaling_factor" => 100
            ],
            "points" => [
                "type" => "integer"
            ],
            "tax_class_id" => [
                "type" => "integer"
            ],
            "date_available" => [
                "type" => "date",
                "format" => "yyyy-MM-dd"
            ],
            "weight" => [
                "type" => "float"
            ],
            "weight_class_id" => [
                "type" => "integer"
            ],
            "length" => [
                "type" => "float"
            ],
            "width" => [
                "type" => "float"
            ],
            "height" => [
                "type" => "float"
            ],
            "length_class_id" => [
                "type" => "integer"
            ],
            "subtract" => [
                "type" => "integer"
            ],
            "minimum" => [
                "type" => "integer"
            ],
            "sort_order" => [
                "type" => "integer"
            ],
            "status" => [
                "type" => "integer"
            ],
            "categories" => [
                "type" => "integer"
            ],
            "category_0" => [
                "type" => "integer"
            ],
            "category_1" => [
                "type" => "integer"
            ],
            "category_2" => [
                "type" => "integer"
            ],
            "viewed" => [
                "type" => "integer"
            ],
            "date_added" => [
                "type" => "date",
                "format" => "yyyy-MM-dd HH:mm:ss"
            ],
            "date_modified" => [
                "type" => "date",
                "format" => "yyyy-MM-dd HH:mm:ss"
            ],
            "language_id" => [
                "type" => "integer"
            ],
        ];

        $this->load->model('localisation/language');

        $languages = $this->model_localisation_language->getLanguages();
        $multi = ['properties' => []];
        $this->load->model('catalog/attribute');

        foreach ($languages as $code => $language):
            $multi['properties'][$code] = ['type' => 'text'];
        endforeach;

        $columns['description'] = $multi;
        $columns['name'] = $multi;
        $columns['tag'] = $multi;
        // add keyword field as well to tag
        foreach ($columns['tag']['properties'] as &$col):
            $col['fields']['keyword'] = ['type' => 'keyword'];
        endforeach;
        $columns['meta_title'] = $multi;
        $columns['meta_description'] = $multi;
        $columns['meta_keyword'] = $multi;
        $columns['category_name_0'] = $multi;
        $columns['category_name_1'] = $multi;

        $attributes = $this->getSearchableAttribues();
        /**
         * Attributes names
         */
        foreach ($attributes as $attr):
            $columns['attr_' . $attr] = $multi;
        endforeach;


        $type = $this->indexType();
        $mapping = [
            "mappings" => [
                // type is depreciated in ES 7
                // $type => [
                //     "_all" => ['enabled' => false],
                    "properties" => $columns,
                // ]
            ],
            "settings" => [
                "index" => [
                    "number_of_shards" => 3,
                    "number_of_replicas" => 1,
                    'analysis' => [
                        'analyzer' => [
                            'custom_analyzer' => [
                                'type' => 'custom',
                                'filter' => ['lowercase'],
                                'tokenizer' => 'standard',
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return $mapping;
    }

    /**
     * Get attribute ids which are marked as searchable
     * @return array
     */
    public function getSearchableAttribues() {
        $attributes = $this->config->get("module_search_plus_attribute_name");
        $attributes = is_array($attributes) ? $attributes : [];
        return $attributes;
    }

    public function getProductsRange() {
        $query = $this->db->query("SELECT MAX(product_id) as max, MIN(product_id) as min, COUNT(product_id) AS total from " . DB_PREFIX . "product");
        return $query->row;
    }

    /**
     * If you make change to this function make sure to changes in catalog model as well.
     * @return string
     */
    public function getPrimaryIndexName() {
        return $this->getIndexName('primary');
    }

    /*
     * return current index name
     * @param string $type
     */
    private function getIndexName($type = 'primary') {
        $index = $this->config->get('module_search_plus_index');
        return $index;
    }

    /**
     * @param array $options start, end
     * @return array
     */
    public function getProductsForIndexing($options) {
        $sql = 'SELECT p.*, m.name as manufacturer_name FROM '
            . DB_PREFIX . 'product p '
            . 'LEFT JOIN ' . DB_PREFIX . 'manufacturer m ON p.manufacturer_id = m.manufacturer_id '
            . 'WHERE 1=1 ';
        if (isset($options['start']) && $options['start'] > 0 && isset($options['end']) && $options['end'] > 0):
            $sql .= 'AND p.product_id BETWEEN ' . $options['start'] . " AND " . $options['end'] . ' ';
        else:
            $sql .= 'AND p.product_id IN (' . implode(',', $options['ids']) . ') ';
        endif;
        $sql .= 'ORDER BY p.product_id ASC';

        $query = $this->db->query($sql);

        if ($query->num_rows == 0):
            return [];
        endif;

        $descriptions = $this->getProductDescriptions($options);
        $attributes = $this->getProductAttributes($options);

        $attributeIds = $this->getSearchableAttribues();
        $categories = $this->getProductCategories($options);

        $specials = $this->getProductSpecials($options);
        $this->load->model('catalog/product');
        $this->load->model('catalog/category');

        $products = [];
        foreach ($query->rows as $row):
            $id = $row['product_id'];
            $products[$id] = $row;
            $productCategories = isset($categories[$id]) ? $categories[$id] : [];

            $categoryPath = [];
            foreach ($productCategories as $category):
                $categoryPath[] = explode('_', $this->getPath($category['category_id']));
            endforeach;

            foreach ($descriptions[$id] as $description):
                $code = &$description['code'];
                $products[$id]['description'][$code] = $description['description'];
                $products[$id]['name'][$code] = $description['name'];
                $products[$id]['meta_title'][$code] = $description['meta_title'];
                $products[$id]['meta_description'][$code] = $description['meta_description'];
                $products[$id]['meta_keyword'][$code] = $description['meta_keyword'];
                $products[$id]['tag'][$code] = $this->parseTag($description['tag']);
                ;

                $products[$id]['language_id'][] = (int) $description['language_id'];
            endforeach;

            $products[$id]['manufacturer'] = [
                'id' => (int) $row['manufacturer_id'],
                'name' => $row['manufacturer_name']
            ];

            unset($products[$id]['manufacturer_id']);
            unset($products[$id]['manufacturer_name']);

            $products[$id]['price_original'] = (float) $row['price'];

            if (isset($specials[$id])):
                if (!($row['price'] > 0)){
                    $products[$id]['price'] = (float) $specials[$id];
                    $products[$id]['discount_percent'] = 0;
                    $products[$id]['discount_amount'] = 0;
                }else {
                $discountAmount = $row['price'] - $specials[$id];

                $discountPercent = round(($discountAmount / $row['price']) * 100, 0);
                $products[$id]['price'] = (float) $specials[$id];
                $products[$id]['discount_amount'] = $discountAmount;
                $products[$id]['discount_percent'] = $discountPercent;
            }
            else:
                $products[$id]['discount_percent'] = 0;
                $products[$id]['discount_amount'] = 0;
            endif;

            if (isset($attributes[$id])):
                foreach ($attributes[$id] as $attribute):
                    // if not searchable attribute then skip
                    if (!in_array($attribute['attribute_id'], $attributeIds)):
                        continue;
                    endif;
                    $products[$id]['attr_' . $attribute['attribute_id']][$attribute['code']] = $attribute['text'];
                endforeach;
            endif;
            $products[$id]['categories'] = [];

            $this->load->model('localisation/language');

            $languages = $this->model_localisation_language->getLanguages();

            foreach ($categoryPath as $cats):
                foreach ($cats as $level => $categoryId):
                    $products[$id]['category_' . $level][] = (int) $categoryId;
                    $products[$id]['categories'][] = (int) $categoryId;
                    $descriptionsCategory = [];
                    $descriptionsCategory = $this->getCategoryDescription($categoryId);
                    foreach ($descriptionsCategory as $code => $info):
                        $products[$id]['category_' . $level . '_name'][$code][] = $info['name'];
                    endforeach;
                endforeach;
            endforeach;
        endforeach;

        unset($query);
        unset($descriptions);
        unset($attributes);

        return $products;
    }

    protected function getPath($parent_id, $current_path = '') {
        $category_info = $this->model_catalog_category->getCategory($parent_id);
        if ($category_info) {
            if (!$current_path) {
                $new_path = $category_info['category_id'];
            } else {
                $new_path = $category_info['category_id'] . '_' . $current_path;
            }
            $path = $this->getPath($category_info['parent_id'], $new_path);
            if ($path) {
                return $path;
            } else {
                return $new_path;
            }
        }
    }

    /**
     * Get description of products
     * @param array $options
     */
    private function getProductDescriptions($options) {
        $sql = 'SELECT d.*, a.code FROM '
            . DB_PREFIX . 'product_description d '
            . 'INNER JOIN ' . DB_PREFIX . 'language a ON d.language_id = a.language_id '
            . 'WHERE 1=1 ';
        if (isset($options['start']) && $options['start'] > 0 && isset($options['end']) && $options['end'] > 0):
            $sql .= 'AND d.product_id BETWEEN ' . $options['start'] . " AND " . $options['end'] . ' ';
        else:
            $sql .= 'AND d.product_id IN (' . implode(',', $options['ids']) . ') ';
        endif;
        $sql .= 'ORDER BY d.product_id ASC';
        $rsDescription = $this->db->query($sql);
        $descriptions = [];
        foreach ($rsDescription->rows as $row):
            $descriptions[$row['product_id']][$row['code']] = $row;
        endforeach;
        unset($rsDescription);

        return $descriptions;
    }

    /**
     * Get description of products
     * @param array $options
     */
    private function getProductCategories($options) {

        $sql = 'SELECT c.* FROM '
            . DB_PREFIX . 'product_to_category c '
            . 'WHERE 1=1 ';
        if (isset($options['start']) && $options['start'] > 0 && isset($options['end']) && $options['end'] > 0):
            $sql .= 'AND c.product_id BETWEEN ' . $options['start'] . " AND " . $options['end'] . ' ';
        else:
            $sql .= 'AND c.product_id IN (' . implode(',', $options['ids']) . ') ';
        endif;

        $rs = $this->db->query($sql);
        $return = [];
        foreach ($rs->rows as $row):
            $return[$row['product_id']][] = $row;
        endforeach;

        unset($rs);

        return $return;
    }

    /**
     * Get names for the category for multiple languages
     * @param int $category
     * @return array
     */
    private function getCategoryDescription($category) {

        if (isset($this->categoryDescriptions[$category])):
            return $this->categoryDescriptions[$category];
        endif;

        $sql = 'SELECT d.category_id, d.name, a.code
		FROM ' . DB_PREFIX . 'category_description d
		INNER JOIN ' . DB_PREFIX . 'language a on a.language_id = d.language_id
		WHERE 1=1 AND d.category_id IN (' . intval($category) . ')';

        $rs = $this->db->query($sql);
        $return = [];
        foreach ($rs->rows as $key => $row):
            $this->categoryDescriptions[$category][$row['code']] = [
                'name' => $row['name'],
            ];
        endforeach;

        unset($rs);
        return $this->categoryDescriptions[$category];
    }

    /**
     * Get special prices for products
     * @param array $options
     * @return array
     */
    private function getProductSpecials($options) {

        $sql = "SELECT * FROM (SELECT ps.product_id,ps.price
		FROM " . DB_PREFIX . "product_special ps
		WHERE ps.customer_group_id = 1 
		AND (
		    (ps.date_start = '0000-00-00' OR ps.date_start < NOW())
		    AND 
		    (ps.date_end = '0000-00-00' OR ps.date_end > NOW())
		    ) ";

        if (isset($options['start']) && $options['start'] > 0 && isset($options['end']) && $options['end'] > 0):
            $sql .= 'AND ps.product_id BETWEEN ' . $options['start'] . " AND " . $options['end'] . ' ';
        else:
            $sql .= 'AND ps.product_id IN (' . implode(',', $options['ids']) . ') ';
        endif;

        $sql .= 'ORDER BY ps.priority ASC, ps.price ASC) AS sub
		GROUP BY sub.product_id';

        $rs = $this->db->query($sql);
        $return = [];
        foreach ($rs->rows as $row):
            $return[$row['product_id']] = (float) $row['price'];
        endforeach;

        unset($rs);

        return $return;
    }

    private function parseTag($tag) {
        $tmp = explode(',', $tag);
        // remove empty values
        $tags = array_filter(array_map('trim', $tmp));
        return count($tags) ? array_values($tags) : [];
    }

    /*
     * returns slug for attribute name
     * @param string $str
     */

    private function getSlug($str) {
        $str = strtolower(trim($str));
        $str = preg_replace('/[^a-z0-9-]/', '-', $str);
        $str = preg_replace('/-+/', "-", $str);
        return rtrim($str, '-');
    }

    /**
     * Get attributes of products
     * @param array $options
     */
    private function getProductAttributes($options) {

        $sql = 'SELECT d.product_id, a.code, d.attribute_id, d.text FROM '
            . DB_PREFIX . 'product_attribute d '
            . 'INNER JOIN ' . DB_PREFIX . 'language a ON d.language_id = a.language_id '
            . 'WHERE 1=1 ';
        if (isset($options['start']) && $options['start'] > 0 && isset($options['end']) && $options['end'] > 0):
            $sql .= 'AND d.product_id BETWEEN ' . $options['start'] . " AND " . $options['end'] . ' ';
        else:
            $sql .= 'AND d.product_id IN (' . implode(',', $options['ids']) . ') ';
        endif;
        $sql .= 'ORDER BY d.product_id ASC';

        $rsAttributes = $this->db->query($sql);
        $attributes = [];
        foreach ($rsAttributes->rows as $row):
            $attributes[$row['product_id']][] = $row;
        endforeach;
        unset($rsAttributes);

        return $attributes;
    }

    /**
     * Bulk index the products
     * @param string $index
     * @param array $products
     */
    public function indexBulk($index, $products) {
        $params = [];
        foreach ($products as $product) {
			$today = date('Y-m-d');
            if ($product['date_available'] === '0000-00-00' || empty($product['date_available']) ){
                $product['date_available'] = date('Y-m-d');
            }elseif($product['date_available'] > $today){
                continue;
            }
            $params['body'][] = [
                'index' => [
                    '_index' => $index,
                    //'_type' => $this->indexType(), //default _type is _doc in ES 7
                    '_id' => (int) $product['product_id'],
                ]
            ];
            $params['body'][] = $product;
        }
        if (count($params) > 0):
            $this->getClient()->bulk($params);
        endif;
        unset($params);
    }

    /*
     * Take action depending on the
     * product modification
     * @param string $route
     * @param array $data
     * @param array $output
     */
    public function processMod($route, $data, $output = null) {
        $str = explode("/", $route, 3);
        $index = $this->getPrimaryIndexName();
        $type = $this->indexType();
        $id = $data[0];
        switch ($str[2]) {
            case "editProduct":
                $result = $this->getProductsForIndexing(['ids' => [$id]]);
                $this->indexBulk($index, $result);
                break;
            case "addProduct":
                $result = $this->getProductsForIndexing(['ids' => [$output]]);
                $this->indexBulk($index, $result);
                break;
            case "deleteProduct":
                $this->deleteDocument($index, $id, $type);
                break;
        }
    }

    /*
     * Deletes single document
     * @param string $index
     * @param string $type
     * @param integer $id
     */

    private function deleteDocument($index, $id, $type) {
        $params = [
            'index' => $index,
            'type' => $type,
            'id' => (int) $id
        ];
        $response = $this->getClient()->delete($params);
    }

    private static function indexType($type = 'product') {
        return 'product';
    }

}
