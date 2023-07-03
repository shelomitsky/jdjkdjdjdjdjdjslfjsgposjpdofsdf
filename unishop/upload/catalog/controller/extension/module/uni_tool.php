<?php
class ControllerExtensionModuleUniTool extends Controller {
	private $uniset;
	
	public function index() {
		$start = microtime(true); 
		
		$uniset = $this->uniset = $this->config->get('config_unishop2');
		$route = isset($this->request->get['route']) ? $this->request->get['route'] : '';
		$store_id = (int)$this->config->get('config_store_id');
		
		//$dir_template = 'catalog/view/theme/'.$this->config->get('theme_unishop2_directory').'/';
		$dir_template = 'catalog/view/theme/unishop2/';
		$dir_style = $dir_template.'stylesheet/';
		$dir_script = $dir_template.'js/';
		$dir_font = $dir_template.'fonts/';
		
		$data['font_preload'] = [
			'regular' 			=> $dir_font.$uniset['font'].'/'.$uniset['font'].'-regular.woff2',
			'medium' 			=> $dir_font.$uniset['font'].'/'.$uniset['font'].'-medium.woff2',
			'bold' 				=> $dir_font.$uniset['font'].'/'.$uniset['font'].'-bold.woff2',
			//'fa-solid-900' 		=> $dir_font.'fa-solid-900.woff2',
			//'fa-regular-400'	=> $dir_font.'fa-regular-400.woff2'
		];
		
		$generated_style = $dir_style.'generated.'.(int)$this->config->get('config_store_id').'.css';
		
		$rel = 'stylesheet';
		$media = 'screen';
		
		$uni_styles = [
			['href' => $dir_style.'bootstrap.min.css', 'rel' => $rel, 'media' => $media],
			['href' => $dir_style.$uniset['font'].'.css', 'rel' => $rel, 'media' => $media],
			['href' => $dir_style.'stylesheet.css?v='.$uniset['version'], 'rel' => $rel, 'media' => $media],
			['href' => $generated_style.'?v='.$uniset['save_date'], 'rel' => $rel, 'media' => $media],
			['href' => $dir_style.'font-awesome.min.css', 'rel' => $rel, 'media' => $media],
			['href' => $dir_style.'animate.css', 'rel' => $rel, 'media' => $media],
        ];

		$this->setGeneratedStyle($generated_style);
		
		//user css
		$user_style = $dir_style.'generated-user-style.'.$store_id.'.css';
		
		if($uniset['user_css'] && !file_exists($user_style)) {
			file_put_contents($user_style, html_entity_decode($uniset['user_css'], ENT_QUOTES, 'UTF-8'));
		}
		
		if(file_exists($user_style)) {
			$this->document->addStyle($user_style);
		}
		
		if($uniset['custom_style']) {
			$custom_styles = explode(', ', $uniset['custom_style']);
			
			if($custom_styles) {
				foreach($custom_styles as $custom_style) {
					if(file_exists($dir_style.trim($custom_style))) {
						$this->document->addStyle($dir_style.trim($custom_style));
					}
				}
			}
		}
		
		$styles = array_merge($uni_styles, $this->document->getStyles());
		
		$merged_style = $this->getMergedStyle($styles, $route, $dir_style);
		
		if($merged_style) {
			$data['styles'] = $merged_style;
		} else {
			$data['styles'] = $styles;
		}
		
		$uni_scripts = [
			$dir_script.'jquery-2.2.4.min.js',
			$dir_script.'bootstrap.min.js',
			$dir_script.'common.js',
			$dir_script.'menu-aim.min.js',
			$dir_script.'owl.carousel.min.js'
		];
		
		$scripts = array_merge($uni_scripts, $this->document->getScripts());
		
		$merged_script = $this->getMergedScript($scripts, $route, $dir_script);
		
		if($merged_script) {
			$data['scripts'] = $merged_script;
		} else {
			$data['scripts'] = $scripts;
		}
		
		//echo 'Время выполнения: '.round((microtime(true) - $start), 4).' сек.<br />';
		
		return $data;
	}
	
	private function getMergedStyle($styles, $route, $dir_style) {
		$uniset = $this->uniset;
		
		if(!isset($uniset['merge_css'])) {
			return false;
		}
		
		$stop_routes = [
			'extension/module/uni_pwa/fallbackPage',
			'checkout/simplecheckout',
			'checkout/uni_checkout',
			'checkout/checkout'
		];
		
		if(in_array($route, $stop_routes)) {
			return false;
		}
		
		$stop_styles = [
			//'catalog/view/theme/unishop2/stylesheet/slideshow.css'
		];
		
		$merged_file = $dir_style.'merged.'.substr(md5(json_encode($styles).$uniset['save_date']), 0, 10).'.min.css';
		
		$files = [];
		
		$results = [['href' => $merged_file.'?v='.$this->uniset['version'], 'rel' => 'stylesheet', 'media' => 'screen']];
		
		foreach($styles as $style) {
			if (strpos($style['href'], '//') !== false || ($stop_styles && in_array($style['href'], $stop_styles))) {
				$results[] = $style;
			} else {
				$files[] = $style['href'];
			}
		}
		
		if (!file_exists($merged_file)) {
			
			$contents = '';
		
			foreach($files as $filename) {
				if(strpos($filename, 'css?v')) {
					$filename = substr($filename, 0, strpos($filename, 'css?v')+3);
				}
				
				$filename = ltrim($filename, '/');
				
				if(file_exists($filename)) {
					$handle = fopen($filename, "r");
					$contents .= fread($handle, filesize($filename));
					fclose($handle);
				} else {
					$this->log->write('Warning: not found '.$filename);
				}
			}
			
			//stackoverflow.com/questions/15195750/minify-compress-css-with-regex
			//github.com/matthiasmullie/minify
		
			$contents = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $contents);
			$contents = preg_replace('/^\s*/m', '', $contents);
			$contents = preg_replace('/\s*$/m', '', $contents);
			$contents = preg_replace('/\s+/', ' ', $contents);
			$contents = preg_replace('/\s*([\*$~^|]?+=|[{};,>~]|!important\b)\s*/', '$1', $contents);
			//$contents = preg_replace('/([\[(:>\+])\s+/', '$1', $contents);
			//$contents = preg_replace('/\s+([\]\)>\+])/', '$1', $contents);
			$contents = preg_replace('/\s+(:)(?![^\}]*\{)/', '$1', $contents);
			
			$contents = trim($contents);
			
			file_put_contents($merged_file, $contents);
		}
		
