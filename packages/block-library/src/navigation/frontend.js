/**
 * External dependencies
 */
import MicroModal from 'micromodal';

function navigationToggleModal() {
	const toggleClass = ( el, className ) => el.classList.toggle( className );
	toggleClass( document.querySelector( 'html' ), 'has-modal-open' );
}

MicroModal.init( {
	// eslint-disable-next-line no-unused-vars
	onShow: ( modal ) => navigationToggleModal(),
	// eslint-disable-next-line no-unused-vars
	onClose: ( modal ) => navigationToggleModal(),
	openClass: 'is-menu-open',
} );
