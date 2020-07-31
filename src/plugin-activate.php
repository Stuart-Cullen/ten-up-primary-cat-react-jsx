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
 * Activation procedures for the plugin
 *
 * @since 1.0.0
 */
class PluginActivate {

    /**
     * mySQL statement to create the new relationship table
     *
     * @since 1.0.0
     * @access private
     * @static
     */
    private static $create_plugin_relationship_table = /** @lang MySQL */ <<<'SQL'
        CREATE TABLE IF NOT EXISTS wp_principal_relationships (
          object_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
          term_taxonomy_id bigint(20) UNSIGNED NOT NULL DEFAULT 0,
          term_order int(11) NOT NULL DEFAULT 0,
          taxonomy varchar(32) DEFAULT 'category',
          PRIMARY KEY (object_id)
        )
        ENGINE = INNODB,
        AVG_ROW_LENGTH = 3276,
        CHARACTER SET utf8mb4,
        COLLATE utf8mb4_unicode_520_ci;
    SQL;


    /**
     * mySQL statement to create a view of all posts with primary categories set
     *
     * @since 1.0.0
     * @access private
     * @static
     */
    private static $create_posts_with_primary_cats_view = /** @lang MySQL */ <<<'SQL'
        CREATE OR REPLACE VIEW view_all_posts_with_primary_category
        AS
        SELECT
            `wp_posts`.`post_title` AS `post_title`,
            `wp_terms`.`name` AS `name`,
            `wp_posts`.`ID` AS `post_id`,
            `wp_terms`.`term_id` AS `term_id`
        FROM ((`wp_posts`
            JOIN `wp_principal_relationships`
        ON ((`wp_posts`.`ID` = `wp_principal_relationships`.`object_id`)))
            JOIN `wp_terms`
        ON ((`wp_principal_relationships`.`term_taxonomy_id` = `wp_terms`.`term_id`)));
    SQL;


    /**
     * Perform the activation and generate the new table
     *
     * @since 1.0.0
     * @access public
     * @static
     *
     * @return int|bool Boolean true for CREATE, ALTER, TRUNCATE and DROP queries. Number of rows
     *                  affected/selected for all other queries. Boolean false on error.
     */
    public static function activate() {
        global $wpdb;
        if ($wpdb->query(PluginActivate::$create_plugin_relationship_table )) {
            return $wpdb->query(PluginActivate::$create_posts_with_primary_cats_view );
        } ;
        return false;
    }

}
