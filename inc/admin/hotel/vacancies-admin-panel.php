<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * functions to manage vacancies
 */
if ( ! class_exists( 'CT_Hotel_Vacancy_List_Table') ) :
class CT_Hotel_Vacancy_List_Table extends WP_List_Table {

	function __construct() {
		global $status, $page;
		parent::__construct( array(
			'singular'  => 'vacancy',     //singular name of the listed records
			'plural'    => 'vacancies',    //plural name of the listed records
			'ajax'      => false        //does this table support ajax?
		) );
	}

	function column_default( $item, $column_name ) {
		$link_pattern = 'edit.php?post_type=%1s&page=%2$s&action=%3$s&vacancy_id=%4$s';
		switch( $column_name ) {
			case 'id':
			case 'rooms':
			case 'price_per_room':
			case 'price_per_person':
				return $item[ $column_name ];
			case 'date_from':
			case 'date_to':
				$actions = array(
					'edit'      => '<a href="' . esc_url( sprintf( $link_pattern, sanitize_text_field( $_REQUEST['post_type'] ), 'vacancies', 'edit', $item['id'] ) ) . '">Edit</a>',
					'delete'    => '<a href="' . esc_url( sprintf( $link_pattern, sanitize_text_field( $_REQUEST['post_type'] ), 'vacancies', 'delete', $item['id'] . '&_wpnonce=' . wp_create_nonce( 'vacancy_delete' ) ) ) . '">Delete</a>'
				);
				$content = '<a href="' . esc_url( sprintf( $link_pattern, sanitize_text_field( $_REQUEST['post_type'] ), 'vacancies', 'edit', $item['id'] ) ) . '">' . $item[$column_name] . '</a>';
				//Return the title contents
				return sprintf( '%1$s %2$s', $content, $this->row_actions( $actions ) );
			default:
				return print_r( $item, true ); //Show the whole array for troubleshooting purposes
		}
	}

