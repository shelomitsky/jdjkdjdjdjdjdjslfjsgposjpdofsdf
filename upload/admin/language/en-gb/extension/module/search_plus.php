<?php
// Heading
$_['heading_title']                 = 'Elasticsearch for OpenCart <span style="font-size:12px; color:#999999"> by <a href="https://qavi.tech/?utm_source=organic&utm_medium=opencart&utm_campaign=searchplus" style="font-size:1em; color:#999999" target="_blank">Qavi technologies</a></span>';
$_['heading_title_main']            = 'Elasticsearch for OpenCart';
$_['heading_view']                  = 'View Records';
// Text
$_['text_success']                  = '<b>Success:</b> You have modified Elasticsearch for OpenCart configurations!';
$_['text_failed']                   = '<b>Failed:</b> Elasticsearch for OpenCart configurations could not be modified due to errors !';

$_['column_name']                   = 'Categories';
$_['column_value']                  = 'Total Views';
$_['text_list']                     = 'Recorded Views';

$_['text_edit']                     = 'Edit Module Settings';
$_['text_home']                     = 'Home';
$_['text_loading']                  = 'Loading...';
$_['text_extensions']               = 'Extensions';
$_['text_extension']                = 'Extension';
$_['text_modules']                  = 'Modules';
$_['text_enabled']                  = 'Enabled';
$_['text_disabled']                 = 'Disabled';
$_['text_default']                  = 'Default';
$_['text_confirm']                  = 'Attention: not saved settings will be lost! Сontinue?';
$_['text_author']                   = 'Author';
$_['text_version']                  = 'Version';
$_['text_pro_version']              = 'These features are only available in Pro verison';
//$_['text_version_info']             = 'Your current PHP version is '.PHP_VERSION.'. Make sure to install Elasticsearch %es_v%';
$_['text_version_info']             = 'Make sure your PHP version and Elasticsearch should be 7.x';
$_['text_connection_success']       = 'Connection established successfully.Detected Elasticsearch version is ';
$_['text_connection_failed']        = 'Connection could not be established.';
$_['text_searchable_attributes']    = 'Select the product attributes which should be included in search. Leave empty if you don’t want to search on attributes.';
$_['text_not_compatible']           = 'Your current version of PHP is '.PHP_VERSION.'. Minimum version required for this extension to work is 5.6.6 !';

// Tab
$_['tab_server']                   = 'Server Settings';
$_['tab_content']                  = 'Content Indexing';
$_['tab_log']                      = 'Search Results';
$_['tab_statistics']               = 'Search Statistics';

// Entry
$_['entry_url']             		= 'Elasticsearch URL';
$_['entry_check_connection']        = 'Check Connection';
$_['entry_read_timeout']            = 'Read Timeout';
$_['entry_write_timeout']           = 'Write Timeout';
$_['entry_advanced_settings']       = 'Index Fields';
$_['entry_attributes_settings']     = 'Searchable Attributes';
$_['entry_index']                   = 'Index';
$_['entry_status']                  = 'Enable Elasticsearch';
$_['entry_second_name']		        = 'Secondary Index Name';
$_['entry_impressions_index']	    = 'Impressions Index Name';
$_['entry_view_index']	            = 'Page View Index Name';
$_['entry_bulk_count']	            = 'Bulk Indexing Count';
$_['entry_log']                     = 'Fuzziness Amount';
$_['entry_index_name']            	= 'Index Name';
$_['entry_extended_log']            = 'Extended Logs';
$_['entry_synonyms']         		= 'Synonyms';
$_['entry_stopwords']         		= 'Stopwords';
$_['entry_redirect']         		= 'Redirect';
$_['entry_authentication']          = 'Authentication';
$_['entry_username']         		= 'Username';
$_['entry_password']         		= 'Password';

// Button
$_['button_save']                   = 'Save';
$_['button_cancel']                 = 'Cancel';
$_['button_status_check']           = 'Check Status';


