<?php

class ModelExtensionModuleSearchPlus extends Model {

    /**
     * If you make change to this function make sure to changes in catalog model as well.
     * @return string
     */
    public function getSecondaryIndexName() {
        return $this->getIndexName('secondary');
    }

    /**
     * If you make change to this function make sure to changes in catalog model as well.
     * @return string
     */
    public function getPrimaryIndexName() {
        return $this->getIndexName('primary');
    }

    private function getIndexName($type = 'primary') {
        if ('secondary' == $type):
            $index = $this->config->get('module_search_plus_second_index');
        elseif($type == 'impressions'):
            $index = $this->config->get('module_search_plus_impressions_index');
        elseif($type == 'view'):
            $index = $this->config->get('module_search_plus_view_index');
        else:
            $index = $this->config->get('module_search_plus_index');
        endif;
        return $index;
    }

    public static function indexType($type = 'product') {
        $return = 'product';
        switch ($type):
            case 'product':
                $return = 'product';
                break;
            case 'view':
                $return = 'view';
                break;
            case 'impression':
            case 'imp':
                $return = 'impression';
                break;
        endswitch;
        return $return;
    }

    public function processParams($data) {
        
        $params = [];
        $params['search'] = isset($data['search']) && trim($data['search']) != '' ? trim($data['search']) : '';
        $params['tag'] = isset($data['tag']) && trim($data['tag']) != '' ? trim($data['tag']) : '';
        $params['location'] = isset($data['location']) && trim($data['location']) != '' ? trim($data['location']) : '';
        $params['description'] = isset($data['description']) && trim($data['description']) == 'true' ? 'true' : '';
        $params['category_id'] = isset($data['category_id']) && (int) $data['category_id'] > 0 ? (int) $data['category_id'] : 0;
        $params['sub_category'] = isset($data['sub_category']) && $data['sub_category'] == 'true' ? 'true' : '';
        $params['sort'] = isset($data['sort']) && trim($data['sort']) != '' ? trim($data['sort']) : '';
        $params['order'] = isset($data['order']) && strtolower($data['order']) == 'asc' ? trim($data['order']) : 'desc';
        $params['page'] = isset($data['page']) && (int) $data['page'] > 0 ? (int) $data['page'] : 1;
//	$params['limit'] = isset($data['limit']) && (int) $data['limit'] > 0 ? (int) $data['limit'] : 1;
        $params['limit'] = isset($data['limit']) && (int) $data['limit'] > 0 ? (int) $data['limit'] : (int) $this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit');

        $params['price_max'] = isset($data['price_max']) && (float) $data['price_max'] > 0 ? (float) $data['price_max'] : null;
        $params['price_min'] = isset($data['price_min']) && (float) $data['price_min'] > 0 ? (float) $data['price_min'] : null;

        $params['discount'] = isset($data['discount']) && (int) $data['discount'] > 0 ? (int) $data['discount'] : null;
        $params['manufacturer_id'] = isset($data['manufacturer_id']) && (int) $data['manufacturer_id'] > 0 ? (int) $data['manufacturer_id'] : null;
        $params['auto_complete'] = 1;
        $params['return_filters'] = isset($data['return_filters']) && (int) $data['return_filters'] == 1 ? 1 : 0;


        return $params;
    }

    private function getFieldsWeights($fields) {

        $return = [];
        foreach ($fields as $field => $weight):
            $return[] = $field . '^' . $weight;
        endforeach;

        return $return;
    }

