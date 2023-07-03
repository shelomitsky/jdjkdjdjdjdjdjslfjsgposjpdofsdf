<?php

/**
 * Created by PhpStorm.
 * User: Qavi technologies
 * Date: 2/28/2018
 * Time: 5:35 PM
 */
class ControllerExtensionModuleSearchPlus extends Controller {

    private $model = 'model_extension_module_search_plus';
    private $module_path = 'extension/module/search_plus';
    private $module_search = null;

    public function index() {
        $this->load->model('tool/image');
        $this->load->model('setting/setting');
        $status = $this->model_setting_setting->getSettingValue('module_search_plus_status');
        if ((int) $status == 0): // search is disabled so do nothing and return
            return null;
        endif;
        $json = array();
        $error = false;
        if( version_compare(VERSION, '3.0.0.0', '>=') ){
            $currency_code = $this->session->data['currency'];
        }
        else{
            $error = true;
            $json[] = array(
                'product_id' => 0,
                'minimum'    => 0,
                'image'      => null,
                'name'       => 'Version Error: '. VERSION,
                'extra_info' => null,
                'price'      => 0,
                'special'    => 0,
                'url'        => '#'
            );
        }

        if(!$error){
            $image_width        = 50;
            $image_height       = 50;
            $title_length       = 100;
            $description_length = 100;

            $params = $this->request->get;
            $results = $this->esSearch($params);
            foreach ($results['products'] as $result) {
                if ($result['thumb']) {
                    $image = $this->model_tool_image->resize($result['thumb'], $image_width, $image_height);
                } else {
                    $image = $this->model_tool_image->resize('placeholder.png', $image_width, $image_height);
                }

                if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                    $price = $result['price'];
                } else {
                    $price = $result['price'];
                }

                if ((float)$result['special']) {
                    $special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $currency_code);
                } else {
                    $special = false;
                }

                if ($this->config->get('config_tax')) {
                    $tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $currency_code);
                } else {
                    $tax = false;
                }

                if ($this->config->get('config_review_status')) {
                    $rating = (int)$result['rating'];
                } else {
                    $rating = false;
                }
                $json['total'] = (int)count($results['products']);
                $json['products'][] = array(
                    'product_id' => $result['product_id'],
                    'minimum'    => $result['minimum'],
                    'image'      => $image,
                    'name'       => utf8_substr(strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')), 0, $title_length) . '..',
                    'extra_info' => utf8_substr(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')), 0, $description_length) . '..',
                    'price'      => $price,
                    'special'    => $special,
                    'url'        => $this->url->link('product/product', 'product_id=' . $result['product_id'])
                );
            }
        }
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /*
     * checks whether searchPlus
     * is enabled for searching
     */
    public function search_event(&$route, &$data) {

        $this->load->model('setting/setting');
        $status = $this->model_setting_setting->getSettingValue('module_search_plus_status');
        if ((int) $status == 0): // search is disabled so do nothing and return
            return null;
        endif;
        $params = $this->request->get;
        $data = $this->esSearch($params);

        $this->response->setOutput($this->load->view('product/search', $data));

        return ''; // $this->esSearch($params);
    }

    /**
     * Event for ajax based search
     * @param type $route
     * @param type $data
     * @return string
     */
    public function search_event_ajax(&$route, &$data) {

        $this->load->model('setting/setting');
        $status = $this->model_setting_setting->getSettingValue('module_search_plus_status');
        if ((int) $status == 0): // search is disabled so do nothing and return
            return null;
        endif;

        $this->load->model($this->module_path);
        $this->load->model('tool/image');
        $this->load->language('product/search');

        $this->module_search = $this->{$this->model};

        $params = $this->request->get;
        $params['auto_complete'] = 1;
        $params['return_filters'] = 1;

        $searchParams = $this->module_search->processParams($params);
        $searchParams['auto_complete'] = 1;
        $searchParams['filter_include'] = ['category_0','manufacturer'];

        $results = $this->module_search->findProducts($searchParams);

        $this->load->model('catalog/product');

        foreach ($results['hits'] as $productId):
            $result = $this->model_catalog_product->getProduct($productId);
            if ($result['image']) {
                $image = $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
            } else {
                $image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
            }

            // $swapimage = false;

            $price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);

            if ((float) $result['special']) {
                $special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
            } else {
                $special = false;
            }

