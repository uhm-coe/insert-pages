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
	Button,
	Dropdown,
	Panel,
	PanelBody,
	TextControl,
	ToggleControl,
	SelectControl,
	ToolbarGroup,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';

/**
 * Style dependencies.
 */
import './style.scss';

/**
 * Internal dependencies.
 */
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

		const buttonLabel = attributes.url
			? __( 'Change Inserted Page', 'insert-page' )
			: __( 'Insert Page', 'insert-page' );

		return (
			<div { ...blockProps }>
				{ attributes.page > 0 || attributes.url ? (
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
						<Dropdown
							popoverProps={ {
								className: 'insert-pages-urlinput-popover',
								placement: 'bottom-start',
							} }
							renderToggle={ ( { isOpen, onToggle } ) => (
								<Button
									icon="admin-links"
									label={ buttonLabel }
									onClick={ onToggle }
									aria-expanded={ isOpen }
								/>
							) }
							renderContent={ () => (
								<URLInput
									label={ __( 'Insert Page', 'insert-page' ) }
									value={ attributes.url }
									onChange={ onChangeLink }
								/>
							) }
						></Dropdown>
					</ToolbarGroup>
				</BlockControls>
				<InspectorControls key="inspector">
					<Panel className="wp-block-insert-pages-block__inspector-controls">
						<PanelBody title={ __( 'Insert Page', 'insert-page' ) }>
							<Dropdown
								popoverProps={ {
									className: 'insert-pages-urlinput-popover',
									placement: 'bottom-end',
								} }
								renderToggle={ ( { isOpen, onToggle } ) => (
									<Button
										variant="primary"
										icon="admin-links"
										label={ buttonLabel }
										onClick={ onToggle }
										aria-expanded={ isOpen }
									>
										{ buttonLabel }
									</Button>
								) }
								renderContent={ () => (
									<URLInput
										value={ attributes.url }
										onChange={ onChangeLink }
									/>
								) }
							></Dropdown>
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
