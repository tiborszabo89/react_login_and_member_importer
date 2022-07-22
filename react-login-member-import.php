<?php

/*
Plugin Name: User Importer for XLSX files with React Login
Description: WordPress Login Plugin built with React for XLSX Import
Author: Evista - Tibor Szabo
Author URI: 
 */


function remove_menus () {
	global $menu;
	if(is_admin() && ! current_user_can( 'administrator' )) {
		$restricted = array(__('Dashboard'), __('Posts'), __('Media'), __('Links'),  __('Appearance'), __('Tools'), __('Settings'), __('Comments'), __('Plugins'));
		end ($menu);
		while (prev($menu)){
				$value = explode(' ',$menu[key($menu)][0]);
				if(in_array($value[0] != NULL?$value[0]:"" , $restricted)){unset($menu[key($menu)]);}
		}
	}
	}
	add_action('admin_menu', 'remove_menus');

/**
 * Generates custom logout URL
 */
function getLogoutUrl($redirectUrl = ''){
	if(!$redirectUrl) $redirectUrl = site_url();
	$return = str_replace("&amp;", '&', wp_logout_url($redirectUrl));
	return $return;
}

function logout_without_confirmation($action, $result){
	if(!$result && ($action == 'log-out')){ 
			wp_safe_redirect(getLogoutUrl()); 
			exit(); 
	}
}
add_action( 'check_admin_referer', 'logout_without_confirmation', 1, 2);
//Add more columns to users.php

function add_custom_columns_to_users($columns) {
	$columns['status_col'] = 'Status';
	$columns['location_col'] = 'Location';
	$columns['subject_col'] = 'Subject';
	return $columns;
}
add_filter('manage_users_columns', 'add_custom_columns_to_users');

function custom_user_column_content($value, $column_name, $user_id) {
	$user = get_userdata( $user_id );
	if ( 'status_col' == $column_name )
		return get_user_meta($user_id, 'sub_status')[0];
	if ( 'location_col' == $column_name )
		return get_user_meta($user_id, 'city')[0];
	if ( 'subject_col' == $column_name )
		return get_user_meta($user_id, 'subscription')[0];

	return $value;
}
add_filter('manage_users_custom_column',  'custom_user_column_content', 10, 3);

require_once __DIR__ . '/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function blockusers_init() { 
	if ( is_admin() && ! current_user_can( 'edit_posts' ) && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) 
	{ 
		wp_redirect( home_url() ); exit; 
	} 
} 
// remove admin bar from non admin users
add_action('after_setup_theme', 'endo_remove_admin_bar');
function endo_remove_admin_bar() {
	if (!current_user_can('manage_options') && !is_admin()) {
		show_admin_bar(false);
	}
}
add_action( 'init', 'blockusers_init' );

function register_admin_dashboard_menu() {
  add_menu_page(
    __( 'Datenupload', 'memberimport' ),
    __( 'Datenupload', 'memberimport' ),
    'manage_options',
    'user-management-page',
    'admin_page_content',
    'dashicons-feedback',
    3
  );

}
add_action( 'admin_menu', 'register_admin_dashboard_menu' );

function admin_page_override() {
	include_once('admin_page.php');
	wp_enqueue_script('base-js', plugins_url('app.js', __FILE__), array(), false,true );
	
}
add_action( 'plugins_loaded', 'admin_page_override' );

wp_enqueue_style('app-css', plugins_url('app.css', __FILE__) );

add_action('wp_enqueue_style','react_login_styles');


function react_login_scripts() {
	if ( is_active_widget( false, false, 'react_login_widget', true ) ) {
		wp_enqueue_script( 'jquery', plugins_url( 'jquery.min.js', __FILE__ ), array(), false, true );
		wp_enqueue_script( 'react-js', plugins_url( 'react.min.js', __FILE__ ), array(), false, true );
		wp_enqueue_script( 'reactdom-js', plugins_url( 'react-dom.min.js', __FILE__ ), array(), false, true );
		wp_enqueue_script('base-js', plugins_url('app.js', __FILE__), array(), false,true );
		wp_register_script( 'babel-js', 'https://cdnjs.cloudflare.com/ajax/libs/babel-core/5.8.24/browser.min.js', array(), false, true );
		wp_localize_script( 'babel-js', 'wpReactLogin', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'wp_react_login' ) ) );
		wp_enqueue_script( 'babel-js' );
	}
}
add_action( 'wp_enqueue_scripts', 'react_login_scripts');


