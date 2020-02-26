/**
 * External dependencies
 */
import '@testing-library/jest-dom/extend-expect';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import Edit from './edit';

// Mock the <InpectorControls> component only, so that the other components in this package behave as usual.
jest.mock( '@wordpress/block-editor', () => {
	const original = require.requireActual( '@wordpress/block-editor' );
	return {
		...original,
		InspectorControls: ( { children } ) => children,
	};
} );

const baseProps = { attributes: {} };
const setup = ( props ) => {
	return render( <Edit { ...props } /> );
};
const getModelViewer = () => document.querySelector( 'model-viewer' );

describe( 'Edit', () => {
	it.each( [
		[ '', 0 ],
		[ 'https://baz.com', 1 ],
	] )( 'only displays a preview of the model-viewer if there is a url', ( url, lengthOfFoundTags ) => {
		setup( { attributes: { url } } );
		expect( document.getElementsByTagName( 'model-viewer' ) ).toHaveLength( lengthOfFoundTags );
	} );

	it.each( [
		[ 'https://foo.com', 'Edit model' ],
		[ '', 'Model' ],
	] )( 'displays the correct title, depending on whether there is a url', ( url, expectedTitle ) => {
		setup( { attributes: { url } } );
		expect( screen.getByText( expectedTitle ) ).toBeInTheDocument();
	} );

	it( 'has the background-color attribute in the model-viewer component when it exists', () => {
		const backgroundColor = '#cd2653';
		setup( { attributes: { backgroundColor, url: 'https://baz.com' } } );
		expect( getModelViewer().getAttribute( 'background-color' ) ).toEqual( backgroundColor );
	} );
 
	it( 'displays the background color label', () => {
		setup( baseProps );
		expect( screen.getByText( 'Background Color' ) ).toBeInTheDocument();
	} );

	it( 'has an auto-rotate attribute in the model-viewer component when autoRotate is true', () => {
		setup( { attributes: { url: 'https://baz.com', autoRotate: true } } );
		expect( getModelViewer().hasAttribute( 'auto-rotate' ) ).toEqual( true );
	} );

	it( 'displays the media instructions, even if there is no url or id', () => {
		setup( baseProps );
		expect( screen.getByText( 'Upload a model file, or choose one from your media library' ) ).toBeInTheDocument();
	} );
} );
