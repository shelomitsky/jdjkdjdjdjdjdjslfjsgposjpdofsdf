<?php
class ControllerExtensionModuleSearchPlus extends Controller {
    private $error = array();

    private $module_name = 'module_search_plus';
    private $module_Id = '25965';
    private $model = 'model_extension_module_search_plus';
    private $module_path = 'extension/module/search_plus';
    private $module_main_path = 'marketplace/extension';
    private $module_token = 'user_token';
    private $module_version = '2.2.1';
    private $module_ssl = true;
    private $module_custom = false;
    private $vqVersion = false;

    public function index() {
        $this->load->model($this->module_path);
        $this->document->addStyle('view/stylesheet/search_plus.css?v=' . $this->module_version);
        $lang_ar = $this->load->language($this->module_path);
        $admin_path = basename( pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_DIRNAME) );

        $product_detail = array(
            'name',
            'description',
            'meta_title',
            'meta_description',
            'meta_keyword',
            'tags',
            'model',
            'sku',
            'manufacturer',
        );
        $this->load->model('catalog/attribute');
        $attributes = $this->model_catalog_attribute->getAttributes();

        $attribute_name = '';
        $default_lang = $this->config->get('config_language_id');
        foreach ($attributes as $attribute) {
            $attribute_name = $this->model_catalog_attribute->getAttributeDescriptions($attribute['attribute_id']);
            foreach ($attribute_name as $lang => $name) {
                if ($default_lang != $lang) { //
                    continue;
                }
                $att_name[] = [
                    "id" => $attribute['attribute_id'],
                    "name" => $name['name']
                ];
            }
        }
        $this->document->setTitle($this->language->get('heading_title_main'));
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST')) {

            $json = array();