add_action('wp_footer', 'react_login_add_babel_script', 999);
function react_login_add_babel_script() {
	?>
	<script type="text/babel">
			var ReactLoginForm = React.createClass({
				render: function() {

						var $errors = '';
						var $errorList = [];
						if( this.props.error.length > 0 ) {

						for(var i = 0; i < this.props.error.length; i++ ) {
								$errorList.push(<li dangerouslySetInnerHTML={{__html: this.props.error[i] }}/>);  
							}
							$errors = <ul>{$errorList}</ul>;
							 
						}

						return (
							<form onSubmit={this.props.handleForm} name="reactLoginForm" className="react-login-form">
								{$errors}
								<div className="closex"></div>
								<h3>
								LOGIN
								</h3>
								<p className="helper-para">
									BITTE BEACHTEN SIE DAS DER LOGIN MIT IHREN KUNDENDATEN AUFGRUND TECHNISCHER <br/>
									EINRICHTUNGSDAUER 9 – 14 TAGE IN ANSPRUCH NEHMEN KANN
								</p>
								<div className="logins">
								<p className="blocker">
									<label for="react-login-name">Username</label>
									<input autocomplete="username" placeholder="Benutzer: *" type="text" name="reactLoginName" id="react-login-name"   />
								</p>
								<p className="blocker">
								<label for="react-login-password">Password</label>
								<input autocomplete="current-password" placeholder="Passwort: *" type="password" name="reactLoginPassword" id="react-login-password"   />
								</p>
								</div>
								<button className="login-submit-react" type="submit">Login</button>
								<a className="support_link" href="mailto:info@gewinnprofi.com">info@gewinnprofi.com</a>
							</form>
						);
					 
				} 
					
				
			});
			
			var ReactUserData = React.createClass({
				render: function() {
						return (
						<div className="react-user-data">
						<div className="closex"></div>
							<div className="modal-inner">
								<div className="modal-display-name">
									<p>
									  {this.props.user.display_name}
									</p>
								</div>
									<div className="modal-customer-number">
										<p>
										Kundennummer: {this.props.user.user_login}
										</p>
									</div>
									<div className="modal-subscription-object">
										<p>
											Subscription: {this.props.subscription}
										</p>
									</div>
									<div className="modal-subscription-location">
										<p>
											Address: {this.props.city}
										</p>
									</div>
									<div className="modal-welcome-message">
										<p>								
										Herzlich Willkommen in Ihrem Mitgliederbereich.
										Vielen Dank, dass Sie sich für eine Mitgliedschaft entschieden haben.
										Auf dieser Seite erfahren Sie alles rundum Ihre Teilnahme, anstehende Auslosungen und natürlich die aktuellsten Neuigkeiten über das ServiceAktiv24.
										</p>
									</div>
									<div className="modal-sub-status">						
										<p>
											Teilnahme: {this.props.status === 'active subscription' ? 'Aktiv' : 'Inaktiv'}
										</p>
									</div>
										<a href="<?php echo wp_logout_url(get_home_url()); ?>">Logout</a>
								</div>
						 </div>
						);
					 
				} 
					
				
			});

			var ReactLogin = React.createClass({
				getInitialState: function(){
					return {
						logged: 0,
						error: [],
						user: {}
					}
				},
				
				checkFields: function(){
					var order = this.props.order;
					var $username = '';
					var $password = '';
					if( reactLogins.length > 1 ) {
						$username = window.reactLoginForm[order].reactLoginName.value;
						$password = window.reactLoginForm[order].reactLoginPassword.value;
					} else {
						$username = window.reactLoginForm.reactLoginName.value;
						$password = window.reactLoginForm.reactLoginPassword.value;
					}
					
					var $currentErrors = [];

					if( $username == '' ) {
						$currentErrors.push( "Username is empty" );
					}

					if( $password == '' ) {
						$currentErrors.push( "Password is empty" );
					}

					 
					this.setState({error: $currentErrors});
					
					
				},
				handleForm: function(e){
					e.preventDefault(); 

					this.checkFields();
					var order = this.props.order; 
					if( this.state.error.length == 0 ) {
						// Request Data
						var data = {
							action: 'react_login_user',
							_wpnonce: wpReactLogin.nonce,
							username: '',
							password: ''
						}
 
						if( reactLogins.length > 1 ) {
							data.username = window.reactLoginForm[order].reactLoginName.value;
							data.password = window.reactLoginForm[order].reactLoginPassword.value;
						} else {
							data.username = window.reactLoginForm.reactLoginName.value;
							data.password = window.reactLoginForm.reactLoginPassword.value;
						}

						jQuery.ajax({
					      url: wpReactLogin.ajax_url,
					      dataType: 'json',
					      method: 'POST',
					      data: data,
								crossDomain: true,
					      cache: false,
								headers: "",
								xhrFields: {
                withCredentials: true
								},
					      success: function(data) {
					         
					        if( ! data.success ) {
					        	var $currentErrors = this.state.error; 
					        	$currentErrors.push( data.message );

					        	if( $currentErrors.length > 0 ) {

									this.setState({error: $currentErrors});
									 
								}
					        } else {
					        	this.setState({logged: 1, user: data.user.data, subscription: data.subscription, status: data.status, city:data.city});
					        	for( var doms = 0; doms < reactLoginDoms.length; doms++ ) {
					        		reactLoginDoms[ doms ].setState({logged: 1, user: data.user.data, subscription: data.subscription, status: data.status, city:data.city});
					        	}
					        }

					      }.bind(this),
					      error: function(xhr, status, err) {

					        alert(err.toString());
					      }.bind(this)
					    });
					}
					
				},
				componentDidMount: function() {

				    jQuery.ajax({
				      url: wpReactLogin.ajax_url,
				      dataType: 'json',
				      data: {action: 'react_check_if_logged'},
							crossDomain: true,
							headers: "",
							cache: false,
							xhrFields: {
							withCredentials: true
							},
				      success: function(data) {
				         
				        if( data.success ) {
				        	this.setState({ logged: 1, user: data.user.data, subscription: data.subscription, status: data.status, city: data.city});
				        }

				      }.bind(this),
				      error: function(xhr, status, err) {

				        alert(err.toString());
				      }.bind(this)
				    });
				  },
				render: function(){
					var $renderElement = <ReactLoginForm 
						error={this.state.error} 
						handleForm={this.handleForm} />;
					if( this.state.logged ) {
						$renderElement = <ReactUserData user={this.state.user} city={this.state.city} status={this.state.status} subscription={this.state.subscription}  />;
					}
					return ( $renderElement  );
				}
			});
			
			// Get all login widgets
			var reactLogins = document.getElementsByClassName("react_login");
			var reactLoginDoms = [];
			// For each login, create a new ReactLogin element
			for(var logins = 0; logins < reactLogins.length; logins++ ) {
				var dom = ReactDOM.render(
			        <ReactLogin order={logins} />,
			        reactLogins[ logins ]
			     ); 
			} 
			 
		</script> 
		<?php
}
 
