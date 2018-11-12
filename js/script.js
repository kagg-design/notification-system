/**
 * Javascript REST API.
 */
document.addEventListener('DOMContentLoaded', function () {
	// Collection to fetch. Should be in global scope to work with Show More button.
	let notifications = null;
	const POPUP_HASH = 'notifications';
	let PER_PAGE = 10;
	if (screen.width < 768) {
		PER_PAGE = 5;
	}

	/**
	 * Handler of change event ion any select.
	 *
	 * @param event Event.
	 */
	function changeEventHandler(event) {
		let query = [];
		const elements = document.querySelectorAll('#notifications-header select');
		for (let i = 0; i < elements.length; i++) {
			const e = elements[i];
			const value = e.options[e.selectedIndex].value;
			if (value !== '') {
				query[e.name] = value;
			}
		}
		getNotifications(query);
	}

	/**
	 * Get notifications via REST API.
	 *
	 * @param query array of taxonomy=term relations.
	 */
	function getNotifications(query) {
		wp.api.loadPromise.done(function () {
			let i = 0;
			let query_string = '';
			for (let key in query) {
				if (query.hasOwnProperty(key)) {
					let sep = (i === 0) ? '?' : '&';
					query_string += sep + key + '=' + query[key];
					i++;
				}
			}

			const Notification = wp.api.models.Post.extend({
				urlRoot: WP_API_Settings.root + WP_API_Settings.base,
			});

			const Notifications = wp.api.collections.Posts.extend({
				url: WP_API_Settings.root + WP_API_Settings.base + query_string,
				model: Notification
			});

			notifications = new Notifications;

			notifications.fetch({
				data: {per_page: PER_PAGE},
				error: function (collection, response, options) {
					document.querySelector('#notifications-list tbody').innerHTML = '<td colspan="4" class="notifications-error">' + response.responseJSON.message + '</td>';
				}
			}).done(function (collection, response, options) {
				document.querySelector('#more-button').disabled = !notifications.hasMore();
				showNotifications(notifications);
			});
		});
	}

	/**
	 * Create notification via REST API.
	 *
	 * @param query array of attributes.
	 */
	function createNotification(query) {
		wp.api.loadPromise.done(function () {
			let notification = new wp.api.models.Notifications({});

			for (let key in query) {
				if (query.hasOwnProperty(key)) {
					notification.attributes[key] = query[key];
				}
			}

			notification.save().done(
				function (response) {
					getNotifications();
				});
		});
	}

	/**
	 * Update notification via REST API.
	 *
	 * @param query array of attributes.
	 */
	function updateNotification(query) {
		wp.api.loadPromise.done(function () {
			let notification = new wp.api.models.Notifications({});

			for (let key in query) {
				if (query.hasOwnProperty(key)) {
					notification.attributes[key] = query[key];
				}
			}

			notification.set();

			notification.save().done(
				function (response) {
					getNotifications();
				});
		});
	}

	/**
	 * Delete notification via REST API.
	 *
	 * @param id string.
	 */
	function deleteNotification(id) {
		wp.api.loadPromise.done(function () {
			let notification = new wp.api.models.Notifications({});

			notification.attributes['id'] = id;

			notification.destroy().done(
				function (response) {
					getNotifications();
				});
		});
	}

	/**
	 * Show notifications received via REST API.
	 *
	 * @param notifications Notifications.
	 * @param add bool Add to output area.
	 */
	function showNotifications(notifications, add) {
		const tbody = document.querySelector('#notifications-list tbody');
		if (tbody.length === 0) {
			return;
		}

		if (notifications.hasMore()) {
			notifications.more();
		}

		let buttons = '';
		if (document.querySelector('.notifications-content').classList.contains('edit')) {
			buttons += '<img class="delete-notification-button" src="' + WP_API_Settings.pluginURL + '/images/delete-button.svg' + '">';
			buttons += '<img class="update-notification-button" src="' + WP_API_Settings.pluginURL + '/images/update-button.svg' + '">';
		}

		let notificationsList = '';
		notifications.each(function (notification) {
			notificationsList += '<tr>';
			notificationsList += '<td>';
			notificationsList += '<div class="notification-content"';
			notificationsList += ' data-id="' + notification.attributes.id + '"';
			notificationsList += ' data-channel="' + notification.attributes.channel + '"';
			notificationsList += ' data-title="' + notification.attributes.title + '"';
			notificationsList += '>';
			notificationsList += notification.attributes.content;
			notificationsList += '</div>';
			notificationsList += '<div class="notification-data">';
			notificationsList += '<span class="notification-channel">' + notification.attributes.channel + '</span>';
			notificationsList += ' - ';
			notificationsList += '<span class="notification-date">' + notification.attributes.date + '</span>';
			notificationsList += '</div>';
			notificationsList += '</td>';
			notificationsList += '<td>' + buttons + '</td>';
			notificationsList += '</tr>';
		});

		if (typeof add === 'undefined') {
			add = false;
		}

		if (add) {
			tbody.innerHTML += notificationsList;
		} else {
			tbody.innerHTML = notificationsList;
		}
	}


	/**
	 * Get content of popup window with notifications.
	 */
	function getPopupContent() {
		const data = {
			action: 'kagg_notification_get_popup_content',
			nonce: WP_API_Settings.nonce,
		};

		const encodedData = Object.keys(data)
			.map((key) => encodeURIComponent(key) + '=' + encodeURIComponent(data[key]))
			.join('&');

		return fetch(WP_API_Settings.ajaxURL,
			{
				method: 'POST',
				body: encodedData,
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
				},
				credentials: 'same-origin'
			})
			.then(response => {
				if (!response.ok) {
					throw new Error(response.statusText);
				}
				return response.json();
			})
			.then(response => {
				if (response.success) {
					return response.data;
				} else {
					throw new Error(response.data);
				}
			})
			.catch((reason) => {
				return reason.message;
			});
	}

	function bindEvents() {
		window.onclick = function (event) {
			// Click on link containing POPUP_HASH
			if (typeof event.target.href !== 'undefined') {
				if (POPUP_HASH === event.target.href.split('#')[1].split('?')[0]) {
					event.preventDefault();
					showPopup();
					return false;
				}
			}

			// When user clicks anywhere outside of the modal, close it.
			if (event.target.matches('.notifications-modal')) {
				event.target.style.display = 'none';
			}

			// When user clicks on <span> (x), close modal.
			if (event.target.matches('.close')) {
				event.target.closest('.notifications-modal').style.display = 'none';
			}

			// Open Create Notification modal.
			if (event.target.matches('#create-notification-button')) {
				document.getElementById('create-modal').style.display = 'block';
			}

			// Create notification.
			if (event.target.matches('#create-button')) {
				const title = document.getElementById('title-text').value.trim();
				if ('' === title) {
					return;
				}

				const content = document.getElementById('content-text').innerHTML.trim();
				if ('' === content) {
					return;
				}

				document.getElementById('create-modal').style.display = 'none';
				let query = [];
				query['title'] = title;
				query['content'] = content;
				query['channel'] = document.getElementById('channel-text').value;
				createNotification(query);
			}

			// Open Update Notification modal.
			if (event.target.matches('.update-notification-button')) {
				const tr = event.target.closest('tr');
				const text = tr.getElementsByClassName('notification-content')[0];

				document.getElementById('update-title-text').value = text.dataset.title;
				document.getElementById('update-title-text').dataset.id = text.dataset.id;
				document.getElementById('update-content-text').innerHTML = text.innerHTML;
				document.getElementById('update-channel-text').value = text.dataset.channel;

				document.getElementById('update-modal').style.display = 'block';
			}

			// Update notification.
			if (event.target.matches('#update-button')) {
				const title = document.getElementById('update-title-text').value.trim();
				if ('' === title) {
					return;
				}

				const content = document.getElementById('update-content-text').innerHTML.trim();
				if ('' === content) {
					return;
				}

				const id = document.getElementById('update-title-text').dataset.id;

				document.getElementById('update-modal').style.display = 'none';
				let query = [];
				query['id'] = id;
				query['title'] = title;
				query['content'] = content;
				query['channel'] = document.getElementById('update-channel-text').value;
				updateNotification(query);
			}

			// Delete notification.
			if (event.target.matches('.delete-notification-button')) {
				const tr = event.target.closest('tr');
				const text = tr.getElementsByClassName('notification-content')[0];

				const title = text.dataset.title;
				if (confirm("Are you sure to delete the following notification?\n\n" + text.innerText)) {
					deleteNotification(text.dataset.id);
				}
			}
		};

		// Select change handler.
		const select = document.querySelector('#notifications-header select');
		if (select !== null) {
			select.addEventListener('change', changeEventHandler, false);
		}

		// Show More button.
		const moreButton = document.querySelector('#more-button');
		if (moreButton !== null) {
			moreButton.addEventListener('click', function () {
				moreButton.disabled = !notifications.hasMore();
				showNotifications(notifications, true);
			}, false);
		}
	}

	/**
	 * Show popup window.
	 */
	function showPopup() {
		let popup = document.getElementById('notifications-popup');
		if (!popup) {
			popup = document.createElement('div');
			popup.id = 'notifications-popup';
			popup.className = 'notifications-modal';
			popup.innerHTML = '<div class="notifications-modal-content"></div>';
			document.body.appendChild(popup);
		}
		getPopupContent().then(response => {
			const modalContent = document.getElementsByClassName('notifications-modal-content')[0];
			modalContent.innerHTML = '<span class="close">&times;</span>' + response;
			bindEvents();
			getNotifications([]);
			popup.style.display = 'block';
		});
	}

	// Init.

	// This initializes the wp.api object with the custom namespace.
	wp.api.init({'versionString': 'kagg/v1/'});

	// Get content.
	const notificationsContent = document.getElementsByClassName('notifications-content')[0];

	// Get and show notifications at page load.
	if (notificationsContent) {
		// Standard page.
		bindEvents();
		getNotifications([]);
	} else {
		// No notifications content.
		if ('#' + POPUP_HASH === window.location.hash.split('?')[0]) {
			// URL with #POPUP_HASH
			showPopup();
		} else {
			// Any page
			bindEvents();
		}
	}
});
