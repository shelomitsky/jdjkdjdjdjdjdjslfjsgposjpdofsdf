<?php
class ControllerExtensionModuleUniBanner extends Controller {
	public function index($setting) {
		static $module = 0;

		$this->load->model('design/banner');
		$this->load->model('tool/image');
		
		$uniset = $this->config->get('config_unishop2');
		
		$isMobile = $uniset['is_mobile'];
		
		$setting['hide_on_mobile'] = isset($setting['hide_on_mobile']) ? $setting['hide_on_mobile'] : false;

		$data['banners'] = [];
		
		if(!$setting['hide_on_mobile'] || ($setting['hide_on_mobile'] && !$isMobile)) {

			$results = $this->model_design_banner->getBanner($setting['banner_id']);
		
			if($results) {
				$this->document->addStyle('catalog/view/theme/unishop2/stylesheet/banner.css');

				foreach ($results as $result) {
					if (is_file(DIR_IMAGE . $result['image'])) {
						$size = getimagesize(DIR_IMAGE . $result['image']);
				
						$width = isset($size[0]) ? $size[0] : 400;
						$height = isset($size[1]) ? $size[1] : 300;
				
						$data['banners'][] = [
							'title' 	=> $result['title'],
							'link'  	=> $result['link'],
							'width'		=> $width,
							'height'	=> $height,
							'image' 	=> $this->model_tool_image->resize($result['image'], $width, $height)
						];
					}
				}
			}
			
			$data['module'] = $module++;
		}

		return $this->load->view('extension/module/uni_banner', $data);
	}
}
?>