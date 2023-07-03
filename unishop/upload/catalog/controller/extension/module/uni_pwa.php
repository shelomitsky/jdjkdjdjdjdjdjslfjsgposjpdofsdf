<?php
class ControllerExtensionModuleUniPwa extends Controller {
	public function index() {
		$uniset = $this->config->get('config_unishop2');
		$store_id = (int)$this->config->get('config_store_id');
		$lang_id = (int)$this->config->get('config_language_id');
		
		$data['pwa_notification'] = [];
		
		if(!isset($uniset['pwa']['status']) || !$uniset['pwa']['icon'] || !$this->request->server['HTTPS']) {
			return;
		}
		
		$sw_name = 'uni-sw.'.$store_id.'.js';
		
		$this->setManifest();
		$this->installSW($sw_name);
		$this->setSW($sw_name);
		
		if(!isset($this->request->cookie['pwaOffTime'])) {
			$this->document->addStyle('catalog/view/theme/unishop2/stylesheet/pwa.css');
			
			//$this->document->addScript('catalog/view/theme/unishop2/js/pwacompat.js');
			
			$data['notification'] = [
				'text_chromium' => html_entity_decode($uniset['pwa']['banner']['text_chromium'][$lang_id], ENT_QUOTES, 'UTF-8'),
				'text_other'	=> html_entity_decode($uniset['pwa']['banner']['text_other'][$lang_id], ENT_QUOTES, 'UTF-8'),
				'text_install'	=> $this->language->get('text_pwa_install'),
				'text_close'	=> $this->language->get('text_pwa_not_now')
			];
		}
		
		return $this->load->view('extension/module/uni_pwa_notification', $data);
	}
	
	public function setPwaBannerTopTimeOff() {
		$uniset = $this->config->get('config_unishop2');
		$time = $uniset['pwa']['banner']['time']*3600;
		
		setcookie('pwaOffTime', true, time()+$time, '/');
	}
	
	public function fallbackPage() {
		$uniset = $this->config->get('config_unishop2');
		$lang_id = (int)$this->config->get('config_language_id');
		
		$data = [];
		
		if(isset($uniset['pwa']['status'])) {
			if (is_file(DIR_IMAGE . $this->config->get('config_icon'))) {
				$icon = $this->config->get('config_ssl') . 'image/' . $this->config->get('config_icon');
			} else {
				$icon = '';
			}
			
			$style  = 'a {color:#'.$uniset['a_color'].'}';
			$style .= '.btn-primary {color:#'.$uniset['btn_primary_color'].';background:#'.$uniset['btn_primary_bg'].'}';
			$style .= '.btn-primary:hover, .btn-primary:focus {color:#'.$uniset['btn_primary_color_hover'].';background:#'.$uniset['btn_primary_bg_hover'].'}';
			
			$data['result'] = [
				'title' 		=> $uniset['pwa']['fallbackpage']['title'][$lang_id],
				'icon'			=> $icon,
				'style' 		=> $style,
				'font'			=> $uniset['font'],
				'image'			=> $this->getImg(),
				'description' 	=> html_entity_decode(trim($uniset['pwa']['fallbackpage']['description'][$lang_id]), ENT_QUOTES, 'UTF-8')
			];
		} else {
			$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');
		}
		
		$this->response->setOutput($this->load->view('extension/module/uni_pwa_fallback_page', $data));
	}
	
	private function setManifest() {
		$uniset = $this->config->get('config_unishop2');
		$store_id = (int)$this->config->get('config_store_id');
		$lang_id = (int)$this->config->get('config_language_id');
		
		$this->load->model('tool/image');
		
		$manifest = 'catalog/view/theme/unishop2/manifest/manifest.'.$lang_id.'.'.$store_id.'.json';
		
		if (!file_exists($manifest)) {
			$img_sizes = [16, 32, 72, 76, 96, 128, 144, 152, 192, 384, 512];
		
			$icons = '';
			
			$image_old = $uniset['pwa']['icon'];
			$extension = pathinfo($image_old, PATHINFO_EXTENSION);
				
			foreach($img_sizes as $key => $size) {
				$icons .= ' {"src":"'.$this->getImg($size).'", "type":"image/png", "sizes":"'.$size.'x'.$size.'"'. ($key + 1 == count($img_sizes) ? ', "purpose":"any maskable"}' :'},');
			}
		
			$manifest_data = '{
				"dir":"ltr",
				"lang":"'.$this->language->get('code').'",
				"name":"'.$uniset['pwa']['name'][$lang_id].'",
				"short_name":"'.$uniset['pwa']['short_name'][$lang_id].'",
				"scope":"/",
				"display":"standalone",
				"start_url":"/",
				"background_color":"#ffffff",
				"theme_color":"#'.(($uniset['menu_type'] == 1) ? $uniset['main_menu_bg'] :$uniset['main_menu2_bg']).'",
				"orientation":"any",
				"related_applications":[],
				"prefer_related_applications":false,
				"icons":['.$icons.'],
				"url":"/"
			}';
		
			file_put_contents($manifest, $manifest_data);
		}
		