	function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'], $item['id'] );
	}

	function column_hotel_name( $item ) {
		return '<a href="' . esc_url( get_edit_post_link( $item['hotel_id'] ) ) . '">' . esc_html( $item['hotel_name'] ) . '</a>';
	}

	function column_room_type_name( $item ) {
		return '<a href="' . esc_url( get_edit_post_link( $item['room_type_id'] ) ) . '">' . esc_html( $item['room_type_name'] ) . '</a>';
	}

	function get_columns() {
		$columns = array(
			'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
			'id'        => esc_html__( 'ID', 'citytours' ),
			'date_from'=> esc_html__( 'Date From', 'citytours' ),
			'date_to'  => esc_html__( 'Date To', 'citytours' ),
			'hotel_name'  => esc_html__( 'Hotel Name', 'citytours' ),
			'room_type_name' => esc_html__( 'Room Type', 'citytours' ),
			'rooms'     => esc_html__( 'Number of Rooms', 'citytours' ),
			'price_per_room'  => esc_html__( 'Price per Room(per night)', 'citytours' ),
			'price_per_person'  => esc_html__( 'Price per Person(per night)', 'citytours' )
		);
		return $columns;
	}

	function get_sortable_columns() {
		$sortable_columns = array(
			'id'            => array( 'id', false ),
			'date_from' => array( 'date_from', false ),
			'date_to'       => array( 'date_to', false ),
			'hotel_name'    => array( 'hotel_name', false ),
			'room_type_name'        => array( 'room_type_name', false ),
			'rooms'         => array( 'rooms', false ),
			'price_per_room'            => array( 'price_per_room', false ),
			'price_per_person'  => array( 'price_per_person', false )
		);
		return $sortable_columns;
	}

	function get_bulk_actions() {
		$actions = array(
			'bulk_delete'    => 'Delete'
		);
		return $actions;
	}

	function process_bulk_action() {
		global $wpdb;
		//Detect when a bulk action is being triggered...

		if ( isset( $_POST['_wpnonce'] ) && ! empty( $_POST['_wpnonce'] ) ) {

			$nonce  = filter_input( INPUT_POST, '_wpnonce', FILTER_SANITIZE_STRING );
			$action = 'bulk-' . $this->_args['plural'];

			if ( ! wp_verify_nonce( $nonce, $action ) )
				wp_die( 'Sorry, your nonce did not verify' );

		}
		if ( 'bulk_delete'===$this->current_action() ) {
			$selected_ids = $_GET[ $this->_args['singular'] ];
			$how_many = count($selected_ids);
			$placeholders = array_fill(0, $how_many, '%d');
			$format = implode(', ', $placeholders);
			$current_user_id = get_current_user_id();
			$post_table_name  = esc_sql( $wpdb->prefix . 'posts' );
			$sql = '';

			if ( current_user_can( 'manage_options' ) ) {
				$sql = sprintf('DELETE FROM %1$s WHERE id IN (%2$s)', CT_HOTEL_VACANCIES_TABLE, "$format" );
			} else {
				$sql = sprintf('DELETE %1$s FROM %1$s INNER JOIN %2$s as hotel ON hotel_id=hotel.ID WHERE %1$s.id IN (%3$s) AND hotel.post_author = %4$d', CT_HOTEL_VACANCIES_TABLE, $post_table_name, "$format", $current_user_id );
			}

			$wpdb->query( $wpdb->prepare( $sql, $selected_ids ) );
			wp_redirect( admin_url( 'edit.php?post_type=hotel&page=vacancies&bulk_delete=true') );
		}
		
	}

	function prepare_items() {
		global $wpdb;
		$per_page = 10;
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->process_bulk_action();
		
		$orderby = ( ! empty( $_REQUEST['orderby'] ) ) ? sanitize_sql_orderby( $_REQUEST['orderby'] ) : 'id'; //If no sort, default to title
		$order = ( ! empty( $_REQUEST['order'] ) ) ? sanitize_text_field( $_REQUEST['order'] ) : 'desc'; //If no order, default to desc
		$current_page = $this->get_pagenum();
		$post_table_name  = $wpdb->prefix . 'posts';

		$where = "1=1";
		if ( ! empty( $_REQUEST['hotel_id'] ) ) $where .= " AND CT_Vacancies.hotel_id = '" . esc_sql( ct_hotel_org_id( $_REQUEST['hotel_id'] ) ) . "'";
		if ( ! empty( $_REQUEST['room_type_id'] ) ) $where .= " AND CT_Vacancies.room_type_id = '" . esc_sql( ct_room_org_id( $_REQUEST['room_type_id'] ) ) . "'";
		if ( ! empty( $_REQUEST['date'] ) ) $where .= " AND CT_Vacancies.date_from <= '" . esc_sql( $_REQUEST['date'] ) . "' and CT_Vacancies.date_to > '" . $_REQUEST['date'] . "'" ;
		if ( ! current_user_can( 'manage_options' ) ) { $where .= " AND hotel.post_author = '" . get_current_user_id() . "' "; }

		$sql = $wpdb->prepare( 'SELECT CT_Vacancies.* , hotel.ID as hotel_id, hotel.post_title as hotel_name, room_type.ID as room_type_id, room_type.post_title as room_type_name FROM %1$s as CT_Vacancies
						INNER JOIN %2$s as hotel ON CT_Vacancies.hotel_id=hotel.ID
						INNER JOIN %2$s as room_type ON CT_Vacancies.room_type_id=room_type.ID
						WHERE ' . $where . ' ORDER BY %4$s %5$s
						LIMIT %6$s, %7$s' , CT_HOTEL_VACANCIES_TABLE, $post_table_name, '', $orderby, $order, ( $per_page * ( $current_page - 1 ) ), $per_page );

		$data = $wpdb->get_results( $sql, ARRAY_A );

		$sql = sprintf( 'SELECT COUNT(*) FROM %1$s as CT_Vacancies INNER JOIN %2$s as hotel ON CT_Vacancies.hotel_id=hotel.ID WHERE %3$s' , CT_HOTEL_VACANCIES_TABLE, $post_table_name, $where );
		$total_items = $wpdb->get_var( $sql );

		$this->items = $data;
		$this->set_pagination_args( array(
			'total_items' => $total_items,                  //WE have to calculate the total number of items
			'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
			'total_pages' => ceil( $total_items/$per_page )   //WE have to calculate the total number of pages
		) );
	}
}
endif;

