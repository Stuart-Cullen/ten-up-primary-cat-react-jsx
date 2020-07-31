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
 * BLOCK: 'ten-up-primary-cat-react-jsx/primary-block'
 *
 * A dynamic block for displaying a list of posts/custom post types for
 * a given primary category using React and JSX at this end
 *
 * Uses the "is_primary" flag that has been supplemented to the standard
 * category rest response by this plugin
 */

import './editor.scss';
import './style.scss';

import React, { useEffect, useState } from 'react';
import { SelectControl } from '@wordpress/components';
import { withState } from '@wordpress/compose';

const { __ } = wp.i18n;
const { registerBlockType } = wp.blocks;


/**
 * Registers the primary category Gutenberg block
 */
registerBlockType( 'ten-up-primary-cat-react-jsx/primary-block2', {
	title: __('Primary Category'),
	icon: 'star-filled',
	category: 'common',
	keywords: [
		__('Primary'),
		__('Category'),
		__('10up'),
	],
	attributes: {
		primary_category: {
			type: 'number',
		}
	},


	/**
	 * Fetch and filter the primary categories
	 * Gives the user the option selector to choose which primary category
	 * from which to list posts
	 *
	 * @returns {*} Rendered output
	 */
	edit: ( { attributes, setAttributes } ) => {
		const [categories, setCategories] = useState([]);

		const primary_categories = [];
		primary_categories.push( { value: 0, label: 'None' } );

		useEffect(() => {
			async function loadAllCategories() {
				const response = await fetch('/wp-json/wp/v2/categories');
				if(!response.ok) {
					return;
				}
				const categories = await response.json();
				setCategories(categories);
			}

			loadAllCategories();

		}, [])

		{categories
			.filter(category => category.is_primary)
			.map((category, index) => {
			primary_categories.push({ value: category.id, label: category.name })
		})}

		const PrimaryCatSelector = withState()( () => (
			<SelectControl
				label="Primary Category Listing:&nbsp;"
				value={attributes.primary_category}
				options={primary_categories}
				onChange={( primary_id ) => {
					setAttributes( { primary_category: parseInt(primary_id)})
				}}
			/>
		));

		return (
			<div>
				<PrimaryCatSelector/>
			</div>
		)
	},


	/**
	 * Driven from the server-side
	 *
	 * @param attributes The attributes needed for rendering
	 * @returns {null} Not rendered here
	 */
	save: ({ attributes }) => {
		return null;
	},

});
