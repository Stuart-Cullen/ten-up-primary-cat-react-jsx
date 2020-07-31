<?php
/**
 * @package ten-up-primary-cat-react-jsx
 */

/**
Plugin Name: 10up Primary Category
Plugin URI: http://stuartcullen.com
Description: A plugin which enables the selection of a primary category for any post type.
Version 1.0.0
Author: Stuart Cullen
Author URI: http://stuartcullen.com
Licence: GPLv2 or later
Text Domain: ten-up-primary-cat-react-jsx
 */

/**
Copyright (C) 2020  Stuart Cullen

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

//Check that this script is being run in the correct context
defined('ABSPATH') or die();
function_exists('add_action') or die();

/**
 * Generate the entry-point singleton style class if it doesn't already exist
 */
if (!class_exists( 'Plugin')) {

	/**
	 * main plugin entry-point class
	 *
	 * @since 1.0.0
	 */
	class Plugin {

		/**
		 * The instance of the class for singleton use.
		 *
		 * @since 1.0.0
		 * @access protected
		 * @static
		 *
		 * @var Plugin
		 */
		protected static $instance = null;


		/**
		 * Returns the class instance.
		 *
		 * @since 1.0.0
		 * @access public
		 * @static
		 *
		 * @return Plugin Returns the class instance
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}


		/**
		 * Constructor
		 *
		 * @since 1.0.0
		 * @access public
		 *
		 * @return void
		 */
		private function __construct() { }


		/**
		 * Activate the plugin
		 *
		 * @since 1.0.0
		 * @access public
		 */
		function activate() {
			require_once plugin_dir_path(__FILE__).'src/plugin-activate.php';
			flush_rewrite_rules();
			PluginActivate::activate();
		}


		/**
		 * Deactivate the plugin
		 *
		 * @since 1.0.0
		 * @access public
		 */
		function deactivate() {
			require_once plugin_dir_path(__FILE__).'src/plugin-deactivate.php';
			flush_rewrite_rules();
			PluginDeactivate::deactivate();
		}

	}
	$plugin = Plugin::get_instance();
}

//Register activation hook
register_activation_hook(__FILE__, array($plugin, 'activate'));

//Register deactivation hook
register_deactivation_hook(__FILE__, array($plugin, 'deactivate'));

//Add the asset loader etc for the custom Gutenberg block
require_once plugin_dir_path(__FILE__).'src/init_all_blocks.php';

//add the ui procedures only if the admin interface is open
if (is_admin()) {
	require_once plugin_dir_path(__FILE__).'src/ui-procedures.php';
}
