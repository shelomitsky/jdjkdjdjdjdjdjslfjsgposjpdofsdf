<?php
class ControllerProductUniFeatured extends Controller {
	public function index() {
		$uniset = $this->config->get('config_unishop2');
		$lang_id = $this->config->get('config_language_id');
		
		$this->load->language('product/uni_featured');
		
		$settings = $uniset['catalog']['featured_page'];
		
		if(isset($settings['status'])) {
			
			$this->load->language('extension/module/uni_othertext');
			$this->load->language('extension/module/uni_reviews');
		
			$this->load->model('catalog/product');
			$this->load->model('tool/image');
			$this->load->model('extension/module/uni_featured');
			$this->load->model('extension/module/uni_new_data');
		
			$category_id = isset($this->request->get['cat_id']) ? (int)$this->request->get['cat_id'] : 0;
		
			$data['latest_href'] = $this->url->link('product/uni_featured', '', true);
			$data['category_id'] = $category_id;
		
			//if($category_id && in_array($category_id, array_column($data['product_categories'], 'category_id'))) {
			//	$this->document->addLink($this->url->link('product/uni_featured', 'cat_id='.(int)$this->request->get['cat_id']), 'canonical');
			//}
			
			$page_heading = isset($settings['heading'][$lang_id]) ? $settings['heading'][$lang_id] : $this->language->get('text_heading_title');
			$page_title = isset($settings['title'][$lang_id]) && $settings['title'][$lang_id] != '' ? $settings['title'][$lang_id] : $page_heading;
			$page_description = isset($settings['description'][$lang_id]) ? $settings['description'][$lang_id] : '';
			
			$this->document->setTitle($page_title);
			$this->document->setDescription($page_description);
			$this->document->addLink($this->url->link('product/uni_featured', ''), 'canonical');

			$data['heading_title'] = $page_heading;
		
			$data['breadcrumbs'] = [];

			$data['breadcrumbs'][] = [
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home')
			];
		
			$data['breadcrumbs'][] = [
				'text' => $page_heading,
				'href' => $this->url->link('product/uni_featured')
			];
		
			$currency = $this->session->data['currency'];
			$config_tax = $this->config->get('config_tax'); 
		
			$stock_warning = (!$this->config->get('config_stock_checkout') || $this->config->get('config_stock_warning')) ? true : false;
		
			$data['show_quick_order_text'] = isset($uniset['show_quick_order_text']) ? $uniset['show_quick_order_text'] : '';			
			$data['quick_order_icon'] = isset($uniset['show_quick_order']) ? $uniset[$lang_id]['quick_order_icon'] : '';
			$data['quick_order_title'] = isset($uniset['show_quick_order']) ? $uniset[$lang_id]['quick_order_title'] : '';
			$data['rating_type'] = $this->config->get('config_review_status') ? $uniset['catalog']['rating']['type'] : '';
			$data['wishlist_btn_disabled'] = isset($uniset['wishlist']['disabled']) ? true : false;
			$data['compare_btn_disabled'] = isset($uniset['compare']['disabled']) ? true : false;
			$data['show_grid_button'] = isset($uniset['show_grid_button']) ? true : false;
			$data['show_list_button'] = isset($uniset['show_list_button']) ? true : false;
			$data['show_compact_button'] = isset($uniset['show_compact_button']) ? true : false;
			
			$data['shop_name'] = $this->config->get('config_name');
		
			$route = isset($this->request->get['route']) ? $this->request->get['route'] : '';
			$menu_schema = isset($uniset['menu_schema']) ? $uniset['menu_schema'] : [];
			
			$data['menu_expanded'] = ($uniset['menu_type'] == 1 && in_array($route, $menu_schema)) ? true : false;
			$data['hide_last_breadcrumb'] = isset($uniset['breadcrumbs']['hide']['last']) ? true : false;
			
			if (isset($this->request->get['sort'])) {
				$sort = $this->request->get['sort'];
			} else {
				$sort = 'p.sort_order';
			}

			if (isset($this->request->get['order'])) {
				$order = $this->request->get['order'];
			} else {
				$order = 'DESC';
			}
			
			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}
			
			$img_width = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width');
			$img_height = $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height');
			
			$data['img_width'] = $img_width;
			$data['img_height'] = $img_height;
			
			$sorts_arr = ['pd.name', 'p.price', 'rating', 'p.model'];
				
			if(!in_array($sort, $sorts_arr)) {
				$sort = 'p.date_available';
			}
			
			$filter_data = [
				'products'		=> isset($settings['products']) ? $settings['products'] : [],
				'category_id'	=> $category_id,
				'sort'			=> $sort,
				'order'			=> $order
			];

			$results = $this->model_extension_module_uni_featured->getProducts($filter_data);
			
			$data['products'] = [];
			
			if($results) {
				$data['categories'] = [];
		
				if(isset($settings['product_category'])) {
			
					$categories = $this->model_extension_module_uni_featured->getProductCategories($filter_data);
			
					if($categories) {
						foreach ($categories as $category) {
							$data['categories'][$category['category_id']] = [
								'category_id'	=> $category['category_id'],
								'name'			=> $category['name'],
								'selected'		=> $category['category_id'] == $category_id ? true : false,
								'href'   		=> $this->url->link('product/uni_featured', '&cat_id='.(int)$category['category_id'] . $url, true)
							];
						}
						
						if (isset($data['categories'][$category_id])) {
							$data['heading_title'] = $page_heading.' - '.$data['categories'][$category_id]['name'];
								
							$data['breadcrumbs'][] = [
								'text' => $data['categories'][$category_id]['name'],
								'href' => $data['categories'][$category_id]['href']
							];
						}
					}
				}

				foreach($results  as $result) {
					if ($result['image']) {
						$image = $this->model_tool_image->resize($result['image'], $img_width, $img_height);
					} else {
						$image = $this->model_tool_image->resize('placeholder.png', $img_width, $img_height);
					}
				
					if (($this->config->get('config_customer_price') && $this->customer->isLogged()) || !$this->config->get('config_customer_price')) {
						$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $currency);
					} else {
						$price = false;
					}
						
					if ((float)$result['special']) {
						$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $currency);
					} else {
						$special = false;
					}
				
					if ($this->config->get('config_review_status')) {
						$rating = (int)$result['rating'];
					} else {
						$rating = false;
					}
			
					if ($this->config->get('config_tax')) {
						$tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $currency);
					} else {
						$tax = false;
					}

