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
	edit: ( props ) => {
		const blockProps = useBlockProps();

		const onChangeLink = ( url, post ) => {
			props.setAttributes( {
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
				{ props.attributes.page > 0 ? (
					<ServerSideRender
						block="insert-pages/block"
						key="insert-pages/block"
						attributes={ props.attributes }
					/>
				) : (
					<h2 key="insert-pages/block">
						{ __( 'Choose a page to insert.', 'insert-page' ) }
					</h2>
				) }
				{ !! props.isSelected && (
					<BlockControls key="controls">
						<Toolbar
							label={ __( 'Insert page', 'insert-page' ) }
							className="components-toolbar"
						>
							<InsertPageButton
								url={ props.attributes.url }
								onChange={ onChangeLink }
							/>
						</Toolbar>
					</BlockControls>
				) }
				{ !! props.isSelected && (
					<InspectorControls key="inspector">
						<Panel className="wp-block-insert-pages-block__inspector-controls">
							<PanelBody
								title={ __( 'Insert Page', 'insert-page' ) }
							>
								<URLInput
									value={ props.attributes.url }
									onChange={ onChangeLink }
									autoFocus={ false }
									className="width-100"
								/>
							</PanelBody>
							<PanelBody
								title={ __( 'Settings', 'insert-page' ) }
							>
								<SelectControl
									label={ __( 'Display', 'insert-page' ) }
									value={ props.attributes.display }
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
											label: __(
												'Content',
												'insert-page'
											),
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
										props.setAttributes( {
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
									value={ props.attributes.template }
									onChange={ ( value ) =>
										props.setAttributes( {
											template: value,
										} )
									}
									className={ clsx( 'custom-template', {
										hidden:
											props.attributes.display !==
											'custom',
									} ) }
									__next40pxDefaultSize
									__nextHasNoMarginBottom
								/>
								<SelectControl
									label={ __(
										'Post Thumbnail Size',
										'insert-page'
									) }
									value={ props.attributes.size }
									options={ imageSizes }
									onChange={ ( value ) =>
										props.setAttributes( { size: value } )
									}
									className={ clsx( 'custom-size', {
										hidden:
											props.attributes.display !==
											'post-thumbnail',
									} ) }
									__next40pxDefaultSize
									__nextHasNoMarginBottom
								/>
								<TextControl
									label={ __(
										'Custom CSS Class',
										'insert-page'
									) }
									value={ props.attributes.class }
									onChange={ ( value ) =>
										props.setAttributes( { class: value } )
									}
									__next40pxDefaultSize
									__nextHasNoMarginBottom
								/>
								<TextControl
									label={ __(
										'Custom Element ID',
										'insert-page'
									) }
									value={ props.attributes.id }
									onChange={ ( value ) =>
										props.setAttributes( { id: value } )
									}
									__next40pxDefaultSize
									__nextHasNoMarginBottom
								/>
								<TextControl
									label={ __(
										'Custom Querystring',
										'insert-page'
									) }
									value={ props.attributes.querystring }
									onChange={ ( value ) =>
										props.setAttributes( {
											querystring: value,
										} )
									}
									__next40pxDefaultSize
									__nextHasNoMarginBottom
								/>
								<ToggleControl
									label={ __( 'Inline?', 'insert-page' ) }
									help={
										props.attributes.inline
											? __(
													'Inserted page rendered in a <span>',
													'insert-page'
											  )
											: __(
													'Inserted page rendered in a <div>',
													'insert-page'
											  )
									}
									checked={ props.attributes.inline }
									onChange={ ( value ) =>
										props.setAttributes( { inline: value } )
									}
									__nextHasNoMarginBottom
								/>
								<ToggleControl
									label={ __(
										'Reveal Private Pages?',
										'insert-page'
									) }
									help={
										props.attributes.public
											? __(
													'Anonymous users can see this inserted even if its status is private',
													'insert-page'
											  )
											: __(
													'If this page is private, only users with permission can see it',
													'insert-page'
											  )
									}
									checked={ props.attributes.public }
									onChange={ ( value ) =>
										props.setAttributes( { public: value } )
									}
									__nextHasNoMarginBottom
								/>
							</PanelBody>
						</Panel>
					</InspectorControls>
				) }
			</div>
		);
	},
	save: ( props ) => {
		// Rendering done server-side in block_render_callback().
		return null;
	},
} );