/*
 * add vacancy list page to menu
 */
if ( ! function_exists( 'ct_hotel_vacancy_add_menu_items' ) ) {
	function ct_hotel_vacancy_add_menu_items() {
		$page = add_submenu_page( 'edit.php?post_type=hotel', 'Hotel Vacancies', 'Vacancies', 'edit_posts', 'vacancies', 'ct_hotel_vacancy_render_pages' );
		add_action( 'admin_print_scripts-' . $page, 'ct_hotel_vacancy_admin_enqueue_scripts' );
	}
}

/*
 * vacancy admin main actions
 */
if ( ! function_exists( 'ct_hotel_vacancy_render_pages' ) ) {
	function ct_hotel_vacancy_render_pages() {

		if ( ( ! empty( $_REQUEST['action'] ) ) && ( ( 'add' == $_REQUEST['action'] ) || ( 'edit' == $_REQUEST['action'] ) ) ) {
			ct_hotel_vacancy_render_manage_page();
		} elseif ( ( ! empty( $_REQUEST['action'] ) ) && ( 'delete' == $_REQUEST['action'] ) ) {
			ct_hotel_vacancy_delete_action();
		} else {
			ct_hotel_vacancy_render_list_page();
		}
	}
}

/*
 * render vacancy list page
 */
if ( ! function_exists( 'ct_hotel_vacancy_render_list_page' ) ) {
	function ct_hotel_vacancy_render_list_page() {
		global $wpdb;
		$ctVancancyTable = new CT_Hotel_Vacancy_List_Table();
		$ctVancancyTable->prepare_items();
		?>

		<div class="wrap">
			<h2>Hotel Vacancies <a href="edit.php?post_type=hotel&amp;page=vacancies&amp;action=add" class="add-new-h2">Add New</a></h2>
			<?php if ( isset( $_REQUEST['bulk_delete'] ) ) echo '<div id="message" class="updated below-h2"><p>Vacancies deleted</p></div>'?>
			<select id="hotel_filter">
				<option></option>
				<?php
				$args = array(
						'post_type'         => 'hotel',
						'posts_per_page'    => -1,
						'orderby'           => 'title',
						'order'             => 'ASC'
				);
				/* bussinerss managers can see their own post only */
				if ( ! current_user_can( 'manage_options' ) ) {
					$args['author'] = get_current_user_id();
				}

				$hotel_query = new WP_Query( $args );

				if ( $hotel_query->have_posts() ) {
					while ( $hotel_query->have_posts() ) {
						$hotel_query->the_post();
						$selected = '';
						$id = $hotel_query->post->ID;
						if ( ! empty( $_REQUEST['hotel_id'] ) && ( $_REQUEST['hotel_id'] == $id ) ) $selected = ' selected ';
						echo '<option ' . esc_attr( $selected ) . 'value="' . esc_attr( $id ) .'">' . wp_kses_post( get_the_title( $id ) ) . '</option>';
					}
				} else {
					// no posts found
				}
				/* Restore original Post Data */
				wp_reset_postdata();
				?>
			</select>
			<select id="room_type_filter">
				<option></option>
				<?php
					$args = array(
							'post_type'         => 'room_type',
							'posts_per_page'    => -1,
							'orderby'           => 'title',
							'order'             => 'ASC'
					);
					/* bussinerss managers can see their own post only */
					if ( ! current_user_can( 'manage_options' ) ) {
						$args['author'] = get_current_user_id();
					}

					if ( ! empty( $_REQUEST['hotel_id'] ) ) {
						$args['meta_query'] = array(
								array(
									'key'     => '_room_hotel_id',
									'value'   => sanitize_text_field( $_REQUEST['hotel_id'] ),
								),
							);
					}
					$room_type_query = new WP_Query( $args );

					if ( $room_type_query->have_posts() ) {
						while ( $room_type_query->have_posts() ) {
							$room_type_query->the_post();
							$selected = '';
							$id = $room_type_query->post->ID;
							if ( ! empty( $_REQUEST['room_type_id'] ) && ( $_REQUEST['room_type_id'] == $id ) ) $selected = ' selected ';
							echo '<option ' . esc_attr( $selected ) . 'value="' . esc_attr( $id ) .'">' . wp_kses_post( get_the_title( $id ) ) . '</option>';
						}
					} else {
						// no posts found
					}
					/* Restore original Post Data */
					wp_reset_postdata();
				?>
			</select>
			<input type="text" id="date_filter" placeholder="Filter by Date" value="<?php echo isset($_REQUEST['date']) ? esc_attr( $_REQUEST['date'] ):'' ?>">
			<input type="button" name="vacancy_filter" id="vacancy-filter" class="button" value="Filter">
			<a href="edit.php?post_type=hotel&amp;page=vacancies" class="button-secondary">Show All</a>
			<form id="accomo-vacancies-filter" method="get">
				<input type="hidden" name="post_type" value="<?php echo esc_attr( $_REQUEST['post_type'] ) ?>" />
				<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ) ?>" />
				<?php $ctVancancyTable->display() ?>
			</form>
			
		</div>
		<?php
	}
}