class ReactLogin_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'react_login_widget', // Base ID
			__( 'React Login', 'reactposts' ), // Name
			array( 'description' => __( 'Login Widget created with React', 'reactposts' ), ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}
		 
		echo '<div class="react_login"></div>';
		 
		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'New title', 'text_domain' );
		?>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( esc_attr( 'Title:' ) ); ?></label> 
		<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php 
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';

		return $instance;
	}

}

add_action( 'wp_ajax_nopriv_react_check_if_logged', 'react_check_if_logged' );
add_action( 'wp_ajax_react_check_if_logged', 'react_check_if_logged' );
function react_check_if_logged(){
	$id = get_current_user_id();

	if( $id > 0 ){
		echo  json_encode(
			array(
				'success' => 1,
				'user' => new WP_User($id),
				'subscription' => get_user_meta($id,'subscription', true),
				'status' => get_user_meta($id,'sub_status', true),
				'city' => get_user_meta($id,'city', true),
			));
	} else {
		echo  json_encode(
			array(
				'success' => 0
			));
	}

	wp_die();
}

add_action( 'wp_ajax_nopriv_react_login_user', 'react_login_user' );
add_action( 'wp_ajax_react_login_user', 'react_login_user' );
function react_login_user() {
	global $wpdb;
	check_ajax_referer( 'wp_react_login', '_wpnonce' );

	$username = $_POST['username'];
	$password = $_POST['password'];

	$auth = wp_authenticate( $username, $password );
	$credentials = array(
		'user_login'    => $username,
		'user_password' => $password,
		'remember'      => isset($_POST['remember'])
	);

	if( is_wp_error( $auth )) {
		echo  json_encode(
			array(
				'success' => 0,
				'message' => $auth->get_error_message()
			));
	} else {
		$user = wp_signon($credentials, is_ssl());
		if( is_wp_error($user) ){
			wp_send_json(array(
				'status'        => 'error',
				'error_code'    => $user->get_error_code(),
				'message'       => $user->get_error_message()
			));
			die();
		}
		
		global $current_user;
		$current_user = wp_set_current_user($user->ID);
		
		do_action( 'wp_login', $user->user_login );
		wp_set_auth_cookie( $auth->ID, 1, is_ssl() );

		echo  json_encode(
			array(
				'success' => 1,
				'user' => $auth,
				'subscription' => get_user_meta($auth->ID,'subscription', true),
				'status' => get_user_meta($auth->ID,'sub_status', true),
				'city' => get_user_meta($auth->ID,'city', true),
			));
	}
	

	wp_die();
}

 
function register_react_login_widget() { 
     register_widget( 'ReactLogin_Widget' );
}
add_action( 'widgets_init', 'register_react_login_widget' );