            if ($this->config->get('config_tax')) {
                $tax = $this->currency->format((float) $result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
            } else {
                $tax = false;
            }

            $rating = false;
            // $out_of_stock_badge = $result['quantity'] <= 0 ? $result['stock_status'] : 1;

            $data['products'][] = array(
                'product_id' => $result['product_id'],
                'thumb' => $image,
                'name' => $result['name'],
                'price' => $price,
                'special' => $special,
                'href' => $this->url->link('product/product', 'product_id=' . $result['product_id'] . $url),
            );

        endforeach;

        $data['url_all'] = $this->url->link('product/search', 'search=' . $searchParams['search']);

        // get terms
        /**
         * Get the global filters
         */
        $data['categories'] = [];

        foreach($results['filters']['category_0']['buckets'] as $bucket):
            $data['categories'][] = [
                'name' => $bucket['display'],
                'count' => $bucket['doc_count'],
                'link' => $this->url->link('product/search','search='.$params['search'].'&category_id='.( (int) $bucket['key'] )),
            ];
        endforeach;

        $data['brands'] = [];

        foreach($results['filters']['manufacturer']['buckets'] as $bucket):
            $data['brands'][] = [
                'name' => $bucket['display'],
                'count' => $bucket['doc_count'],
                'link' => $this->url->link('product/search','search='.$params['search'].'&manufacturer_id='.( (int) $bucket['key'] )),
            ];
        endforeach;

        $data['popular_searches'] = $this->module_search->findPopularSearches($searchParams);

        foreach ($data['popular_searches'] as &$term):

            $term['url'] =  $this->url->link('product/search', 'search=' . $term['keyword'].'&popular=1');

        endforeach;
        echo json_encode( $data );
        return '';
    }

    /*
     * perform elasticsearch query
     * @param array $word
     */

