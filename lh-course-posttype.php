<?php
/*
Plugin Name: LH Course Post Type
Description: Post type for courses
Version: 1.0
Author: paulbarnett
License: GPLv2 or later
Text Domain: lighthouse
*/

if( ! class_exists('Course_Posttype') ){

	class Course_Posttype {

		public function __construct() {

			load_plugin_textdomain( 'lighthouse', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );

			register_activation_hook( __FILE__, array( $this, 'activate' ) );
			register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'load_course_admin_scripts' ) );

			add_action( 'init', array( $this, 'create_post_type' ) );

			add_action( 'add_meta_boxes', array( $this, 'lh_courses_meta_boxes' ) );
			add_action( 'save_post_lh_courses', array( $this, 'save_courses_session_meta' ) );
			add_action( 'save_post_lh_courses', array( $this, 'save_courses_objective_meta' ) );

			add_action( 'wp_trash_post', array( $this, 'trash_course_sessions' ) );
			add_action( 'untrash_post', array( $this, 'untrash_course_sessions' ) );
			add_action( 'delete_post', array( $this, 'delete_course_sessions' ) );

			add_filter( 'archive_template', array( $this, 'register_course_archive_template' ), 11 );
			add_filter( 'archive_template', array( $this, 'register_course_sessions_archive_template' ), 11 );
			add_filter( 'single_template', array( $this, 'register_course_single_template' ), 11 );

			add_action( 'pre_get_posts', array( $this, 'update_archive_query' ) );

			add_action( 'wp_ajax_lh_remove_session_from_course', array( $this, 'remove_session_from_course' ) );
			add_action( 'wp_ajax_nopriv_lh_remove_session_from_course', array( $this, 'remove_session_from_course' ) );
		}

		public function activate() {
			$this->create_post_type();
			flush_rewrite_rules();
		}

		public function deactivate(){
			flush_rewrite_rules();
		}

		function load_course_admin_scripts( $hook ){
			$screen = get_current_screen();

			if ( $hook != 'post.php' || $screen->post_type != 'lh_courses' ) {
				return;
			}

			wp_enqueue_script( 'lh_course_admin_scripts', plugins_url( '/includes/admin_scripts.js' , __FILE__ ), array('jquery'), false );
			wp_localize_script( 'lh_course_admin_scripts', 'lh_course_ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );

		}

		public function create_post_type(){

			$labels = array(
				'name'               => _x( 'Courses', 'post type general name', 'lighthouse' ),
				'singular_name'      => _x( 'Course', 'post type singular name', 'lighthouse' ),
				'menu_name'          => _x( 'Courses', 'admin menu', 'lighthouse' ),
				'name_admin_bar'     => _x( 'Course', 'add new on admin bar', 'lighthouse' ),
				'add_new'            => _x( 'Add New', 'vacancy', 'lighthouse' ),
				'add_new_item'       => __( 'Add New Course', 'lighthouse' ),
				'new_item'           => __( 'New Course', 'lighthouse' ),
				'edit_item'          => __( 'Edit Course', 'lighthouse' ),
				'view_item'          => __( 'View Course', 'lighthouse' ),
				'all_items'          => __( 'All Courses', 'lighthouse' ),
				'search_items'       => __( 'Search Courses', 'lighthouse' ),
				'parent_item_colon'  => __( 'Parent Courses:', 'lighthouse' ),
				'not_found'          => __( 'No courses found.', 'lighthouse' ),
				'not_found_in_trash' => __( 'No courses found in Trash.', 'lighthouse' ),
			);

			$args = array(
				'labels'             => $labels,
				'description'        => __( '', 'lighthouse' ),
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'query_var'          => true,
				'rewrite'            => array( 'slug' => 'courses' ),
				'capability_type'    => 'post',
				'has_archive'        => true,
				'hierarchical'       => false,
				'menu_position'      => null,
				'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
				'menu_icon'          => 'dashicons-welcome-learn-more'
			);

			register_post_type( 'lh_courses', $args );


			$labels = array(
				'name'               => _x( 'Course Sessions', 'post type general name', 'lighthouse' ),
				'singular_name'      => _x( 'Course Session', 'post type singular name', 'lighthouse' ),
				'menu_name'          => _x( 'Course Sessions', 'admin menu', 'lighthouse' ),
				'name_admin_bar'     => _x( 'Course Session', 'add new on admin bar', 'lighthouse' ),
				'add_new'            => _x( 'Add New', 'vacancy', 'lighthouse' ),
				'add_new_item'       => __( 'Add New Course Session', 'lighthouse' ),
				'new_item'           => __( 'New Course Session', 'lighthouse' ),
				'edit_item'          => __( 'Edit Course Session', 'lighthouse' ),
				'view_item'          => __( 'View Course Session', 'lighthouse' ),
				'all_items'          => __( 'All Course Sessions', 'lighthouse' ),
				'search_items'       => __( 'Search Course Sessions', 'lighthouse' ),
				'parent_item_colon'  => __( 'Parent Course Sessions:', 'lighthouse' ),
				'not_found'          => __( 'No course sessions found.', 'lighthouse' ),
				'not_found_in_trash' => __( 'No course sessions found in Trash.', 'lighthouse' ),
			);
			$args = array(
				'labels'             => $labels,
				'description'        => __( '', 'lighthouse' ),
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'query_var'          => true,
				'rewrite'            => array( 'slug' => 'course_sessions' ),
				'capability_type'    => 'post',
				'has_archive'        => true,
				'hierarchical'       => false,
				'menu_position'      => null,
				'supports'           => array( 'title', 'editor', 'excerpt', 'thumbnail' ),
				'menu_icon'          => 'dashicons-welcome-learn-more'
			);
			register_post_type( 'lh_course_sessions', $args );

			// Add new taxonomy, make it hierarchical (like categories)
			$labels = array(
				'name'              => _x( 'Categories', 'taxonomy general name' ),
				'singular_name'     => _x( 'Category', 'taxonomy singular name' ),
				'search_items'      => __( 'Search Categories' ),
				'all_items'         => __( 'All Categories' ),
				'parent_item'       => __( 'Parent Category' ),
				'parent_item_colon' => __( 'Parent Category:' ),
				'edit_item'         => __( 'Edit Category' ),
				'update_item'       => __( 'Update Category' ),
				'add_new_item'      => __( 'Add New Category' ),
				'new_item_name'     => __( 'New Category Name' ),
				'menu_name'         => __( 'Categories' ),
			);

			$args = array(
				'hierarchical'      => true,
				'labels'            => $labels,
				'show_ui'           => false,
				'show_admin_column' => true,
				'query_var'         => true,
				'rewrite'           => array( 'slug' => 'course-category' ),
			);

			register_taxonomy( 'course_category', array( 'lh_courses' ), $args );

			// Location
			$labels = array(
				'name'              => _x( 'Locations', 'taxonomy general name' ),
				'singular_name'     => _x( 'Location', 'taxonomy singular name' ),
				'search_items'      => __( 'Search Locations' ),
				'all_items'         => __( 'All Locations' ),
				'edit_item'         => __( 'Edit Location' ),
				'update_item'       => __( 'Update Location' ),
				'add_new_item'      => __( 'Add New Location' ),
				'new_item_name'     => __( 'New Location Name' ),
				'menu_name'         => __( 'Locations' ),
			);

			$args = array(
				'hierarchical'      => false,
				'labels'            => $labels,
				'show_ui'           => false,
				'show_admin_column' => true,
				'query_var'         => true,
				'rewrite'           => array( 'slug' => 'course-location' ),
			);

			register_taxonomy( 'course_location', array( 'lh_courses' ), $args );

		}

		function lh_courses_meta_boxes() {
			add_meta_box( 'course-sessions', __( 'Course Sessions', 'lighthouse' ), array( $this, 'courses_sessions_meta_callback' ), 'lh_courses' );

			add_meta_box( 'course-objectives', __( 'Course Objectives', 'lighthouse' ), array( $this, 'courses_objectives_meta_callback' ), 'lh_courses' );
		}

		function courses_sessions_meta_callback( $post ){
			wp_nonce_field( plugin_basename(__FILE__), 'lh_course_sessions_nonce' );

			$sessions = get_posts( array(
				'posts_per_page'   => -1,
				'post_type'        => 'lh_course_sessions',
				'meta_key'         => '_parent_course',
				'meta_value'       => $post->ID,
				'post_status'      => 'all',
			) );

			$type = get_post_meta( $post->ID, '_event_qualification', true );

			?>
			<table id="session-table">
				<thead>
					<tr>
						<th>Start Date</th>
						<th>End Date</th>
						<th>Location</th>
						<th>Price</th>
						<th>External Link</th>
						<th>Course is run over multipule sessions</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
					<?php
					if( $sessions ): ?>
						<?php foreach ($sessions as $i => $session): ?>
							<?php
								$start_date = date( 'Y-m-d', get_post_meta( $session->ID, '_start_date', true ) );
								$end_date = date( 'Y-m-d', get_post_meta( $session->ID, '_end_date', true ) );
								$location = get_post_meta( $session->ID, '_location', true );
								$price = get_post_meta( $session->ID, '_price', true );
								$external_link = get_post_meta( $session->ID, '_external_link', true );
								$sessions = get_post_meta( $session->ID, '_course_periods', true );
							?>
							<tr class="course-instance" data-course-instance="<?php echo $i ?>">
								<td>
									<input type="hidden" name="instance[<?php echo $i ?>][ID]" value="<?php echo $session->ID ?>" class="session_id_field" >
									<input type="date" name="instance[<?php echo $i ?>][start_date]" value="<?php echo $start_date ?>" />
								</td>
								<td>
									<input type="date" name="instance[<?php echo $i ?>][end_date]" value="<?php echo $end_date ?>" />
								</td>
								<td>
									<input type="text" name="instance[<?php echo $i ?>][location]" class="regular-text" value="<?php echo $location ?>" />
								</td>
								<td>
									<input type="text" name="instance[<?php echo $i ?>][price]" value="<?php echo $price ?>" />
								</td>
								<td>
									<input  type="text" name="instance[<?php echo $i ?>][external_link]" value="<?php echo $external_link ?>" />
								</td>
								<td style="text-align:center;">
									<input type="checkbox" class="multi-session-box" name="instance[<?php echo $i ?>][multi_session]" value="1" <?php echo (count($sessions) > 0)?'checked':''; ?> />
								</td>
								<td>
									<a href="#" class="delete-instance">X</a>
								</td>
							</tr>
							<?php if( count($sessions) > 0 ): ?>
								<tr class="course-sessions" data-course-instance="<?php echo $i ?>">
									<td></td>
									<td colspan="6">
										<table class="course-sessions">
											<thead><tr>
												<th>Session Date</th>
												<th>Session Length (hours)</th>
												<th></th>
											</tr></thead>
											<tbody>
												<?php foreach($sessions as $j => $session): ?>
													<tr class="course-session" data-course-sessions="<?php echo $j ?>">
														<td>
															<?php $session_date = date( 'Y-m-d', $session['date'] ); ?>
															<input type="date" name="instance[<?php echo $i ?>][sessions][<?php echo $j ?>][date]" value="<?php echo $session_date ?>"></td>
														<td><input type="number" name="instance[<?php echo $i ?>][sessions][<?php echo $j ?>][length]" value="<?php echo $session['length'] ?>"></td>
														<td><a href="#" class="remove-corse-session">X</a></td>
													</tr>
												<?php endforeach; ?>
										</tbody>
										<tfoot><tr>
											<td colspan="3"><a href="#" class="add-session">Add session</a></td>
										</tr></tfoot>
									</table>
								</td>
							</tr>
						<?php endif; ?>

						<?php endforeach; ?>
					<?php endif; ?>

				</tbody>
			</table>
			<a href="#" class="add-instance">Add another instnance of this course</a>
			<?php
		}

		function courses_objectives_meta_callback( $post ){
			wp_nonce_field( plugin_basename(__FILE__), 'lh_course_objective_nonce' );

			$objectives = get_post_meta( $post->ID, '_course_objectives', true );
			?>
			<table id="objective-table" style="width:100%;">
				<thead>
					<tr>
						<th></th>
						<th style="width:64px;">Remove</th>
					</tr>
				</thead>
				<tbody>
					<?php if($objectives): foreach( $objectives as $i => $objective ): ?>
					<tr>
						<td>
							<label>Objective Title</label><br />
							<input type="text" name="objective[<?php echo $i ?>][title]" style="width:100%;" value="<?php echo $objective['title'] ?>" />
							<br />
							<label>Objective Description</label><br />
							<textarea name="objective[<?php echo $i ?>][desc]" style="width:100%;" rows="6"><?php echo $objective['desc'] ?></textarea>
						</td>
						<td style="vertical-align:top;padding-top:24px;text-align:center;">
							<a href="#" class="delete-objective">X</a>
						</td>
					</tr>
					<?php endforeach; endif; ?>
				</tbody>
			</table>
			<a href="#" class="add-objective">Add Objective</a>
			<?php
		}

		function save_courses_session_meta( $post_id ) {
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return false;
			}

			if ( ! wp_verify_nonce( $_POST['lh_course_sessions_nonce'], plugin_basename(__FILE__) ) ) {
				return false;
			}

			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return false;
			}

			$course_sessions = array();
			$course_locations = array();
			foreach ( $_POST['instance'] as $i => $instance ) {
				switch ( get_post_meta( $post_id, '_event_qualification', true ) ) {
					case '1':
						$type = '<span style="color:#659FA1;">Associate Certification</span>';
						break;
					case '2':
						$type = '<span style="color:#C15A2D;">Member Certification</span>';
						break;
					case '3':
						$type = '<span style="color:#A1ACB2;">Fellow Certification</span>';
						break;
					case '4':
						$type = '<span style="color:#E19E19;">Master Certification</span>';
						break;
				}

				$connected = get_posts( array(
					'connected_type' => 'courses_to_providers',
					'connected_items' => $post_id,
					'nopaging' => true,
					'suppress_filters' => false,
				) );
				if( $connected ){
					$provider = $connected[0]->post_title;
				}

				$datediff = strtotime( $instance['end_date'] ) - strtotime( $instance['start_date'] );
				$days = count($instance['sessions']);
				$length = floor($datediff / (60 * 60 * 24)) + 1;
				if( $days < 1 ){
					$days = $length;
				}
				$length_str = $days.' day course';

				if( isset($provider) && isset($type) && isset($length_str) ){
					$instance_title = $provider.' <br />'.$type.' - '.$length_str;
				}elseif( isset($type) && isset($length_str) ) {
					$instance_title = $_POST['post_title'].' <br />'.$type.' - '.$length_str;
				}elseif( isset($length_str) ) {
					$instance_title = $_POST['post_title'].' - '.$length_str;
				}else{
					$instance_title = $_POST['post_title'];
				}

				$status = get_post_status( $post_id );

				if ( ! isset($instance['ID']) || $instance['ID'] == '' || $instance['ID'] == 0 ) {
					//Create new post
					$new_post_id = wp_insert_post( array(
						'post_title'  => $instance_title,
						'post_status' => $status,
						'post_type'   => 'lh_course_sessions'
					) );

				} else {
					//Update existing post
					$new_post_id = $instance['ID'];
					wp_update_post( array(
						'ID'          => $instance['ID'],
						'post_title'  => $instance_title,
						'post_status' => $status,
						'post_type'   => 'lh_course_sessions'
					) );
				}

				$course_sessions[] = $new_post_id;

				error_log( print_r( $instance, true ) );

				$start_date = strtotime( $instance['start_date'] );
				update_post_meta( $new_post_id, '_start_date', $start_date );

				$end_date = strtotime( $instance['end_date'] );
				update_post_meta( $new_post_id, '_end_date', $end_date );

				$location = sanitize_text_field( $instance['location'] );
				update_post_meta( $new_post_id, '_location', $location );
				$course_locations[] = $location;

				$price = sanitize_text_field( $instance['price'] );
				update_post_meta( $new_post_id, '_price', $price );

				update_post_meta( $new_post_id, '_external_link', $instance['external_link'] );

				update_post_meta( $new_post_id, '_parent_course', $post_id );

				$qualification = $_POST['event_qualification'];
				update_post_meta( $new_post_id, '_qualification', $qualification );

				$course_periods = array();
				if( count($instance['sessions']) > 0 ){
					foreach( $instance['sessions'] as $j => $session ){
						$period = array();
						$period['date'] = strtotime( $session['date'] );
						$period['length'] = $session['length'];
						$course_periods[] = $period;
					}
					$course_periods = array_values($course_periods);
				}
				update_post_meta( $new_post_id, '_course_periods', $course_periods );
			}

			update_post_meta( $post_id, '_course_sessions', $course_sessions );
			wp_set_post_terms( $post_id, array_unique($course_locations), 'course_location', false );
		}

		function save_courses_objective_meta( $post_id ){

			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
				return false;

			if ( ! wp_verify_nonce( $_POST['lh_course_objective_nonce'], plugin_basename(__FILE__) ) ) {
				return false;
			}

			if ( !current_user_can( 'edit_post', $post_id ))
				return false;

			$meta = array();
			$objectives = $_POST['objective'];
			if ( $objectives ) {
				foreach ($objectives as $i => $objective ){
					$meta[$i]['title'] = $objective['title'];
					$meta[$i]['desc'] = $objective['desc'];
				}
				update_post_meta( $post_id, '_course_objectives', $meta );
			}

		}

		static function register_course_archive_template( $template ) {
			if ( ! is_post_type_archive( 'lh_courses' ) ) {
				return $template;
			}

			if ( file_exists( get_template_directory() . '/lh-templates/course-templates/archive-course.php' ) ) {

				return get_template_directory() . '/lh-templates/course-templates/archive-course.php';

			} elseif( file_exists( plugin_dir_path( __FILE__ ) . 'course-templates/archive-course.php' ) ) {

				return plugin_dir_path( __FILE__ ) . 'course-templates/archive-course.php';
			}

			return $template;
		}

		static function register_course_sessions_archive_template( $template ) {
			if ( ! is_post_type_archive( 'lh_course_sessions' ) ) {
				return $template;
			}

			if ( file_exists( get_template_directory() . '/lh-templates/course-templates/archive-course.php' ) ) {

				return get_template_directory() . '/lh-templates/course-templates/archive-course.php';

			} elseif( file_exists( plugin_dir_path( __FILE__ ) . 'course-templates/archive-course.php' ) ) {

				return plugin_dir_path( __FILE__ ) . 'course-templates/archive-course.php';
			}

			return $template;
		}

		// Add the required templates to display the post type
		static function register_course_single_template( $template ) {
			if ( ! is_singular( 'lh_courses' ) ) {
				return $template;
			}

			if( file_exists( get_template_directory() . '/lh-templates/course-templates/single-course.php' ) ){

				return get_template_directory() . '/lh-templates/course-templates/single-course.php';

			}elseif( file_exists( plugin_dir_path( __FILE__ ) . 'course-templates/single-course.php') ){

				return plugin_dir_path( __FILE__ ) . 'course-templates/single-course.php';

			}

			return $template;
		}

		function update_archive_query( $query ){
			if( !is_admin() && $query->is_main_query() ){

				if( ( is_post_type_archive( 'lh_courses' ) && $query->get('post_type') == 'lh_courses' ) || ( is_post_type_archive( 'lh_course_sessions' ) && $query->get('post_type') == 'lh_course_sessions' ) ){

					$query->set('post_type', 'lh_course_sessions');
					$query->set('post_status', 'publish');
					$query->set('posts_per_page', -1);

					if ( ! empty( $_GET['month'] ) ) {

						$last_day = date('t', strtotime($_GET['month']));
						$timestamp = strtotime($last_day.' '.$_GET['month']) + ( 60 * 60 * 23 ) + ( 60 * 59 );
						$now = time();
						if ( $timestamp < $now ) {
							$timestamp += ( 60 * 60 * 24 * 365 ); //Add a year if date has past
						}

						$start = strtotime( date('Y-m-01', $timestamp) );
						$end = strtotime( date('Y-m-t', $timestamp) );

						$datebetweenmeta = array(
							'key'       => '_start_date',
							'value'     => array($start, $end),
							'compare'   => 'BETWEEN',
							'type'      => 'NUMERIC',
						);
					} else {
						$now = time();
						$datebetweenmeta = array(
							'key'     => '_end_date',
							'value'   => $now,
							'compare' => '>=',
							'type'    => 'NUMERIC',
						);
					}

					if ( ! empty( $_GET['location'] ) ) {
						$location_meta_arr = array(
							'key'     => '_location',
							'value'   => $_GET['location'],
							'compare' => 'LIKE',
						);

					} else {
						$location_meta_arr = '';
					}

					if ( ! empty( $_GET['qualification'] ) ) {
						$qualification_meta_arr = array(
							'key'   => '_qualification',
							'value' => $_GET['qualification'],
						);
					} else {
						$qualification_meta_arr = '';
					}

					$meta_query = array(
						'relation' => 'AND',
						$location_meta_arr,
						$datebetweenmeta,
						$qualification_meta_arr,
					);
					$query->set( 'meta_query', $meta_query );

					$query->set('order', 'ASC');
					$query->set('orderby', 'meta_value_num' );
					$query->set('meta_key', '_start_date' );

				}

			}
		}

		public static function build_course_query( $location, $date, $qualification ) {

			if ( ! empty( $date ) ) {

				$last_day = date('t', strtotime($date));
				$timestamp = strtotime($last_day.' '.$date) + ( 60 * 60 * 23 ) + ( 60 * 59 );
				$now = time();
				if( $timestamp < $now ){
					$timestamp += ( 60 * 60 * 24 * 365 ); //Add a year if date has past
				}

				$start = strtotime( date('Y-m-01', $timestamp) );
				$end = strtotime( date('Y-m-t', $timestamp) );

				$datebetweenmeta = array(
					'key'     => '_start_date',
					'value'   => array( $start, $end ),
					'compare' => 'BETWEEN',
					'type'    => 'NUMERIC'
				);
			} else {
					$now = time();
					$datebetweenmeta = array(
						'key'     => '_end_date',
						'value'   => $now,
						'compare' => '>=',
						'type'    => 'NUMERIC'
					);
			}

			if ( ! empty( $location ) ) {

				$location_meta_arr = array(
					'key'     => '_location',
					'value'   => $location,
					'compare' => 'LIKE',
				);
			} else {
				$location_meta_arr = '';
			}

			if ( ! empty( $qualification ) ) {
				$qualification_meta_arr = array(
					'key' => '_qualification',
					'value' => $qualification,
				);
			} else {
				$qualification_meta_arr = '';
			}

			$meta_query = array(
				'relation' => 'AND',
				$location_meta_arr,
				$datebetweenmeta,
				$qualification_meta_arr,
			);


			$new_course_query = new WP_Query( array(
				'post_type' => 'lh_course_sessions',
				'posts_per_page'   => -1,
				'post_status' => 'publish',
				'meta_query' => $meta_query,
				'order' => 'ASC',
				'orderby' => 'meta_value_num',
				'meta_key' => '_start_date'
			) );

			return $new_course_query;
		}

			// Move session to trash if course is trashed
			function trash_course_sessions( $post_id ){
				$sessions = get_posts( array(
					'posts_per_page' => -1,
					'post_type'      => 'lh_course_sessions',
					'meta_key'       => '_parent_course',
					'meta_value'     => $post_id,
					'post_status'    => 'all',
				) );

				if ( $sessions ) {
					foreach ($sessions as $i => $session){
						wp_trash_post($session->ID);
					}
				}
			}

			function untrash_course_sessions( $post_id ){
				$sessions = get_posts( array(
					'posts_per_page' => -1,
					'post_type'      => 'lh_course_sessions',
					'meta_key'       => '_parent_course',
					'meta_value'     => $post_id,
					'post_status'    => 'all',
				) );
				if( $sessions ){
					foreach ($sessions as $i => $session){
						wp_untrash_post($session->ID);
					}
				}
			}

			function delete_course_sessions( $post_id ){
				$sessions = get_posts( array(
					'posts_per_page' => -1,
					'post_type'      => 'lh_course_sessions',
					'meta_key'       => '_parent_course',
					'meta_value'     => $post_id,
					'post_status'    => 'all',
				) );
				if ( $sessions ){
					foreach ( $sessions as $i => $session ) {
						wp_delete_post($session->ID);
					}
				}
			}

			function remove_session_from_course(){
				$session_id = $_POST['session_id'];

				if( empty($session_id) || $session_id == 0 || $session_id == '' ){
					return false;
				}else{
					wp_delete_post( $session_id, true );
					echo 1;
				}

				die();
			}

	} //End Class

}

if( class_exists('Course_Posttype') )
{
	$course = new Course_Posttype();
}

function course_formatted_title( $c_id ) {
	echo get_course_formatted_title( $c_id );
}
function get_course_formatted_title( $c_id ) {
	$post_type = get_post_type( $c_id );
	$html = '';

	if ( $post_type == 'lh_course_sessions' ) {
		$parent_id = get_post_meta( $c_id, '_parent_course', true );
		$t1 = get_the_title( $c_id );
		$t2 = '';
	} elseif ( $post_type == 'lh_courses' ) {
		$sessions = get_post_meta( $c_id, '_course_sessions', true );
		$t1 = get_the_title( $sessions[0] );
		$t2 = get_the_title( $c_id );
	}
	$html = '<h4>'.$t1.'<br />'.$t2.'</h4>';
	return $html;
}
