<?php
class ControllerProductUniSpecial extends Controller {
	private $uniset = [];
	
	public function index() {
		$uniset = $this->config->get('config_unishop2');
		$lang_id = $this->config->get('config_language_id');
		
		$this->uniset = $uniset;
		
		$this->load->model('extension/module/uni_special');
		$this->load->language('product/uni_special');
		
		$category_id = isset($this->request->get['cat_id']) ? (int)$this->request->get['cat_id'] : 0;
		
		$data['specials_href'] = $this->url->link('product/special', '', true);
		$data['category_id'] = $category_id;
		$data['product_categories'] = $this->getProductCategories($category_id);
		
		//if($category_id && in_array($category_id, array_column($data['product_categories'], 'category_id'))) {
		//	$this->document->addLink($this->url->link('product/special', 'cat_id='.(int)$this->request->get['cat_id']), 'canonical');
		//}
		
		$special_page = $uniset['catalog']['special_page'];
			
		$page_heading = isset($special_page['heading'][$lang_id]) ? $special_page['heading'][$lang_id] : $this->language->get('text_heading_title');
		$page_title = isset($special_page['title'][$lang_id]) && $special_page['title'][$lang_id] != '' ? $special_page['title'][$lang_id] : $page_heading;
		$page_description = isset($special_page['description'][$lang_id]) ? $special_page['description'][$lang_id] : '';
			
		$this->document->setTitle($page_title);
		$this->document->setDescription($page_description);
		
		$data['heading_title'] = $page_heading;
		
		$data['product_categories'] = [];
		
		if(isset($special_page['product_category'])) {
			
			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}
			
			$categories = $this->model_extension_module_uni_special->getProductCategories();
			
			if($categories) {
				foreach ($categories as $category) {
					$data['product_categories'][$category['category_id']] = [
						'category_id'  	=> $category['category_id'],
						'name'			=> $category['name'],
						'selected'		=> $category['category_id'] == $category_id ? true : false,
						'href'   		=> $this->url->link('product/special', '&cat_id='.(int)$category['category_id'] . $url, true)
					];
				}
				
				if(isset($data['product_categories'][$category_id])) {
					$data['heading_title'] = $page_heading.' - '.$data['product_categories'][$category_id]['name'];
								
					$data['breadcrumbs_new'][] = [
						'text' => $data['product_categories'][$category_id]['name'],
						'href' => $data['product_categories'][$category_id]['href']
					];
				}
			}
		}
		
		return $data;
	}
	
	private function getProductCategories($category_id) {
		$uniset = $this->uniset;
		
		$result = [];
		
		
	}
}