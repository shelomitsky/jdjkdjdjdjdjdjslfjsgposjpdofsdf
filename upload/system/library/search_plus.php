<?php

class search_plus
{
    public $client = null;
    /**
     * @param  object  $registry  Registry Object
     */
    public static function get_instance($registry) {
        if (is_null(static::$instance)) {
            static::$instance = new static($registry);
        }

        return static::$instance;
    }

    public function __construct($registry) {

    $url = parse_url($registry->get('config')->get('module_search_plus_url'));
    $host = $this->getHost($url, $registry);
    $timeout = (int) $registry->get('config')->get('module_search_plus_read_timeout');
    $timeout = $timeout > 0 ? $timeout : 3;

    $clientOptions = [
        'client' => [
        'curl' => [
            CURLOPT_CONNECTTIMEOUT => $timeout
        ]
        ]
    ];

    if (version_compare(PHP_VERSION, '7.0.0', '>=')) {
        require DIR_SYSTEM . '/library/search_plus_7x/vendor/autoload.php';
    }
    elseif (version_compare(PHP_VERSION, '7.0.0', '<=')) {
        require DIR_SYSTEM . '/library/search_plus_5x/vendor/autoload.php';
    }

    $this->client = \Elasticsearch\ClientBuilder::create()->setConnectionParams($clientOptions)->setHosts($host)->build();
    }

    public function ping(){
        return $this->client->cat()->indices();
    }
    
    function escape($string) {
    $regex = "/[\\+\\-\\=\\&\\|\\!\\(\\)\\{\\}\\[\\]\\^\\\"\\~\\*\\<\\>\\?\\:\\\\\\/]/";
    return preg_replace($regex, addslashes('\\$0'), $string);
    }

    public function getHost($url, $registry){
        $host = [];

        $host['host']   = isset($url['port']) ? $url['host'].':'.(string)$url['port'] : $url['host'];
        $host['scheme'] = isset($url['scheme']) ? $url['scheme'] : 'http';
        $host['port']   = isset($url['port']) ? (string)$url['port'] : ( ( isset($url['scheme']) && $url['scheme'] == 'https') ? '443' : '9200');

        if ($registry->get('config')->get('module_search_plus_authentication')) {
            if (isset($url['user']) || $registry->get('config')->get('module_search_plus_username') ) {
                $host['user'] = isset($url['user']) ? $url['user'] : $registry->get('config')->get('module_search_plus_username');
            }

            if (isset($url['pass']) || $registry->get('config')->get('module_search_plus_password') ) {
                $host['pass'] = isset($url['pass']) ? $url['pass'] : $registry->get('config')->get('module_search_plus_password');
            }
        }
        return $host;
    }
}