    public function findProducts($params) {

        $searchConfig = $this->getSearchConfigs();
        $searchFields = $this->getFieldsWeights($searchConfig['fields']);

        $index = $this->getPrimaryIndexName();
        $type = $this->indexType();

        $params['filter_include'] = isset($params['filter_include']) ? $params['filter_include'] : [];

        $this->load->library('search_plus');
        $this->client = $this->search_plus->client;

        $query = ['bool' => ['must' => []]];

        /**
         * @todo Escape query string
         */
        if ($params['search'] != ''):

            $queryString['fields'] = $searchFields;
            $queryString['analyzer'] = 'custom_analyzer';

            $words = explode(' ',$this->search_plus->escape($params['search']));
            if($searchConfig['fuzziness'] > 0):
                /**
                 * Append fuzzy operator ~ to words
                 */
                array_walk($words, function (&$value, $key){
                    $value.='~';
                });
                $queryString['fuzziness'] = $searchConfig['fuzziness'];
            endif;

            $queryWords = implode(' ', $words);
            
            if($params['auto_complete'] > 0):

                $words = $this->search_plus->escape($params['search']).'';
                $queryString['query'] = '('.$queryWords.')^10 OR ('.$words.'*)';
            else:
                $queryString['query'] = $queryWords;
            endif;
            
            $query['bool']['must']['query_string'] = $queryString;
            $query['bool']['should']['query_string'] = $queryString;
            $query['bool']['must']['query_string']['default_operator'] = 'OR';
            $query['bool']['should']['query_string']['default_operator'] = 'AND';
            $query['bool']['should']['query_string']['boost'] = 100;

        endif;

        $filters = [];
        // status
        $filters[] = ['term' => ['status' => 1]];

        if ($params['category_id'] > 0):
            $filters[] = ['term' => ['categories' => $params['category_id']]];
        endif;

        if ($params['manufacturer_id'] > 0):
            $filters[] = ['term' => ['manufacturer.id' => $params['manufacturer_id']]];
        endif;

        if ($params['location'] != ''):
            $filters[] = ['term' => ['location.keyword' => $params['location']]];
        endif;

        $priceFilter = [];
        if ($params['price_max'] > 0):
            $priceFilter['lte'] =  $params['price_max'];
        endif;

        if ($params['price_min'] > 0):
            $priceFilter['gte'] =  $params['price_min'];
        endif;

        if(count($priceFilter)):
            $filters[]['range']['price'] =  $priceFilter;
        endif;

        if ($params['discount'] > 0):
            $filters[]['range']['discount_percent'] = ['gte' => $params['discount']];
        endif;


        $query['bool']['filter'] = $filters;
        $start = ($params['page'] - 1) * $params['limit'];
        $body = [
            '_source' => ['name','categories','category_0_name','category_1_name','model','manufacturer'],
            'query' => $query,
            'from' => $start,
            'size' => $params['limit'],
            'sort' => ['_score' => ['order' => $params['order']]]
        ];

        if($params['return_filters']):

            $body['aggregations'] = $this->getAggregationsCols($params['filter_include']);

        endif;

        $search = [
            'index' => $index,
            //'type' => $type,
            //'type' => '_doc',
            'body' => $body
        ];

        $return = [
            'hits' => [],
            'total' => 0,
            'took' => null,
            'filters' => [],
        ];

        try{
            $results = $this->client->search($search);
        }catch(\Exception $e){
            $this->registry->get('log')->write('FATAL: Search failed. Error: '.$e->getMessage());
            return $return;
        }

        $return['took'] = $results['took'];
        $return['timed_out'] = $results['timed_out'];

        $language = $this->session->data['language'];

        if (isset($results['hits']) && count($results['hits'])):
            $return['total'] = $results['hits']['total'];
            $return['hits'] = [];
            $position = 1;
            foreach ($results['hits']['hits'] as $hit):
                $return['hits'][] = $hit['_id'];
                $return['products'][$hit['_id']] = [
                    'id' => (int) $hit['_id'],
                    'name' => $hit['_source']['name'][$language],
                    'category_0_name' => isset($hit['_source']['category_0_name'][$language]) ? $hit['_source']['category_0_name'][$language] : '',
                    'category_1_name' => isset($hit['_source']['category_1_name'][$language]) ? $hit['_source']['category_1_name'][$language] : '',
                    'position' =>   $position,
                    'model' =>   $hit['_source']['model'],
                    'position_overall' =>   $position + $start,
                    'manufacturer' => isset($hit['_source']['manufacturer']) ? $hit['_source']['manufacturer']['name'] : '_none_',
                ];
                $position++;
            endforeach;
        endif;

        $return['products'] = isset($return['products']) ? array_values($return['products']) : [];

        if(isset($results['aggregations'])):

            $return['filters'] = $this->processFacets($results['aggregations']);
        endif;
        return $return;
    }
    public function findPopularSearches($params) {

        $index = $this->getIndexName('impressions');
        $type = $this->indexType('imp');

        $this->load->library('search_plus');
        $this->client = $this->search_plus->client;

        $query = ['bool' => ['must' => []]];

        /**
         * @todo Escape query string
         */
        if ($params['search'] != ''):
            $queryString = [
                'fields' => ['term'],
                'query' =>  $this->search_plus->escape($params['search']).'*',
            ];
            $query['bool']['must']['query_string'] = $queryString;
            $query['bool']['should']['query_string'] = $queryString;
            $query['bool']['must']['query_string']['default_operator'] = 'OR';
            $query['bool']['should']['query_string']['default_operator'] = 'AND';
            $query['bool']['should']['query_string']['boost'] = 100;

        endif;

        $filters = [];
        // status

        $language = $this->session->data['language'];

        $filters[] = ['term' => ['lang' => $language]];

        $body = [
            '_source' => ['term'],
            'query' => $query,
            'from' => 0,
            'size' => 0,
            'sort' => ['_score' => ['order' => 'desc']]
        ];
        $body['aggregations'] = $this->getAggregationsCols(['term']);

        $search = [
            'index' => $index,
            //'type' => $type,
            //'type' => '_doc',
            'body' => $body
        ];

        $return = [
            'hits' => [],
            'total' => 0,
            'took' => null,
            'filters' => [],
        ];

        try{
            $results = $this->client->search($search);
        }catch(\Exception $e){
            $this->registry->get('log')->write('FATAL: Autocomplete search failed. Error: '.$e->getMessage());
            return $return;
        }

        $terms = [];

        if(isset($results['aggregations'])):
            foreach($results['aggregations']['term']['buckets'] as $bucket):
                if($bucket['doc_count'] <= 3):
                    continue;
                endif;
                $term = strtolower($bucket['key']);
                $terms[$term] = [
                    'keyword'=> $term,
                    'count'=>$bucket['doc_count']
                ];
            endforeach;
        endif;
        return $terms;
    }

