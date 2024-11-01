<?php 
/**
Plugin Name: StreamSend API for WP
Plugin URI: http://www.williamegarcia.com/streamsend
Description: Add a StreamSend form to easily populate a mailing list. API-integrated for a better customer experience. Use widget or shortcode to display the form anywhere in the WordPress blog or page.
You will not need to create a StreamSend form and the subscriber will never leave your page in the signup process.
Version: 1.0
Author: William E. Garcia.
Author URI: http://www.williamegarcia.com
*/

// --------------------------------------------------------------------------------------
// THIS FUNCTION RECEIVES THE XML, METHOD AND STREAMSEND'S API URL TO INVOKE
// AND SENDS THE INFORMATION VIA CURL TO STREAMSEND API
// THE RESULT FROM THE CURL COMMANDS WILL BE ECHOED HERE.
// CALLBACK FUNCTION FOR :  streamsend_form() AND add_shortcode('streamsend_api', 'streamsend_form');
// --------------------------------------------------------------------------------------

// Send request to StreamSend API to include email address into audience's list.

function send_streamsend_request( $args ) {

	$sr_custreq = 'POST';
  	$sr_url = "audiences/1/people.xml";
	
	$email = $args['email'];
	$fname =  $args['first_name'];
	$lname =  $args['last_name'];
	
	$options = get_option('streamsend_api_options');

	if( $options['streamsend_api_optin_mode'] == 'single' ) {
			$activate = 'true'; 
			$deliver_activation = 'false';
			$deliver_welcome = 'false';
				
	}else{ 
			$activate = 'false';
			$deliver_activation = 'true';
			$deliver_welcome = 'true';
	}
		
		
			//Put XML code together with the information received from the form and send to StreamSend
				
	$xml = "<person><email-address>".$email."</email-address><first-name>".$fname."</first-name><last-name>".$lname."</last-name><activate>".$activate."</activate><deliver-activation>".$deliver_activation."</deliver-activation><deliver-welcome>".$deliver_welcome."</deliver-welcome></person>";


	$user_agent = "Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.1)";
	$url = ("https://app.streamsend.com/" . $sr_url);

	$headers = array('Accept: application/xml',	'Content-Type: application/xml');
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	
	# AUTHENTICATION
	curl_setopt($ch, CURLOPT_USERPWD, $options['streamsend_api_login_id'] .":".  $options['streamsend_api_key'] );
	
	# PASSING DATA
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $sr_custreq);
	if ($xml != "") curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);  # the result of curl_exec is the actual data instead of a boolean
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
	//RESPONSE PARSING
	# get the HTTP status code for our session
	$http_status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	# return all options into an associative array
	$info = curl_getinfo($ch);
	# get the HTTP status code for our session $info['http_code'];
	// Send Request
	$response = curl_exec($ch);
	if (curl_errno($ch)) {
		echo curl_error($ch);
	} 
	else
	{
	#returns <xml>...</xml>
		
		echo $response; //If any other stuff was sent by the API, show it here.
			
		echo "<br />";
		
		if( $options['streamsend_api_optin_mode'] == 'double' ) {
			
			_e('You will receive an eamil to confirm your subscription.');
						
			}else{
			
			_e('Thanks for subscribing.');
			
			}
		}
	// Close Connection Handle
	curl_close ($ch);
	
}


// --------------------------------------------------------------------------------------
// THIS FUNCTION DISPLAYS THE STREAMSEND FORM WIDGET WHEN ADDED TO THE SIDEBAR, HEADER OR FOOTER.
// CALLBACK FUNCTION FOR: register_sidebar_widget 
// --------------------------------------------------------------------------------------

// Display WordPress Widget including the form in streandsend_form().

function streamsend_widget($args) {
	global $pagenow;
	extract($args);

	$options = get_option('streamsend_widget');
	$title = empty($options['title']) ? __('Join Our Mailing List') : apply_filters('widget_title', $options['title']);
?>
	<?php echo $before_widget; ?>
	<?php echo $before_title . $title . $after_title; ?>

	<?php streamsend_form();	?>

	<?php echo $after_widget; ?>

<?php } ?>
<?php

// --------------------------------------------------------------------------------------
// THIS FUNCTION RENDER THE STREAMSEND FORM AREA. IT IS INVOKED FROM THE WIDGET AND FROM THE SHORTCODE HOOK.
// WHEN SUBMITTED THE FORM WILL CALL send_streamsend_request() AND SUBMIT THE VALUES.
// CALLBACK FUNCTION FOR: streamsend_widget, 
// --------------------------------------------------------------------------------------

// Renders StreamSend Form.

