import { store, getContext } from '@wordpress/interactivity';

const { actions } = store( 'nickhiebert/modal-cta-notification', {
	actions: {
		close( event ) {
			event.preventDefault();
			const context = getContext();
			context.isOpen = false;
		},
		handleKeydown( event ) {
			if ( event.key === ' ' ) {
				actions.close( event );
			}
		},
	},
} );