    public function getFilters($params){

        $params['limit'] = 0;
        $params['from'] = 0;

        // reset all filters and get the aggrigations
        $paramTemp = $params;
        $paramTemp['category_id'] = 0;
        $paramTemp['manufacturer_id'] = 0;
        $paramTemp['discount'] = 0;
        $paramTemp['price_max'] = 0;
        $paramTemp['price_min'] = 0;

        $paramTemp['return_filters'] = 1;

        $results = $this->findProducts($paramTemp);
        return $results['filters'];

    }

    public function getSearchConfigs() {
        $attributes = $this->config->get('module_search_plus_attribute_name');

        if (!$attributes):
            $attributes = [];
        endif;

        $fuzziness = $this->config->get('module_search_plus_fuzziness');
        $name = $this->config->get('module_search_plus_field_name');
        $description = $this->config->get('module_search_plus_field_description');
        $meta_title = $this->config->get('module_search_plus_field_meta_title');
        $meta_description = $this->config->get('module_search_plus_field_meta_description');
        $meta_keyword = $this->config->get('module_search_plus_field_meta_keyword');
        $tags = $this->config->get('module_search_plus_field_tags');
        $model = $this->config->get('module_search_plus_field_model');
        $sku = $this->config->get('module_search_plus_field_sku');
        $location = $this->config->get('module_search_plus_field_location');
        $manufacturer = $this->config->get('module_search_plus_field_manufacturer');
        
        $att['fuzziness'] = (int) $fuzziness;

        $this->load->model('localisation/language');

        $language = $this->session->data['language'];

        $att['fields'] = [];
        if ($name != ''):
            $att['fields']['name.' . $language] = intval($name);
        endif;
        if ($description != ''):
            $att['fields']['description.' . $language] = intval($description);
        endif;
        if ($meta_title != ''):
            $att['fields']['meta_title.' . $language] = intval($meta_title);
        endif;

        if ($meta_description != ''):
            $att['fields']['meta_description.' . $language] = intval($meta_description);
        endif;

        if ($meta_keyword != ''):
            $att['fields']['meta_keyword.' . $language] = intval($meta_keyword);
        endif;

        if ($tags != ''):
            $att['fields']['tag.' . $language] = intval($tags);
        endif;

        /**
         * @todo Make all these weights come from admin
         */

        foreach ($attributes as $attribute) {
            $att['fields']['attr_' . $attribute . '.' . $language] = 1;
        }

        if ($model != ''):
            $att['fields']['model'] = intval($model);
        endif;

        if ($sku != ''):
            $att['fields']['sku'] = intval($sku);
        endif;

        if ($manufacturer != ''):
            $att['fields']['manufacturer.name'] = intval($manufacturer);
        endif;

        if ($location != ''):
            $att['fields']['location'] = intval($location);
        endif;

        return $att;
    }

    private function getAttributeName($att_id) {
        $attribute_data = array();
        $query = $this->db->query("SELECT name FROM " . DB_PREFIX . "attribute_description WHERE attribute_id = '" . (int) $att_id . "' AND language_id =1");

        return $query->rows[0]['name'];
    }

