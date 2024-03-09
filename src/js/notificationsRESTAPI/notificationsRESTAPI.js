/* globals WPAPISettings */

/**
 * Notifications REST API.
 *
 * @package
 */

/**
 * Class NotificationsRESTAPI.
 */
class NotificationsRESTAPI {
	constructor() {
		// Collection to fetch. Should be in global scope to work with Show More button.
		this.POPUP_HASH = 'notifications';
		this.UNREAD_COUNT = 'unread-notifications-count';
		this.PER_PAGE = 9;
		if ( 768 > screen.width ) {
			this.PER_PAGE = 5;
		}

		// Init.
		// Initialize the wp.api object with the custom namespace.
		wp.api.init( { versionString: 'kagg/v1/' } );

		// Get content.
		const notificationContent = document.getElementsByClassName(
			'notifications-content'
		)[ 0 ];

		// Get and show notifications at a page load.
		if ( notificationContent ) {
			// Standard page.
			this.getNotifications( [] );
			return;
		}

		// No notifications content.
		if (
			'#' + this.POPUP_HASH ===
			window.location.hash.split( '?' )[ 0 ]
		) {
			this.showPopup(); // URL with #POPUP_HASH.
		} else {
			this.bindEvents(); // Any page.
		}
	}

	/**
	 * Get notifications list element.
	 *
	 * @return {null|HTMLDivElement} Element containing notifications list.
	 */
	getNotificationListElement() {
		const popup = document.querySelector( '#notifications-popup' );

		if ( ! popup ) {
			return document.querySelector( '#notifications-list tbody' );
		}

		if ( 'block' === popup.style.display ) {
			return popup.querySelector( '.notifications-modal-content tbody' );
		}

		return null;
	}

	/**
	 * Handler of change event on any select.
	 */
	changeEventHandler() {
		const query = [];

		[ ...document.querySelectorAll( 'select[name="channel"]' ) ].map(
			( select ) => {
				const value = select.options[ select.selectedIndex ].value;
				if ( '' !== value ) {
					query[ select.name ] = value;
				}

				return false;
			}
		);

		this.getNotifications( query );
	}

	/**
	 * Get notifications via REST API.
	 *
	 * @param {{}} query array of taxonomy=term relations.
	 */
	getNotifications( query = {} ) {
		this.notifications = null;

		const showNotifications = () =>
			this.showNotifications( this.notifications );

		return wp.api.loadPromise.done( () => {
			let i = 0;
			let queryString = '';
			for ( const key in query ) {
				if ( query.hasOwnProperty( key ) ) {
					const sep = 0 === i ? '?' : '&';

					queryString += sep + key + '=' + query[ key ];
					i++;
				}
			}

			// noinspection JSUnresolvedVariable
			const Notification = wp.api.models.Post.extend( {
				urlRoot: WPAPISettings.root + WPAPISettings.base,
			} );

			// noinspection JSUnresolvedVariable
			const Notifications = wp.api.collections.Posts.extend( {
				url: WPAPISettings.root + WPAPISettings.base + queryString,
				model: Notification,
			} );

			this.notifications = new Notifications();

			const notifications = this.notifications;

			return notifications
				.fetch( {
					// eslint-disable-next-line
					data: { per_page: this.PER_PAGE },
					error: ( collection, response ) => {
						// noinspection JSUnresolvedVariable
						this.getNotificationListElement.innerHTML =
							'<td colspan="4" class="notifications-error">' +
							response.responseJSON.message +
							'</td>';
					},
				} )
				.done( () => {
					document.querySelector(
						'#more-button'
					).disabled = ! notifications.hasMore();
					showNotifications();
				} );
		} );
	}

	/**
	 * Create notification via REST API.
	 *
	 * @param {{}[]} query array of attributes.
	 */
	createNotification( query ) {
		const getNotifications = () => this.getNotifications();

		wp.api.loadPromise.done( () => {
			// noinspection JSUnresolvedFunction
			const notification = new wp.api.models.Notifications( {} );

			for ( const key in query ) {
				if ( query.hasOwnProperty( key ) ) {
					notification.attributes[ key ] = query[ key ];
				}
			}

			notification.save().done( function() {
				getNotifications();
			} );
		} );
	}

