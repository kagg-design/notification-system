<?php
/**
 * Notifications_API_Controller class file.
 *
 * @package notification-system
 */

namespace KAGG\Notification_System;

use Exception;
use WP_Error;
use WP_Post;
use WP_Query;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Class Notifications_API_Controller
 */
class Notifications_API_Controller extends WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'kagg/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'notifications';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'notification';

	/**
	 * List_In_Meta instance.
	 *
	 * @var List_In_Meta
	 */
	public $list_in_meta;

	/**
	 * Notifications_API_Controller constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Init controller.
	 */
	private function init() {
		$this->list_in_meta = new List_In_Meta();
	}

	/**
	 * Register routes for API.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => [ $this, 'get_items_permissions_check' ],
					'args'                => $this->get_collection_params(),
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_item' ],
					'permission_callback' => [ $this, 'create_item_permissions_check' ],
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			[
				'args'   => [
					'id' => [
						'description' => __( 'Unique identifier for the resource.', 'notification-system' ),
						'type'        => 'integer',
					],
				],
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_item' ],
					'permission_callback' => [ $this, 'get_item_permissions_check' ],
					'args'                => [
						'context' => $this->get_context_param(
							[
								'default' => 'view',
							]
						),
					],
				],
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'update_item' ],
					'permission_callback' => [ $this, 'update_item_permissions_check' ],
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_item' ],
					'permission_callback' => [ $this, 'delete_item_permissions_check' ],
					'args'                => [
						'force' => [
							'default'     => false,
							'description' => __( 'Whether to bypass trash and force deletion.', 'notification-system' ),
							'type'        => 'boolean',
						],
					],
				],
				'schema' => [ $this, 'get_public_item_schema' ],
			]
		);
	}

	/**
	 * Get the query params for collections of attachments.
	 *
	 * @return array
	 */
	public function get_collection_params(): array {
		$params['slug']    = [
			'description'       => __( 'Limit result set to notification with a specific slug.', 'notification-system' ),
			'type'              => 'string',
			'validate_callback' => 'rest_validate_request_arg',
		];
		$params['status']  = [
			'default'           => 'any',
			'description'       => __( 'Limit result set to notifications assigned a specific status.', 'notification-system' ),
			'type'              => 'string',
			'enum'              => array_merge( [ 'any' ], array_keys( get_post_statuses() ) ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		];
		$params['channel'] = [
			'description'       => __( 'Limit result set to notifications assigned a specific channel.', 'notification-system' ),
			'type'              => 'string',
			'sanitize_callback' => 'wp_parse_slug_list',
			'validate_callback' => 'rest_validate_request_arg',
		];

		return $params;
	}


	/**
	 * Retrieves an array of endpoint arguments from the item schema for the controller.
	 *
	 * @param string $method Optional. HTTP method of the request. The arguments for `CREATABLE` requests are
	 *                       checked for required values and may fall back to a given default, this is not done
	 *                       on `EDITABLE` requests. Default WP_REST_Server::CREATABLE.
	 *
	 * @return array Endpoint arguments.
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ): array {
		// @todo Expand.
		return [];
	}

	/**
	 * Get a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_item( $request ) {
		$object = get_post( (int) $request['id'] );

		if ( ! $object ) {
			return new WP_Error(
				'KAGG_Notification_rest_invalid_id',
				__( 'Invalid ID.', 'notification-system' ),
				[ 'status' => 404 ]
			);
		}

		/**
		 * Post object for response.
		 *
		 * @var WP_Post $object
		 */
		$data = $this->prepare_object_for_response( $object, $request );

		return rest_ensure_response( $data );
	}

	/**
	 * Create a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		if ( ! empty( $request['id'] ) ) {
			return new WP_Error(
				'KAGG_Notification_rest_exists',
				__( 'Cannot create existing post.', 'notification-system' ),
				[ 'status' => 400 ]
			);
		}

		$postarr = [
			'post_type'   => $this->post_type,
			'post_status' => 'publish',
		];

		$postarr = $this->copy_request_fields( $request, $postarr );

		$post_id = wp_insert_post( $postarr );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$response = $this->process_item_fields( $post_id, $request );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $post_id ) ) );

		return $response;
	}

	/**
	 * Process item fields.
	 *
	 * @param int             $post_id Post ID.
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	private function process_item_fields( $post_id, $request ) {
		if ( isset( $request['users'] ) ) {
			$this->set_user_list( $post_id, $request['users'] );
		}

		$this->add_taxonomies( $post_id, $request );

		$object = get_post( $post_id );

		try {
			$this->update_additional_fields_for_object( $object, $request );
		} catch ( Exception $e ) {
			return new WP_Error( $e->getCode(), $e->getMessage(), [ 'status' => $e->getCode() ] );
		}

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_object_for_response( $object, $request );

		return rest_ensure_response( $response );
	}

	/**
	 * Update a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 */
	public function update_item( $request ) {
		$object = get_post( (int) $request['id'] );

		if ( ! $object || 0 === $object->ID ) {
			return new WP_Error(
				'KAGG_Notification_invalid_id',
				__( 'Invalid ID.', 'notification-system' ),
				[ 'status' => 400 ]
			);
		}

		$postarr = [
			'ID' => $object->ID,
		];

		$postarr = $this->copy_request_fields( $request, $postarr );

		$post_id = wp_update_post( $postarr );

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		if ( isset( $request['read'] ) ) {
			$this->set_read_status( $post_id, $request['read'] );
		}

		return $this->process_item_fields( $post_id, $request );
	}

	/**
	 * Delete a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$id     = (int) $request['id'];
		$object = get_post( $id );

		if ( ! $object || 0 === $object->ID ) {
			return new WP_Error(
				'KAGG_Notification_invalid_id',
				__( 'Invalid ID.', 'notification-system' ),
				[
					'status' => 404,
				]
			);
		}

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_object_for_response( $object, $request );

		$result = wp_delete_post( $object->ID );

		if ( ! $result ) {
			return new WP_Error(
				'KAGG_NOTIFICATIONS_rest_cannot_delete',
				__( 'The item cannot be deleted.', 'notification-system' ),
				[
					'status' => 500,
				]
			);
		}

		return $response;
	}

	/**
	 * Get a collection of posts.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|WP_REST_Response
	 * @noinspection DuplicatedCode DuplicatedCode.
	 */
	public function get_items( $request ) {
		$query_args    = $this->prepare_objects_query( $request );
		$query_results = $this->get_objects( $query_args );

		$objects = [];
		foreach ( $query_results['objects'] as $object ) {
			$data      = $this->prepare_object_for_response( $object, $request );
			$objects[] = $this->prepare_response_for_collection( $data );
		}

		$paged     = $query_args['paged'] ?? '';
		$page      = (int) $paged;
		$max_pages = $query_results['pages'];

		$response = rest_ensure_response( $objects );
		$response->header( 'X-WP-Total', $query_results['total'] );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		$base = add_query_arg(
			$request->get_query_params(),
			rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) )
		);

		if ( $page > 1 ) {
			$prev_page = $page - 1;

			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}

			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}

		if ( $max_pages > $page ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );
			$response->link_header( 'next', $next_link );
		}

		return $response;
	}

	/**
	 * Prepare objects query.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return array
	 */
	protected function prepare_objects_query( $request ): array {
		$args                        = [];
		$args['offset']              = $request['offset'];
		$args['order']               = $request['order'];
		$args['orderby']             = $request['orderby'];
		$args['paged']               = $request['page'];
		$args['post__in']            = $request['include'];
		$args['post__not_in']        = $request['exclude'];
		$args['posts_per_page']      = $request['per_page'];
		$args['name']                = $request['slug'];
		$args['post_parent__in']     = $request['parent'];
		$args['post_parent__not_in'] = $request['parent_exclude'];
		$args['s']                   = $request['search'];
		$args['read']                = $request['read'];

		if ( 'date' === $args['orderby'] ) {
			$args['orderby'] = 'date ID';
		}

		// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		$args['tax_query'] = [];
		if ( isset( $request['channel'] ) ) {
			$args['tax_query'] = [
				[
					'taxonomy' => 'channel',
					'field'    => 'slug',
					'terms'    => $request['channel'],
				],
			];
		}
		// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_tax_query

		$args['date_query'] = [];
		// Set before into a date query. Date query must be specified as an array of an array.
		if ( isset( $request['before'] ) ) {
			$args['date_query'][0]['before'] = $request['before'];
		}

		// Set after into a date query. Date query must be specified as an array of an array.
		if ( isset( $request['after'] ) ) {
			$args['date_query'][0]['after'] = $request['after'];
		}

		// Force the post_type argument, since it's not a user input variable.
		$args['post_type'] = $this->post_type;

		return $this->prepare_items_query( $args, $request );
	}

	/**
	 * Determine the allowed query_vars for a get_items() response and
	 * prepare for WP_Query.
	 *
	 * @param array           $prepared_args Prepared arguments.
	 * @param WP_REST_Request $request       Request object.
	 *
	 * @return array          $query_args
	 * @noinspection PhpUnusedParameterInspection PhpUnusedParameterInspection.
	 */
	protected function prepare_items_query( $prepared_args = [], $request = null ): array {

		$query_args = array_diff_key( $prepared_args, array_keys( $this->get_allowed_query_vars() ) );
		$query_args = array_filter(
			$query_args,
			static function ( $query_arg ) {
				return null !== $query_arg;
			}
		);

		$query_args['ignore_sticky_posts'] = true;

		$orderby = $query_args['orderby'] ?? '';

		if ( 'include' === $orderby ) {
			$query_args['orderby'] = 'post__in';
		} elseif ( 'id' === $orderby ) {
			$query_args['orderby'] = 'ID'; // ID must be capitalized.
		}

		if ( current_user_can( 'edit_posts' ) ) {
			return $query_args;
		}

		// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		$query_args['meta_query'] = [
			'relation' => 'OR',
			[
				'key'     => Notification::USERS_META_KEY,
				'value'   => List_In_Meta::get_prepared_item( wp_get_current_user()->ID ),
				'compare' => 'LIKE',
			],
			[
				'key'     => Notification::USERS_META_KEY,
				'compare' => 'NOT EXISTS',
			],
		];
		// phpcs:enable WordPress.DB.SlowDBQuery.slow_db_query_meta_query

		return $query_args;
	}

	/**
	 * Get all the WP Query vars that are allowed for the API request.
	 *
	 * @return array
	 */
	protected function get_allowed_query_vars(): array {
		global $wp;

		/**
		 * Filter the publicly allowed query vars.
		 *
		 * Allows adjusting of the default query vars that are made public.
		 *
		 * @param array $query_vars Array of allowed WP_Query query vars.
		 */
		$valid_vars = apply_filters( 'query_vars', $wp->public_query_vars );

		$post_type_obj = get_post_type_object( $this->post_type );

		if ( $post_type_obj && current_user_can( $post_type_obj->cap->edit_posts ) ) {
			/**
			 * Filter the allowed 'private' query vars for authorized users.
			 *
			 * If the user has the `edit_posts` capability, we also allow use of
			 * private query parameters, which are only undesirable on the
			 * frontend, but are safe for use in query strings.
			 *
			 * To disable anyway, use
			 * `add_filter( 'kagg_notification_rest_private_query_vars', '__return_empty_array' );`
			 *
			 * @param array $private_query_vars Array of allowed query vars for authorized users.
			 *                                  }
			 */
			$private    = $wp->private_query_vars;
			$valid_vars = array_merge( $valid_vars, $private );
		}

		// Define our own in addition to WP normal vars.
		$rest_valid = [
			'date_query',
			'ignore_sticky_posts',
			'offset',
			'post__in',
			'post__not_in',
			'post_parent',
			'post_parent__in',
			'post_parent__not_in',
			'posts_per_page',
			'meta_query',
			'tax_query',
			'meta_key',
			'meta_value',
			'meta_compare',
			'meta_value_num',
		];
		return array_merge( $valid_vars, $rest_valid );
	}

	/**
	 * Get objects.
	 *
	 * @param array $query_args Query args.
	 *
	 * @return array
	 */
	protected function get_objects( $query_args ): array {
		$query  = new WP_Query();
		$result = $query->query( $query_args );

		$total_posts = $query->found_posts;
		if ( $total_posts < 1 ) {
			// Out-of-bounds, run the query again without LIMIT for total count.
			unset( $query_args['paged'] );
			$count_query = new WP_Query();
			$count_query->query( $query_args );
			$total_posts = $count_query->found_posts;
		}

		return [
			'objects' => $result,
			'total'   => $total_posts,
			'pages'   => (int) ceil( $total_posts / (int) $query->query_vars['posts_per_page'] ),
		];
	}

	/**
	 * Prepare a single notification output for response.
	 *
	 * @param WP_Post         $post    Post data.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function prepare_object_for_response( $post, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->get_notification_data( $post, $context );

		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response->add_links( $this->prepare_links( $post, $request ) );

		return $response;
	}

	/**
	 * Get notification data.
	 *
	 * @param WP_Post $notification notification post.
	 * @param string  $context      Request context.
	 *                              Options: 'view' and 'edit'.
	 *
	 * @return array
	 * @noinspection PhpUnusedParameterInspection PhpUnusedParameterInspection.
	 */
	protected function get_notification_data( $notification, $context = 'view' ): array {
		$data = [
			'id'      => $notification->ID,
			'title'   => $notification->post_title,
			'slug'    => $notification->post_name,
			'content' => $notification->post_content,
			'date'    => $this->time_ago( $notification->post_date ),
			'channel' => $this->get_terms_list( $notification->ID, 'channel', ', ' ),
			'read'    => $this->get_read_status( $notification->ID ),
		];

		if ( current_user_can( 'edit_posts' ) ) {
			$data['users'] = $this->get_user_list( $notification->ID );
		}

		return $data;
	}

	/**
	 * Get term names as a list.
	 *
	 * @param int    $id       Post ID.
	 * @param string $taxonomy Taxonomy name.
	 * @param string $sep      List separator.
	 *
	 * @return string
	 */
	protected function get_terms_list( $id, $taxonomy, $sep = '' ): string {
		$terms = get_the_terms( $id, $taxonomy );

		if ( is_wp_error( $terms ) ) {
			return '';
		}

		if ( empty( $terms ) ) {
			return '';
		}

		$names = [];
		foreach ( $terms as $term ) {
			$names[] = $term->name;
		}

		return implode( $sep, $names );
	}

	/**
	 * Get read status of the notification for current user.
	 *
	 * @param int $id Notification ID.
	 *
	 * @return bool
	 */
	protected function get_read_status( $id ): bool {
		return ( new Notification( $id ) )->get_read_status();
	}

	/**
	 * Set read status of the notification for the current user.
	 *
	 * @param int  $id          Notification ID.
	 * @param bool $read_status Read status.
	 */
	protected function set_read_status( $id, $read_status ) {
		$notification = new Notification( $id );
		$notification->set_read_status( $read_status );
	}

	/**
	 * Get a list of users as comma-separated string.
	 *
	 * @param int $id Notification ID.
	 *
	 * @return string
	 */
	protected function get_user_list( $id ): string {
		return ( new Notification( $id ) )->get_user_list();
	}

	/**
	 * Set the list of users defined by the comma-separated string.
	 *
	 * @param int    $id    Notification ID.
	 * @param string $users User list as comma-separated string.
	 */
	protected function set_user_list( $id, $users ) {
		$notification = new Notification( $id );
		$notification->set_user_list( $users );
	}

	/**
	 * Prepare links for the request.
	 *
	 * @param WP_Post         $post    Object data.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return array          Links for the given post.
	 * @noinspection PhpUnusedParameterInspection PhpUnusedParameterInspection.
	 */
	protected function prepare_links( $post, $request ): array {
		return [
			'self'       => [
				'href' => rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $post->ID ) ),
			],
			'collection' => [
				'href' => rest_url( sprintf( '/%s/%s', $this->namespace, $this->rest_base ) ),
			],
		];
	}

	/**
	 * Check if a given request has access to read items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! current_user_can( 'read' ) ) {
			return new WP_Error(
				'KAGG_Notification_rest_cannot_create',
				__( 'Sorry, you cannot view resources.', 'notification-system' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		return true;
	}

	/**
	 * Check if a given request has access to read item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! current_user_can( 'read' ) ) {
			return new WP_Error(
				'KAGG_Notification_rest_cannot_create',
				__( 'Sorry, you cannot view resources.', 'notification-system' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		return true;
	}

	/**
	 * Check if a given request has access to create items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error(
				'KAGG_Notification_rest_cannot_create',
				__( 'Sorry, you cannot create resources.', 'notification-system' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		return true;
	}

	/**
	 * Check if a given request has access to update items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|boolean
	 */
	public function update_item_permissions_check( $request ) {
		$json          = $request->get_params();
		$non_priv_keys = [ 'id', 'read' ];
		sort( $non_priv_keys );
		$intersect = array_intersect_key( array_keys( $json ), $non_priv_keys );
		sort( $intersect );
		if ( $intersect === $non_priv_keys ) {
			// Allow any user to manipulate own read status.
			return true;
		}
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error(
				'KAGG_Notification_rest_cannot_update',
				__( 'Sorry, you cannot update resources.', 'notification-system' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		return true;
	}

	/**
	 * Check if a given request has access to delete items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|boolean
	 */
	public function delete_item_permissions_check( $request ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error(
				'KAGG_Notification_rest_cannot_delete',
				__( 'Sorry, you cannot delete resources.', 'notification-system' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		return true;
	}

	/**
	 * Add taxonomies specified in the request to the post.
	 *
	 * @param int             $post_id Post ID.
	 * @param WP_REST_Request $request Full details about the request.
	 */
	protected function add_taxonomies( $post_id, $request ) {
		$taxonomies = [ 'channel' ];

		foreach ( $taxonomies as $taxonomy ) {
			$term_slug_list = $request[ $taxonomy ] ?? null;
			$term_slugs     = explode( '|', $term_slug_list );
			$append         = false; // To drop any existing terms at first call of wp_set_post_terms().

			foreach ( $term_slugs as $term_slug ) {
				$term_slug = trim( $term_slug );

				if ( ! $term_slug ) {
					continue;
				}

				$this->add_term( $term_slug, $taxonomy, $post_id, $append );

				$append = true; // To add terms at next calls of wp_set_post_terms().
			}
		}
	}

	/**
	 * Get time past from now.
	 *
	 * @param string $datetime Date and time in the past.
	 *
	 * @return bool|int|string
	 */
	protected function time_ago( $datetime ) {
		$time = strtotime( $datetime . ' +0000' );

		$time_diff = time() - $time;

		if ( 0 === $time_diff ) {
			$h_time = __( 'Right now', 'notification-system' );
		} elseif ( $time_diff > 0 && $time_diff < MINUTE_IN_SECONDS ) {
			$h_time = __( 'Seconds ago', 'notification-system' );
		} elseif ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
			/* translators: days/hours/minutes etc. ago */
			$h_time = sprintf( __( '%s ago', 'notification-system' ), human_time_diff( $time ) );
		} else {
			$h_time = mysql2date( get_option( 'date_format' ), $datetime, true );
		}

		return $h_time;
	}

	/**
	 * Copy request fields.
	 *
	 * @param WP_REST_Request $request Request.
	 * @param array           $postarr Post array.
	 *
	 * @return array
	 */
	private function copy_request_fields( WP_REST_Request $request, array $postarr ): array {
		if ( isset( $request['slug'] ) ) {
			$postarr['name'] = $request['slug'];
		}

		if ( isset( $request['title'] ) ) {
			$postarr['post_title'] = $request['title'];
		}

		if ( isset( $request['content'] ) ) {
			$postarr['post_content'] = $request['content'];
		}

		return $postarr;
	}

	/**
	 * Add term.
	 *
	 * @param string $term_slug Term slug.
	 * @param string $taxonomy  Taxonomy.
	 * @param int    $post_id   Post ID.
	 * @param bool   $append    Append.
	 *
	 * @return void
	 */
	private function add_term( $term_slug, $taxonomy, $post_id, $append ) {
		$term = get_term_by( 'slug', $term_slug, $taxonomy );

		if ( $term ) {
			wp_set_post_terms( $post_id, [ $term->term_id ], $taxonomy, $append );
			return;
		}

		$new_term_arr = wp_insert_term( $term_slug, $taxonomy, [] );

		if ( is_wp_error( $new_term_arr ) ) {
			return;
		}

		if ( is_taxonomy_hierarchical( $taxonomy ) ) {
			wp_set_post_terms( $post_id, $new_term_arr['term_id'], $taxonomy, $append );
			return;
		}

		wp_set_post_terms( $post_id, $term_slug, $taxonomy, $append );
	}
}