    public function getSearchUrl($params) {
        $url = '';

        if (isset($params['search']) && $params['search']) {
            $url .= '&search=' . urlencode(html_entity_decode($params['search'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($params['tag']) && $params['tag']) {
            $url .= '&tag=' . urlencode(html_entity_decode($params['tag'], ENT_QUOTES, 'UTF-8'));
        }

        if (isset($params['description']) && $params['description']) {
            $url .= '&description=' . $params['description'];
        }

        if (isset($params['category_id']) && $params['category_id'] > 0) {
            $url .= '&category_id=' . $params['category_id'];
        }

        if (isset($params['sub_category']) && $params['sub_category'] > 0) {
            $url .= '&sub_category=' . $params['sub_category'];
        }

        if (isset($params['sort']) && $params['sort']) {
            $url .= '&sort=' . $params['sort'];
        }

        if (isset($params['order']) && $params['order']) {
            $url .= '&order=' . $params['order'];
        }

        if (isset($params['discount']) && $params['discount']) {
            $url .= '&discount=' . $params['discount'];
        }

        if (isset($params['price_max']) && $params['price_max']) {
            $url .= '&price_max=' . $params['price_max'];
        }

        if (isset($params['price_min']) && $params['price_min']) {
            $url .= '&price_min=' . $params['price_min'];
        }
        return $url;
    }

    public function getLimits($url) {
        $data['limits'] = array();

        $limits = array_unique(array($this->config->get('theme_' . $this->config->get('config_theme') . '_product_limit'), 25, 50, 75, 100));

        sort($limits);

        foreach ($limits as $value) {
            $data['limits'][] = array(
                'text' => $value,
                'value' => $value,
                'href' => $this->url->link('product/search', $url . '&limit=' . $value)
            );
        }
        return $data['limits'];
    }

    /**
     * Get the configs for aggrigations
     * @return array
     */
    public function getAggregationsCols($include = []){
        $size = 5;

        $all = [
            'category_0'=>[
                'terms'=>[
                    'field'=>'category_0',
                    'size'=> $size,
                ]
            ],
            'term'=>[
                'terms'=>[
                    'field'=>'term.keyword',
                    'size'=> $size,
                ]
            ],
            'manufacturer'=>[
                'terms'=>[
                    'field'=>'manufacturer.id',
                    'size'=> $size,
                ]
            ],
            'location'=>[
                'terms'=>[
                    'field'=>'location.keyword',
                    'size'=> $size,
                ]
            ],
            'price_max'=>[
                'max'=>[
                    'field'=>'price',
                ]
            ],
            'price_min'=>[
                'min'=>[
                    'field'=>'price',
                ]
            ],
            'discount'=>[
                'range'=>[
                    'field'=>'discount_percent',
                    'ranges'=>[
                        ['from'=>10],
                        ['from'=>20],
                        ['from'=>30],
                        ['from'=>50],
                    ],
                ]
            ],
        ];

        $include = count($include) ? $include : array_keys($all);
        $return = [];
        foreach($include as $key):
            $return[$key] = $all[$key];
        endforeach;

        return $return;

    }

    /**
     * Does nothing for the moment but later can be used to remove etc
     * @param type $filters
     * @return type
     */
    public function processFacets($filters) {
        $this->load->model('catalog/category');
        $this->load->model('catalog/manufacturer');
        foreach ($filters as $key => &$bucket):
            switch ($key):
                case 'category_0':
                case 'category_1':
                    foreach ($bucket['buckets'] as &$values):
                        $category = $this->model_catalog_category->getCategory($values['key']);
                        $values['display'] = $category['name'];
                    endforeach;
                    break;
                case 'manufacturer':
                    foreach ($bucket['buckets'] as &$values):
                        $manufacturer = $this->model_catalog_manufacturer->getManufacturer($values['key']);
                        $values['display'] = isset($manufacturer['name']) ? $manufacturer['name'] : '';
                    endforeach;
                    break;
            endswitch;
        endforeach;
        return $filters;
    }
    /**
     * Records the impression date into elasticsearch
     * @param array $impression
     * @return mixed boolean false if failed, string _id if success
     */
    public function recordImpression($impression){
        $params = [
            'index' => $this->getIndexName('impressions'),
            'type' => $this->indexType('impression'),
            'body' => $impression
        ];

        try{
            $response = $this->client->index($params);
            return $response['_id'];
        }catch(\Exception $e){
            $this->registry->get('log')->write('FATAL: Failed to record impression. Error: '.$e->getMessage());
            return false;
        }
    }

    public function recordViews($views){
        $params = [
            'index' => $this->getIndexName('view'),
            'type'  => $this->indexType('view'),
            'body'  => $views
        ];

        try{
            $response = $this->client->index($params);
            return $response;
        }catch (\Exception $e){
            $this->registry->get('log')->write('FATAL: Failed to record page View. Error: '.$e->getMessage());
            return false;
        }
    }
}