            if ($this->validate()) {
                $this->request->post['module_search_plus_url'] = rtrim($this->request->post['module_search_plus_url'], "/");
                $this->model_setting_setting->editSetting($this->module_name, $this->request->post);      // Parse all the coming data to Setting Model to save it in database.
                $json['success'] = $this->language->get('text_success');
            } else {
                $json['warning'] = $this->language->get('text_failed');
                $json['error'] = $this->error;
            }
            $this->response->addHeader('Content-Type: application/json');
            return $this->response->setOutput(json_encode($json));
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', $this->module_token . '=' . $this->session->data[$this->module_token], $this->module_ssl)
        );

        $data['breadcrumbs'][] = array(
            'text' => (substr(VERSION, 0, 7) > '2.2.0.0') ? $this->language->get('text_extensions') : $this->language->get('text_modules'),
            'href' => $this->url->link($this->module_main_path, $this->module_token . '=' . $this->session->data[$this->module_token] . '&type=module', $this->module_ssl)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title_main'),
            'href' => $this->url->link($this->module_path, $this->module_token . '=' . $this->session->data[$this->module_token], $this->module_ssl)
        );

        $fields = array(
            "read_status",
            "url",
            "username",
            "password",
            "authentication",
            "index",
            "second_index",
            "read_timeout",
            "read_timeout",
            "write_timeout",
            "product_field",
            "attribute_name",
            "fuzziness",
            "field_name",
            "field_description",
            "field_meta_title",
            "field_meta_description",
            "field_meta_keyword",
            "field_model",
            "field_sku",
            "field_manufacturer",
            "field_tag",
            "bulk_count",
            "redirect",
            "status"
        );

        foreach ($fields as $field) {                        
            if (isset($this->request->post['module_search_plus_'.$field])) {
                $data['module_search_plus_'.$field] = $this->request->post['module_search_plus_'.$field];
            } else {
                $data['module_search_plus_'.$field] = $this->config->get('module_search_plus_'.$field);
            }
        }
        
        $data['module_search_plus_synonyms'] = implode(PHP_EOL,[
            't shirt, tee, short sleeve, v-neck',
            'foozball , foosball',
            'universe , cosmos',
            'lol, laughing out loud',
            'ipod, i-pod, i pod => ipod, i-pod, i pod',
            'winter clothes => jackets , sweaters, gloves, scarves'
        ]);
        $data['module_search_plus_stopwords'] = implode(PHP_EOL, ["and", "is", "the"]);


        $data['action'] = html_entity_decode($this->url->link($this->module_path, $this->module_token . '=' . $this->session->data[$this->module_token], $this->module_ssl));
        $data['cancel'] = html_entity_decode($this->url->link($this->module_main_path, $this->module_token . '=' . $this->session->data[$this->module_token] . '&type=module', $this->module_ssl));
        $data['product_detail'] = $product_detail;


        $data['attribute_name'] = $att_name;


        $data['es_v'] = version_compare(PHP_VERSION, '7.0.0', '>=') ? '6x' : ((version_compare(PHP_VERSION, '5.6.6', '>=') && version_compare(PHP_VERSION, '7.0.0', '<=')) ? '5x' : '2x');


        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        $data['user_token'] = $this->session->data['user_token'];        
        if (substr(VERSION, 0, 7) > '2.1.0.2') {
            $this->response->setOutput($this->load->view($this->module_path, $data));
        } else {
            $this->response->setOutput($this->load->view($this->module_path . '.tpl', $data));
        }
    }

    public function install() {
        $this->load->model('setting/store');
        $this->load->model('setting/setting');
        $this->load->model("setting/event");        
        $this->model_setting_event->addEvent("search_plus_event", "admin/model/catalog/product/editProduct/after", "extension/module/search_plus/productIndex");
        $this->model_setting_event->addEvent("search_plus_event", "admin/model/catalog/product/addProduct/after", "extension/module/search_plus/productIndex");
        $this->model_setting_event->addEvent("search_plus_event", "admin/model/catalog/product/deleteProduct/after", "extension/module/search_plus/productIndex");
        $this->model_setting_event->addEvent("search_plus_query", "catalog/controller/product/search/before", "extension/module/search_plus/search_event");
        $this->model_setting_event->addEvent("search_plus_query", "catalog/controller/product/search/after", "extension/module/search_plus/search_event_ajax");
        $this->model_setting_event->addEvent("search_plus_query", "admin/view/common/column_left/before", "extension/module/search_plus/injectMenu");
        $this->model_setting_event->addEvent('search_plus_live_search', 'catalog/view/common/header/after', 'extension/module/search_plus/injectLiveSearch');

        $settings = [
            $this->module_name . '_status' => 0,
            $this->module_name . '_url' => 'http://localhost:9200',
            $this->module_name . '_index' => 'products',
            // $this->module_name . '_second_index' => 'products_second',
            $this->module_name . '_impressions_index' => 'search_stats_impression',
            $this->module_name . '_read_timeout' => 1,
            $this->module_name . '_write_timeout' => 1,
            $this->module_name . '_fuzziness' => 0,
            $this->module_name . '_field_name' => 10,
            $this->module_name . '_field_description' => 10,
            $this->module_name . '_field_meta_title' => 10,
            $this->module_name . '_field_meta_description' => 10,
            $this->module_name . '_field_meta_keyword' => 10,
            $this->module_name . '_field_tag' => 1,
            $this->module_name . '_field_manufacturer' => 1,
            $this->module_name . '_field_model' => 1,
            $this->module_name . '_field_sku' => 1,
            $this->module_name . '_bulk_count' => 10,
            $this->module_name . '_attribute_name' => [],
            $this->module_name . '_authentication' => 0,
            $this->module_name . '_redirect' => 0,
        ];
        $this->model_setting_setting->editSetting($this->module_name, $settings);
    }

    public function uninstall() {
        $this->load->model('setting/store');
        $this->load->model('setting/setting');
        $this->load->model("setting/event");

        $this->model_setting_setting->deleteSetting($this->module_name, 0);

        $stores = $this->model_setting_store->getStores();

        foreach ($stores as $store) {
            $this->model_setting_setting->deleteSetting($this->module_name, $store['store_id']);
        }

        // remove events
        $this->model_setting_event->deleteEventByCode("search_plus_event");
        $this->model_setting_event->deleteEventByCode("search_plus_query");
        $this->model_setting_event->deleteEventByCode('search_plus_live_search');
    }

    protected function validate() {
        if (!$this->user->hasPermission('modify', $this->module_path)) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        if ($this->request->post[$this->module_name . '_status']) {

            if ($this->request->post[$this->module_name . '_authentication']){
                if (empty($this->request->post[$this->module_name . '_username'])) {
                    $this->error['error_username'] = $this->language->get('error_username');
                }
                if (empty($this->request->post[$this->module_name . '_password'])) {
                    $this->error['error_password'] = $this->language->get('error_password');
                }
            }

            if (utf8_strlen(trim($this->request->post[$this->module_name . '_url'])) < 1) {
                $this->error['error_url'] = $this->language->get('error_url');
            }

            if (empty($this->request->post[$this->module_name . '_index'])) {
                $this->error['error_index'] = $this->language->get('error_index');
            }

            /* if (empty($this->request->post[$this->module_name . '_second_index'])) {
                $this->error['error_second_name'] = $this->language->get('error_second_name');
            } */

            if (empty($this->request->post[$this->module_name . '_read_timeout'])) {
                $this->request->post[$this->module_name . '_read_timeout'] = 0;
            }

            if (empty($this->request->post[$this->module_name . '_write_timeout'])) {
                $this->request->post[$this->module_name . '_write_timeout'] = 0;
            }

            if (empty($this->request->post[$this->module_name . '_fuzziness'])) {
                $this->request->post[$this->module_name . '_fuzziness'] = 0;
            }

            if (empty($this->request->post[$this->module_name . '_bulk_count'])) {
                $this->request->post[$this->module_name . '_bulk_count']=10;
            }
        }

        return !$this->error;
    }

    public function reindex() {
        $json = array();
        try {
            $logs = array();
            $startTime = microtime(true);
            $logs[] = 'Starting reindex';
            
            $this->load->model($this->module_path);
            $model = $this->{$this->model};
            $index_name = $model->getPrimaryIndexName();
            $mapping = $model->getIndexMapping();
            
            $logs[] = 'Checking for existing index';
            if ($model->getClient()->indices()->exists(['index' => $index_name])):
                $logs[] = 'Deleting index ' . $index_name;
                $model->getClient()->indices()->delete(['index' => $index_name]);
            endif;
            
            $model->getClient()->indices()->create(['index' => $index_name, 'body' => $mapping]);

            $logs[] = 'Schema added to ' . $index_name;

            $query = $model->getProductsRange();
            $min = $query['min'];
            $max = $query['max'];

            $logs[] = 'Processing proudcts: Min: ' . $min . ' Max: ' . $max . ' Total: ' . $query['total'];

            $this->load->model('catalog/product');

            $perPage = $this->config->get('module_search_plus_bulk_count');
            $pages = ceil(($min + $max) / $perPage);

            $json['ittrations'] = $pages;

            $totalProducts = 0;
            for ($i = 0; $i < $pages; $i++) {

                $start = $min + ($i * $perPage);
                $options = array(
                    'start' => $start,
                    'end' => $start + $perPage - 1,
                );
                $products = $model->getProductsForIndexing($options);
                if (!count($products)):
                    continue;
                endif;
                $totalProducts += count($products);
                $model->indexBulk($index_name, $products);
            }

            $endTime = microtime(true);

            $totalTime = round($endTime - $startTime, 2);

            $json['products_indexed'] = $totalProducts ;
            $json['time'] = $totalTime . 's';

            $json['active_index'] = $index_name;
            $logs[] = 'Done with indexing';
            $json['logs'] = $logs;
            $json['status'] = true;
            $json['message'] = $totalProducts.' Products Indexed Successfully';
        }
        catch(Exception $e) {
            $json['status'] = false;
            $json['error']['message'] = $e->getMessage();
        }
        echo json_encode($json);
    }

    public function connectionStatus(){
        $this->load->language($this->module_path);
        $this->load->library('search_plus');
        $json = array();
        try {
            $json = $this->search_plus->client->info();
            $json['message'] = $this->language->get('text_connection_success');
            $json['status'] = true;
        } catch (Exception $e) {
            $json['status'] = false;
            $json['error']['message'] = $e->getMessage();
        }
        echo json_encode($json);
    }

    public function productIndex(&$route, &$data, &$output) {
        $this->load->model($this->module_path);
        $model = $this->model;
        $this->$model->processMod($route, $data, $output);
    }

    /**
     * Add menu link for search plus
     * @param type $route
     * @param type $data
     */
    public function injectMenu($route, &$data){
        $data['menus'][] = [
            'id' => 'menu-search-plus',
            'icon' => 'fa-search-plus',
            'name' => 'Elasticsearch for OpenCart',
            'href' => $this->url->link('extension/module/search_plus','user_token='.$this->session->data['user_token'],true),
            'children' => [
                array(
                'name' => 'Settings',
                'href' => $this->url->link('extension/module/search_plus','user_token='.$this->session->data['user_token'],true),
                'children' => array()
                )
            ],

        ];
    }
}