    public function esSearch($options) {

        $this->load->model($this->module_path);
        $this->load->model('tool/image');
        $this->load->language('product/search');

        $this->module_search = $this->{$this->model};

        $searchParams = $this->module_search->processParams($options);
        
        $results = $this->module_search->findProducts($searchParams);
        
        $data['filters'] = $this->module_search->getFilters($searchParams);

        /**
         * Get the global filters
         */

        if (isset($searchParams['search'])) {
            $this->document->setTitle($this->language->get('heading_title') . ' - ' . $searchParams['search']);
        } elseif (isset($this->request->get['tag'])) {
            $this->document->setTitle($this->language->get('heading_title') . ' - ' . $this->language->get('heading_tag') . $searchParams['tag']);
        } else {
            $this->document->setTitle($this->language->get('heading_title'));
        }

        $data['lang_id'] = $this->config->get('config_language_id');

        $data['t1o_text_gallery'] = $this->config->get('t1o_text_gallery');
        $data['t1o_text_small_list'] = $this->config->get('t1o_text_small_list');
        $data['t1o_text_sale'] = $this->config->get('t1o_text_sale');
        $data['t1o_text_new_prod'] = $this->config->get('t1o_text_new_prod');
        $data['t1o_text_quickview'] = $this->config->get('t1o_text_quickview');

        $data['t1o_out_of_stock_badge_status'] = $this->config->get('t1o_out_of_stock_badge_status');
        $data['t1o_sale_badge_status'] = $this->config->get('t1o_sale_badge_status');
        $data['t1o_sale_badge_type'] = $this->config->get('t1o_sale_badge_type');
        $data['t1o_new_badge_status'] = $this->config->get('t1o_new_badge_status');
        $data['t1o_category_prod_box_style'] = $this->config->get('t1o_category_prod_box_style');
        $data['t1d_img_style'] = $this->config->get('t1d_img_style');

        $data['text_compare'] = sprintf($this->language->get('text_compare'), (isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0));

        $data['compare'] = $this->url->link('product/compare');
        $url = $this->module_search->getSearchUrl($searchParams);
        $data['products'] = [];
        $this->load->model('catalog/product');
        // record impressions
        // $impressions = [];
        // $position = 1;
        foreach ($results['hits'] as $productId):
            $result = $this->model_catalog_product->getProduct($productId);

            if ($result['image']) {
                $image = $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
            } else {
                $image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
            }

            $swapimages = $this->model_catalog_product->getProductImages($result['product_id']);
            if ($swapimages) {
                $swapimage = $this->model_tool_image->resize($swapimages[0]['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
            } else {
                $swapimage = false;
            }

            if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
                $price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
            } else {
                $price = false;
            }

            if ((float) $result['special']) {
                $special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
            } else {
                $special = false;
            }

            if ($this->config->get('config_tax')) {
                $tax = $this->currency->format((float) $result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
            } else {
                $tax = false;
            }

            if ($this->config->get('config_review_status')) {
                $rating = (int) $result['rating'];
            } else {
                $rating = false;
            }

            $out_of_stock_badge = $result['quantity'] <= 0 ? $result['stock_status'] : 1;

        	// $impressions['products'][] = [
        	// 	'id' => $result['product_id'],
        	// 	'name' => $result['name'],
        	// 	'position' => $position,
        	// 	'position_overall' => $searchParams['start'] + $position,
        	// 	'manufacturer' => $result['manufacturer']['name'],
        	// ];

            // $position++;
            $data['products'][] = array(
                'product_id' => $result['product_id'],
                'thumb' => $image,
                'thumb_swap' => $swapimage,
                'newstart' => $result['date_added'],
                'quickview' => $this->url->link('product/quickview', 'product_id=' . $result['product_id']),
                'name' => $result['name'],
                'description' => utf8_substr(trim(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'))), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
                'price' => $price,
                'special' => $special,
                'tax' => $tax,
                'minimum' => $result['minimum'] > 0 ? $result['minimum'] : 1,
                'rating' => $result['rating'],
                'href' => $this->url->link('product/product', 'product_id=' . $result['product_id'] . $url),
                'out_of_stock_quantity' => $result['quantity'],
                'out_of_stock_badge' => $out_of_stock_badge,
                'brand' => $result['manufacturer'],
                'brand_url' => $this->url->link('product/manufacturer/info', 'manufacturer_id=' . $result['manufacturer_id']),
                'val1' => preg_replace("/[^0-9.]/", "", $result['special']),
                'val2' => preg_replace("/[^0-9.]/", "", $result['price']),
                'startDate1' => strtotime(mb_substr($result['date_added'], 0, 10)),
                'endDate2' => strtotime(date("Y-m-d")),
            );

        endforeach;

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        ];

        $data['categories'] = $this->getCategories();
        $data['sorts'] = $this->getSorts($url);

        $data['limits'] = $this->module_search->getLimits($url);

        $pagination = new Pagination();
        $pagination->total = $results['total']['value'];
        $pagination->page = $searchParams['page'];
        $pagination->limit = $searchParams['limit'];
        $pagination->url = $this->url->link('product/search', $url . '&page={page}');

        $data['pagination'] = $pagination->render();

        $data['results'] = sprintf($this->language->get('text_pagination'), ($pagination->total) ? (($pagination->page - 1) * $pagination->limit) + 1 : 0, ((($pagination->page - 1) * $pagination->limit) > ($pagination->total - $pagination->limit)) ? $pagination->total : ((($searchParams['page'] - 1) * $pagination->limit) + $pagination->limit), $pagination->total, ceil($pagination->total / $pagination->limit));

        if (isset($searchParams['search']) && $this->config->get('config_customer_search')) {
            $this->load->model('account/search');

            $customer = [
                'name' => '_anonymous_',
                'id' => 0,
            ];
            if ($this->customer->isLogged()) {
                $customer = [
                    'name' => $this->customer->getFirstname().' '.$this->customer->getLastname(),
                    'id' => (int) $this->customer->getId(),
                ];
            }

            $queryTime = null;
            if($this->request->server['REQUEST_TIME_FLOAT']):
                $queryTime = (microtime(true) - $this->request->server['REQUEST_TIME_FLOAT']) * 1000; // in milli seconds
            endif;

            if (isset($this->request->server['REMOTE_ADDR'])) {
                $ip = $this->request->server['REMOTE_ADDR'];
            } else {
                $ip = '';
            }

            $impression = [
                'term' => $searchParams['search'],
                'customer' => $customer,
                'products' => $results['products'],
                'datetime' => date('Y-m-d H:i:s'),
                'took' => $results['took'],
                'query_time' => $queryTime,
                'total' => $pagination->total,
                'ip' => $ip,
                'lang' => $this->session->data['language'],
                'store_id' => $this->config->get('config_store_id'),
            ];

            $this->module_search->recordImpression($impression);

        }

        $data['search'] = $searchParams['search'];
        $data['description'] = $searchParams['description'];
        $data['category_id'] = $searchParams['category_id'];
        $data['sub_category'] = $searchParams['sub_category'];

        $data['sort'] = $searchParams['sort'];
        $data['order'] = $searchParams['order'];
        $data['limit'] = $searchParams['limit'];

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        return $data;
    }

    private function getCategories() {
        // 3 Level Category Search
        $data['categories'] = array();

        $this->load->model('catalog/category');

        $categories_1 = $this->model_catalog_category->getCategories(0);

        foreach ($categories_1 as $category_1) {
            $level_2_data = array();

            $categories_2 = $this->model_catalog_category->getCategories($category_1['category_id']);

            foreach ($categories_2 as $category_2) {
                $level_3_data = array();

                $categories_3 = $this->model_catalog_category->getCategories($category_2['category_id']);

                foreach ($categories_3 as $category_3) {
                    $level_3_data[] = array(
                        'category_id' => $category_3['category_id'],
                        'name' => $category_3['name'],
                    );
                }

                $level_2_data[] = array(
                    'category_id' => $category_2['category_id'],
                    'name' => $category_2['name'],
                    'children' => $level_3_data
                );
            }

            $data['categories'][] = array(
                'category_id' => $category_1['category_id'],
                'name' => $category_1['name'],
                'children' => $level_2_data
            );
        }
        return $data['categories'];
    }

    private function getSorts($url) {
        $data['sorts'] = array();

        $data['sorts'][] = array(
            'text' => $this->language->get('text_default'),
            'value' => 'p.sort_order-ASC',
            'href' => $this->url->link('product/search', 'sort=p.sort_order&order=ASC' . $url)
        );

        $data['sorts'][] = array(
            'text' => $this->language->get('text_name_asc'),
            'value' => 'pd.name-ASC',
            'href' => $this->url->link('product/search', 'sort=pd.name&order=ASC' . $url)
        );

        $data['sorts'][] = array(
            'text' => $this->language->get('text_name_desc'),
            'value' => 'pd.name-DESC',
            'href' => $this->url->link('product/search', 'sort=pd.name&order=DESC' . $url)
        );

        $data['sorts'][] = array(
            'text' => $this->language->get('text_price_asc'),
            'value' => 'p.price-ASC',
            'href' => $this->url->link('product/search', 'sort=p.price&order=ASC' . $url)
        );

        $data['sorts'][] = array(
            'text' => $this->language->get('text_price_desc'),
            'value' => 'p.price-DESC',
            'href' => $this->url->link('product/search', 'sort=p.price&order=DESC' . $url)
        );

        if ($this->config->get('config_review_status')) {
            $data['sorts'][] = array(
                'text' => $this->language->get('text_rating_desc'),
                'value' => 'rating-DESC',
                'href' => $this->url->link('product/search', 'sort=rating&order=DESC' . $url)
            );

            $data['sorts'][] = array(
                'text' => $this->language->get('text_rating_asc'),
                'value' => 'rating-ASC',
                'href' => $this->url->link('product/search', 'sort=rating&order=ASC' . $url)
            );
        }

        $data['sorts'][] = array(
            'text' => $this->language->get('text_model_asc'),
            'value' => 'p.model-ASC',
            'href' => $this->url->link('product/search', 'sort=p.model&order=ASC' . $url)
        );

        $data['sorts'][] = array(
            'text' => $this->language->get('text_model_desc'),
            'value' => 'p.model-DESC',
            'href' => $this->url->link('product/search', 'sort=p.model&order=DESC' . $url)
        );

        return $data['sorts'];
    }

    /*
     * page view index field to be added
     */
    public function record(){
        if($this->request->server['REQUEST_METHOD'] == 'POST'){

            $this->load->model($this->module_path);
            $this->module_search = $this->{$this->model};

            $this->load->library('search_plus');
            $this->client = $this->search_plus->client;
            $type = $this->request->post['type'];

            foreach ($this->request->post['data'] as $data){
                $views = [
                    'id' => $data['id'],
                    'name' => $data['name'],
                    'model' => $data['model'],
                    'category' => $data['category'],
                    'parent_category' => $data['category_parent'],
                    'datetime' => date('Y-m-d H:i:s'),
                    'type' => $type,
                ];
                $this->module_search->recordViews($views);
            }


        }
    }

    public function injectLiveSearch(&$route, &$data, &$output) {
        $this->load->language('product/search', 'search');

        $liveSearch = [
            'text_view_all_results'               => 'View all results',
            'text_empty'                          => 'There is no product that matches the search criteria.',
            'module_live_search_show_image'       => '0',
            'module_live_search_show_price'       => '0',
            'module_live_search_show_description' => '0',
            'module_live_search_min_length'       => '1',
            'module_live_search_show_add_button'  => '0',
        ];

        $liveSearchJS = '<link href="catalog/view/javascript/live_search/live_search.css" rel="stylesheet" type="text/css">'."\n";
        $liveSearchJS .= '<script src="catalog/view/javascript/live_search/live_search.js"></script>'."\n";
        $liveSearchJS .= '<script type="text/javascript"><!--'."\n";
        $liveSearchJS .= '$(document).ready(function() {'."\n";
        $liveSearchJS .= 'var options = '.json_encode($liveSearch).';'."\n";
        $liveSearchJS .= 'LiveSearchJs.init(options); '."\n";
        $liveSearchJS .= '});'."\n";
        $liveSearchJS .= '//--></script>'."\n";
        $liveSearchJS .= '</head>'."\n";
        $output = str_replace('</head>', $liveSearchJS, $output);
    }
}