	/**
	 * Update notification via REST API.
	 *
	 * @param {{}[]} query array of attributes.
	 */
	updateNotification( query ) {
		const getNotifications = () => this.getNotifications();
		wp.api.loadPromise.done( function() {
			// noinspection JSUnresolvedFunction
			const notification = new wp.api.models.Notifications( {} );

			for ( const key in query ) {
				if ( query.hasOwnProperty( key ) ) {
					notification.attributes[ key ] = query[ key ];
				}
			}

			notification.set();

			notification.save().done( function() {
				getNotifications();
			} );
		} );
	}

	/**
	 * Delete notification via REST API.
	 *
	 * @param {string} id string.
	 */
	deleteNotification( id ) {
		const getNotifications = () => this.getNotifications();
		wp.api.loadPromise.done( function() {
			// noinspection JSUnresolvedFunction
			const notification = new wp.api.models.Notifications( {} );

			notification.attributes.id = id;

			notification.destroy().done( function() {
				getNotifications();
			} );
		} );
	}

	/**
	 * Show notifications received via REST API.
	 *
	 * @param {{}}      notifications Notifications.
	 * @param {boolean} add           Add to output area.
	 */
	showNotifications( notifications, add = false ) {
		const notificationsListElement = this.getNotificationListElement();

		if ( ! notificationsListElement ) {
			return;
		}

		if ( notifications.hasMore() ) {
			notifications.more();
		}

		let buttons = '';
		if (
			document
				.querySelector( '.notifications-content' )
				.classList.contains( 'edit' )
		) {
			// noinspection JSUnresolvedVariable
			buttons +=
				'<img alt="Delete Button" class="delete-notification-button" src="' +
				WPAPISettings.pluginURL +
				'/images/delete-button.svg">';
			// noinspection JSUnresolvedVariable
			buttons +=
				'<img alt="Update Button" class="update-notification-button" src="' +
				WPAPISettings.pluginURL +
				'/images/update-button.svg">';
		}

		let unreadCount = 0;
		let notificationsList = '';
		notifications.each( function( notification ) {
			let readClass = '';
			if ( notification.attributes.read ) {
				readClass = ' read';
			} else {
				unreadCount++;
			}
			notificationsList += '<tr>';
			notificationsList +=
				'<td class="notification-cell' + readClass + '">';
			notificationsList += '<div class="notification-content"';
			notificationsList +=
				' data-id="' + notification.attributes.id + '"';
			notificationsList +=
				' data-channel="' + notification.attributes.channel + '"';
			notificationsList +=
				' data-users="' + notification.attributes.users + '"';
			notificationsList +=
				' data-title="' + notification.attributes.title + '"';
			notificationsList += '>';
			if ( notification.attributes.content ) {
				notificationsList += notification.attributes.content;
			} else {
				notificationsList += notification.attributes.title;
			}
			notificationsList += '</div>';
			notificationsList += '<div class="notification-data">';
			notificationsList +=
				'<span class="notification-channel">' +
				notification.attributes.channel +
				'</span>';
			if ( notification.attributes.channel ) {
				notificationsList += ' - ';
			}
			notificationsList +=
				'<span class="notification-date">' +
				notification.attributes.date +
				'</span>';
			if ( notification.attributes.users ) {
				notificationsList += ' - ';
				notificationsList +=
					'<span class="notification-users">' +
					notification.attributes.users +
					'</span>';
			}
			notificationsList += '</div>';
			notificationsList += '</td>';
			notificationsList += '<td>' + buttons + '</td>';
			notificationsList += '</tr>';
		} );

		if ( add ) {
			notificationsListElement.innerHTML += notificationsList;
		} else {
			notificationsListElement.innerHTML = notificationsList;
		}

		this.updateUnreadCountElements( unreadCount );

		this.bindEvents();
	}

	updateUnreadCountElements( unreadCount ) {
		const unreadCountElements = document.getElementsByClassName(
			this.UNREAD_COUNT
		);
		const unreadCountElementsLength = unreadCountElements.length;
		for ( let i = 0; i < unreadCountElementsLength; i++ ) {
			if ( 9 > unreadCount ) {
				unreadCountElements[ i ].innerText = unreadCount.toString();
			} else {
				unreadCountElements[ i ].innerText = '9+';
			}
			if ( unreadCount ) {
				unreadCountElements[ i ].style.display = 'inline-block';
			} else {
				unreadCountElements[ i ].style.display = 'none';
			}
		}
	}

