# wc-conditionally-allow-download-access
Contiionally allow access to downloads folder based role of user that is logged in

For Downloadable Products in WooCommerce (WC), when "Force Downloads" is selected as the download method, WC protects the downloads folder with a .htaccess file containing "deny from all". This is normally desired.

An issue arises when downloadable products include standard image files (.jpg, .png, etc.) where WP displays image thumbnails in either the media library or when attempting to select images as part of downloadable product packages. With the downloads folder pretected by .htaccess file instead of seeing the image and image information there is a blank image square.

This plugin aims to correct this issue by altering the .htaccess file created by WC to allow access to files in the downloads folder if the user has a specific cookie set. This cookie is set based on the logged in user role upon logging into the site.

This plugin should not be used as is. The cookie name and value should be altererd/customized on every site and should not be public knowledge. Using this plugin without altering these values or if these values become public knowledge could result in the downloads folder not being protected from direct access. Edit the main plugin file to set your cookie name and value or to change user roles that you want to permit access to the downloads folder for.
