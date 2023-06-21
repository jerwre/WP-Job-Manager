<?php
/**
 * File containing the class WP_Job_Manager_Promoted_Jobs_API.
 *
 * @package wp-job-manager
 */

/**
 * Handles functionality related to the Promoted Jobs REST API.
 *
 * @since $$next-version$$
 */
class WP_Job_Manager_Promoted_Jobs_API extends WP_REST_Controller {

	/**
	 * The namespace.
	 *
	 * @var string
	 */
	protected $namespace;

	/**
	 * Rest base for the current object.
	 *
	 * @var string
	 */
	protected $rest_base;

	/**
	 * Promoted Jobs Class constructor.
	 */
	public function __construct() {
		$this->namespace = 'wpjm-internal/v1';
		$this->rest_base = 'promoted-jobs';
	}

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' .
			$this->rest_base,
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_items' ],
					'permission_callback' => '__return_true',
				],
			]
		);
	}

	/**
	 * Get all promoted jobs.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function get_items( $request ) {
		$args = [
			'post_type'   => 'job_listing',
			'post_status' => 'publish',
			'no_found_rows' => true,
			'ignore_sticky_posts' => true,
			'meta_query'  => [
				[
					'key'     => '_promoted',
					'value'   => '1',
					'compare' => '==',
				],
			],
		];

		$job_listings = new WP_Query( $args );
		$items = $job_listings->posts;
		$data = [];

		if ( empty( $items ) ) {
			return rest_ensure_response( $data );
		}

		foreach ( $items as $item ) {
			$itemdata = $this->prepare_item_for_response( $item, $request );
			$data[] = $this->prepare_response_for_collection( $itemdata );
		}

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Prepare the item for the REST response
	 *
	 * @param mixed           $item WordPress representation of the item.
	 * @param WP_REST_Request $request Request object.
	 * @return mixed
	 */
	public function prepare_item_for_response( $item, $request ) {
		$terms = get_the_terms( $item->ID, 'job_listing_type' );

		$terms_array = [];
		foreach ( $terms as $term ) {
			$terms_array[] = $term->slug;
		}

		$data = [
			'id'              => $item->ID,
			'title'           => $item->post_title,
			'description'     => $item->post_content,
			'permalink'       => get_permalink( $item ),
			'location'        => get_post_meta( $item->ID, '_job_location', true ),
			'company_name'    => get_post_meta( $item->ID, '_company_name', true ),
			'is_remote'       => (bool) get_post_meta( $item->ID, '_remote_position', true ),
			'job_type'        => $terms_array,
			'salary'          => [
				'salary_amount'   => get_post_meta( $item->ID, '_job_salary', true ),
				'salary_currency' => get_post_meta( $item->ID, '_job_salary_currency', true ),
				'salary_unit'     => get_post_meta( $item->ID, '_job_salary_unit', true ),
			],
		];
		return rest_ensure_response( $data );
	}
}

new WP_Job_Manager_Promoted_Jobs_API();