	/**
	 * Bind events to methods.
	 */
	bindEvents() {
		let query = [];
		let deleteId = null;
		const showPopup = () => this.showPopup();
		const createNotification = () => this.createNotification( query );
		const showNotifications = () =>
			this.showNotifications( this.notifications, true );
		const updateNotification = () => this.updateNotification( query );
		const deleteNotification = () => this.deleteNotification( deleteId );
		const markAllAsReadAjax = () => this.markAllAsReadAjax();
		const getNotifications = () => this.getNotifications();

		// Click on a link containing POPUP_HASH.
		const links = document.getElementsByTagName( 'a' );
		for ( const link of links ) {
			if ( this.hasPopupHash( link.hash ) ) {
				link.onclick = function( event ) {
					event.preventDefault();
					showPopup();
					return false;
				};
			}
		}

		// Click on notification cell, toggle read status.
		const cells = document.getElementsByClassName( 'notification-cell' );
		for ( const cell of cells ) {
			cell.onclick = ( event ) => {
				if ( 'A' === event.target.tagName ) {
					return true;
				}

				const closestCell = event.target.closest(
					'.notification-cell'
				);
				closestCell.classList.toggle( 'read' );
				const id = closestCell.querySelector( '.notification-content' )
					.dataset.id;
				query = [];
				query.id = id;
				query.read = closestCell.classList.contains( 'read' );
				updateNotification( query );
				return false;
			};
		}

		window.onclick = function( event ) {
			// When a user clicks anywhere outside the modal, close it.
			if ( event.target.matches( '.notifications-modal' ) ) {
				event.target.style.display = 'none';
			}

			// When a user clicks on <span> (x), close modal.
			if ( event.target.matches( '.close' ) ) {
				event.target.closest( '.notifications-modal' ).style.display =
					'none';
			}

			// Open Create Notification modal.
			if ( event.target.matches( '#create-notification-button' ) ) {
				document.getElementById( 'create-modal' ).style.display =
					'block';
			}

			// Create notification.
			if ( event.target.matches( '#create-button' ) ) {
				const title = document
					.getElementById( 'title-text' )
					.value.trim();
				if ( '' === title ) {
					return;
				}

				const content = document
					.getElementById( 'content-text' )
					.innerHTML.trim();
				if ( '' === content ) {
					return;
				}

				document.getElementById( 'create-modal' ).style.display =
					'none';

				query = [];
				query.title = title;
				query.content = content;
				query.channel = document.getElementById( 'channel-text' ).value;
				query.users = document.getElementById( 'users-text' ).value;
				createNotification();
			}

			// Open Update Notification modal.
			if ( event.target.matches( '.update-notification-button' ) ) {
				const tr = event.target.closest( 'tr' );
				const text = tr.getElementsByClassName(
					'notification-content'
				)[ 0 ];

				document.getElementById( 'update-title-text' ).value =
					text.dataset.title;
				document.getElementById( 'update-title-text' ).dataset.id =
					text.dataset.id;
				document.getElementById( 'update-content-text' ).innerHTML =
					text.innerHTML;
				document.getElementById( 'update-channel-text' ).value =
					text.dataset.channel;
				document.getElementById( 'update-users-text' ).value =
					text.dataset.users;

				document.getElementById( 'update-modal' ).style.display =
					'block';
			}

			// Update notification.
			if ( event.target.matches( '#update-button' ) ) {
				const title = document
					.getElementById( 'update-title-text' )
					.value.trim();
				if ( '' === title ) {
					return;
				}

				const content = document
					.getElementById( 'update-content-text' )
					.innerHTML.trim();
				if ( '' === content ) {
					return;
				}

				const id = document.getElementById( 'update-title-text' )
					.dataset.id;

				document.getElementById( 'update-modal' ).style.display =
					'none';

				query = [];
				query.id = id;
				query.title = title;
				query.content = content;
				query.channel = document.getElementById(
					'update-channel-text'
				).value;
				query.users = document.getElementById(
					'update-users-text'
				).value;
				updateNotification();
			}

			// Delete notification.
			if ( event.target.matches( '.delete-notification-button' ) ) {
				const tr = event.target.closest( 'tr' );
				const text = tr.getElementsByClassName(
					'notification-content'
				)[ 0 ];

				if (
					// eslint-disable-next-line no-alert
					confirm(
						'Are you sure to delete the following notification?\n\n' +
						text.innerText
					)
				) {
					deleteId = text.dataset.id;
					deleteNotification();
				}
			}

			// Mark all as read.
			if ( event.target.matches( '#read-button' ) ) {
				markAllAsReadAjax().then( ( response ) => {
					const notifications = document.getElementsByClassName(
						'notification-cell'
					);
					if ( response === 'done' ) {
						for ( const notification in notifications ) {
							notifications
								.item( notification )
								.classList.add( 'read' );
						}
					}
				} );
				getNotifications();
			}
		};

		// Select change handler.
		[ ...document.querySelectorAll( 'select[name="channel"]' ) ].map(
			( select ) => ( select.onchange = () => this.changeEventHandler() )
		);

		// Show More button.
		const moreButton = document.querySelector( '#more-button' );
		if ( null !== moreButton ) {
			moreButton.onclick = () => {
				moreButton.disabled = ! this.notifications.hasMore();
				showNotifications();
			};
		}

		document.addEventListener( 'update_unread_counts', ( event ) => {
			this.updateUnreadCountElements( event.detail );
		} );
	}