					$new_data = $this->model_extension_module_uni_new_data->getNewData($result, ['width' => $img_width, 'height' => $img_height]);
					
					if($new_data['special_date_end']) {
						$data['show_timer'] = true;
					}
					
					if((int)$result['reviews']) {
						$data['show_rating'] = true;
					}
							
					$data['products'][] = [
						'product_id' 		=> $result['product_id'],
						'thumb'   	 		=> $image,
						'name'    			=> $result['name'],
						'description' 		=> utf8_substr(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get('theme_'.$this->config->get('config_theme').'_product_description_length')) . '..',
						'tax'         		=> $tax,
						'price'   	 		=> $price,
						'special' 	 		=> $special,
						'rating'     		=> $rating,
						'reviews'    		=> sprintf($this->language->get('text_reviews'), (int)$result['reviews']),
						'rating_new'		=> $result['rating'],
						'text_reviews'		=> $new_data['text_reviews'],
						'href'    	 		=> $this->url->link('product/product', 'product_id='.$result['product_id']),
						'minimum' 			=> $result['minimum'] ? $result['minimum'] : 1,
						'maximum'			=> $stock_warning ? $result['quantity'] : 100000,
						'price_value' 		=> $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $config_tax), $currency, false, false),
						'special_value' 	=> $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $config_tax), $currency, false, false),
						'model'				=> $new_data['model'],
						'additional_image'	=> $new_data['additional_image'],
						'stickers' 			=> $new_data['stickers'],
						'special_date_end' 	=> $new_data['special_date_end'],
						'discounts'			=> $new_data['discounts'],
						'attributes' 		=> $new_data['attributes'],
						'options'			=> $new_data['options'],
						'show_description'	=> $new_data['show_description'],
						'show_quantity'		=> $new_data['show_quantity'],
						'quantity_indicator'=> $new_data['quantity_indicator'],
						'cart_btn_icon'		=> $new_data['cart_btn_icon'],
						'cart_btn_text'		=> $new_data['cart_btn_text'],
						'cart_btn_class'	=> $new_data['cart_btn_class'],
						'quick_order'		=> $new_data['quick_order']
					];
				}
			}
			
			$url = '';

			if (isset($this->request->get['cat_id'])) {
				$url .= '&cat_id=' . (int)$this->request->get['cat_id'];
			}
			
			$data['sorts'] = [];

			$data['sorts'][] = [
				'text'  => $this->language->get('text_default'),
				'value' => 'p.date_available',
				'href'  => $this->url->link('product/uni_featured', '' . $url)
			];

			$data['sorts'][] = [
				'text'  => $this->language->get('text_name_asc'),
				'value' => 'pd.name-ASC',
				'href'  => $this->url->link('product/uni_featured', 'sort=pd.name&order=ASC' . $url)
			];

			$data['sorts'][] = [
				'text'  => $this->language->get('text_name_desc'),
				'value' => 'pd.name-DESC',
				'href'  => $this->url->link('product/uni_featured', 'sort=pd.name&order=DESC' . $url)
			];

			$data['sorts'][] = [
				'text'  => $this->language->get('text_price_asc'),
				'value' => 'p.price-ASC',
				'href'  => $this->url->link('product/uni_featured', 'sort=p.price&order=ASC' . $url)
			];

			$data['sorts'][] = [
				'text'  => $this->language->get('text_price_desc'),
				'value' => 'p.price-DESC',
				'href'  => $this->url->link('product/uni_featured', 'sort=p.price&order=DESC' . $url)
			];

			if ($this->config->get('config_review_status')) {
				$data['sorts'][] = [
					'text'  => $this->language->get('text_rating_desc'),
					'value' => 'rating-DESC',
					'href'  => $this->url->link('product/uni_featured', 'sort=rating&order=DESC' . $url)
				];

				$data['sorts'][] = [
					'text'  => $this->language->get('text_rating_asc'),
					'value' => 'rating-ASC',
					'href'  => $this->url->link('product/uni_featured', 'sort=rating&order=ASC' . $url)
				];
			}

			$data['sorts'][] = [
				'text'  => $this->language->get('text_model_asc'),
				'value' => 'p.model-ASC',
				'href'  => $this->url->link('product/uni_featured', 'sort=p.model&order=ASC' . $url)
			];

			$data['sorts'][] = [
				'text'  => $this->language->get('text_model_desc'),
				'value' => 'p.model-DESC',
				'href'  => $this->url->link('product/uni_featured', 'sort=p.model&order=DESC' . $url)
			];
			
			if(isset($uniset['catalog']['disabled_sorts']) && isset($data['sorts'])) {
				foreach($data['sorts'] as $key => $sorts) {
					if(in_array(explode('-', $sorts['value'])[0], $uniset['catalog']['disabled_sorts'])) {
						unset($data['sorts'][$key]);
					}
				}
			}
			
			$data['sort'] = $sort;
			$data['order'] = $order;

			$data['continue'] = $this->url->link('common/home');
			
			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');
		
			$this->response->setOutput($this->load->view('product/uni_featured', $data));
		} else {
			$data['breadcrumbs'] = [];
			
			$data['breadcrumbs'][] = [
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home')
			];

			$this->document->setTitle($this->language->get('text_error'));

			$data['continue'] = $this->url->link('common/home');

			$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('error/not_found', $data));
		}
	}
}