function streamsend_form($args=null) {

	$options = get_option('streamsend_api_options');
	
	$join_button = __('Submit');
	if(isset( $args['join_button'] ) ) $join_button = $args['join_button'];
		?> 
        <style>
		/* TO DO This CSS code can be customized from Admin Area */
		#form_footer, #form_footer span, #form_footer a{font-size:10px;} 
        </style>
	<form class="streamsend_form" name="streamsend_form" id="streamsend_form" action="" method="post">
	<p><font color="#FF0000">
<?php  

   	if( $_POST['streamsed_email'] ) {  //IF form is posted, send to send_streamsend_request, echo results here.
		$email = $_POST['streamsed_email']; 

		if(! is_email($email) ){
			
				_e('Invalid Email Address');
				
		}else{
		
		//Sanitize input to avoid security threads.
			
			if($_POST['streamsed_firstname'] && $options['streamsend_api_fname']) $fname = sanitize_text_field($_POST['streamsed_firstname']); 
		
			if($_POST['streamsed_lastname'] && $options['streamsend_api_lname']) $laname = sanitize_text_field($_POST['streamsed_lastname']);
				
			$streamsend_args = array( 'email' => $email, 'first_name' => $fname, 'last_name' => $lname );
			
			
			send_streamsend_request($streamsend_args);
					
		}
	}
?></font></p>

	<p>
    <?php //Check options to see wich fields to render in the form ?>
    <?php if( $options['streamsend_api_fname'] ) { ?>
    <label><?php _e('First Name') ?>:</label>
            <input tabindex="1" type="text" name="streamsed_firstname" id="streamsed_firstname" class="input" value="<?php echo attribute_escape(stripslashes($fname)); ?>" size="25" tabindex="10" />
			<br />
    <?php } ?>
    
      <?php if( $options['streamsend_api_lname'] ) { ?>
    <label><?php _e('Last Name') ?>:</label>
            <input tabindex="1" type="text" name="streamsed_lastname" id="streamsed_lastname" class="input" value="<?php echo attribute_escape(stripslashes($lname)); ?>" size="25" tabindex="10" />
			<br />
    <?php } ?>
    
			<label><?php _e('E-mail') ?>:</label>
            <input tabindex="1" type="text" name="streamsed_email" id="streamsed_email" class="input" value="<?php echo attribute_escape(stripslashes($email)); ?>" size="25" tabindex="10" />
		</p>
			
		<p class="submit"><input tabindex="4" type="submit" name="streamsend_submit" id="streamsend_submit" value="<?php echo($join_button) ?>" tabindex="100" /></p>
        
        <?php if( $options['streamsend_api_link'] ) { ?>
        
        <span id="form_footer">
		<a target="_blank" href="http://www.streamsend.com/463.html"><?php _e('Email Marketing') ?></a> powered by <a href="http://www.streamsend.com/463.html" target="_blank"><?php _e('StreamSend') ?></a></span>
        <?php } ?>
	</form>
	<?php
}

// *** ADMIN FUNCTIONS ***

// ------------------------------------------------------------------------------
// RUNS WHEN THE PLUGIN IS ACTIVATED. IF THERE ARE NO THEME OPTIONS
// CURRENTLY SET, OR THE USER HAS SELECTED THE CHECKBOX TO RESET OPTIONS TO THEIR
// DEFAULTS THEN THE OPTIONS ARE SET/RESET.
//
// OTHERWISE, THE PLUGIN OPTIONS REMAIN UNCHANGED.
// ------------------------------------------------------------------------------

// Define StreamSend API default option settings
function streamsend_api_add_defaults() {
	$tmp = get_option('streamsend_api_options');
    if(($tmp['streamsend_api_default_options']=='1')||(!is_array($tmp))) {
		delete_option('streamsend_api_options'); // so we don't have to reset all the 'off' checkboxes too! (don't think this is needed but leave for now)
		$arr = array(	"streamsend_api_login_id" => "Enter API Login ID",
						"streamsend_api_key" => "Enter API key",
						"streamsend_api_optin_mode" => "double",
						"streamsend_api_fname" => "0",
						"streamsend_api_lname" => "0",
						"streamsend_api_email" => "1",
						"streamsend_api_link" => "1",
						"streamsend_api_default_options" => ""
						
		);
		update_option('streamsend_api_options', $arr);
	}
}


// --------------------------------------------------------------------------------------
// THIS FUNCTION RENDER THE STREAMSEND FORM IN THE ADMIN WIDGET AREA. 
// --------------------------------------------------------------------------------------

// Display draggable widget in widget admin area.

