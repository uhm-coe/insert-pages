/**
 * External dependencies (npm packages).
 */
import clsx from 'clsx';

/**
 * WordPress block libraries.
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import {
	BlockControls,
	InspectorControls,
	URLInput,
	useBlockProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import {
	Panel,
	PanelBody,
	TextControl,
	ToggleControl,
	SelectControl,
	Toolbar,
	ToolbarGroup,
	ToolbarButton,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

/**
 * Style dependencies.
 */
import './style.scss';

/**
 * Internal dependencies.
 */
import InsertPageButton from './button';
import metadata from './block.json';

/**
 * Register Insert Pages block.
 */
registerBlockType( metadata.name, {
	edit: ( { attributes, setAttributes, isSelected } ) => {
		const blockProps = useBlockProps();

		const onChangeLink = ( url, post ) => {
			setAttributes( {
				url: url,
				page: ( post && post.id ) || 0,
			} );
		};

		const imageSizes = useSelect( ( select ) => {
			const { getSettings } = select( blockEditorStore );
			const sizes = getSettings()?.imageSizes || [];
			return sizes.map( ( size ) => {
				return { label: size.name, value: size.slug };
			} );
		} );

		return (
			<div { ...blockProps }>
				{ attributes.page > 0 ? (
					<ServerSideRender
						block="insert-pages/block"
						key="insert-pages/block"
						attributes={ attributes }
					/>
				) : (
					<h2 key="insert-pages/block">
						{ __( 'Choose a page to insert.', 'insert-page' ) }
					</h2>
				) }
				<BlockControls key="controls">
					<ToolbarGroup>
						<InsertPageButton
							url={ attributes.url }
							onChange={ onChangeLink }
						/>
					</ToolbarGroup>
				</BlockControls>
				<InspectorControls key="inspector">
					<Panel className="wp-block-insert-pages-block__inspector-controls">
						<PanelBody title={ __( 'Insert Page', 'insert-page' ) }>
							<URLInput
								value={ attributes.url }
								onChange={ onChangeLink }
								autoFocus={ false }
								className="width-100"
							/>
						</PanelBody>
						<PanelBody title={ __( 'Settings', 'insert-page' ) }>
							<SelectControl
								label={ __( 'Display', 'insert-page' ) }
								value={ attributes.display }
								options={ [
									{
										label: __( 'Title', 'insert-page' ),
										value: 'title',
									},
									{
										label: __(
											'Title and content',
											'insert-page'
										),
										value: 'title-content',
									},
									{
										label: __( 'Link', 'insert-page' ),
										value: 'link',
									},
									{
										label: __(
											'Excerpt with title',
											'insert-page'
										),
										value: 'excerpt',
									},
									{
										label: __(
											'Excerpt only (no title)',
											'insert-page'
										),
										value: 'excerpt-only',
									},
									{
										label: __( 'Content', 'insert-page' ),
										value: 'content',
									},
									{
										label: __(
											'Post Thumbnail',
											'insert-page'
										),
										value: 'post-thumbnail',
									},
									{
										label: __(
											'All (includes custom fields)',
											'insert-page'
										),
										value: 'all',
									},
									{
										label: __(
											'Use a custom template »',
											'insert-page'
										),
										value: 'custom',
									},
								] }
								onChange={ ( value ) =>
									setAttributes( {
										display: value,
									} )
								}
								__next40pxDefaultSize
								__nextHasNoMarginBottom
							/>
							<TextControl
								label={ __(
									'Custom Template Filename',
									'insert-page'
								) }
								value={ attributes.template }
								onChange={ ( value ) =>
									setAttributes( {
										template: value,
									} )
								}
								className={ clsx( 'custom-template', {
									hidden: attributes.display !== 'custom',
								} ) }
								__next40pxDefaultSize
								__nextHasNoMarginBottom
							/>
							<SelectControl
								label={ __(
									'Post Thumbnail Size',
									'insert-page'
								) }
								value={ attributes.size }
								options={ imageSizes }
								onChange={ ( value ) =>
									setAttributes( { size: value } )
								}
								className={ clsx( 'custom-size', {
									hidden:
										attributes.display !== 'post-thumbnail',
								} ) }
								__next40pxDefaultSize
								__nextHasNoMarginBottom
							/>
							<TextControl
								label={ __(
									'Custom CSS Class',
									'insert-page'
								) }
								value={ attributes.class }
								onChange={ ( value ) =>
									setAttributes( { class: value } )
								}
								__next40pxDefaultSize
								__nextHasNoMarginBottom
							/>
							<TextControl
								label={ __(
									'Custom Element ID',
									'insert-page'
								) }
								value={ attributes.id }
								onChange={ ( value ) =>
									setAttributes( { id: value } )
								}
								__next40pxDefaultSize
								__nextHasNoMarginBottom
							/>
							<TextControl
								label={ __(
									'Custom Querystring',
									'insert-page'
								) }
								value={ attributes.querystring }
								onChange={ ( value ) =>
									setAttributes( {
										querystring: value,
									} )
								}
								__next40pxDefaultSize
								__nextHasNoMarginBottom
							/>
							<ToggleControl
								label={ __( 'Inline?', 'insert-page' ) }
								help={
									attributes.inline
										? __(
												'Inserted page rendered in a <span>',
												'insert-page'
										  )
										: __(
												'Inserted page rendered in a <div>',
												'insert-page'
										  )
								}
								checked={ attributes.inline }
								onChange={ ( value ) =>
									setAttributes( { inline: value } )
								}
								__nextHasNoMarginBottom
							/>
							<ToggleControl
								label={ __(
									'Reveal Private Pages?',
									'insert-page'
								) }
								help={
									attributes.public
										? __(
												'Anonymous users can see this inserted even if its status is private',
												'insert-page'
										  )
										: __(
												'If this page is private, only users with permission can see it',
												'insert-page'
										  )
								}
								checked={ attributes.public }
								onChange={ ( value ) =>
									setAttributes( { public: value } )
								}
								__nextHasNoMarginBottom
							/>
						</PanelBody>
					</Panel>
				</InspectorControls>
			</div>
		);
	},
	save: ( props ) => {
		// Rendering done server-side in block_render_callback().
		return null;
	},
} );