/*
 * render vacancy detail page
 */
if ( ! function_exists( 'ct_hotel_vacancy_render_manage_page' ) ) {
	function ct_hotel_vacancy_render_manage_page() {

		global $wpdb;

		if ( ! empty( $_POST['save'] ) ) {
			ct_hotel_vacancy_save_action();
			return;
		}

		$default_vacancy_data = array(  'id'                => '',
										'hotel_id'  => '',
										'room_type_id'      => '',
										'rooms'        => 1,
										'date_from'        => date( 'Y-m-d' ),
										'date_to'          => '',
										'price_per_room'     => '',
										'price_per_person'=>''
									);
		$vacancy_data = array();

		if ( 'add' == $_REQUEST['action'] ) {
			$page_title = "Add New Hotel Vacancy";
		} elseif ( 'edit' == $_REQUEST['action'] ) {
			$page_title = 'Edit Hotel Vacancy<a href="edit.php?post_type=hotel&amp;page=vacancies&amp;action=add" class="add-new-h2">Add New</a>';
			
			if ( empty( $_REQUEST['vacancy_id'] ) ) {
				echo "<h2>You attempted to edit an item that doesn't exist. Perhaps it was deleted?</h2>";
				return;
			}
			$vacancy_id = sanitize_text_field( $_REQUEST['vacancy_id'] );
			$post_table_name  = $wpdb->prefix . 'posts';

			$where = 'CT_Vacancies.id = %3$d';
			if ( ! current_user_can( 'manage_options' ) ) { $where .= " AND hotel.post_author = '" . get_current_user_id() . "' "; }

			$sql = $wpdb->prepare( 'SELECT CT_Vacancies.* , hotel.post_title as hotel_name, room_type.post_title as room_type_name FROM %1$s as CT_Vacancies
							INNER JOIN %2$s as hotel ON CT_Vacancies.hotel_id=hotel.ID
							INNER JOIN %2$s as room_type ON CT_Vacancies.room_type_id=room_type.ID
							WHERE ' . $where , CT_HOTEL_VACANCIES_TABLE, $post_table_name, $vacancy_id );

			$vacancy_data = $wpdb->get_row( $sql, ARRAY_A );
			if ( empty( $vacancy_data ) ) {
				echo "<h2>You attempted to edit an item that doesn't exist. Perhaps it was deleted?</h2>";
				return;
			}
		}

		$vacancy_data = array_replace( $default_vacancy_data, $vacancy_data );
		?>

		<div class="wrap">
			<h2><?php echo wp_kses_post( $page_title ); ?></h2>
			<?php if ( isset( $_REQUEST['updated'] ) ) echo '<div id="message" class="updated below-h2"><p>Vacancy saved</p></div>'?>
			<form method="post" onsubmit="return manage_vacancy_validateForm();">
				<input type="hidden" name="id" value="<?php if ( ! empty( $vacancy_data['id'] ) ) echo esc_attr( $vacancy_data['id'] ); ?>">
				<table class="ct_admin_table ct_hotel_vacancy_manage_table">
					<tr>
						<th>Hotel</th>
						<td>
							<select name="hotel_id" id="hotel_id">
								<option></option>
								<?php
									$args = array(
											'post_type'         => 'hotel',
											'posts_per_page'    => -1,
											'orderby'           => 'title',
											'order'             => 'ASC'
									);
									/* bussinerss managers can see their own post only */
									if ( ! current_user_can( 'manage_options' ) ) {
										$args['author'] = get_current_user_id();
									}
									$hotel_query = new WP_Query( $args );

									if ( $hotel_query->have_posts() ) {
										while ( $hotel_query->have_posts() ) {
											$hotel_query->the_post();
											$selected = '';
											$id = $hotel_query->post->ID;
											$org_id = ct_hotel_org_id( $id );
											$clang_id = ct_hotel_clang_id( $id );
											if ( ( ! empty( $vacancy_data['hotel_id'] ) ) && ( $vacancy_data['hotel_id'] == $org_id ) ) $selected = ' selected ';
											echo '<option ' . esc_attr( $selected ) . 'value="' . esc_attr( $org_id ) .'">' . wp_kses_post( get_the_title( $clang_id ) ) . '</option>';
										}
									}
									wp_reset_postdata();
								?>
							</select>
						</td>
					</tr>
					<tr>
						<th>Room Type</th>
						<td>
							<select name="room_type_id" id="room_type_id">
								<option></option>
								<?php
									$args = array(
											'post_type'         => 'room_type',
											'posts_per_page'    => -1,
											'orderby'           => 'title',
											'order'             => 'ASC',
											'suppress_filters'  => true
									);
									/* bussinerss managers can see their own post only */
									if ( ! current_user_can( 'manage_options' ) ) {
										$args['author'] = get_current_user_id();
									}
									if ( ! empty( $vacancy_data['hotel_id'] ) ) {
										$args['meta_query'] = array(
												array(
													'key'     => '_room_hotel_id',
													'value'   => $vacancy_data['hotel_id']
												),
											);
									}
									$room_type_query = new WP_Query( $args );
									if ( $room_type_query->have_posts() ) {
										while ( $room_type_query->have_posts() ) {
											$room_type_query->the_post();
											$selected = '';
											$id = $room_type_query->post->ID;
											$org_id = ct_room_org_id( $id );
											$clang_id = ct_room_clang_id( $id );
											if ( ( ! empty( $vacancy_data['room_type_id'] ) ) && ( $vacancy_data['room_type_id'] == $org_id ) ) $selected = ' selected ';
											echo '<option ' . esc_attr( $selected ) . 'value="' . esc_attr( $org_id ) .'">' . wp_kses_post( get_the_title( $clang_id ) ) . '</option>';
										}
									}
									wp_reset_postdata();
								?>
							</select>
						</td>
					</tr>
					<tr>
						<th>Number of Rooms</th>
						<td><input type="number" name="rooms" min="1" value="<?php if ( ! empty( $vacancy_data['rooms'] ) ) echo esc_attr( $vacancy_data['rooms'] ); ?>"></td>
					</tr>
					<tr>
						<th>Date From</th>
						<td><input type="text" name="date_from" id="date_from" value="<?php if ( ! empty( $vacancy_data['date_from'] ) ) echo esc_attr( $vacancy_data['date_from'] ); ?>"></td>
						<td><span>If you leave this field blank it will be set as current date</span></td>
					</tr>
					<tr>
						<th>Date To</th>
						<td><input type="text" name="date_to" id="date_to" value="<?php if ( ( ! empty( $vacancy_data['date_to'] ) ) && ( $vacancy_data['date_to'] != '9999-12-31' ) ) echo esc_attr( $vacancy_data['date_to'] ); ?>"></td>
						<td><span>Leave it blank if this rooms are available all the time</span></td>
					</tr>
					<tr>
						<th>Price Per Room (per night)</th>
						<td><input type="text" name="price_per_room" value="<?php if ( ! empty( $vacancy_data['price_per_room'] ) ) echo esc_attr( $vacancy_data['price_per_room'] ); ?>"></td>
					</tr>
					<tr>
						<th>Price Per Person (per night)</th>
						<td><input type="text" name="price_per_person" value="<?php if ( ! empty( $vacancy_data['price_per_person'] ) ) echo esc_attr( $vacancy_data['price_per_person'] ); ?>"></td>
					</tr>
					<tr>
						<th>Price Per Child (per night)</th>
						<td><input type="text" name="price_per_child" value="<?php if ( ! empty( $vacancy_data['price_per_child'] ) ) echo esc_attr( $vacancy_data['price_per_child'] ); ?>"></td>
					</tr>
				</table>
				<input type="submit" class="button-primary" name="save" value="Save Vacancy">
				<a href="edit.php?post_type=hotel&amp;page=vacancies" class="button-secondary">Cancel</a>
				<?php wp_nonce_field('ct_hotel_vacancy_manage','vacancy_save'); ?>
			</form>
		</div>
		<?php
	}
}

/*
 * vacancy delete action
 */
if ( ! function_exists( 'ct_hotel_vacancy_delete_action' ) ) {
	function ct_hotel_vacancy_delete_action() {

		global $wpdb;
		// data validation
		if ( empty( $_REQUEST['vacancy_id'] ) ) {
			print 'Sorry, you tried to remove nothing.';
			exit;
		}

		// nonce check
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'vacancy_delete' ) ) {
			print 'Sorry, your nonce did not verify.';
			exit;
		}

		// check ownership if user is not admin
		if ( ! current_user_can( 'manage_options' ) ) {
			$sql = $wpdb->prepare( 'SELECT CT_Vacancies.hotel_id FROM %1$s as CT_Vacancies WHERE CT_Vacancies.id = %2$d' , CT_HOTEL_VACANCIES_TABLE, $_REQUEST['vacancy_id'] );
			$hotel_id = $wpdb->get_var( $sql );
			$post_author_id = get_post_field( 'post_author', $hotel_id );
			if ( get_current_user_id() != $post_author_id ) {
				print 'You don\'t have permission to remove other\'s item.';
				exit;
			}
		}

		// do action
		$wpdb->delete( CT_HOTEL_VACANCIES_TABLE, array( 'id' => $_REQUEST['vacancy_id'] ) );
		//}
		wp_redirect( admin_url( 'edit.php?post_type=hotel&page=vacancies') );
		exit;
	}
}