function streamsend_widget_control() {
	$options = $newoptions = get_option('widget_streamsed');
	if ( isset($_POST["streamsend_submit"]) ) {
		$newoptions['title'] = strip_tags(stripslashes($_POST["streamsend_title"]));
	}
	if ( $options != $newoptions ) {
		$options = $newoptions;
		update_option('streamsend_widget', $options);
	}
	$title = attribute_escape($options['title']);
?>
	<label><?php _e('Title:'); ?> <input class="widefat" id="streamsend_title" name="streamsend_title" type="text" value="<?php echo $title; ?>" /></label><input type="hidden" id="streamsend_submit" name="streamsend_submit" value="1" />
<?php
}



// ------------------------------------------------------------------------------
// CALLBACK FUNCTION SPECIFIED IN: add_options_page()
// ------------------------------------------------------------------------------
// THIS FUNCTION IS SPECIFIED IN add_options_page() AS THE CALLBACK FUNCTION THAT
// ACTUALLY RENDER THE PLUGIN OPTIONS FORM AS A SUB-MENU UNDER THE EXISTING
// SETTINGS ADMIN MENU.
// ------------------------------------------------------------------------------

// Render the StreamSend API Plugin options form
function streamsend_api_admin_form() {
	?>
	<div class="wrap">
		
		<!-- Display Plugin Icon, Header, and Description -->
		<div class="icon32" id="icon-options-general"><br></div>
	  <h2><?php _e('StreamSend API for WP'); ?></h2>
	    <p><?php _e('Enter here the information to control the StreamSend API for WordPress subscription.');?></p>
        

		<!-- Beginning of the Plugin Options Form -->
		<form method="post" action="options.php">
			<?php settings_fields('streamsend_api_plugin_options'); 
			 // !IMPORTANT: Options will be updated automatically when form submitted.
			?>
			<?php $options = get_option('streamsend_api_options'); ?>
            <?php // print_r($options); ?>


			<table class="form-table">

				
				<!-- Api Control Info  -->
				<tr>
					<th scope="row"><?php _e('API Login ID');?></th>
					<td>
					  <input type="text" size="30" name="streamsend_api_options[streamsend_api_login_id]" value="<?php echo $options['streamsend_api_login_id']; ?>" />
					</td>
				</tr>
                
                <tr>
					<th scope="row"><?php _e('API Key');?></th>
					<td>
					  <input type="text" size="30" name="streamsend_api_options[streamsend_api_key]" value="<?php echo $options['streamsend_api_key']; ?>" />
					</td>
				</tr>

				<!-- Radio Button Group for API Options -->
				<tr valign="top" style="border-top:#dddddd 1px solid;">
					<th scope="row">Opt-in Mode</th>
					<td>
						<label><input name="streamsend_api_options[streamsend_api_optin_mode]" type="radio" value="single" <?php checked('single', $options['streamsend_api_optin_mode']); ?> /> Single <span style="color:#666666;margin-left:32px;">The subscriber will not be asked for confirmation.</span></label><br />

						<label><input name="streamsend_api_options[streamsend_api_optin_mode]" type="radio" value="double" <?php checked('double', $options['streamsend_api_optin_mode']); ?> /> Double <span style="color:#666666;margin-left:32px;">The subscriber's will receive a confirmtion email (recommended).</span></label>
					</td>
				</tr>

				<!-- Checkbox Buttons for Subscriber's Form Options -->
				<tr valign="top" style="border-top:#dddddd 1px solid;">
					<th scope="row"><?php _e('Form Fields');?></th>
					<td>
						
						<label><input name="streamsend_api_options[streamsend_api_fname]" type="checkbox" value="1" <?php if (isset($options['streamsend_api_fname'])) { checked('1', $options['streamsend_api_fname']); } ?> /> First Name <em></em></label><br />

						
						<label><input name="streamsend_api_options[streamsend_api_lname]" type="checkbox" value="1" <?php if (isset($options['streamsend_api_lname'])) { checked('1', $options['streamsend_api_lname']); } ?> /> Last Name </label><br />


                        <label><input name="streamsend_api_options[streamsend_api_email]" type="checkbox" value="1" <?php if (isset($options['streamsend_api_email'])) { checked('1', $options['streamsend_api_email']); } ?> disabled="disabled" checked="checked" />E-Mail <em> (required)</em></label><br />

						
					</td>
				</tr>
				<!-- Checkbox Buttons for Database Options -->
				<tr valign="top" style="border-top:#dddddd 1px solid;">
					<th scope="row"><?php _e('Database Options');?></th>
					<td>
						<label><input name="streamsend_api_options[streamsend_api_link]" type="checkbox" value="1" <?php if (isset($options['streamsend_api_link'])) { checked('1', $options['streamsend_api_link']); } ?> /> Display <em>"Powered by Streamsed"</em> link</label>
                        <br />
                        <label><input name="streamsend_api_options[streamsend_api_default_options]" type="checkbox" value="1" <?php if (isset($options['streamsend_api_default_options'])) { checked('1', $options['streamsend_api_default_options']); } ?> /> Restore defaults upon plugin deactivation/reactivation</label>
						<br /><span style="color:#666666;margin-left:2px;">Only check this if you want to reset plugin settings upon Plugin reactivation</span>
					</td>
				</tr>

				<tr><td colspan="2" style="border-top:#dddddd 1px solid;">
				
                    
                    <p class="submit">
			<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
			</p>
                    
                    
					</td>
				</tr>
			</table>
			
		</form>

		<p style="margin-top:15px;">
        <!-- How to use the admin settings and help the author. !-->
        <p>To configure this Plugin you will need to have a <a target="_blank" href="http://www.streamsend.com/463.html"><a href="http://www.streamsend.com/463.html" target="_blank"><?php _e('StreamSend') ?></a> account.        </p>
        <p>1) Click on "Account".
          <br />
          2) On the API section, click on Activate.
          <br />
          3) Click on Generate/Regenerate to get the API Login ID and Key.
          <br />
        4) Copy and Paste into the appropied fields above.        </p>
        <a target="_blank" href="http://www.streamsend.com/463.html"><?php _e('Email Marketing') ?></a> powered by <a href="http://www.streamsend.com/463.html" target="_blank"><?php _e('StreamSend') ?></a>
        <br /><br />
			If you have found this plugin useful please consider donating to keep the development and support current. Thanks. <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="CW3NR78KDA5JE">
