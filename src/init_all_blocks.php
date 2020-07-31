<?php
/**
 * @package ten-up-primary-cat-react-jsx
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

require_once plugin_dir_path(__FILE__).'plugin-queries.php';

/**
 * Generate the blocks singleton style class if it doesn't already exist
 */
if (!class_exists( 'InitAllBlocks')) {

	/**
	 * The "entry-point" class for a custom Gutenberg block which allows the user to search for
	 * posts/custom post types by their "Primary Taxonomy"
	 *
	 * @since 1.0.0
	 */
	class InitAllBlocks {

		/**
		 * The instance of the class for singleton use.
		 *
		 * @since 1.0.0
		 * @access protected
		 * @static
		 *
		 * @var InitAllBlocks
		 */
		protected static $instance = null;


		/**
		 * Returns the class instance.
		 *
		 * @return InitAllBlocks Returns the class instance
		 * @since 1.0.0
		 * @access public
		 * @static
		 *
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
		 * @return void
		 * @since 1.0.0
		 * @access public
		 *
		 */
		private function __construct() { }


		/**
		 * Prepare the necessary assets for the custom, dynamic
		 * Gutenberg block UI.
		 *
		 * @since 1.0.0
		 * @access public
		 *
		 * @uses {wp-blocks} for block type registration & related functions.
		 * @uses {wp-element} for WP Element abstraction — structure of blocks.
		 * @uses {wp-editor} for WP editor styles.
		 */
		public function prepare_assets() {
			wp_register_style(
				'primary-block-style-css',
				plugins_url( '/dist/blocks.style.build.css', dirname( __FILE__ ) ),
				is_admin() ? array('wp-editor') : null
			);

			wp_register_script(
				'primary-block-block-js',
				plugins_url( '/dist/blocks.build.js', dirname( __FILE__ ) ),
				array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ),
				null,
				true
			);

			wp_register_style(
				'primary-block-editor-css',
				plugins_url( '/dist/blocks.editor.build.css', dirname( __FILE__ ) ),
				array('wp-edit-blocks')
			);

			wp_localize_script(
				'primary-block-block-js',
				'cgbGlobal',
				[
					'pluginDirPath' => plugin_dir_path( __DIR__ ),
					'pluginDirUrl'  => plugin_dir_url( __DIR__ ),
				]
			);

			register_block_type(
				'ten-up-primary-cat-react-jsx/primary-block2', array(
					'style'         => 'primary-block-style-css',
					'editor_script' => 'primary-block-block-js',
					'editor_style'  => 'primary-block-editor-css',
					'render_callback' => array($this, 'primary_block_render')
				)
			);
		}


		/**
		 * Render a dynamic gutenblock with the posts from
		 * a given primary category
		 *
		 * @param $attributes object The incoming param object
		 *
		 * @return false|string The output to render
		 */
		public function primary_block_render($attributes) {

			$empty_statement = '<h2> There are no posts to display! </h2>';

			if ($attributes['primary_category']<1)
				return $empty_statement;

			$args = [];
			$args['id'] = $attributes['primary_category'];
			$posts = $this->fetch_posts_for_primary_cat_id( $args );
			if (empty($posts))
				return $empty_statement;

			ob_start();
			echo '<pre>';
			foreach ($posts as $post) {
				echo '<h2>'.$post->post_title.'</h2>';
				echo get_the_post_thumbnail($post->ID);
				echo '<p>'.$post->post_excerpt.'</p>';
				echo "<hr>";
			}
			echo '</pre>';
			return ob_get_clean();
		}


		//Memoize the cat list as it is required for a filter callback
		private static $memoized_primary_cats;


		/**
		 * Clear the memoize cache
		 */
		public static function clear_memoized_primary_cats() {
			$memoized_primary_cats = null;
		}


		/**
		 * The memoized retrieval and subsequent plucking of the array of primary categories
		 *
		 * @return array The reference to the array (which might be empty)
		 */
		public static function &get_memoized_primary_cats() {
			if (null === self::$memoized_primary_cats) {
				$result = PluginQueries::fetch_all_primary_categories();
				if (empty($result))
					self::$memoized_primary_cats = $result;
				else
					self::$memoized_primary_cats = wp_list_pluck($result,'term_id');
			}
			return self::$memoized_primary_cats;
		}


		/**
		 * Determine whether category in incoming param is
		 * a primary category
		 *
		 * @param $object object The incoming param object
		 *
		 * @return bool Whether it is in use as a current primary category
		 */
		public function is_primary_category($object) {
			$primaries = InitAllBlocks::get_memoized_primary_cats();
			if (empty($primaries))
				return false;
			return in_array($object['id'], $primaries);
		}


		/**
		 * Add the primary field flag to each of the categories enumerated
		 * by the vanilla REST api
		 *
		 * I've done it in this way so as that it can be extended easily later.
		 * Adding, for example a set of icons at different sizes for each relationship
		 * which may also be bound into the category object after memoized retrieval.
		 *
		 * Since it is also using a custom table instead of meta data this can also
		 * easily be extended for other taxonomies
		 *
		 * In future this could be made more efficient
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function api_inject_category_is_primary() {
			register_rest_field('category', 'is_primary', array(
				'get_callback' => array($this, 'is_primary_category'),
				'update_callback' => null,
				'schema' => null,
			));
		}


		/**
		 * Grab the post objects via the ORM after retrieving the id
		 * list from the plugin's separate primary relationship table.
		 *
		 * Also adding the feature image for display convenience
		 *
		 * @param array $object The incoming param object
		 *
		 * @return array the posts
		 */
		public function fetch_posts_for_primary_cat_id($object) {
			$ids = PluginQueries::fetch_all_post_ids_for_primary_category($object['id']);
			if (empty($ids))
				return null;
			$posts = get_posts(array('include' => $ids));
			if (empty($posts)) {
				return null;
			}
			foreach ($posts as $post) {
				$attachment_id = get_post_thumbnail_id($post->ID);
				if (!is_numeric($attachment_id)) continue;

				$feature_image = wp_get_attachment_url($attachment_id);
				if ($feature_image)
					$post->{"feature_image"} = $feature_image;
			}
			return $posts;
		}


		/**
		 * Add the new REST route to grab posts specifically for a given primary category
		 *
		 * @since 1.0.0
		 * @access public
		 */
		public function api_add_primary_cat_rest_route() {
			register_rest_route(
				'ten-up-primary-cat-react-jsx/v1',
				'/category/(?P<id>\d+)',
				array(
					'methods' => 'GET',
					'callback' => array($this, 'fetch_posts_for_primary_cat_id'),
					'args' => array(
						'id' => array(
							'validate_callback' => function($param, $request, $key) {
								return is_numeric($param);
							}
						),
				)
			));
		}

	}

	$block = InitAllBlocks::get_instance();
	add_action('init', array($block, 'prepare_assets'));
	add_action('wp_loaded', array($block, 'clear_memoized_primary_cats'));
	add_action('rest_api_init', array($block, 'api_inject_category_is_primary'));
	add_action('rest_api_init', array($block, 'api_add_primary_cat_rest_route'));

}