/*
 * vacancy save action
 */
if ( ! function_exists( 'ct_hotel_vacancy_save_action' ) ) {
	function ct_hotel_vacancy_save_action() {

		if ( ! isset( $_POST['vacancy_save'] ) || ! wp_verify_nonce( $_POST['vacancy_save'], 'ct_hotel_vacancy_manage' ) ) {
		   print 'Sorry, your nonce did not verify.';
		   exit;
		} else {

			global $wpdb;

			$default_vacancy_data = array( 'hotel_id'  => '',
										'room_type_id'      => '',
										'rooms'        => 0,
										'date_from'        => date( 'Y-m-d' ),
										'date_to'          => '9999-12-31',
										'price_per_room'     => 0,
										'price_per_person'=>0,
										'price_per_child'=>0,
									);

			$table_fields = array( 'date_from', 'date_to', 'hotel_id', 'room_type_id', 'rooms', 'price_per_room', 'price_per_person', 'price_per_child');
			$data = array();
			foreach ( $table_fields as $table_field ) {
				if ( ! empty( $_POST[ $table_field ] ) ) {
					$data[ $table_field ] = sanitize_text_field( $_POST[ $table_field ] );
				}
			}

			$data = array_replace( $default_vacancy_data, $data );
			$data['hotel_id'] = ct_hotel_org_id( $data['hotel_id'] );
			$data['room_type_id'] = ct_room_org_id( $data['room_type_id'] );
			if ( empty( $_POST['id'] ) ) {
				//insert
				$wpdb->insert( CT_HOTEL_VACANCIES_TABLE, $data );
				$id = $wpdb->insert_id;
			} else {
				//update
				$wpdb->update( CT_HOTEL_VACANCIES_TABLE, $data, array( 'id' => sanitize_text_field( $_POST['id'] ) ) );
				$id = sanitize_text_field( $_POST['id'] );
			}
			wp_redirect( admin_url( 'edit.php?post_type=hotel&page=vacancies&action=edit&vacancy_id=' . $id . '&updated=true') );
			exit;
		}
	}
}