// Help
$_['help_status']                   = 'Enable/Disable search via Elasticsearch. If disabled the default OpenCart search will be used.';
$_['help_url']              		= 'ElasticSearch <b><i>URL</i></b>. If your search provider has given you a connection URL use that. Make sure to include scheme (http or https) and port. Do not include user name or password or index name. Example for valid URLs are http://localhost:9200 , https://search-example-wzj15zxlafm7vaeti6s2borj2q.eu-central-1.es.amazonaws.com , http://127.0.0.1:9200 etc.';
$_['help_read_timeout']             = 'The maximum time (in seconds) that read requests should wait for server response. If the call times out, OpenCart will fallback to standard search';
$_['help_write_timeout']            = 'The maximum time (in seconds) that write requests should wait for server response. This should be set long enough to index your entire site';
$_['help_bulk_count']        		= 'Number of products to be fetched and sent to Elasticsearch in bulk. If you are experiencing timeout errors while indexing you may try reduce this value. Lower value will increase indexing time';
$_['help_index_name']               = 'Name of the Elasticsearch index for products. If you modify it make sure to reindex products.';
$_['help_empty']                    = 'Leave empty if you want to exclude this field from search completely';
$_['help_redirect']                 = 'Redirect if single result';
$_['help_authentication']          	= 'Authenticate user';
$_['help_username']                 = 'Username';
$_['help_password']                 = 'Password';

// Legend
$_['legend_general']                = 'Server Settings';
$_['legend_advanced']               = 'Content Indexing';
$_['legend_log']                    = 'Search Results';
$_['legend_statistics']             = 'Search Statistics';
$_['legend_synonyms']               = 'Synonyms & Stopwords';
$_['legend_relevance']               = 'Relevance';
$_['legend_result_setting']         = 'Result Settings';

// Error
$_['error_permission']              = 'Warning: You do not have permission to modify Elasticsearch for OpenCart module!';
$_['error_warning']                 = 'Warning: Please check the form carefully for errors!';
$_['error_mysql_table']             = 'Warning: One or more the extension tables don\'t exist in the database! Please reinstall this extension in <a href="%s">the module list</a> to solve this problem. If the problem doesn\'t solve, see FAQ for more details.';
$_['error_file_exist']              = 'Warning: One or more the extension files don\'t exist in the \'catalog\' folder! Please upload the archive of extension again.';

$_['error_save']                    = 'Warning: The extension was updated, please re-save settings!';
$_['error_url']                     = 'URL is required!';
$_['error_index']                   = 'Index Name is required!';
$_['error_second_name']             = 'Secondary Index Name is required!';
$_['error_impressions_index']       = 'Impressions index Name is required!';
$_['error_authentication']          = 'Username & Password must be set when authentication is Enabled!';
$_['error_username']          		= 'Username must be set when authentication is Enabled!';
$_['error_password']          		= 'Password must be set when authentication is Enabled!';

$_['desc_fuzz']                     = 'The number of characters that can be swapped out to match a word. For example; 1 = anoth(a)r~ = anoth(e)r; 2 = (e)noth(a)r~ = (a)noth(e)r; 2 = an()th(u)r~ = an(o)th(e)r. The smaller the number, the better the performance. Leave this zero to disable fuzzy searching.';
$_['desc_field']                    = 'When executing a search, not all fields of a product are equal. Review each of the fields that are more important than others and adjust their importance. Higher value mean more importance. Leave the field empty if you don\'t want to inlucde that field in search. Zero value means no influence on importance but that filed still be included in search.';
$_['wipe_desc']                     = 'Clear the search history and stats. This can\'t be undone!';
$_['wipe_title']                    = 'Wipe Search Logs';
$_['reindex_title']                 = 'Products Re-index';
$_['reindex_desc']                  = 'For optimzied performance reindex the products from ssh or command line. Use following command to reindex all products.';
$_['reindex_msg']                   = 'Same can be used to schedule it via Crontab. Make sure default php is minimum 7.2.';
$_['synonyms_desc']                 = 'Synonyms enhance the user experience by providing relevant results even if user is not using the correct keywords. In real word if users are searching for “tee shirts” they can type in many variations like "t shirt", "tee", "short sleeve", "v-neck" etc. This feature enables you to provide most relevant results for all these users.
Another use case is “winter clothes”. Using this feature a store can show results for “jackets”, “sweaters”, “gloves” and “scarves” against this search';
$_['stopwords_desc']                = 'Add common words which you want to ignore completely. Reindex products if you modify this setting. Only input one word per line or it may break the index.';