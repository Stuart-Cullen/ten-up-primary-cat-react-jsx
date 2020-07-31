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
 * Generate the UI procedures singleton style class if it doesn't already exist
 */
if (!class_exists( 'UIProcedures')) {

    /**
     * Editor UI procedures for the plugin
     *
     * This class contains all of the code for the publisher's interaction with
     * the post editor UI.  I had planned on doing a more elaborate injection into
     * the existing categories list (perhaps adding a star icon which can be toggled);
     * but in the interests of time and future extensibility to all other possible taxonomies
     * I decided I would simply add a meta-box.
     *
     * Known issue:
     * The primary taxonomy meta-box has no intrinsic knowledge of the currently selected taxonomies before saving
     * the post.  This can be fixed in several ways but is beyond the scope of the assignment as it would require
     * a backbone that took multiple taxonomy types into account from the front end editor.  Whilst new categories are
     * inserted into the db immediately, no post meta data is added when changing a category... therefor no hook without
     * hacking into the rendered page directly to trigger something.  Or perhaps making a different taxonomy selection
     * box... in which case a "primary selection" may as well be included as part of that work.
     *
     * @since 1.0.0
     */
    class UIProcedures {

        /**
         * The instance of the class for singleton use.
         *
         * @since 1.0.0
         * @access protected
         * @static
         *
         * @var UIProcedures
         */
        protected static $instance = null;


        /**
         * Returns the class instance.
         *
         * @since 1.0.0
         * @access public
         * @static
         *
         * @return UIProcedures Returns the class instance
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
        public function __construct() {
            add_action('admin_enqueue_scripts', array($this, 'select_post_admin_and_enqueue'));
            add_action("add_meta_boxes", array($this, 'add_primary_cat_meta_box'));
            add_action('save_post', array($this, 'save_primary_cat'));
        }


        /**
         * Enqueue the scripts for the admin post editor and
         * custom Gutenberg block
         *
         * @since 1.0.0
         * @access public
         *
         * @param int $hook Hook suffix for the current admin page.
         */
        public function select_post_admin_and_enqueue($hook) {
            if ('post-new.php' != $hook && 'post.php' != $hook)
                return;

            //unused until style added for post edit
        }


        /**
         * The callback from the primary taxonomy selection meta-box
         * (this can be improved upon later on to add a drop down for all relevant taxonomies)
         *
         * This takes into account the fact that taxonomies for sub levels of hierarchies
         * may share exactly the same name...
         *
         * @since 1.0.0
         * @access public
         *
         * @param $post WP_Post The post in question
         */
        public function primary_cat_meta_box_callback(WP_Post $post) {
            wp_nonce_field(basename(__FILE__), "primary-cat-meta-box-nonce");

            //Query db to find the current primary category for the post
            $primary_cat_id = PluginQueries::fetch_primary_category_id_for_post($post);

            ?>
            <div>
                <label for="cat-dropdown">Primary Category</label>
                <select name="cat-dropdown" id="cat-dropdown">
                    <?php
                    $option_values = $this->get_employed_terms($post->ID);
                    self::add_option(null == $primary_cat_id, "-1", "None");
                    if (is_array($option_values)) {
                        foreach ( $option_values as $key => $value ) {
                            self::add_option( $value->term_id == $primary_cat_id, $value->term_id, $value->name );
                        }
                    }
                    ?>
                </select>
                <br>
            </div>
            <?php
        }


        /**
         * Static utility function to build an option for the selector drop-down
         *
         * @param $selected bool Whether the option is currently selected
         * @param $id int The id of the option
         * @param $name String The label to display for the option
         *
         * @since 1.0.0
         * @access private
         * @static
         */
        private static function add_option(bool $selected, int $id, String $name) {
            if($selected) {
                ?>
                <option selected value=<?php echo $id; ?>><?php echo $name; ?></option>
                <?php
            } else {
                ?>
                <option value=<?php echo $id; ?>><?php echo $name; ?></option>
                <?php
            }
        }


        /**
         * Add the primary cat selector meta-box for the plugin
         * Just working with categories for this version
         *
         * @since 1.0.0
         * @access public
         */
        public function add_primary_cat_meta_box() {

            //Has to work for any post type for which categories are enabled as a taxonomy
            $post_types = get_post_types(array('public' => true), 'names');

            add_meta_box(
                "primary-cat-meta-box",
                "Primary Taxonomy",
                array($this,"primary_cat_meta_box_callback"),
                $post_types,
                "side",
                "high",
                null
            );
        }


        /**
         * Save the data from the meta-box
         *
         * As mentioned at the top of the class, there is currently no facility
         * to update the primary taxonomy selection in realtime based on the chosen categories in
         * the post admin edit UI.  So for now this method double checks that the current selection
         * is still valid before saving.  If the selected primary category has been removed from
         * the post's categories at the time of saving, the primary category will save as "None"
         * even though the UI does not immediately reflect it.
         *
         * As mentioned this is a known issue, but beyond the scope of the assignment for various
         * reasons.
         *
         * @since 1.0.0
         * @access public
         *
         * @param $post_id String post in question
         *
         * @return String The post id
         */
        public function save_primary_cat(String $post_id) {

            if (!isset( $_POST['primary-cat-meta-box-nonce']))
                return $post_id;

            $nonce = $_POST['primary-cat-meta-box-nonce'];

            if (!wp_verify_nonce($nonce, basename(__FILE__)))
                return $post_id;

            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
                return $post_id;

            if(!current_user_can("edit_post", $post_id))
                return $post_id;

            if(isset($_POST["cat-dropdown"])) {

                $cat_id = $_POST["cat-dropdown"];

                //if the category has gone the publisher has removed it as a category from the post
                if ($cat_id !== "-1" && in_array($cat_id, wp_list_pluck($this->get_employed_terms($post_id),'term_id'))) {
                    PluginQueries::edit_primary_category_for_post(intval($post_id), intval($cat_id));
                } else {
                    PluginQueries::edit_primary_category_for_post(intval($post_id), PluginQueries::$NONE);
                }
            }
            return $post_id;
        }


        /**
         * Get the currently employed terms for a post/custom post
         *
         * So for now this is for the "category" taxonomy but can be easily
         * extended for all other taxonomies in future.
         *
         * @param $post_id String The id of the post for which to get the terms
         *
         * @param string $name The taxonomy name
         *
         * @return WP_Term[]|false|WP_Error Array of WP_Term objects on success, false if there are no terms
         *                                  or the post does not exist, WP_Error on failure.
         *
         * @since 1.0.0
         * @access private
         */
        private function get_employed_terms(String $post_id, $name = 'category') {
            return get_the_terms($post_id, $name);
        }

    }
    $ui = UIProcedures::get_instance();
}