		return $results;
	}
	
	private function getMergedScript($scripts, $route, $dir_script) {
		$uniset = $this->uniset;
		
		if(!isset($uniset['merge_js'])) {
			return false;
		}
		
		$stop_routes = [
			'checkout/simplecheckout',
			'checkout/uni_checkout',
			'checkout/checkout'
		];
		
		if(in_array($route, $stop_routes)) {
			return false;
		}
		
		$stop_scripts = [
			//'catalog/view/theme/unishop2/js/login-register.js'
		];
		
		$merged_file = $dir_script.'merged.'.substr(md5(json_encode($scripts).$uniset['save_date']), 0, 10).'.min.js';
		
		$files = [];
		
		$results = [$merged_file];
		
		foreach($scripts as $script) {
			if (strpos($script, '//') !== false || ($stop_scripts && in_array($script, $stop_scripts))) {
				$results[] = $script;
			} else {
				$files[] = $script;
			}
		}
		
		if (!file_exists($merged_file)) {
			
			$contents = '';
			
			foreach($files as $filename) {
				
				if(strpos($filename, 'js?v')) {
					$filename = substr($filename, 0, strpos($filename, 'js?v')+2);
				}
				
				$filename = ltrim($filename, '/');
				
				if(file_exists($filename)) {
					$handle = fopen($filename, "r");
					$data = fread($handle, filesize($filename));
					fclose($handle);
				
					$contents .= $data;
				} else {
					$this->log->write('Warning: not found '.$filename);
				}
			}
			
			//github.com/matthiasmullie/minify
			
			$contents = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $contents);
			$contents = preg_replace('/^\/\/!.+(?:\r\n|\r|\n)/m', '', $contents);
			$contents = preg_replace('/[^\S\n]+/', ' ', $contents);
			$contents = str_replace([" \n", "\n "], "\n", $contents);
			$contents = preg_replace('/\n+/', "\n", $contents);
			$contents = preg_replace('/\breturn\s+(["\'\/\+\-])/', 'return$1', $contents);
			$contents = preg_replace('/\)\s+\{/', '){', $contents);
			$contents = preg_replace('/}\n(else|catch|finally)\b/', '}$1', $contents);
			
			$contents = trim($contents);
			
			file_put_contents($merged_file, $contents);
		}
		
		return $results;
	}
	
	private function setGeneratedStyle($generated_file) {
		$uniset = $this->uniset;
		
		if(file_exists($generated_file)) {
			return false;
		}
		
		if ($this->request->server['HTTPS']) {
			$server = $this->config->get('config_ssl');
		} else {
			$server = $this->config->get('config_url');
		}
			
		$style = '';
		
		$style .= ':root{';
		
		switch($uniset['font']) {
			case 'montserrat':
				$font_family = '\'Montserrat\', \'Courier New\', sans-serif';
				break;
			case 'opensans':
				$font_family = '\'OpenSans\', \'Verdana\', sans-serif';
				break;
			case 'roboto':
				$font_family = '\'Roboto\', \'Tahoma\', sans-serif';
				break;
			case 'rubik':
				$font_family = '\'Rubik\', \'Tahoma\', sans-serif';
				break;
			default:
				$font_family = '';
		}
		
		$style .= '--body-font:'.$font_family.';';
		$style .= '--body-c:#'.$uniset['text_color'].';';
		
		//background image or background color
		$is_background = (isset($uniset['background_image']) && $uniset['background_image'] != '') || (isset($uniset['background_color']) && $uniset['background_color'] != 'fff' && $uniset['background_color'] != 'ffffff') ? true : false;
		
		if($is_background) {
			if($uniset['background_image'] != '') {
				$style .= '--body-bg:url("'.$server.'image/'.$uniset['background_image'].'");';
			}
			
			if(!$uniset['background_image'] && $uniset['background_color'] != 'ffffff') {
				$style .= '--body-bg:#'.$uniset['background_color'].';';
			}
			
			if(isset($uniset['background_old_type'])) {
				$header_bg = '#fff';
				
				$style .= '--main-bg:#fff;';
			}
		}
		
		//basic  elements
		$style .= '--h1-c:#'.$uniset['h1_color'].';';
		$style .= '--h2-c:#'.$uniset['h2_color'].';';
		$style .= '--h3-c:#'.$uniset['h3_color'].';';
		$style .= '--h4-c:#'.$uniset['h4_color'].';';
		$style .= '--h5-c:#'.$uniset['h5_color'].';';
		$style .= '--heading-c:#'.$uniset['h3_heading_color'].';';
		
		//a
		$style .= '--a-c:#'.$uniset['a_color'].';';
		$style .= '--a-c-hover:#'.$uniset['a_color_hover'].';';
		
		//btn
		$style .= '--btn-default-c:#'.$uniset['btn_default_color'].';--btn-default-bg:#'.$uniset['btn_default_bg'].';';
		$style .= '--btn-default-c-hover:#'.$uniset['btn_default_color_hover'].';--btn-default-bg-hover:#'.$uniset['btn_default_bg_hover'].';';
		$style .= '--btn-primary-c:#'.$uniset['btn_primary_color'].';--btn-primary-bg:#'.$uniset['btn_primary_bg'].';';
		$style .= '--btn-primary-c-hover:#'.$uniset['btn_primary_color_hover'].';--btn-primary-bg-hover:#'.$uniset['btn_primary_bg_hover'].';';
		$style .= '--btn-danger-c:#'.$uniset['btn_danger_color'].';--btn-danger-bg:#'.$uniset['btn_danger_bg'].';';
		$style .= '--btn-danger-c-hover:#'.$uniset['btn_danger_color_hover'].';--btn-danger-bg-hover:#'.$uniset['btn_danger_bg_hover'].';';
		
		$style .= '--input-checked-bg:#'.$uniset['checkbox_radiobutton_bg'].';';
		
		$style .= '--input-warning-c:#'.$uniset['text_alert_color'].';--input-warning-border-c:#'.$uniset['text_alert_color'].';';
		
		//rating star
		$style .= '--rating-star-c-active:#'.$uniset['rating_star_color'].';';
		
		//top menu
		$style .= '--top-menu-bg:#'.$uniset['top_menu_bg'].';';
		$style .= '--top-menu-btn-c:#'.$uniset['top_menu_color'].';';
		$style .= '--top-menu-btn-c-hover:#'.$uniset['top_menu_color_hover'].';';
		
		//header bg
		if(isset($uniset['header']['bg']['status'])) {
			if($uniset['header']['bg']['img'] != '') {
				$header_bg = 'url("/image/'.$uniset['header']['bg']['img'].'")';
			} else {
				$header_bg = '#'.$uniset['header']['bg']['color'];
			}
		}
		
		if(!isset($header_bg)) {
			$header_bg = 'transparent';
		}
		
		$style .= '--header-bg:'.$header_bg.';';
		
		//search block
		$style .= '--header-search-cat-btn-c:#'.$uniset['header']['search']['color']['btn_color'].';--header-search-cat-btn-bg:#'.$uniset['header']['search']['color']['btn_bg'].';';
		$style .= '--header-search-input-c:#'.$uniset['header']['search']['color']['input_color'].';--header-search-input-bg:#'.$uniset['header']['search']['color']['input_bg'].';';
		
		//phone block
		$style .= '--header-phones-m-c:#'.$uniset['header']['contacts']['color']['main_phone'].';';
		$style .= '--header-phones-m-c-hover:#'.$uniset['header']['contacts']['color']['main_phone_hover'].';';
		$style .= '--header-phones-a-c:#'.$uniset['additional_phone_color'].';';
		
		//account & wishlist & compare & cart block
		$style .= '--header-icon-c:#'.$uniset['header']['icon']['color']['icon_color'].';';
		$style .= '--header-icon-total-c:#'.$uniset['header']['icon']['color']['total_color'].';--header-icon-total-bg:#'.$uniset['header']['icon']['color']['total_bg'].';';
		
		//main menu
		if($uniset['menu_type'] == 1) {
			$style .= '--menu-main-c:#'.$uniset['main_menu_parent_color'].';--menu-main-bg:#'.$uniset['main_menu_parent_bg'].';';
			$style .= '--menu-main-header-c:#'.$uniset['main_menu_color'].';--menu-main-header-bg:#'.$uniset['main_menu_bg'].';';
			$style .= '--menu-main-level-1-c:#'.$uniset['main_menu_parent_color'].';';
			$style .= '--menu-main-level-1-c-hover:#'.$uniset['main_menu_parent_color_hover'].';';
			$style .= '--menu-main-level-2-c:#'.$uniset['main_menu_children_color'].';';
			$style .= '--menu-main-level-2-c-hover:#'.$uniset['main_menu_children_color_hover'].';';
			$style .= '--menu-main-level-2-bg:#'.$uniset['main_menu_children_bg'].';';
			$style .= '--menu-main-level-3-c:#'.$uniset['main_menu_children_color2'].';';
			$style .= '--menu-main-level-3-c-hover:#'.$uniset['main_menu_children_color2_hover'].';';
			$style .= '--menu-main-before:#'.$uniset['right_menu']['bg'].';';
		}
		
		//main menu type2
		if($uniset['menu_type'] == 2) {
			$style .= '--menu-main-c:#'.$uniset['main_menu2_color'].';--menu-main-bg:#'.$uniset['main_menu2_bg'].';';
			$style .= '--menu-main-header-c:#'.$uniset['main_menu2_color'].';--menu-main-header-bg:#'.$uniset['main_menu2_bg'].';';
			$style .= '--menu-main-level-1-c:#'.$uniset['main_menu2_color'].';';
			$style .= '--menu-main-level-1-c-hover:#'.$uniset['main_menu2_color'].';';
			$style .= '--menu-main-level-2-c:#'.$uniset['main_menu2_children_color'].';';
			$style .= '--menu-main-level-2-c-hover:#'.$uniset['main_menu2_children_color_hover'].';';
			$style .= '--menu-main-level-2-bg:#'.$uniset['main_menu2_children_bg'].';';
			$style .= '--menu-main-level-3-c:#'.$uniset['main_menu2_children_color2'].';';
			$style .= '--menu-main-level-3-c-hover:#'.$uniset['main_menu2_children_color2_hover'].';';
			$style .= '--menu-main-before:#'.$uniset['main_menu2_bg'].';';
		}
		
		//right menu
		if($uniset['menu_type'] == 1) {
			$style .= '--menu-right-bg:#'.$uniset['right_menu']['bg'].';';
			$style .= '--menu-right-level-1-c:#'.$uniset['right_menu']['col'].';';
			$style .= '--menu-right-level-1-c-hover:#'.$uniset['right_menu']['col_hov'].';';
			$style .= '--menu-right-level-2-c:#'.$uniset['right_menu']['child_col'].';';
			$style .= '--menu-right-level-2-c-hover:#'.$uniset['right_menu']['child_col_hov'].';';
			$style .= '--menu-right-level-2-bg:#'.$uniset['right_menu']['child_bg'].';';
			$style .= '--menu-right-level-3-c:#'.$uniset['right_menu']['child2_col'].';';
			$style .= '--menu-right-level-3-c-hover:#'.$uniset['right_menu']['child2_col_hov'].';';
		}
		
		//sidebar menu
		$style .= '--menu-module-bg:#'.$uniset['sidebar_menu']['bg'].';';
		$style .= '--menu-module-c:#'.$uniset['sidebar_menu']['color'].';';
		$style .= '--menu-module-c-hover:#'.$uniset['sidebar_menu']['color_active'].';';
		
		//unislideshow
		$style .= '--slideshow-title-c:#'.$uniset['unislideshow_title_color'].';';
		$style .= '--slideshow-text-c:#'.$uniset['unislideshow_text_color'].';';
		$style .= '--slideshow-btn-c:#'.$uniset['unislideshow_button_color'].';--slideshow-btn-bg:#'.$uniset['unislideshow_button_bg'].';';
		$style .= '--slideshow-nav-btn-c:#'.$uniset['unislideshow_nav_bg_active'].';';
		$style .= '--slideshow-dot-bg:#'.$uniset['unislideshow_nav_bg'].';';
		$style .= '--slideshow-dot-bg-active:#'.$uniset['unislideshow_nav_bg_active'].';';
		
		//swiper
		$style .= '--swiper-pagination-bg:#'.$uniset['slideshow_pagination_bg'].';';
		$style .= '--swiper-pagination-bg-active:#'.$uniset['slideshow_pagination_bg_active'].';';
		
		//home text banners
		$style .= '--home-banner-bg:#'.$uniset['home']['text_banner']['color']['bg'].';';
		$style .= '--home-banner-icon-c:#'.$uniset['home']['text_banner']['color']['icon'].';';
		$style .= '--home-banner-text-c:#'.$uniset['home']['text_banner']['color']['text'].';';
		
		//stock indicator
		if(isset($uniset['show_stock_indicator']) && $uniset['show_stock_indicator'] > 0) {
			$style .= '--qty-indicator-5:#'.$uniset['stock_i_c_5'].';';
			$style .= '--qty-indicator-4:#'.$uniset['stock_i_c_4'].';';
			$style .= '--qty-indicator-3:#'.$uniset['stock_i_c_3'].';';
			$style .= '--qty-indicator-2:#'.$uniset['stock_i_c_2'].';';
			$style .= '--qty-indicator-1:#'.$uniset['stock_i_c_1'].';';
			$style .= '--qty-indicator-0:#'.$uniset['stock_i_c_0'].';';
		}
		
		//special timer
		$style .= '--timer-bg:#'.$uniset['special_timer_bg'].';';
		$style .= '--timer-text-c:#'.$uniset['special_timer_text_color'].';';
		$style .= '--timer-digit-c:#'.$uniset['special_timer_color'].';';
		
		//product-thumb
		$style .= '--prod-thumb-name:#'.$uniset['product_thumb_h4_color'].';';
		$style .= '--prod-thumb-name-hover:#'.$uniset['product_thumb_h4_color_hover'].';';
		if(isset($uniset['show_quick_order_always'])) {
			$style .= '--prod-thumb-quick-order-opacity:1;';
		}
		
		//option
		$style .= '--option-select-c:#'.$uniset['options']['color']['color'].';';
		$style .= '--option-name-c:#'.$uniset['options']['color']['color'].';--option-name-bg:#'.$uniset['options']['color']['bg'].';';
		$style .= '--option-name-c-hover:solid 1px #'.$uniset['options']['color']['bg_active'].';';
		$style .= '--option-name-c-checked:#'.$uniset['options']['color']['color_active'].';--option-name-bg-checked:#'.$uniset['options']['color']['bg_active'].';';
		$style .= '--option-img-hover:#'.$uniset['options']['color']['bg_active'].';';
		$style .= '--option-popup-img-w:'.$uniset['options']['popup_img_width'].'px;';
		
		//price
		$style .= '--price-c:#'.$uniset['price_color'].';';
		$style .= '--price-old-c:#'.$uniset['price_color_old'].';';
		$style .= '--price-new-c:#'.$uniset['price_color_new'].';';
		
		//cart btn
		$style .= '--add-to-cart-btn-c:#'.$uniset['cart_btn_color'].';--add-to-cart-btn-bg:#'.$uniset['cart_btn_bg'].';';
		$style .= '--add-to-cart-btn-c-hover:#'.$uniset['cart_btn_color_hover'].';--add-to-cart-btn-bg-hover:#'.$uniset['cart_btn_bg_hover'].';';
		$style .= '--add-to-cart-btn-c-incart:#'.$uniset['cart_btn_color_incart'].';--add-to-cart-btn-bg-incart:#'.$uniset['cart_btn_bg_incart'].';';
		$style .= '--add-to-cart-btn-c-qty0:#'.$uniset['cart_btn_color_disabled'].';--add-to-cart-btn-bg-qty0:#'.$uniset['cart_btn_bg_disabled'].';';	
		$style .= '--add-to-cart-btn-c-disabled:#'.$uniset['cart_btn_color_disabled'].';--add-to-cart-btn-bg-disabled:#'.$uniset['cart_btn_bg_disabled'].';';	
		
		//quick order btn
		$style .= '--quick-order-btn-c:#'.$uniset['quick_order_btn_color'].';--quick-order-btn-bg:#'.$uniset['quick_order_btn_bg'].';';
		$style .= '--quick-order-btn-c-hover:#'.$uniset['quick_order_btn_color_hover'].';--quick-order-btn-bg-hover:#'.$uniset['quick_order_btn_bg_hover'].';';
		
		//wishlist&compare btn
		$style .= '--wishlist-btn-c:#'.$uniset['wishlist']['btn_color'].';--wishlist-btn-bg:#'.$uniset['wishlist']['btn_bg'].';';
		$style .= '--wishlist-btn-c-hover:#'.$uniset['wishlist']['btn_color_hover'].';--wishlist-btn-bg-hover:#'.$uniset['wishlist']['btn_bg_hover'].';';
		$style .= '--compare-btn-c:#'.$uniset['compare']['btn_color'].';--compare-btn-bg:#'.$uniset['compare']['btn_bg'].';';
		$style .= '--compare-btn-c-hover:#'.$uniset['compare']['btn_color_hover'].';--compare-btn-bg-hover:#'.$uniset['compare']['btn_bg_hover'].';';
		
		//stickers
		$style .= '--sticker-reward-c:#'.$uniset['sticker_reward_text_color'].';--sticker-reward-b:#'.$uniset['sticker_reward_background_color'].';';
		$style .= '--sticker-special-c:#'.$uniset['sticker_special_text_color'].';--sticker-special-b:#'.$uniset['sticker_special_background_color'].';';
		$style .= '--sticker-bestseller-c:#'.$uniset['sticker_bestseller_text_color'].';--sticker-bestseller-b:#'.$uniset['sticker_bestseller_background_color'].';';
		$style .= '--sticker-new-c:#'.$uniset['sticker_new_text_color'].';--sticker-new-b:#'.$uniset['sticker_new_background_color'].';';
		
		if(isset($uniset['sku_as_sticker'])) {
			$style .= '--sticker-sku-c:#'.$uniset['sticker_sku_text_color'].';--sticker-sku-b:#'.$uniset['sticker_sku_background_color'].';';
		}
		
		if(isset($uniset['upc_as_sticker'])) {
			$style .= '--sticker-upc-c:#'.$uniset['sticker_upc_text_color'].';--sticker-upc-b:#'.$uniset['sticker_upc_background_color'].';';
		}
		
		if(isset($uniset['ean_as_sticker'])) {
			$style .= '--sticker-ean-c:#'.$uniset['sticker_ean_text_color'].';--sticker-ean-b:#'.$uniset['sticker_ean_background_color'].';';
		}
		
		if(isset($uniset['jan_as_sticker'])) {
			$style .= '--sticker-jan-c:#'.$uniset['sticker_jan_text_color'].';--sticker-jan-b:#'.$uniset['sticker_jan_background_color'].';';
		}
		
		if(isset($uniset['isbn_as_sticker'])) {
			$style .= '--sticker-isbn-c:#'.$uniset['sticker_isbn_text_color'].';--sticker-isbn-b:#'.$uniset['sticker_isbn_background_color'].';';
		}
		
		if(isset($uniset['mpn_as_sticker'])) {
			$style .= '--sticker-mpn-c:#'.$uniset['sticker_mpn_text_color'].';--sticker-mpn-b:#'.$uniset['sticker_mpn_background_color'].';';
		}
		
		//product text banners
		$style .= '--product-banner-bg:#'.$uniset['product']['text_banner']['color']['bg'].';';
		$style .= '--product-banner-icon:#'.$uniset['product']['text_banner']['color']['icon'].';';
		$style .= '--product-banner-text:#'.$uniset['product']['text_banner']['color']['text'].';';
		
		//tabs
		$style .= '--nav-tabs-bg:#'.$uniset['tabs']['bg'].';';
		$style .= '--nav-tabs-c:#'.$uniset['tabs']['color'].';';
		$style .= '--nav-tabs-c-active:#'.$uniset['tabs']['color_active'].';';
		
		if($uniset['tabs']['mobile']['without_scroll']) {
			$style .= '--nav-tabs-flex-wrap:wrap;';
		}
		
		//carousel
		$style .= '--carousel-dot-bg:#'.$uniset['carousel']['dots']['bg'].';';
		$style .= '--carousel-dot-bg-active:#'.$uniset['carousel']['dots']['bg_active'].';';
		$style .= '--carousel-nav-btn-c:#'.$uniset['carousel']['nav']['color'].';--carousel-nav-btn-bg:#'.$uniset['carousel']['nav']['bg'].';';
		
		//pagination
		$style .= '--pagination-c:#'.$uniset['pagination_color'].';--pagination-bg:#'.$uniset['pagination_bg'].';';
		$style .= '--pagination-c-active:#'.$uniset['pagination_color_active'].';--pagination-bg-active:#'.$uniset['pagination_bg_active'].';';
		
		//footer
		$style .= '--footer-c:#'.$uniset['footer_text_color'].';--footer-bg:#'.$uniset['footer_bg'].';';
		$style .= '--footer-heading-c:#'.$uniset['footer_h5_color'].';';
		
		//subscribe
		if(isset($uniset['show_subscribe'])) {
			$style .= '--subscribe-info-c:#'.$uniset['subscribe_text_color'].';';
			$style .= '--subscribe-points-c:#'.$uniset['subscribe_points_color'].';';
			$style .= '--subscribe-input-c:#'.$uniset['subscribe_input_color'].';--subscribe-input-bg:#'.$uniset['subscribe_input_bg'].';';
			$style .= '--subscribe-btn-c:#'.$uniset['subscribe_button_color'].';--subscribe-btn-bg:#'.$uniset['subscribe_button_bg'].';';
		}
		
		//fly menu
		if(isset($uniset['fly_menu']['desktop']) || isset($uniset['fly_menu']['mobile'])) {
			$style .= '--fly-menu-bg:#'.$uniset['fly_menu']['color']['bg'].';';
			$style .= '--fly-menu-icon-c:#'.$uniset['fly_menu']['color']['icon_color'].';';
			$style .= '--fly-menu-icon-total-c:#'.$uniset['fly_menu']['color']['total_color'].';';
			$style .= '--fly-menu-icon-total-bg:#'.$uniset['fly_menu']['color']['total_bg'].';';
			$style .= '--fly-menu-search-cat-btn-bg:#'.$uniset['fly_menu']['search']['color']['btn_bg'].';';
			$style .= '--fly-menu-search-cat-btn-color:#'.$uniset['fly_menu']['search']['color']['btn_color'].';';
			$style .= '--fly-menu-search-input-bg:#'.$uniset['fly_menu']['search']['color']['input_bg'].';';
			$style .= '--fly-menu-search-input-color:#'.$uniset['fly_menu']['search']['color']['input_color'].';';
			$style .= '--fly-menu-label-c:#'.$uniset['fly_menu']['label']['color'].';';
			
			if($uniset['menu_type'] == 1) {
				$style .= '--fly-menu-level-1-bg-hover:#'.$uniset['main_menu_children_bg'].';';
			} else {
				$style .= '--fly-menu-level-1-bg-hover:rgba(0, 0, 0, .05);';
			}
		}
		
		//fly wishlist & compare
		if(isset($uniset['wishlist']['fly_btn'])) {
			$style .= '--fly-wishlist-c:#'.$uniset['wishlist']['fly_btn_color'].';--fly-wishlist-bg:#'.$uniset['wishlist']['fly_btn_bg'].';';
		}
		
		if(isset($uniset['compare']['fly_btn'])) {
			$style .= '--fly-compare-c:#'.$uniset['compare']['fly_btn_color'].';--fly-compare-bg:#'.$uniset['compare']['fly_btn_bg'].';';
		}
		
		//fly callback button
		$style .= '--fly-callback-c:#'.$uniset['fly_callback_color'].';--fly-callback-bg:#'.$uniset['fly_callback_bg'].';';
		
		//notification
		$notification = isset($uniset['notification']) ? $uniset['notification'] : [];
		
		if($notification && $notification['status']) {
			$style .= '--notification-body-bg:#'.$notification['bg'].';';
			$style .= '--notification-text-c:#'.$notification['color'].';';
			$style .= '--notification-btn-cancel-c:#'.$notification['color'].';';
		}
		
		//topstripe
		if(isset($uniset['topstripe']['status'])) {
			$style .= '--topstripe-c:#'.$uniset['topstripe']['color'].';--topstripe-bg:#'.$uniset['topstripe']['bg'].';';
		}
		
		//pwa
		if(isset($uniset['pwa']['status'])) {
			$style .= '--pwa-c:#'.$uniset['pwa']['banner']['color'].';--pwa-bg:#'.$uniset['pwa']['banner']['bg'].';';
			$style .= '--pwa-install-c:#'.$uniset['pwa']['banner']['color_btn'].';--pwa-install-bg:#'.$uniset['pwa']['banner']['color_btn_bg'].';';
			$style .= '--pwa-close-c:#'.$uniset['pwa']['banner']['color_btn_bg'].';';
		}
		
		//alerts
		$style .= '--alert-success-c:#'.$uniset['alert']['success']['color'].';--alert-success-bg:#'.$uniset['alert']['success']['bg'].';';
		$style .= '--alert-warning-c:#'.$uniset['alert']['warning']['color'].';--alert-warning-bg:#'.$uniset['alert']['warning']['bg'].';';
		$style .= '--alert-danger-c:#'.$uniset['alert']['danger']['color'].';--alert-danger-bg:#'.$uniset['alert']['danger']['bg'].';';
		
		//preloader
		$style .= '--preloader-border-c:#'.$uniset['a_color'].' #'.$uniset['a_color'].' #'.$uniset['a_color'].' transparent;';
		
		//tooltip
		$style .= '--tooltip-c:#'.$uniset['tooltip_color'].';--tooltip-bg:#'.$uniset['tooltip_bg'].';';
		
		$style .= '}';
		
		//custom menu
		/*
		$style .= '#custom_menu .nav{background:#'.$uniset['main_menu_parent_bg'].'}';
		$style .= '#custom_menu .nav > li > a, #custom_menu .nav li > .visible-xs i{color:#'.$uniset['main_menu_parent_color'].'}';
		$style .= '#custom_menu .nav > li:hover > a, #custom_menu .nav > li:hover > .visible-xs i{color:#'.$uniset['main_menu_parent_color_hover'].'}';
		$style .= '#custom_menu .nav > li > .dropdown-menu{background:#'.$uniset['main_menu_children_bg'].'}';
		$style .= '#custom_menu .nav > li:hover{background:#'.$uniset['main_menu_children_bg'].'}';
		$style .= '#custom_menu .nav > li.has_chidren:hover:before{background:#'.$uniset['main_menu_children_bg'].'}';
		$style .= '#custom_menu .nav > li ul > li > a{color:#'.$uniset['main_menu_children_color'].'}';
		$style .= '#custom_menu .nav > li ul li ul > li > a{color:#'.$uniset['main_menu_children_color2'].'}';
		*/
		
		//cat description
		$style .= $uniset['catalog']['cat_description']['position'] == 'bottom' ? '.category-page.category-info, .manufacturer-page.category-info{display:none}' : '';
		$style .= $uniset['catalog']['cat_description']['height'] > 0 ? '.category-page.category-info, .manufacturer-page.category-info{max-height:'.$uniset['catalog']['cat_description']['height'].'px}' : '';
		
		if(isset($uniset['catalog']['img_bg'])) {
			$style .= '.product-thumb__image a:before{position:absolute;top:-15px;bottom:0;left:-15px;right:-15px;content:"";background:#000;opacity:.03}';
			$style .= '.list-view .product-thumb__image, .compact-view .product-thumb__image{margin-right:15px}';
			$style .= '.list-view .product-thumb__image a:before, .compact-view .product-thumb__image a:before{bottom:-15px}';
		}
		
		if(isset($uniset['show_quick_order'])) {
			if(isset($uniset['show_quick_order_text'])) {
				$style .= '.product-thumb__cart{flex-wrap:wrap}';
				$style .= '.uni-module .product-thumb__add-to-cart, .grid-view .product-thumb__add-to-cart {flex:1 1 auto}';
				$style .= '.uni-module .product-thumb__quick-order, .grid-view .product-thumb__quick-order {flex:1 1 100%;margin:15px 0 0;opacity:1}';
			}
			
			if($uniset['catalog']['items_per_row_on_mobile'] == 2) {
				if(isset($uniset['show_quick_order_text'])) {
					$style .= '@media (max-width:374px){';
					$style .= '.product-thumb .qty-switch{display:none}';
					$style .= '}';
					$style .= '@media (max-width:424px){';
					$style .= '.uni-module .qty-switch, .grid-view .qty-switch{display:none}';
					$style .= '}';
				} else {
					$style .= '@media (max-width:424px){';
					$style .= '.product-thumb .qty-switch{display:none}';
					$style .= '}';
					$style .= '@media (max-width:484px){';
					$style .= '.uni-module .qty-switch, .grid-view .qty-switch{display:none}';
					$style .= '}';
				}
			} else {
				if(!isset($uniset['show_quick_order_text'])) {
					$style .= '@media (max-width:484px){';
					$style .= '.list-view .qty-switch{display:none}';
					$style .= '}';
				}
			}
		} else {
			$style .= '@media (max-width:374px){';
			$style .= '.uni-module .qty-switch, .grid-view .qty-switch{display:none}';
			$style .= '}';		
		}
		
		//ocfilter
		if($this->config->get('module_ocfilter_status')) {
			$style .= '.ocf-noUi-connect:before, .ocf-noUi-handle{background:#'.$uniset['checkbox_radiobutton_bg'].'}';
		}
		
		//blur on hover menu
		if($uniset['main_menu_blur']) {
			$style .= 'main:after, footer:after{display:block;position:absolute;z-index:9;-webkit-transform:translate3d(0, 0, 0);content:"";opacity:0;transition:opacity linear .15s}';
			$style .= 'main.blur:after, footer.blur:after {top:0;bottom:0;left:50%;width:100vw;transform:translate(-50%);background:#'.($uniset['main_menu_blur'] == 1 ? 'fff' : '000').';opacity:.5}';
		}
		
		//max-width 1200
		$style .= '@media (max-width:1200px){';
			if(isset($uniset['show_quick_order']) && isset($uniset['show_quick_order_text'])) {
				$style .= '.product-thumb__quick-order i{display:none}';
				$style .= '.product-thumb__quick-order span{margin:0 !important}';
			}
		$style .= '}';
		
		//max-width 992
		$style .= '@media (max-width:992px){';
			$style .= ':root{';
			
			if(isset($uniset['header']['bg']['status'])) {
				$style .= '--header-padding-bottom:15px;';
			}
			
			$style .= '}';
			
			if(isset($uniset['show_quick_order_text'])) {
				$style .= '.list-view .product-thumb__add-to-cart {flex:1 1 auto}';
				$style .= '.list-view .product-thumb__quick-order {flex:1 1 100%;margin:15px 0 0;opacity:1}';
			}
			
			if(isset($uniset['fly_menu']['mobile'])) {
				$style .= '.fly-block__wishlist, .fly-block__compare{display:none}';
			}
		$style .= '}';
		
		//max-width 767
		$style .= '@media (max-width:767px){';
			$style .= ':root{';
			
			$style .= '--body-bg:#fff;';
			
			if(isset($uniset['header']['bg']['status']) && $uniset['header']['bg']['img'] == '' && $uniset['header']['bg']['color'] == 'ffffff') {
				$style .= '--header-padding-bottom:0;';
			}
				
			if(isset($uniset['breadcrumbs']['hide']['mobile'])) {
				$style .= '--breadcrumb-mobile-display:none;';
			}
				
			$style .= '}';
			
			if($uniset['catalog']['items_per_row_on_mobile'] == 1) {
				$style .= '.grid-view{flex:0 1 100%;max-width:100%;padding:0 10px}';
			}
			
			$nav_tabs = $uniset['tabs']['mobile']['without_scroll'];
			
			if($nav_tabs && $nav_tabs == 1) {
				$style .= '.product-page-tabs{flex-direction:column}';
				$style .= '.nav-tabs li{height:44px;padding:0 15px}';
				$style .= '.product-page-tabs li:not(:first-child){border-top:solid 1px rgba(0, 0, 0, .07)}';
				$style .= '.product-page-tabs li a:after{display:none}';
				$style .= '.nav-tabs .uni-badge{position:absolute;right:0}';
			} else {
				$style .= '.nav-tabs{margin-left:-15px;margin-right:-15px;border-radius:0}';
				$style .= '.product-page-tabs{position:sticky;top:0;z-index:1029}';
			}
			
			if(isset($uniset['hide_fly_callback'])) {
				$style .= '.fly-block__callback{display:none}';
			}	
		$style .= '}';
		
		//max-width 575
		$style .= '@media (max-width:575px){';
		
			$style .= ':root{';
			
			if($uniset['catalog']['items_per_row_on_mobile'] == 1) {
				$style .= '--prod-thumb-model-before-display:inline;';
			}
			
			if($uniset['show_stock_indicator'] == 3 || $uniset['catalog']['items_per_row_on_mobile'] == 1) {
				$style .= '--prod-thumb-indicator-before-display:inline;';
			}
			
			$style .= '}';
		
			if(isset($uniset['catalog']['prod_name']['clamp']) && $uniset['catalog']['prod_name']['clamp'] > 0) {
				$style .= '.product-thumb__name{overflow:hidden;padding:0 !important;display:-webkit-box;-webkit-line-clamp:'.(int)$uniset['catalog']['prod_name']['clamp'].';-webkit-box-orient:vertical;text-overflow:ellipsis}';
				$style .= '.product-thumb__name:after{display:block;content:"";height:10px;background:#fff}';
			}
			
			if($uniset['catalog']['items_per_row_on_mobile'] == 2) {
				$style .= '.grid-view:nth-child(odd){padding-right:5px}.grid-view:nth-child(even){padding-left:5px}';
			}
			
			if(isset($uniset['catalog']['img_bg'])) {
				$style .= '.product-thumb__image a:before{top:-10px;left:-10px;right:-10px}';
				$style .= '.list-view .product-thumb__image{margin-right:10px}';
				$style .= '.list-view .product-thumb__image a:before{bottom:-10px}';
			}
		
			if(isset($uniset['show_quick_order_text_product'])) {
				$style .= '.product-page__add-to-cart{flex:1 1 auto;margin-right:0 !important}';
				$style .= '.product-page__quick-order{flex:1 1 100%;margin:15px 0 0 !important}';
				$style .= '.product-page__quick-order span{display:inline !important}';
			}
		$style .= '}';
		
		//min-width 767 
		$style .= '@media (min-width:767px){';
			if($is_background) {
				if(isset($uniset['background_old_type'])) {
					$style .= 'header, main, footer{margin:0 auto} .uni-wrapper{padding:0 5px} .main-menu:before{display:none}';
				} else {
					$style .= '.uni-wrapper{margin:0 0 30px;padding:20px;background:#fff;border-radius:4px}';
				}
			}
		$style .= '}';
		
		//min-width 992 
		$style .= '@media (min-width:992px){';
			if(isset($uniset['catalog']['description_hover'])) {
				$style .= 'body:not(.touch-support) .product-thumb .description{display:none}';
				$style .= 'body:not(.touch-support) .product-thumb .attribute{display:block}';
			}
		
			if(isset($uniset['catalog']['attr_hover'])) {
				$style .= 'body:not(.touch-support) .product-thumb .attribute{display:none}';
			}
		
			if(isset($uniset['catalog']['option_hover'])) {
				$style .= 'body:not(.touch-support) .product-thumb .option{display:none}';
			}
			
			if(isset($uniset['main_menu_sec_lev_pos'])) {
				$style .= '.menu1 .menu__level-1-li{position:static}';
				$style .= '.menu1:not(.new) .menu__level-2{min-height:100%}';
			}
			
		$style .= '}';
		
		//min-width 1180
		$style .= '@media (min-width:1180px){';
			if(isset($uniset['header']['icon']['captions']['status'])) {
				$style .= '.header-block__item-account, .header-block__item-wishlist, .header-block__item-compare{padding:0 25px}';
				$style .= '.header-block__item-cart {padding:0 15px 0 25px}';
				$style .= '.header-account, .header-wishlist, .header-compare, .header-cart{position:relative;margin-top:-14px}';
				$style .= '.header-wishlist__total-items, .header-compare__total-items, .header-cart__total-items{top:0;right:-10px}';
				$style .= '.header-account:after, .header-wishlist:after, .header-compare:after, .header-cart:after{position:absolute;left:50%;transform:translateX(-50%);display:block;margin:4px 0 0;content:attr(title);font-size:.7em;color:#'.$uniset['header']['icon']['color']['captions_color'].'}';
			}
		$style .= '}';
		
		$arr_from = ['#ffffff', '#000000', '#111111', '#222222', '#333333', '#444444', '#555555', '#666666', '#777777', '#888888', '#999999'];
		$arr_to = ['#fff', '#000', '#111', '#222', '#333', '#444', '#555', '#666', '#777', '#888', '#999'];
		
		$style = str_replace($arr_from, $arr_to, $style);
			
		file_put_contents($generated_file, $style);
	}
}
?>