		$this->document->addLink($manifest, 'manifest');
		$this->document->addLink($this->getImg(152), 'apple-touch-icon');
	}
	
	private function getImg($size = 512){
		//it's all here, because the modules for converting images to webp format can be installed 
		
		$uniset = $this->config->get('config_unishop2');
		
		$image_old = $uniset['pwa']['icon'];
		
		if(!file_exists(DIR_IMAGE.$image_old)) {
			return;
		}
		
		$size = (int)$size;
		
		$extension = pathinfo($image_old, PATHINFO_EXTENSION);
		
		$image_new = 'cache/' . utf8_substr($image_old, 0, utf8_strrpos($image_old, '.')) . '-' . $size . 'x' . $size . '.' . $extension;
				
		if(!is_file(DIR_IMAGE . $image_new) || (filemtime(DIR_IMAGE . $image_old) > filemtime(DIR_IMAGE . $image_new))) {
			$path = '';

			$directories = explode('/', dirname($image_new));

			foreach ($directories as $directory) {
				$path = $path . '/' . $directory;

				if (!is_dir(DIR_IMAGE . $path)) {
					@mkdir(DIR_IMAGE . $path, 0777);
				}
			}
					
			$image = new Image(DIR_IMAGE . $image_old);
			$image->resize($size, $size);
			$image->save(DIR_IMAGE . $image_new);
		}
		
		return $this->config->get('config_ssl') . 'image/'.$image_new;
	}
	
	private function installSW($sw_name) {
		$uniset = $this->config->get('config_unishop2');
		$store_id = (int)$this->config->get('config_store_id');
		$lang_id = (int)$this->config->get('config_language_id');
		$isMobile = $uniset['is_mobile'];
		
		$date = isset($uniset['save_date']) ? $uniset['save_date'] :strtotime('now');
		
		$install_sw_name = 'catalog/view/theme/unishop2/js/install-sw.'.$store_id.'.min.js';
		
		if (!file_exists($install_sw_name )) {
			
			$pwaBannerChromium = isset($uniset['pwa']['banner']['text_chromium'][$lang_id]) && $uniset['pwa']['banner']['text_chromium'][$lang_id] != '' ? true :false;
			$pwaBannerOther = isset($uniset['pwa']['banner']['text_other'][$lang_id]) && $uniset['pwa']['banner']['text_other'][$lang_id] != '' ? true :false;
			
			$data = '
				const swUrl = "'.$sw_name.'",
					  userAgent = navigator.userAgent.toLowerCase(),
					  touchSupport = (\'ontouchstart\' in document.documentElement),
					  pwaOffTimeCookie = document.cookie.match(\'pwaOffTime\') ? true : false,
					  pwaBannerChromium = "'.$pwaBannerChromium.'",
					  pwaBannerOther = "'.$pwaBannerOther.'",
					  displayStandalone = (window.matchMedia(\'(display-mode:standalone)\').matches || ((\'standalone\' in navigator) && navigator.standalone)) ? true : false;
					  
				const uniShowPWABanner = (newClass) => {
					if ($(window).width() < 767 && !pwaOffTimeCookie && !displayStandalone) {
						if(newClass == \'chromium\') $(\'.pwa-notification\').removeClass(\'other\');
							
						$(\'header\').before($(\'.pwa-notification\').removeClass(\'hidden\').addClass(newClass));
						
						setTimeout(() => {			
							$(\'.pwa-notification .container\').addClass(\'active\');
						}, 50);
							
						$(document).on(\'click\', \'.pwa-notification__close\', () => {
							$.get(\'index.php?route=extension/module/uni_pwa/setPwaBannerTopTimeOff\');
							$(\'.pwa-notification .container\').removeClass(\'active\');
						});
					}
				}
				
				window.addEventListener(\'load\', () => {
					if (\'serviceWorker\' in navigator) {
						if (!navigator.serviceWorker.controller) {
							navigator.serviceWorker.register(swUrl, {scope: "/"});
						}
					}

					function showNetworkStatusAlert() {
						if(navigator.onLine) {
							uniFlyAlert(\'success\', uniJsVars.pwa.text_online);
						} else {
							uniFlyAlert(\'danger\', uniJsVars.pwa.text_offline);
						}
					}
					
					window.addEventListener(\'online\', showNetworkStatusAlert);
					window.addEventListener(\'offline\', showNetworkStatusAlert);
					
					$(document).ajaxError(() => {
						if(!navigator.onLine) {
							uniFlyAlert(\'danger\', uniJsVars.pwa.text_offline);
						}
					});
					
					if((/iphone|ipad|ipod|firefox|opr/.test(userAgent)) && touchSupport && pwaBannerOther) {
						uniShowPWABanner(\'other\');
					}
					
					uniReloadSW();
					
					if(displayStandalone) {
						if(!$(\'.fly-block .fly-block__back\').length) {
							$(\'.fly-block\').prepend(\'<div class="fly-block__item fly-block__back" onclick="window.history.back();"><i class="fas fa-arrow-left"></i></div>\');
						}
					}
				});
				
				var deferredPrompt;

				window.addEventListener(\'beforeinstallprompt\', (e) => {
					e.preventDefault();

					deferredPrompt = e;
					
					if(pwaBannerChromium) {
						uniShowPWABanner(\'chromium\');
					}
							
					$(".pwa-notification__install").click(() => {
						deferredPrompt.prompt();
					});
				});
				
				window.addEventListener(\'appinstalled\', () => {
					$(\'.pwa-notification .container\').removeClass(\'active\');
					deferredPrompt = null;
				});
				
				function uniSendNotification(title, options) {
					if (\'Notification\' in window) {
						navigator.serviceWorker.ready.then((reg) => {
							if (Notification.permission === \'granted\') {
								reg.showNotification(title, options);
							} else if (Notification.permission !== \'denied\') {
								Notification.requestPermission((permission) => {
									if (permission === "granted") {
										reg.showNotification(title, options);
									}
								});
							}
						});
					}
				}
				
				function uniReloadSW() {
					if (\'serviceWorker\' in navigator) {
						navigator.serviceWorker.ready.then((reg) => {
							if (reg.waiting) {
								reg.waiting.postMessage({ type: \'SKIP_WAITING\' });

								if (displayStandalone) {
									uniSendNotification(\'Update\', {
										body: uniJsVars.pwa.text_reload,
										vibrate: [100, 50, 100],
										tag: \'uniReloadSW\'
									});
								};
							};
						});
					};
				};
				
				function uniDelPageCache(name) {
					caches.keys().then((cacheNames) => {
						cacheNames.forEach(cacheName => {
							if(typeof(name) == \'undefined\' || cacheName == name) {
								caches.delete(cacheName);
							}
						});
					});
				};
				
				$(document).on(\'click\', \'.top-menu__currency-item, .top-menu__language-item\', () => {
					uniDelPageCache();
				});
			';
			
			file_put_contents($install_sw_name, $data);
		}
		
		$this->document->addScript($install_sw_name.'?v='.$date);
	}
	
	private function setSW($sw_name) {
		$uniset = $this->config->get('config_unishop2');
		
		if (file_exists($sw_name)) {
			return;
		}
		
		$data = 'importScripts("catalog/view/theme/unishop2/js/workbox/workbox-sw.js");';
		$data .= 'const pageCache = "page",';
		$data .= 'jsCache = "js",';
		$data .= 'cssCache = "css",';
		$data .= 'imgCache = "img",';
		$data .= 'fontCache = "fonts",';
		$data .= 'preCache = "fallback",';
		$data .= 'fallbackPage = "index.php?route=extension/module/uni_pwa/fallbackPage",';
		$data .= 'fallbackCss = "catalog/view/theme/unishop2/stylesheet/stylesheet.css",';
		$data .= 'fallbackFontCss = "catalog/view/theme/unishop2/stylesheet/'.$uniset['font'].'.css",';
		$data .= 'fallbackImg = "'.$this->getImg().'";';

		$data .= 'self.addEventListener(\'activate\', async (event) => {';
		$data .= 'event.waitUntil(';
		$data .= 'caches.open(preCache).then(cache => {';
		$data .= 'cache.addAll([fallbackPage, fallbackCss, fallbackFontCss, fallbackImg]);';
		$data .= '})';
		$data .= ');';
		$data .= 'event.waitUntil(clients.claim());';
		$data .= '});';

		$data .= 'self.addEventListener(\'message\', async (event) => {';
		$data .= 'if (event.data && event.data.type === "SKIP_WAITING") {';
		$data .= 'self.skipWaiting();';
		$data .= 'caches.keys().then(cacheNames => {';
		$data .= 'cacheNames.forEach(cacheName => {';
		$data .= 'caches.delete(cacheName);';
		$data .= '});';
		$data .= '});';
		$data .= '}';
		$data .= '});';

		$data .= 'workbox.setConfig({';
		$data .= 'modulePathPrefix:"catalog/view/theme/unishop2/js/workbox/"';
		$data .= '});';
			
		//$data .= 'const documentStrategy = \''.$uniset['pwa']['cache']['document']['strategy'].'\';';

		//$data .= 'if (documentStrategy == \'NetworkFirst\' && workbox.navigationPreload.isSupported()) {';
		//$data .= '//workbox.navigationPreload.enable();';
		//$data .= '}';

		$data .= 'workbox.routing.registerRoute(';
		$data .= '({event, url}) => event.request.destination === \'document\' && !(/account|wishlist|compare|checkout|cart|admin|login|register|captcha/i.test(url.href)),';
		$data .= 'new workbox.strategies.'.$uniset['pwa']['cache']['document']['strategy'].'({';
		$data .= 'cacheName:pageCache,';
		$data .= 'plugins:[';
		$data .= 'new workbox.expiration.ExpirationPlugin({';
		$data .= 'maxEntries:'.$uniset['pwa']['cache']['document']['items'].',';
		$data .= 'maxAgeSeconds:60 * 60 * 24 * '.$uniset['pwa']['cache']['document']['lifetime'].',';
		$data .= '}),';
		$data .= '],';
		$data .= '})';
		$data .= ');';

		$data .= 'workbox.routing.registerRoute(';
		$data .= '({event, url}) => event.request.destination === \'script\' && !url.pathname.startsWith(\'/admin/\'),';
		$data .= 'new workbox.strategies.'.$uniset['pwa']['cache']['script']['strategy'].'({';
		$data .= 'cacheName:jsCache,';
		$data .= 'plugins:[';
		$data .= 'new workbox.expiration.ExpirationPlugin({';
		$data .= 'maxEntries:'.$uniset['pwa']['cache']['script']['items'].',';
		$data .= 'maxAgeSeconds:60 * 60 * 24 * '.$uniset['pwa']['cache']['script']['lifetime'].',';
		$data .= '}),';
		$data .= '],';
		$data .= '})';
		$data .= ');';

		$data .= 'workbox.routing.registerRoute(';
		$data .= '({event, url}) => event.request.destination === \'style\' && !url.pathname.startsWith(\'/admin/\'),';
		$data .= 'new workbox.strategies.'.$uniset['pwa']['cache']['style']['strategy'].'({';
		$data .= 'cacheName:cssCache,';
		$data .= 'plugins:[';
		$data .= 'new workbox.expiration.ExpirationPlugin({';
		$data .= 'maxEntries:'.$uniset['pwa']['cache']['style']['items'].',';
		$data .= 'maxAgeSeconds:60 * 60 * 24 * '.$uniset['pwa']['cache']['style']['lifetime'].',';
		$data .= '}),';
		$data .= '],';
		$data .= '})';
		$data .= ');';

		$data .= 'workbox.routing.registerRoute(';
		$data .= '({event, url}) => event.request.destination === \'image\' && !(/captcha/i.test(url.href)),';
		$data .= 'new workbox.strategies.'.$uniset['pwa']['cache']['image']['strategy'].'({';
		$data .= 'cacheName:imgCache,';
		$data .= 'plugins:[';
		$data .= 'new workbox.expiration.ExpirationPlugin({';
		$data .= 'maxEntries:'.$uniset['pwa']['cache']['image']['items'].',';
		$data .= 'maxAgeSeconds:60 * 60 * 24 * '.$uniset['pwa']['cache']['image']['lifetime'].',';
		$data .= 'purgeOnQuotaError:true';
		$data .= '}),';
		$data .= '],';
		$data .= '})';
		$data .= ');';

		$data .= 'workbox.routing.registerRoute(';
		$data .= '({event}) => event.request.destination === \'font\',';
		$data .= 'new workbox.strategies.CacheFirst({';
		$data .= 'cacheName:fontCache,';
		$data .= 'plugins:[';
		$data .= 'new workbox.expiration.ExpirationPlugin({';
		$data .= 'maxEntries:15,';
		$data .= '}),';
		$data .= '],';
		$data .= '})';
		$data .= ');';

		$data .= 'workbox.routing.setCatchHandler(';
		$data .= '({event, url}) => {';
		$data .= 'if ((event.request.destination) == \'document\') return caches.match(fallbackPage);';
		$data .= '}';
		$data .= ');';
		
		file_put_contents($sw_name, $data);
	}
}