	/**
	 * Check if url has popup hash (#notifications by default).
	 *
	 * @param {string} href Href.
	 */
	hasPopupHash( href ) {
		if ( 'undefined' === typeof href || '' === href ) {
			return false;
		}
		return this.POPUP_HASH === href.split( '#' )[ 1 ].split( '?' )[ 0 ];
	}

	/**
	 * Get content of a popup window with notifications.
	 *
	 * @return {Promise<any>} Promise.
	 */
	getPopupContent() {
		const data = {
			action: 'kagg_notification_get_popup_content',
			nonce: WPAPISettings.nonce,
		};

		return this.ajax( data );
	}

	/**
	 * Show a popup window.
	 */
	showPopup() {
		let popup = document.getElementById( 'notifications-popup' );

		if ( ! popup ) {
			popup = document.createElement( 'div' );
			popup.id = 'notifications-popup';
			popup.className = 'notifications-modal';
			popup.innerHTML = '<div class="notifications-modal-content"></div>';
			document.body.appendChild( popup );
		}

		this.getPopupContent().then( ( response ) => {
			const modalContent = popup.getElementsByClassName(
				'notifications-modal-content'
			)[ 0 ];
			modalContent.innerHTML =
				'<span class="close">&times;</span>' + response;
			popup.style.display = 'block';

			this.bindEvents();

			this.getNotifications();

			document.body.appendChild(
				document.getElementById( 'create-modal' )
			);
			document.body.appendChild(
				document.getElementById( 'update-modal' )
			);
		} );
	}

	/**
	 * Mark all notifications as read.
	 *
	 * @return {Promise<any>} Promise.
	 */
	markAllAsReadAjax() {
		const data = {
			action: 'kagg_notification_make_all_as_read',
			nonce: WPAPISettings.nonce,
			current_user: document.getElementById( 'current-user' ).value,
		};

		return this.ajax( data );
	}

	ajax( data ) {
		const encodedData = Object.keys( data )
			.map(
				( key ) =>
					encodeURIComponent( key ) +
					'=' +
					encodeURIComponent( data[ key ] )
			)
			.join( '&' );

		// noinspection JSUnresolvedVariable
		return fetch( WPAPISettings.ajaxURL, {
			method: 'POST',
			body: encodedData,
			headers: {
				'Content-Type':
					'application/x-www-form-urlencoded; charset=utf-8',
			},
			credentials: 'same-origin',
		} )
			.then( ( response ) => {
				if ( ! response.ok ) {
					throw new Error( response.statusText );
				}
				return response.json();
			} )
			.then( ( response ) => {
				if ( response.success ) {
					return response.data;
				}
				throw new Error( response.data );
			} )
			.catch( ( reason ) => {
				return reason.message;
			} );
	}
}

export default NotificationsRESTAPI;