<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>

            
		</p>
	</div>
	<?php	
}

// --------------------------------------------------------------------------------------
// RUNS WHEN THE USER DEACTIVATES AND DELETES THE PLUGIN. IT SIMPLY DELETES
// THE PLUGIN OPTIONS DB ENTRY (WHICH IS AN ARRAY STORING ALL THE PLUGIN OPTIONS).
// --------------------------------------------------------------------------------------

// Delete options table entries ONLY when plugin deactivated AND deleted
function streamsend_delete_plugin_options() {
	delete_option('streamsend_options');
	delete_option('streamsend_widget');
}



// ------------------------------------------------------------------------------
// THIS FUNCTION RUNS WHEN THE 'admin_init' HOOK FIRES, AND REGISTERS YOUR PLUGIN
// SETTING WITH THE WORDPRESS SETTINGS API. REQUIRED TO MAKE USE THE API OPTIONS
// SETTINGS FROM WORDPRESS
// ------------------------------------------------------------------------------

// Init plugin options to white list our options
function streamsend_api_init(){
	register_setting( 'streamsend_api_plugin_options', 'streamsend_api_options' );
}

// ------------------------------------------------------------------------------
// CALLBACK FUNCTION FOR: add_action('admin_menu', 'streamsend_add_options_page');
// ------------------------------------------------------------------------------
// THIS FUNCTION RUNS WHEN THE 'admin_menu' HOOK FIRES, AND ADDS A NEW OPTIONS
// PAGE FOR YOUR PLUGIN TO THE SETTINGS MENU.
// ------------------------------------------------------------------------------

// Add menu page
function streamsend_add_options_page() {
	add_options_page('StreamSend API Options Page', 'StreamSend API for WP', 'manage_options', __FILE__, 'streamsend_api_admin_form');
}

// ------------------------------------------------------------------------------
// INTIATE WIDGET CONTROL DEPRECATED, HERE FOR BACKWARD COMPATIBILITY
// CALLBACK FUNCTION FOR: streamsend_widget, streamsend_widget_contol
// ------------------------------------------------------------------------------

function streamsend_widget_init(){
	
	register_sidebar_widget("Streamsend API Form", "streamsend_widget");
	register_widget_control("Streamsend API Form","streamsend_widget_control");

}
// Set-up Action, Filter Hooks and Shorcodes

function streamsend_register_user( $user_id ) {

	/* Add the user's email to streamsend list */
	//if ( function_exists( 'send_streamsend_request' ) ) {
		
		$user_arr = get_userdata( $user_id );
		
		$email = $user_arr->user_email; 
		
		$fname = get_usermeta( $user_id, 'first_name' );
		$lname = get_usermeta( $user_id, 'last_name' );
		
		$streamsend_args = array('email' => $email , 'first_name'=> $fname ,  'last_name'=> $lname );

		send_streamsend_request( $streamsend_args );
	//}
}
// Add user to streamsedn mailing list when user is created
add_action( 'user_register', 'streamsend_register_user' );

add_shortcode('streamsend_api', 'streamsend_form');

register_activation_hook(__FILE__, 'streamsend_api_add_defaults');
register_uninstall_hook(__FILE__, 'streamsend_delete_plugin_options');

add_action("plugins_loaded", "streamsend_widget_init");
add_action('admin_init', 'streamsend_api_init' );
add_action('admin_menu', 'streamsend_add_options_page');

?>