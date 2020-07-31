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

/**
 * This script is triggered automatically by the WP engine as needed
 */

//Check that this script is being run in the correct context
defined('ABSPATH') or die();
defined('WP_UNINSTALL_PLUGIN') or die();

$drop_posts_with_primary_cat_view = /** @lang MySQL */ <<<'SQL'
DROP VIEW IF EXISTS view_all_posts_with_primary_category
SQL;

$drop_plugin_relationship_table = /** @lang MySQL */ <<<'SQL'
DROP TABLE IF EXISTS wp_principal_relationships
SQL;

global $wpdb;
$wpdb->query($drop_posts_with_primary_cat_view);
$wpdb->query($drop_plugin_relationship_table);