/*
 * vacancy admin enqueue script action
 */
if ( ! function_exists( 'ct_hotel_vacancy_admin_enqueue_scripts' ) ) {
	function ct_hotel_vacancy_admin_enqueue_scripts() {

		// support select2
		wp_enqueue_style( 'rwmb_select2', RWMB_URL . 'css/select2/select2.css', array(), '3.2' );
		wp_enqueue_script( 'rwmb_select2', RWMB_URL . 'js/select2/select2.min.js', array(), '3.2', true );

		// datepicker
		$url = RWMB_URL . 'css/jqueryui';
		wp_register_style( 'jquery-ui-core', "{$url}/jquery.ui.core.css", array(), '1.8.17' );
		wp_register_style( 'jquery-ui-theme', "{$url}/jquery.ui.theme.css", array(), '1.8.17' );
		wp_enqueue_style( 'jquery-ui-datepicker', "{$url}/jquery.ui.datepicker.css", array( 'jquery-ui-core', 'jquery-ui-theme' ), '1.8.17' );

		// Load localized scripts
		$locale = str_replace( '_', '-', get_locale() );
		$file_path = 'jqueryui/datepicker-i18n/jquery.ui.datepicker-' . $locale . '.js';
		$deps = array( 'jquery-ui-datepicker' );
		if ( file_exists( RWMB_DIR . 'js/' . $file_path ) )
		{
			wp_register_script( 'jquery-ui-datepicker-i18n', RWMB_URL . 'js/' . $file_path, $deps, '1.8.17', true );
			$deps[] = 'jquery-ui-datepicker-i18n';
		}

		wp_enqueue_script( 'rwmb-date', RWMB_URL . 'js/' . 'date.js', $deps, RWMB_VER, true );
		wp_localize_script( 'rwmb-date', 'RWMB_Datepicker', array( 'lang' => $locale ) );

		// custom style and js
		wp_enqueue_style( 'ct_admin_hotel_style' , CT_TEMPLATE_DIRECTORY_URI . '/inc/admin/css/style.css' ); 
		wp_enqueue_script( 'ct_admin_vacancy_script' , CT_TEMPLATE_DIRECTORY_URI . '/inc/admin/hotel/js/vacancy.js', array('jquery'), '1.0', true );
	}
}

add_action( 'admin_menu', 'ct_hotel_vacancy_add_menu_items' );