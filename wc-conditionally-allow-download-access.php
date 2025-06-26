<?php 

	/*
		Plugin Name: WooCommerce Conditionally Allow Downloads Folder Access
		Plugin URI: https://github.com/Hube2/wc-conditionally-allow-download-access
		Description: Allow direct access to WooCommerce downloadable products folder based on user role.
		Author: Hube2
		Author URI: https://github.com/Hube2/
		Version: 1.0.0
	*/
	
	// If this file is called directly, abort.
	if (!defined('WPINC')) {die;}
	
	new wc_condtional_download_access();
	
	class wc_condtional_download_access {
		
		// edit these values before using this plugin
		// use only letters and underscores in cookie name, all other characters will be removed
		// use only letters or numbers in cookie value, all other characters will be removed
		// cookie name/vlaue are required to not be empty or nothing will happen
		private $cookie_name = '';
		private $cookie_value = '';
		private $roles = array(
			'administrator',
			'editor',
			'shop_manager'
		);
		
		public function __construct() {
			// replace any not letter characters in cookie name/value
			$this->cookie_name = preg_replace('/[^_a-z]/i', '', $this->cookie_name);
			$this->cookie_value = preg_replace('/[^a-z0-9]/i', '', $this->cookie_value);
			if (empty($this->cookie_name) || empty($this->cookie_value)) {
				// abort remainder of __construct
				// this does not prevent the opbject from being created but prevents the actions from being added
				return;
			}
			// maybe set or unset cookie on login/logout
			add_action('wp_login', array($this, 'maybe_set_login_cookie'), 20, 2);
			add_action('wp_logout', array($this, 'maybe_clear_login_cookie'), 20, 2);
			
			// I believe that the following are the only times that WC writes the .htaccess file, but not 100% sure
			// to ensure our .htaccess file is in place it will also be checked during login
			// this hook fires after WC updates settings and writes its .htaccess file to downloads folder
			add_action('woocommerce_settings_saved', array($this, 'maybe_replace_htaccess'), 100);
			// this hook is called when WC is installed or updated
			add_action('woocommerce_installed', array($this, 'maybe_replace_htaccess'), 100);
		} // end public function __construct
		
		public function maybe_set_login_cookie($login, $user) {
			if (!class_exists('WooCommerce')) {
				// do nothing if WC is not active
				return;
			}
			// set cookit to allow direct access to WC downloads folder if user has an allowed role
			$set_cookie = false;
			foreach ($this->roles as $role) {
				//echo $role.' '; var_dump(current_user_can($role)); echo '<br />';
				if (current_user_can($role)) {
					$set_cookie = true;;
				}
			}
			if (!$set_cookie) {
				//echo 'no cookie';
				return;
			}
			//echo 'set cookie';
			$options = array(
				'expires' => 0, // session only
				'path' => '/',
				'secure' => true,
				'httponly' => true
			);
			setcookie($this->cookie_name, $this->cookie_value, $options);
			// htaccess file may have been changed, check it
			// rechecking during login because I cannot be sure I have caught all the places
			// that WC writes the .htaccess file
			$this->maybe_replace_htaccess();
		} // end public function maybe_set_login_cookie
		
		public function maybe_clear_login_cookie($user_id) {
			if (!class_exists('WooCommerce')) {
				// do nothing if WC is not active
				return;
			}
			// only clear the cookie if it was previously set
			// so that we don't send any cookie information at all to the browser
			// unsetting a cookie that is not set would still send this information to the browswer
			// and potentially reveal our cookie name
			if (!isset($_COOKIE[$this->cookie_name])) {
				return;
			}
			$options = array(
				'expires' => time()-86400, // time in 1 day ago
				'path' => '/',
				'secure' => true,
				'httponly' => true
			);
			setcookie($this->cookie_name, NULL, $options); 
		} // end public function maybe_clear_login_cookie
		
		public function maybe_replace_htaccess() {
			// maybe overwite the .htaccess file in downloads folder
			// this is basically pulled directly from WC_Admin_Settings::check_download_folder_protection
			// and then modified for use
			$upload_dir = wp_get_upload_dir();
			$downloads_path = $upload_dir['basedir'].'/woocommerce_uploads';
			$file_path = $downloads_path . '/.htaccess';
			$download_method = get_option('woocommerce_file_download_method');
			
			if ($download_method == 'redirect') {
				$file_content = 'Options -Indexes';
			} elseif ($download_method == 'force') {
				$file_content = 'RewriteEngine On'.PHP_EOL.'RewriteCond %{HTTP_COOKIE} !^.*'.
				                 $this->cookie_name.'='.$this->cookie_value.
				                 '.*$ [NC]'.PHP_EOL.'RewriteRule .* - [L,R=404]';
			}
			$create = false;
			if (wp_mkdir_p($downloads_path ) && !file_exists($file_path)) {
				$create = true;
			} else {
				$current_content = @file_get_contents($file_path);
				if ($current_content !== $file_content) {
					unlink($file_path);
					$create = true;
				}
			}
			if ($create) {
				$file_handle = @fopen($file_path, 'wb');
				if ($file_handle) {
					fwrite($file_handle, $file_content);
					fclose($file_handle);
				}
			}
		} // end public function maybe_replace_htaccess
		
	} // end class wc_condtional_download_access