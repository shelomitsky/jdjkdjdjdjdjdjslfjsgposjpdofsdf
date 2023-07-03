<?php
class ControllerExtensionModuleUniNews extends Controller {
	public function index($setting) {
		static $module = 0;
		
		$this->load->language('extension/module/uni_othertext');
		$this->load->language('extension/module/uni_news');
		
		$this->load->model('extension/module/uni_news');
		$this->load->model('tool/image');
		
		$lang_id = $this->config->get('config_language_id');
		$store_id = (int)$this->config->get('config_store_id');
		
		$this->document->addStyle('catalog/view/theme/unishop2/stylesheet/news.css');
		
		$data['heading_title'] = $setting['title'][$lang_id] ? $setting['title'][$lang_id] : $this->language->get('heading_title');
		$data['type_view'] = isset($setting['view_type']) ? 'grid' : 'carousel';
		
		$data['img_width'] = $thumb_width = $setting['thumb_width'];
		$data['img_height'] = $thumb_height = $setting['thumb_height'];
		
		$category_id = isset($setting['category']) ? $setting['category'] : 0;
		
		$cache_name = 'unishop.news.short.'.$category_id.'.'.$lang_id.'.'.$store_id;

		$news = $this->cache->get($cache_name);
			
		if(!$news) {
			$filter_data = [
				'filter_category_id' => $category_id,
				'filter_sub_category'=> isset($setting['sub_category']) ? true : false,
				'limit'				 => $setting['limit'],
				'start'				 => 0,
			];
			
			$news = $this->model_extension_module_uni_news->getNews($filter_data);
				
			if($news) {
				$this->cache->set($cache_name, $news);
			}
		}
		
		$data['news'] = [];
		
		if($news) {
			$cache_name2 = 'unishop.news.short.categories.'.$lang_id.'.'.$store_id;
			
			$news_category = $this->cache->get($cache_name2);
			
			foreach ($news as $result) {
				if ($result['image']) {
					$image = $this->model_tool_image->resize($result['image'], $thumb_width, $thumb_height);
				} else {
					$image = $this->model_tool_image->resize('placeholder.png', $thumb_width, $thumb_height);
				}
			
				$category_id = $result['category_id'];
			
				if(!isset($news_category[$category_id])) {
					$news_category[$category_id] = $this->model_extension_module_uni_news->getCategory($category_id);
					
					$this->cache->set($cache_name2, $news_category);
				}
				
				$category_info = [
					'name' => $news_category[$category_id]['name'],
					'href' => $this->url->link('information/uni_news', 'news_path='.$category_id, true)
				];
				
				if(isset($setting['numchars']) && $setting['numchars'] > 0) {
					$description = utf8_substr(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')), 0, isset($setting['numchars']) ? $setting['numchars'] : 200) . '..';
				} else {
					$description = '';
				}
				
				$data['news'][] = [
					'name'        	=> $result['name'],
					'image'			=> $image,
					'description'	=> $description,
					'href'         	=> $this->url->link('information/uni_news_story', 'news_id='.$result['news_id'], true),
					'category_name' => $category_info['name'],
					'category_href' => $category_info['href'],
					'viewed'   		=> $result['viewed'],
					'posted'   		=> date($this->language->get('date_format_short'), strtotime($result['date_added'])),
				];
			}
		}
		
		$data['module'] = $module++;

		return $this->load->view('extension/module/uni_news', $data);
	}
}
?>