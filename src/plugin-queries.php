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

/**
 * General queries for the plugin
 *
 * @since 1.0.0
 */
class PluginQueries {

    public static $NONE = -1;

    /**
     * mySQL statement to apply a primary category to a given post
     *
     * @since 1.0.0
     * @access private
     * @static
     */
    private static $apply_primary_category_to_post = /** @lang MySQL */ <<<'SQL'
        INSERT INTO wp_principal_relationships (object_id, term_taxonomy_id, term_order, taxonomy)
        VALUES(%d, %d, 0, 'category')
        ON DUPLICATE KEY UPDATE term_taxonomy_id=%d
    SQL;


    /**
     * mySQL statement to remove any primary category from a given post
     *
     * @since 1.0.0
     * @access private
     * @static
     */
    private static $remove_primary_category_from_post = /** @lang MySQL */ <<<'SQL'
        DELETE FROM wp_principal_relationships WHERE object_id=%d;
    SQL;


    /**
     * mySQL statement to list any posts (or custom posts) for a given primary category
     * (the view will not include categories that have been deleted, by design)
     *
     * @since 1.0.0
     * @access private
     * @static
     */
    private static $query_posts_for_primary_category = /** @lang MySQL */ <<<'SQL'
        SELECT
          view_all_posts_with_primary_category.post_id
        FROM view_all_posts_with_primary_category
        WHERE view_all_posts_with_primary_category.term_id=%d
    SQL;


    /**
     * mySQL statement to get the current primary category for a given post
     *
     * @since 1.0.0
     * @access private
     * @static
     */
    private static $fetch_primary_category_for_post = /** @lang MySQL */ <<<'SQL'
        SELECT
          view_all_posts_with_primary_category.term_id
        FROM view_all_posts_with_primary_category
        WHERE view_all_posts_with_primary_category.post_id=%d
        LIMIT 1
    SQL;


	/**
	 * mySQL statement to list all current primary categories
	 * based on a view which already excludes invalid relationships
	 * (such as removed categories or posts etc)
	 *
	 * @since 1.0.0
	 * @access private
	 * @static
	 */
	private static $query_all_primary_categories = /** @lang MySQL */ <<<'SQL'
        SELECT
            view_all_posts_with_primary_category.term_id,
            view_all_posts_with_primary_category.name
        FROM view_all_posts_with_primary_category
        GROUP BY view_all_posts_with_primary_category.term_id
    SQL;


    /**
     * Apply a primary category to a post
     *
     * @param $safe_post_id int The post id that is to be edited
     *
     * @param $safe_cat_id int id of the category or null if it is explicitly being set to none
     *
     * @return int|bool Boolean true for CREATE, ALTER, TRUNCATE and DROP queries. Number of rows
     *                  affected/selected for all other queries. Boolean false on error.
     * @since 1.0.0
     * @access public
     * @static
     */
    public static function edit_primary_category_for_post(int $safe_post_id, int $safe_cat_id) {
        global $wpdb;
        if ($safe_cat_id === PluginQueries::$NONE) {
            return $wpdb->query(
                $wpdb->prepare(PluginQueries::$remove_primary_category_from_post, $safe_post_id)
            );
        }
        return $wpdb->query(
            $wpdb->prepare(PluginQueries::$apply_primary_category_to_post, $safe_post_id, $safe_cat_id, $safe_cat_id)
        );
    }


    /**
     * Get all posts for a given primary category
     *
     * @param $safe_cat_id int The id of the primary category
     *
     * @return array all post ids for posts (and custom posts) if there are any for the given primary category
     *
     * @since 1.0.0
     * @access public
     * @static
     */
    public static function fetch_all_post_ids_for_primary_category(int $safe_cat_id) {
        global $wpdb;
        if ($safe_cat_id < 0) {
            return [];
        }
        return $wpdb -> get_col(
            $wpdb -> prepare(PluginQueries::$query_posts_for_primary_category, $safe_cat_id)
        );
    }


    /**
     * Get the primary category for a given post, if available
     *
     * @param WP_Post $post The post for which we are looking for a primary category
     *
     * @return String Either the category id or null if none could be found
     *
     * @since 1.0.0
     * @access public
     * @static
     */
    public static function fetch_primary_category_id_for_post(WP_Post $post) {
        global $wpdb;
        return $wpdb -> get_var(
            $wpdb -> prepare(PluginQueries::$fetch_primary_category_for_post, $post->ID)
        );
    }


	/**
	 * Get all valid primary categories that are currently in use
	 *
	 * @return array The objects ready for restful use
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function fetch_all_primary_categories() {
		global $wpdb;
		$result = $wpdb->get_results(PluginQueries::$query_all_primary_categories, OBJECT_K);
		if (null === $result)
			return [];
		return $result;
	}

}
