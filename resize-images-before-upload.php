<?php
/*
Plugin Name: Browser Resize Images
Plugin URI: http://www.createdays.com/browser-resize-images-wordpress/
Description: Enables resizing images in browser for smaller file uploads and storage usage. Configure it in "Settings" > "Media".
Version: 1.0
Author: Create Days - Mariusz Misiek
Author URI: http://www.createdays.com/
License: GPLv3 or later
*/ 

register_activation_hook( __FILE__, array('WP_GB_Browser_Resize', 'activate_plugin') );

$gb_browser_Resize = new WP_GB_Browser_Resize;
       
class WP_GB_Browser_Resize {
		
	function __construct() {
		
		add_action('admin_init', array($this,'add_admin_media_settings'));
		add_action('load-media-new.php', array($this,'activate_browser_resizing_media_page'));
		
		add_filter('plupload_init', array($this,'activate_browser_resizing'));
		add_filter('plupload_default_settings', array($this,'activate_browser_resizing'));

	}
	
	function activate_plugin() {
		if( !get_option( 'gb_browser_resize_quality' ) ) {
			add_option( 'gb_browser_resize_quality', 80 );
		}
	}

	function activate_browser_resizing($data) { 
		
		$resize_quality = get_option( 'gb_browser_resize_quality' );
		
		if( !empty($resize_quality) ) {
			
			$resize_width = $this->get_resize_parameter('width');
			$resize_height = $this->get_resize_parameter('height');
			
			$data['resize'] = array( 'width' => $resize_width, 'height' => $resize_height, 'quality' => $resize_quality );
			unset($data['max_file_size']);
			
			if ( !$this->browser_status() ){
				$data['runtimes'] = "flash";
			}
		
		}
		
		return $data;
	}
	
	function activate_browser_resizing_media_page() {
		add_action('admin_head', array($this,'activate_browser_resizing_media_page_script'));
	}
	
	function activate_browser_resizing_media_page_script(){
		
		$resize_quality = get_option( 'gb_browser_resize_quality' );
		
		if( !empty($resize_quality) ) {
			
			echo "
			<script>
				jQuery(window).load(function($){
					
					setResize(false);
					uploader.settings['resize'] = { width: ".$this->get_resize_parameter('width').", height: ".$this->get_resize_parameter('height').", quality: ".$resize_quality." };
	
				});
			</script>
			";	
		
		}	
	}
	
	function get_resize_parameter($type) {
		$resize_parameter = get_option( 'gb_browser_resize_'.$type, '' );

		if(empty($resize_parameter)) {
			$resize_parameter = get_option( 'large_size_'.substr($type, 0, 1), '' );
		}

		return $resize_parameter;
	}
	
	function add_admin_media_settings(){
		
		add_settings_section('gb_browser_resize', 'Browser resize images for smaller upload', array($this,'browser_resize_section_html'), 'media');
		
		add_settings_field('gb_browser_resize_quality', 'Browser resize quality', array($this,'browser_resize_quality_html'), 'media', 'gb_browser_resize');
		add_settings_field('gb_browser_resize_size', 'Browser max resize size', array($this,'browser_resize_size_html'), 'media', 'gb_browser_resize');
		
		register_setting('media', 'gb_browser_resize_quality', array($this,'browser_resize_quality_validate_option') );
		register_setting('media', 'gb_browser_resize_width', array($this,'browser_resize_size_validate_option') );
		register_setting('media', 'gb_browser_resize_height', array($this,'browser_resize_size_validate_option') );
	
	}
	
	function browser_resize_section_html(){
		echo '<p>For best browser resizing experience please use Firefox or Chrome.';
	}
	
	function browser_resize_quality_html(){
		echo '
			<input name="gb_browser_resize_quality" id="gb_browser_resize_quality" type="number"  min="0" max="100" value="'.get_option( 'gb_browser_resize_quality', '' ).'" class="small-text" /> 
			<em class="description">Value from 1 to 100. Bigger value = bigger quality = bigger file size. Leave empty to disable Browser resizing.</em>
		';
	}
	
	function browser_resize_size_html(){
		echo '
			<label for="gb_browser_resize_width">Width</label>
			<input name="gb_browser_resize_width" id="gb_browser_resize_width" type="number" value="'.get_option( 'gb_browser_resize_width', '' ).'" class="small-text" />
			<label for="gb_browser_resize_height">Height</label>
			<input name="gb_browser_resize_height" id="gb_browser_resize_height" type="number" value="'.get_option( 'gb_browser_resize_height', '' ).'" class="small-text" />
			<em class="description">If width or height empty, "Large size" values will be used.</em>
		';
	}

	function browser_resize_quality_validate_option($quality){
		
		$return_quality = '';
		$quality = absint($quality);
		
		if($quality > 0 && $quality <= 100) 
			$return_quality = $quality;
		elseif(!empty($quality) && !is_numeric($size))
			add_settings_error( 'gb_browser_resize_quality', 'gb_browser_resize_quality_error', 'Incorrect resize quality - value should be range from 1 up to and including 100.','error' );

		return $return_quality;
		
	}
	
	function browser_resize_size_validate_option($size){
		
		$return_size = '';
		
		if(is_numeric($size))
			$return_size = absint($size);
		elseif(!empty($size) && !is_numeric($size))
			add_settings_error( 'gb_browser_resize_size', 'gb_browser_resize_size_error', 'Browser resize size should be number bigger than 0.','error' );

		return $return_size;
		
	}
	
	
	function browser_status(){
	    
		if(preg_match("#Firefox|Chrome#", $_SERVER['HTTP_USER_AGENT']) )
			return true;
			
		return false;
	}

}