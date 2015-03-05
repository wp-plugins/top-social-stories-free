<?php
/*
Plugin Name: Top Social Stories Free
Plugin URI: http://www.barattalo.it/top-stories-plugin-widget/
Description: Create top stories from Facebook's likes, shares, comments and Twitter's tweets, Google+, Pinterest, VKontakte and Linkedin, found your top viral posts (most shared), display charts and show trending posts and authors. This is a free version of the full plugin sold on CodeCanyon.
Version: 1.74
Author: Giulio Pons
Author URI: http://codecanyon.net/user/ginoplusio
*/

include("widget.php");


function top_stories_get_defaults() {
	return array(
		'top_stories_delay' => '30',		// 30 seconds default delay
		'top_stories_hits'=>'10',
		'top_stories_days'=>'365',
		'top_stories_save_custom'=>'0',		// 1=yes, save also custom fields
		'top_stories_placeholder'=>plugin_dir_url( __FILE__ ).'images/placeholder.jpg',
		'top_stories_start'=>date("Y-m-d"),
		'top_stories_pt' => array('post','page','attachment'),
		'top_stories_serie'=>"ftglpv",	// "ftglpv" f=facebook, t=twitter, g=google+, l=linkedin, p=pinterest, v=vkontakte
		'top_stories_mail_level'=>"0"	// send mail when pass 1000 total interactions, 0 not send
	);

}

//---------------------------------------------
// Scripts And Css
//---------------------------------------------
function top_stories_scripts(){
	global $post;
	wp_enqueue_script("jquery");

	if ( is_single() || is_page()) {
		/*
			load javascript only on single page
		*/
		$options = wp_parse_args(get_option('top_stories_settings'), top_stories_get_defaults());

		wp_register_script('top_stories_script_js',plugin_dir_url( __FILE__ ).'js/top-stories.js');
		wp_enqueue_script('top_stories_script_js');

		/*
			if a post has been published before plugin activation date
			force first record on first ajax call
		*/
		if($options['top_stories_start'] > date("Y-m-d",strtotime($post->post_date))) {
			$force_date = date("Y-m-d",strtotime($post->post_date));
		} else {
			$force_date = "";
		}

		$params = array(
			"url" => plugin_dir_url( __FILE__ ).'plusones.php',
			"ajax_url" => admin_url( 'admin-ajax.php' ),
			"post_id" => $post->ID,
			"timer" => $options['top_stories_delay'],
			"permalink" => get_permalink(),
			"force_date"=>$force_date,
			"serie"=> $options['top_stories_serie']
		);
		wp_localize_script( 'top_stories_script_js', 'top_stories_params', $params );
	}

	wp_register_style( 'top-stories', plugins_url( 'top-stories-free/css/style.css' ) );
	wp_enqueue_style( 'top-stories' );
}
add_action('wp_enqueue_scripts','top_stories_scripts');






//---------------------------------------------
// Modify Posts List in WP-Admin: two more columns
//---------------------------------------------
function top_stories_show_like_column( $column ) {
	$column['facebook'] = "<img src='".plugin_dir_url( __FILE__ )."images/fb-admin-icon.png'/>";
	$column['twitter'] = "<img src='".plugin_dir_url( __FILE__ )."images/tw-admin-icon.png'/>";
	$column['google'] = "<img src='".plugin_dir_url( __FILE__ )."images/go-admin-icon.png'/>";
	$column['linkedin'] = "<img src='".plugin_dir_url( __FILE__ )."images/li-admin-icon.png'/>";
	$column['pinterest'] = "<img src='".plugin_dir_url( __FILE__ )."images/pi-admin-icon.png'/>";
	$column['vkontakte'] = "<img src='".plugin_dir_url( __FILE__ )."images/vk-admin-icon.png'/>";
	return $column;
}
function top_stories_show_like_column_row( $column_name, $post_id ) {
	global $wpdb;
	if( $column_name=='facebook' || 
		$column_name=='twitter' || 
		$column_name=='google' || 
		$column_name=='pinterest' || 
		$column_name=='vkontakte' || 
		$column_name=='linkedin') {
		$v = execute_row("sel"."ect {$column_name}_shares f"."rom {$wpdb->prefix}top_stories where id_post=$post_id order by dt_day desc limit 0,1");
		if(is_array($v)) $v = $v["{$column_name}_shares"] ;  else $v = "-";
		echo $v;
	}
}

add_filter( 'manage_posts_columns', 'top_stories_show_like_column' );
add_filter( 'manage_posts_custom_column', 'top_stories_show_like_column_row', 10,2 );


//---------------------------------------------
// Add Menu Item "Top Stories" Inside Posts in Admin
//---------------------------------------------
function top_stories_menu() {
	add_submenu_page( 'edit.php','Top Social Stories' , 'Top Social Stories', 'manage_options', 'top_stories_menu_analytics', 'top_stories_analytics');
	add_submenu_page( 'options-general.php','Top Stories Settings' , 'Top Stories Settings', 'manage_options', 'top_stories_menu_settings', 'top_stories_settings');
}
add_action( 'admin_menu', 'top_stories_menu' );


//---------------------------------------------
// Handle "Top Stories" Settings Page Functions
//---------------------------------------------

function top_stories_admin_script($hook) {
	global $wp_version;
	if($hook!="posts_page_top_stories_menu_analytics" && $hook!="edit.php" && $hook!="settings_page_top_stories_menu_settings") return;
	// admin scripts should be loaded only where they are useful:
	// on top stories plugin page, list of posts

	if($hook=="posts_page_top_stories_menu_analytics" || $hook=="settings_page_top_stories_menu_settings") {

		// js/css scripts for admin
		wp_enqueue_script("jquery");
		wp_register_script('top_stories_admin_script_js',plugin_dir_url( __FILE__ ).'js/top-stories.js');
		wp_enqueue_script('top_stories_admin_script_js');

	}
	
	if($wp_version > '3.7.9') {
		// css metro like ui
		wp_register_style( 'top-stories-admin', plugin_dir_url( __FILE__ ).'css/style.3.8.css'  );
	} else {
		wp_register_style( 'top-stories-admin', plugin_dir_url( __FILE__ ).'css/style.css'  );
	}
	wp_enqueue_style( 'top-stories-admin' );
}
add_action( 'admin_enqueue_scripts', 'top_stories_admin_script' );


add_action('admin_init', 'top_stories_register_settings');
function top_stories_register_settings(){
	register_setting('top_stories_settings_group', 'top_stories_settings', 'top_stories_settings_validate');
}

function top_stories_settings_validate($args){

	/*
	if(!isset($args['top_stories_delay'])) {
		if(isset($args['top_stories_days']) &&
			$args['top_stories_hits']
			) {

			//$options = get_option( 'top_stories_settings' , top_stories_get_defaults() );
			//$options['top_stories_delay'] = $args['top_stories_delay'];
			//$options['top_stories_hits'] = $args['top_stories_hits'];
			////print_r($options);
			////die;
			return $args;
		}
		
	}*/


	if(!isset($args['top_stories_pt'])) $args['top_stories_pt'] = array('post','page','attachment');

	if(!isset($args['top_stories_start'])|| $args['top_stories_start']=="") $args['top_stories_start'] = date("Y-m-d");

	if(!isset($args['top_stories_mail_level'])|| $args['top_stories_mail_level']=="") $args['top_stories_mail_level'] = 0;

	if(!isset($args['top_stories_serie'])|| $args['top_stories_serie']=="") $args['top_stories_serie'] = "ftglpv";

	if(!isset($args['top_stories_hits'])) $args['top_stories_hits'] = 10;
	if((integer)$args['top_stories_hits']>100) $args['top_stories_hits'] = 100;
	if((integer)$args['top_stories_hits']<1) $args['top_stories_hits'] = 1;
	if((integer)$args['top_stories_save_custom']<1) $args['top_stories_save_custom'] = 0;

	if(!isset($args['top_stories_days'])) $args['top_stories_days'] = 30;
	if((integer)$args['top_stories_days']>20000) $args['top_stories_days'] = 20000;	// from the beginning...
	if((integer)$args['top_stories_days']<0) $args['top_stories_days'] = 0;

	if(!isset($args['top_stories_placeholder']) || $args['top_stories_placeholder']=="") {
		$args['top_stories_placeholder'] = plugin_dir_url( __FILE__ ).'images/placeholder.jpg';
	}

	if(!isset($args['top_stories_serie'])) $args['top_stories_serie'] = "ftglpv";
	$args['top_stories_serie'] = preg_replace("/[^ftglpv]/","",$args['top_stories_serie']);

	if(!isset($args['top_stories_delay']) 
		|| (integer)$args['top_stories_delay']<1 
		|| (integer)$args['top_stories_delay']>999
		|| preg_match("/[^0-9]/",$args['top_stories_delay'])
	) {
		//add a settings error because the number specified is invalid
		$args['top_stories_delay'] = '';
		add_settings_error(
			'top_stories_settings',
			'top_stories_errors', 
			__('Please enter a number of seconds between 1 and 999 for Delay parameter!','top-stories-plugin'), 
			'error'
		);
	}

	if($args['top'.'_stories_'.'days']>30) {
		$args['top'.'_stories_'.'days'] = '30';
		add_settings_error(
			'top_stories_settings',
			'top_stories_errors', 
			__('Sorry, this period is not available in the FREE version.','top-stories-plugin'), 
			'error'
		);
	}


	return $args;
}

add_action('admin_notices', 'top_stories_settings_admin_notices');
function top_stories_settings_admin_notices(){
	//settings_errors();
}


//---------------------------------------------
// Form Top Stories Settings
//---------------------------------------------
function top_stories_analytics() {
	global $wpdb,$wp_version;
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __('You do not have sufficient permissions to access this page.','top-stories-plugin') );
	}
	$options = get_option( 'top_stories_settings' , top_stories_get_defaults() );
	if(!is_array($options['top_stories_pt'])) $options['top_stories_pt'] = array('post','page','attachment');

	?>
	<div class="wrap tss">

		<form action="options.php" method="post">
			<h2><?php 
				//$from = date("jS \of F Y",strtotime("-{$options['top_stories_days']} days"));
				//$to = date("jS \of F Y");
				$from = date_i18n( get_option( 'date_format' ), strtotime("-{$options['top_stories_days']} days") );
				$to = date_i18n( get_option( 'date_format' ), strtotime("now" ) );
				printf( __( 'Top stories from %1$s to %2$s', 'top-stories-plugin' ), $from, $to );
			?></h2>


		<div class="updated"><p>Notice: this free version analyzes data only from one month to date. The full version comes without date-limit and comes with  <code>shortcodes</code>, top authors widget, charts for single posts and for all your blog, email alerts for authors and also multilingual support. <a href="http://codecanyon.net/item/top-social-stories-plugin-and-widget/5888553?ref=ginoplusio" target="_blank">[more info]</a></p></div>

		<?php
		$c = execute_row("s"."el"."e"."ct count(*) as q fr"."o"."m ".$wpdb->prefix."top_stories");
		if($c['q']<1000 && (integer)$options['top_stories_delay']>=5) {
			?>
			<div class="update-nag"><p>It seems that you haven't yet collected enaugh data to show interesting things. Try this trick: go to settings page and put the <b>Delay for ajax calls</b> parameter to <code>1</code> or <code>2</code>, now it is <code><?php echo $options['top_stories_delay'];?></code>, after a few days put it back to <code><?php echo $options['top_stories_delay'];?></code>. <a href="options-general.php?page=top_stories_menu_settings">[go to settings]</a></p></div>
			<?php
		}
		?>


			<?php
			settings_fields( 'top_stories_settings_group' );
			do_settings_sections( 'top_stories_settings_group' );
			settings_errors();
			if (!function_exists("curl_init"))  {
				?><div class='error settings-error'><p><?php _e("WARNING: CURL module missing in your php configuration, Google+ data are not fetchable.",'top-stories-plugin') ;?></p></div><?php
			}

			?>
			<div id='top_stories_settings_errors'></div>
			<table class="form-table" width="100%" style="margin-top:40px">
				<tr>
					<td width="13%" style="vertical-align:bottom">
						<fieldset>
							<label for="top_stories_hits"><?php _e("Items:","top-stories-plugin");?></label>
							<select class='fat' id="top_stories_hits" name="top_stories_settings[top_stories_hits]">
							<?php
								$vals = array(5,10,15,20,30,40,50,100);
								for($i=1;$i<count($vals);$i++) {
									?><option value="<?php echo $vals[$i];?>" <?php echo $options['top_stories_hits']==$vals[$i] ? "selected='selected'" : ""; ?>><?php echo $vals[$i] ?> <?php _e("posts","top-stories-plugin");?></option><?php
								}
							?>
							</select>
							</fieldset>
						</td>
						<td width="13%" style="vertical-align:bottom">
						<fieldset>
							<label for="top_stories_days"><?php _e("Period:","top-stories-plugin");?></label>
								<select id="top_stories_days" name="top_stories_settings[top_stories_days]">
								<option value="0" <?php echo $options['top_stories_days']=="0" ? "selected='selected'" : ""; ?>><?php _e("today","top-stories-plugin");?></option>
								<option value="7" <?php echo $options['top_stories_days']=="7" ? "selected='selected'" : ""; ?>><?php _e("1 week","top-stories-plugin");?></option>
								<option value="14" <?php echo $options['top_stories_days']=="14" ? "selected='selected'" : ""; ?>><?php _e("2 weeks","top-stories-plugin");?></option>
								<option value="30" <?php echo $options['top_stories_days']=="30" ? "selected='selected'" : ""; ?>><?php _e("1 month","top-stories-plugin");?></option>
								<option value="60" <?php echo $options['top_stories_days']=="60" ? "selected='selected'" : ""; ?>><?php _e("2 months","top-stories-plugin");?></option>
								<option value="91" <?php echo $options['top_stories_days']=="91" ? "selected='selected'" : ""; ?>><?php _e("3 months","top-stories-plugin");?></option>
								<option value="182" <?php echo $options['top_stories_days']=="182" ? "selected='selected'" : ""; ?>><?php _e("6 months","top-stories-plugin");?></option>
								<option value="365" <?php echo $options['top_stories_days']=="365" ? "selected='selected'" : ""; ?>><?php _e("1 year","top-stories-plugin");?></option>
								<option value="20000" <?php echo $options['top_stories_days']=="20000" ? "selected='selected'" : ""; ?>><?php _e("ever","top-stories-plugin");?></option>
							</select>
							</fieldset>
					</td>
					<td width="15.5%%" style="vertical-align:bottom">
						<fieldset class='submittt'><?php submit_button(__('Save & Refresh',"top-stories-plugin")); ?></fieldset>
					</td>
					<?php
					if($wp_version > '3.7.9') $m = "m"; else $m="";
					?>
					<td width="50%" style="padding:0 2.5% 0 2.5%">
						<div id='legend'>
							<div><h3><?php _e("Legenda","top-stories-plugin");?></h3><img src='<?php echo plugin_dir_url( __FILE__ )?>images/up<?php echo $m;?>.png'/> <?php _e("total daily shares increasing","top-stories-plugin");?><br/>
							<img src='<?php echo plugin_dir_url( __FILE__ )?>images/down<?php echo $m;?>.png'/> <?php _e("total daily shares decreasing","top-stories-plugin");?></div>
							<div><img src='<?php echo plugin_dir_url( __FILE__ )?>images/fire50<?php echo $m;?>.png'/> <?php _e("&gt;50 shares today","top-stories-plugin");?><br/>
							<img src='<?php echo plugin_dir_url( __FILE__ )?>images/fire100<?php echo $m;?>.png'/> <?php _e("&gt;100 shares today","top-stories-plugin");?><br/>
							<img src='<?php echo plugin_dir_url( __FILE__ )?>images/fire1000<?php echo $m;?>.png'/> <?php _e("&gt;1000 shares today","top-stories-plugin");?></div>
						</div>
					</td>
				</tr>
			</table>
			
			<?php
			echo top_stories_getStats(
				$options['top_stories_days'],
				$options['top_stories_hits'],
				$options['top_stories_placeholder'],
				$options['top_stories_pt']
			);
			?>
			
			<br/><br/>

			<?php
			/*
			pass data if saving
			*/
			$custom_post_types = get_post_types( array( 'public' => true ) );
			foreach($custom_post_types as $typ) {
				$checked = in_array($typ, $options['top_stories_pt']) ?  1 : 0;
				if($checked) echo "<input type='hidden' name='top_stories_settings[top_stories_pt][]' value='$typ' />";
			}

			if(!isset($options['top_stories_serie'])) $options['top_stories_serie'] = "ftglpv";
			$options['top_stories_serie'] = preg_replace("[^ftglpv]","",$options['top_stories_serie']);


			?>
			<input type="hidden" name="top_stories_settings[top_stories_placeholder]" value="<?php echo esc_url( $options['top_stories_placeholder'] ); ?>"/>

			<input name="top_stories_settings[top_stories_delay]" type="hidden" id="top_stories_delay" value="<?php echo $options['top_stories_delay']; ?>"/>

			<input type="hidden" name="top_stories_settings[top_stories_save_custom]" value="<?php echo $options['top_stories_save_custom'];?>"/> 
			<input type="hidden" name="top_stories_settings[top_stories_serie]" value="<?php echo $options['top_stories_serie'];?>"/> 
			<input name="top_stories_settings[top_stories_start]" type="hidden" value="<?php echo $options['top_stories_start']; ?>"/>
			<input name="top_stories_settings[top_stories_mail_level]" type="hidden" value="<?php echo $options['top_stories_mail_level']; ?>"/>

		</form>


	</div>
	<?php
}

function top_stories_settings() {
	global $wpdb,$wp_version;
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( 'You do not have sufficient permissions to access this page.' );
	}
	$options = get_option( 'top_stories_settings' , top_stories_get_defaults() );
	//print_r($options);die;
	if(!is_array($options['top_stories_pt'])) $options['top_stories_pt'] = array('post','page','attachment');


	?>
	<div class="wrap tss">
		<form action="options.php" method="post">

			<h2><?php _e("Top Stories Settings","top-stories-plugin");?></h2>
			<?php
			//echo $options['top_stories_start']."(";
			settings_fields( 'top_stories_settings_group' );
			do_settings_sections( 'top_stories_settings_group' );
			//settings_errors();
			if (!function_exists("curl_init"))  {
				?><div class='error settings-error'><p><?php _e("WARNING: CURL module missing in your php configuration, Google+ data are not fetchable.",'top-stories-plugin') ;?></p></div><?php
			}

			?>
			<div id='top_stories_settings_errors'></div>
			<div id='poststuff' style="margin-right:5%">
				<div class='postbox' style='max-width:600px;width:60%;float:left;'>

					<!--<h3 style="cursor:default">Plugin Settings</h3>-->
					<table class="form-table" style="margin:0 10px;width:95%">
						<tr>
							<th scope="row"><?php _e("Analyze these post types","top-stories-plugin");?></th>
							<td>
								<fieldset>
								<?php
								$custom_post_types = get_post_types( array(
									// Set to TRUE to return only public post types
									'public' => true
								) );
								foreach($custom_post_types as $typ) {
									$checked = in_array($typ, $options['top_stories_pt']) ?  "checked" : "";
									echo "<label><input type='checkbox' name='top_stories_settings[top_stories_pt][]' value='$typ' ".$checked."/> $typ</label><br/>";
								}
								?>
								</fieldset>
							</td>
						</tr>


						<tr>
							<th scope="row"><label for="serie"><?php _e("Social Networks","top-stories-plugin");?></label></th>
							<td>
								<fieldset id='serie_checks'>
									<input type="hidden" id="serie" name="top_stories_settings[top_stories_serie]" value="<?php echo  $options['top_stories_serie'] ; ?>" size="6"/>
									<span class="description"><?php _e("Fetch data only for these social networks.","top-stories-plugin");?></span>
									<br>
									<?php
									$column = array();
									$column = top_stories_show_like_column($column);
									?>
									<label><input type='checkbox' id='facebook'><?php echo $column['facebook'];?> facebook</label><br>
									<label><input type='checkbox' id='twitter'><?php echo $column['twitter'];?> twitter</label><br>
									<label><input type='checkbox' id='google'><?php echo $column['google'];?> google+</label><br>
									<label><input type='checkbox' id='linkedin'><?php echo $column['linkedin'];?> linkedin</label><br>
									<label><input type='checkbox' id='pinterest'><?php echo $column['pinterest'];?> pinterest</label><br>
									<label><input type='checkbox' id='vkontakte'><?php echo $column['vkontakte'];?> vkontakte</label><br>
								</fieldset>
							</td>
						</tr>

						<tr>
							<th scope="row"><label for="placeholder_url"><?php _e("Placeholder image","top-stories-plugin");?></label></th>
							<td>
								<fieldset>
									<input type="text" id="placeholder_url" name="top_stories_settings[top_stories_placeholder]" value="<?php echo esc_url( $options['top_stories_placeholder'] ); ?>" size="35"/><br/>
									<span class="description"><?php _e("Url used if there is no image (choose a square file about 150x150 pixel).","top-stories-plugin");?></span>
								</fieldset>
							</td>
						</tr>

						<tr>
							<th scope="row"><label for="top_stories_delay"><?php _e("Delay for ajax calls","top-stories-plugin");?></label></th>
							<td>
								<fieldset>
									
										<input name="top_stories_settings[top_stories_delay]" size="3" maxlength="3" type="text" id="top_stories_delay" value="<?php echo (isset($options['top_stories_delay']) && $options['top_stories_delay'] != '') ? $options['top_stories_delay'] : ''; ?>"/> <?php _e("seconds","top-stories-plugin");?>
										<br />
										<span class="description"><?php _e("Calls are delayed to not overload servers.","top-stories-plugin");?></span>
										<span class="description">When you first install this plugin, put this value to <code>1</code> or <code>2</code> to speed up the data grabbing. After a few days, put it back -at least- to <code>15</code> seconds to not overload servers.</span>
										
									
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="top_stories_save_custom"><?php _e("Export data in custom fields","top-stories-plugin");?></label></th>
							<td>
								<fieldset>
									<select name="top_stories_settings[top_stories_save_custom]" id="top_stories_save_custom">
										<option value="1" <?php echo (isset($options['top_stories_save_custom']) && $options['top_stories_save_custom'] == '1') ? "selected='selected'" : ""; ?>><?php _e("Yes","top-stories-plugin");?></option>
										<option value="0" <?php echo (isset($options['top_stories_save_custom']) && $options['top_stories_save_custom'] == '0') ? "selected='selected'" : ""; ?>><?php _e("No","top-stories-plugin");?></option>
									</select>
									<br />
									<span class="description"><?php _e("Save data in custom fields so you can use in your theme.","top-stories-plugin");?></span>
								</fieldset>
							</td>
						</tr>

						<tr>
							<th scope="row"><label for="top_stories_mail_level"><?php _e("Send mail notice level","top-stories-plugin");?></label></th>
							<td>
								<fieldset>
									<input name="top_stories_settings[top_stories_mail_level]" size="10" maxlength="10" type="text" id="top_stories_mail_level" value="<?php echo (isset($options['top_stories_mail_level']) && $options['top_stories_mail_level'] != '') ? $options['top_stories_mail_level'] : '0'; ?>"/>
									<br />
									<span class="description"><?php _e("Top Stories plugin will send an email notice to the author when its post will pass this number of social interactions. Put 0 to stop sending emails.","top-stories-plugin");?> <b>THIS FEATURE IS AVAILABLE ONLY IN THE FULL VERSION</b><br/>
									</span>
								</fieldset>
							</td>
						</tr>

						<tr>
							<th scope="row"><label for="top_stories_start"><?php _e("Activation date (read only)","top-stories-plugin");?></label></th>
							<td>
								<fieldset>
									<input name="top_stories_settings[top_stories_start]" size="10" maxlength="10" type="text" id="top_stories_start" value="<?php echo (isset($options['top_stories_start']) && $options['top_stories_start'] != '') ? $options['top_stories_start'] : ''; ?>"/> (Y-m-d)
									<br />
									<span class="description"><?php _e("The plugin starts tracking posts from this date. It's used to handle old posts correctly.","top-stories-plugin");?><br/>
									You should not touch this value.
									</span>
								</fieldset>
							</td>
						</tr>

						<tr>
							<th scope="row"><label for="top_stories_start"><?php _e("Database usage","top-stories-plugin");?></label></th>
							<td>
								<fieldset>
									<?php
									$c = execute_row("s"."el"."e"."ct count(*) as q fr"."o"."m ".$wpdb->prefix."top_stories");
									echo number_format($c["q"],0,'.',',');
									?> <?php _e("rows saved","top-stories-plugin");?><br>
									<span class="description"><?php _e("For better performance switch the this table in your database to InnoDB engine:","top-stories-plugin");?> <code><?php echo $wpdb->prefix;?>top_stories</code>
									</span>
								</fieldset>
								<?php submit_button(); ?>
							</td>
						</tr>

					
					</table>
					
				</div>

				<div class="postbox" style='max-width:600px;width:10%;float:left;margin-left:20px'>
					<h3 style="cursor:default"><?php _e("Author","top-stories-plugin");?></h3>
					<table class="form-table">
						<tr><td><p><?php _e("This plugin was made by Giulio Pons, visit the <a href=\"http://www.barattalo.it/top-stories-plugin-widget/\" target=\"_blank\">plugin homepage</a> and follow me on <a href=\"http://codecanyon.net/user/ginoplusio\" target=\"_blank\">CodeCanyon</a>.","top-stories-plugin");?></p></td></tr>
					</table>
				</div>

				<div class="postbox" style='max-width:600px;width:10%;float:left;margin-left:20px'>
					<h3 style="cursor:default"><?php _e("More features in full version!","top-stories-plugin");?></h3>
					<table class="form-table">
						<tr><td><p><?php _e("Discover the full version with also analytics charts, shortcodes, email alerts and author widget.<br/> <a href=\"http://www.barattalo.it/top-stories-plugin-widget/\" target=\"_blank\"><img src='".plugin_dir_url( __FILE__ ).'images/complete-version.jpg'."'></a>.","top-stories-plugin");?></p></td></tr>
					</table>
				</div>


			</div>

			<input type="hidden" name="top_stories_settings[top_stories_hits]" value="<?php echo $options['top_stories_hits']; ?>"/>

			<input type="hidden" name="top_stories_settings[top_stories_days]" value="<?php echo $options['top_stories_days']; ?>"/>

		</form>
		<div class="updated"><p>Notice: this free version analyzes data only from one month to date. The full version comes without date-limit and comes with  <code>shortcodes</code>, top authors widget, charts for single posts and for all your blog, email alerts for authors and also multilingual support. <a href="http://codecanyon.net/item/top-social-stories-plugin-and-widget/5888553?ref=ginoplusio" target="_blank">[more info]</a></p></div>

	</div>
	<?php
}

//
//---------------------------------------------
// On activation create table to store social historical data
//---------------------------------------------
function top_stories_activate() {
	global $wpdb;

	$wpdb->query("
	CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."top_stories` (
		`id_post` int(10) unsigned NOT NULL,
		`dt_day` date NOT NULL,
		`facebook_shares` int(11) NOT NULL,
		`twitter_shares` int(11) NOT NULL,
		`google_shares` int(11) NOT NULL,
		`facebook_shares_start` int(11) NOT NULL,
		`twitter_shares_start` int(11) NOT NULL,
		`google_shares_start` int(11) NOT NULL,
		PRIMARY KEY  (`id_post`,`dt_day`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='Data for top stories plugin';");

	$ar = execute_row ("SE"."LECT * FR"."O"."M `".$wpdb->prefix."top_stories`");

	/* upgrade old table for Google+ support*/
	if(!isset($ar['google_shares'])) {
		$wpdb->query("
			ALTER TABLE  `".$wpdb->prefix."top_stories` ADD  `google_shares` INT NOT NULL DEFAULT  '0',
			ADD `google_shares_start` INT NOT NULL DEFAULT  '0';
		");
	}

	/* upgrade old table for Linkedin support*/
	if(!isset($ar['linkedin_shares'])) {
		$wpdb->query("
			ALTER TABLE  `".$wpdb->prefix."top_stories` ADD  `linkedin_shares` INT NOT NULL DEFAULT  '0',
			ADD `linkedin_shares_start` INT NOT NULL DEFAULT  '0';
		");
	}

	/* upgrade old table for Pinterest support*/
	if(!isset($ar['pinterest_shares'])) {
		$wpdb->query("
			ALTER TABLE  `".$wpdb->prefix."top_stories` ADD  `pinterest_shares` INT NOT NULL DEFAULT  '0',
			ADD `pinterest_shares_start` INT NOT NULL DEFAULT  '0';
		");
	}

	/* upgrade old table for Vkontakte support*/
	if(!isset($ar['vkontakte_shares'])) {
		$wpdb->query("
			ALTER TABLE  `".$wpdb->prefix."top_stories` ADD  `vkontakte_shares` INT NOT NULL DEFAULT  '0',
			ADD `vkontakte_shares_start` INT NOT NULL DEFAULT  '0';
		");
	}


	/*
		check fields type (fix old bugged versions)
	*/
	$rs = $wpdb->get_results("se"."lect COLUMN_NAME,COLUMN_TYPE f"."ro"."m information_schema.COLUMNS where TABLE_NAME='".$wpdb->prefix."top_stories' and COLUMN_TYPE LIKE '%unsigned%'", OBJECT);
	if ($rs) {
		foreach ($rs as $row) {
			if($row->COLUMN_NAME!="id_post") {
				$wpdb->query("ALTER TABLE  `".$wpdb->prefix."top_stories` CHANGE  `".$row->COLUMN_NAME."`  `".$row->COLUMN_NAME."` INT NOT NULL;");
			}
		}
	}

}
register_activation_hook( __FILE__, 'top_stories_activate' );



function top_stories_get_pic($post,$placeholder) {
	$pic = "";
	$pic = get_the_post_thumbnail( $post->post_id, 'thumbnail' );
	if(!$pic) {
		preg_match("#img(.*)src=('|\")([^'\"]*)('|\")#",$post->post_content,$matches);
		if(isset($matches[3])) {
			preg_match_all('/(width|height)=\"\d*\"\s/',$post->post_content,$ar);
			$width = preg_replace("/[^0-9]/","",$ar[0][0]);
			$height = preg_replace("/[^0-9]/","",$ar[0][1]);
			$pic = "<img src=\"".$matches[3]."\" width=\"$width\" height=\"$height\" />"; 
		} else $pic = "";
	}
	if($pic) {
		preg_match_all('/(width|height)=\"\d*\"\s/',$pic,$ar);
		$pic = "<span class='img'>".preg_replace( '/(width|height)=\"\d*\"\s/', "", $pic )."</span>";
		if(isset($ar[0][1])) {
			$width = preg_replace("/[^0-9]/","",$ar[0][0]);
			$height = preg_replace("/[^0-9]/","",$ar[0][1]);
			if($width/$height >= 1) {
				// horizontal
				if($width/$height > 8/5) {
					// very horizontal
					$pic = str_replace(" src="," class='long' src=",$pic);
				}
			} else {
				// vertical
				$newheight = round ( $height * 80 / $width);

				if($newheight>=100) {
					$newheight = round(($newheight-50)/2);
				} else {
					$newheight = round(($newheight)/2);
				}
				$pic = str_replace(" src="," class='vertical' style='margin-top:-".($newheight)."px' src=",$pic);
			}
		}
	}
	if($pic=="" && $placeholder){
		$pic = "<span class='img'><img src=\"".$placeholder."\"/></span>";
	}
	return $pic;

}

function top_stories_get_days($id) {
	// used to merge data from sources and display
	// daily data (every item in the historical table has
	// the total count, for chart is needed daily).
	//
	global $wpdb;
	$ieri = execute_row("se"."le"."ct * fr"."o"."m ".$wpdb->prefix."top_stories where id_post='".$id."' and dt_day<'".date("Y-m-d")."' order by dt_day desc limit 0,1");
	if(!is_array($ieri)) {
		$ieri['facebook_shares']=0; $ieri['twitter_shares']=0; $ieri['google_shares']=0; $ieri['tot']=0; 
		$ieri['linkedin_shares']=0; $ieri['pinterest_shares']=0; $ieri['vkontakte_shares']=0;
	} else {
		$ieri['tot'] = $ieri['facebook_shares'] + $ieri['twitter_shares'] + $ieri['google_shares'] +
			$ieri['linkedin_shares'] + $ieri['pinterest_shares'] + $ieri['vkontakte_shares'];
	}
	
	$oggi = execute_row("s"."ele"."ct * f"."r"."om ".$wpdb->prefix."top_stories where id_post='".$id."' and dt_day='".date("Y-m-d")."'");
	if(!is_array($oggi)) {
		$oggi = $ieri; 
	} else {
		$oggi['tot'] = $oggi['facebook_shares'] + $oggi['twitter_shares'] + $oggi['google_shares'] +
			$oggi['linkedin_shares'] + $oggi['pinterest_shares'] + $oggi['vkontakte_shares'];
	}

	$altroieri = execute_row("s"."ele"."ct * fr"."om ".$wpdb->prefix."top_stories where id_post='".$id."' and dt_day<'".date("Y-m-d",strtotime("-1 day"))."' order by dt_day desc limit 0,1");
	if(!is_array($altroieri)) {
		$altroieri['facebook_shares']=0; $altroieri['twitter_shares']=0; $altroieri['google_shares']=0; $altroieri['tot']=0; 
		$altroieri['linkedin_shares']=0; $altroieri['pinterest_shares']=0; $altroieri['vkontakte_shares'];
	} else {
		$altroieri['tot'] = $altroieri['facebook_shares'] + $altroieri['twitter_shares']  + $altroieri['google_shares'] +
			$altroieri['linkedin_shares'] + $altroieri['pinterest_shares'] + $altroieri['vkontakte_shares'];
	}

	return array("ieri"=>$ieri,"oggi"=>$oggi,"altroieri"=>$altroieri);

}
// Extract Top Stories for Admin panel, with stats
function top_stories_getStats($days=7,$howmany=6,$placeholder="", $ptArr = null) {
	global $wpdb;

	// post types filtering
	if(!$ptArr) $ptStr = "'page','post','attachment'"; else $ptStr = "'".implode("','",$ptArr)."'";

	// check for cached
	$transient_key=sha1($days." ".$howmany." ".$ptStr);

	$ret=get_transient( $transient_key );

	if ($ret !== false ) return $ret;


	$data = date("Y-m-d",strtotime("-{$days} days",strtotime( date("Y-m-d") )));
	 $querystr = "
		SEL"."ECT ID as post_id,
			(SE"."LECT facebook_shares+twitter_shares+google_shares+linkedin_shares+pinterest_shares+vkontakte_shares FR"."O"."M ".$wpdb->prefix."top_stories 
				WHERE id_post=ID ORDER BY dt_day DESC LIMIT 0,1) as a,
			post_title,post_content
		F"."RO"."M ".$wpdb->prefix."posts 
		WHERE (post_date > '".$data."') and post_status='publish'
		AND post_type in ($ptStr)
		AND (SE"."LE"."CT facebook_shares+twitter_shares+google_shares+linkedin_shares+pinterest_shares+vkontakte_shares F"."R"."OM ".$wpdb->prefix."top_stories 
				WHERE id_post=ID ORDER BY dt_day DESC LIMIT 0,1)>0
		ORDER BY a DESC
		LIMIT 0,".$howmany."
		";
	$pageposts = $wpdb->get_results($querystr, OBJECT);

	$out = ""; 
	if ($pageposts) {
		foreach ($pageposts as $post) {

			/*
				Find post Image
			*/
			$pic = top_stories_get_pic($post,$placeholder);

			$growth = top_stories_get_days($post->post_id);
			$oggi = $growth["oggi"];
			$ieri = $growth["ieri"];
			$altroieri = $growth["altroieri"];
			
			$deltaTot = $oggi['tot'] - $ieri['tot'];

			$deltaLike = $deltaTot - ($ieri['tot'] - $altroieri['tot']);
					//     fatti oggi   -   fatti ieri

			$icon = $icon2="";
			if($deltaTot > 1000) $icon = " fire1000";
				elseif($deltaTot > 100) $icon = " fire100";
				elseif($deltaTot > 50) $icon = " fire50";
			if($deltaLike>0) {$icon2="sale"; $textlike=", +$deltaLike more than yesterday";}
				elseif($deltaLike<0) {$icon2="scende"; $textlike=", ".number_format($deltaLike,0,'.',',')." less than yesterday";}

			$deltaFb = $oggi['facebook_shares'] - $ieri['facebook_shares'];
			$deltaTw = $oggi['twitter_shares'] - $ieri['twitter_shares'];
			$deltaGo = $oggi['google_shares'] - $ieri['google_shares'];
			$deltaPi = $oggi['pinterest_shares'] - $ieri['pinterest_shares'];
			$deltaLi = $oggi['linkedin_shares'] - $ieri['linkedin_shares'];
			$deltaVk = $oggi['vkontakte_shares'] - $ieri['vkontakte_shares'];

			$out.="<li><a href='".site_url()."/?p=".$post->post_id."' target='_blank'>";
			$out.= $pic;
			$out.="<span class='tit back'>".$post->post_title."</span> ";

			/*$out.="<span class='stats{$icon}'>".
					("<b class='{$icon2}' title='".__("Today","top-stories-plugin")." ".
					number_format($deltaFb,0,'.',',')." fcb, ".
					number_format($deltaTw,0,'.',',')." twi, ".
					number_format($deltaGo,0,'.',',')." g+, ".
					number_format($deltaLi,0,'.',',')." lnk, ".
					number_format($deltaPi,0,'.',',')." pin, ".
					number_format($deltaVk,0,'.',',')." vk".
				"'>".($deltaTot>0?"+":"").number_format($deltaTot,0,'.',',')."</b>")
					."</span>";*/


			$alt = sprintf( __('Yesterday %1$s, today %2$s', 'top-stories-plugin' ), 
				number_format(($ieri['tot'] - $altroieri['tot']),0,'.',','), 
				number_format($deltaTot,0,'.',',') );

			$out.="<span class='stats{$icon}'>".
					("<b class='{$icon2}' title=\"".$alt."\">".($deltaTot>0?"+":"").number_format($deltaTot,0,'.',',')."</b>")
					."</span>";


			$q = $post->a < 1000 ? $post->a : number_format($post->a / 1000 ,1) ."k";
			$out.="<span class='num'>{$q}</span> ";
			$out.="</a><a href='#' rel='".plugin_dir_url( __FILE__ )."chart.php?d=".($days>365 ? 14 : $days)."&p=".$post->post_id."' class='stat'>c</a></li>";
				

		}
	} else {
		$out.="<li>".__("Sorry, can't find nothing in the selected period.","top-stories-plugin")."</li>";
	}


	 $querystr = "
 		SE"."LE"."C"."T ID as post_id,x.dt_day,(x.facebook_shares+x.twitter_shares+x.google_shares+x.linkedin_shares+x.pinterest_shares) as a,
			(x.facebook_shares - x.facebook_shares_start +x.twitter_shares - x.twitter_shares_start +x.google_shares - x.google_shares_start + x.linkedin_shares - x.linkedin_shares_start + x.pinterest_shares - x.pinterest_shares_start + x.vkontakte_shares - x.vkontakte_shares_start) as d
			,post_title,post_content
		 FRO"."M ".$wpdb->prefix."top_stories x inner join ".$wpdb->prefix."posts on ID=id_post AND dt_day='".date("Y-m-d")."'
		 WHE"."RE (x.facebook_shares - x.facebook_shares_start +x.twitter_shares - x.twitter_shares_start +x.google_shares - x.google_shares_start+ x.linkedin_shares - x.linkedin_shares_start + x.pinterest_shares - x.pinterest_shares_start+ x.vkontakte_shares - x.vkontakte_shares_start) >0 AND post_status='publish'
		AND post_type in ($ptStr)
		ORDER BY d DESC
		LIMIT 0,".$howmany."
		";
		//echo $querystr;
	$pageposts = $wpdb->get_results($querystr, OBJECT);

	$out2 = ""; 
	if ($pageposts) {
		foreach ($pageposts as $post) {

			/*
				Find post Image
			*/
			$pic = top_stories_get_pic($post,$placeholder);
			$growth = top_stories_get_days($post->post_id);
			$oggi = $growth["oggi"];
			$ieri = $growth["ieri"];
			$altroieri = $growth["altroieri"];

			$deltaTot = $oggi['tot'] - $ieri['tot'];

			$deltaLike = $deltaTot - ($ieri['tot'] - $altroieri['tot']);
					//     fatti oggi   -   fatti ieri

			$icon = $icon2="";
			if($deltaTot > 1000) $icon = " fire1000";
				elseif($deltaTot > 100) $icon = " fire100";
				elseif($deltaTot > 50) $icon = " fire50";
			if($deltaLike>0) {$icon2="sale"; $textlike=", +$deltaLike more than yesterday";}
				elseif($deltaLike<0) {$icon2="scende"; $textlike=", ".number_format($deltaLike,0,'.',',')." less than yesterday";}

			$deltaFb = $oggi['facebook_shares'] - $ieri['facebook_shares'];
			$deltaTw = $oggi['twitter_shares'] - $ieri['twitter_shares'];
			$deltaGo = $oggi['google_shares'] - $ieri['google_shares'];
			$deltaPi = $oggi['pinterest_shares'] - $ieri['pinterest_shares'];
			$deltaLi = $oggi['linkedin_shares'] - $ieri['linkedin_shares'];
			$deltaVk = $oggi['vkontakte_shares'] - $ieri['vkontakte_shares'];

			$out2.="<li><a href='".site_url()."/?p=".$post->post_id."' target='_blank'>";
			$out2.= $pic;
			$out2.="<span class='tit back'>".$post->post_title."</span> ";

			$alt = sprintf( __('Yesterday %1$s, today %2$s', 'top-stories-plugin' ), 
				number_format(($ieri['tot'] - $altroieri['tot']),0,'.',','), 
				number_format($deltaTot,0,'.',',') );

			$out2.="<span class='stats{$icon}'>".
					("<b class='{$icon2}' title=\"".$alt."\">".($deltaTot>0?"+":"").number_format($deltaTot,0,'.',',')."</b>")
					."</span>";

			$q = $post->a < 1000 ? $post->a : number_format($post->a / 1000 ,1) ."k";
			$out2.="<span class='num'>{$q}</span> ";
			$out2.="</a><a href='#' rel='".plugin_dir_url( __FILE__ )."chart.php?d=".$days."&p=".$post->post_id."' class='stat'>c</a></li>";
				

		}
	} else {
		$out2.="<li>".__("Can't find any activity of your posts on social networks today.","top-stories-plugin")."</li>";

	}


	$querystr="SE"."LE"."C"."T ".$wpdb->prefix."users.ID,user_login,user_nicename,
				SUM((S"."E"."LEC"."T facebook_shares+twitter_shares+google_shares+linkedin_shares+pinterest_shares+vkontakte_shares
				F"."ROM ".$wpdb->prefix."top_stories 
					WHERE id_post=".$wpdb->prefix."posts.ID ORDER BY dt_day DESC LIMIT 0,1)) as a,
				display_name
			F"."ROM ".$wpdb->prefix."posts 
	INNER JOIN ".$wpdb->prefix."users ON ".$wpdb->prefix."users.ID=post_author
			WHERE (post_date > '".$data."') AND post_status='publish'
	GROUP BY display_name
	HAVING a >0 
			ORDER BY a DESC
			LIMIT 0,10";
	$userposts = $wpdb->get_results($querystr, OBJECT);
	$out3= "";
	
	if ($userposts) {
		foreach ($userposts as $user) {
			$out3.="<li><a target='_balnk' href='/author/".$user->user_nicename."/'>".$user->display_name."</a> <b>".number_format($user->a,0,'.',',')."</b></li>";
		}
	} else {
		$out3.="<li>".__("Nobody<br/>Sorry, can't find any powerful author.","top-stories-plugin")."</li>";
	}



	$querystr="SEL"."ECT ".$wpdb->prefix."users.ID,user_login,user_nicename,
				SUM((SEL"."ECT facebook_shares+twitter_shares+google_shares+pinterest_shares+linkedin_shares+vkontakte_shares - facebook_shares_start-twitter_shares_start-google_shares_start-pinterest_shares_start-linkedin_shares_start
				-vkontakte_shares_start
				FRO"."M ".$wpdb->prefix."top_stories 
					WHERE id_post=".$wpdb->prefix."posts.ID AND dt_day='".date("Y-m-d")."' LIMIT 0,1)) as a,
				display_name
			F"."ROM ".$wpdb->prefix."posts 
	INNER JOIN ".$wpdb->prefix."users ON ".$wpdb->prefix."users.ID=post_author
	WHERE post_status='publish'
	GROUP BY display_name
	HAVING a >0 
			ORDER BY a DESC
			LIMIT 0,10";
	$userposts = $wpdb->get_results($querystr, OBJECT);
	$out4= "";
	
	if ($userposts) {
		foreach ($userposts as $user) {
			$out4.="<li><a target='_balnk' href='/author/".$user->user_nicename."/'>".$user->display_name."</a> <b>".number_format($user->a,0,'.',',')."</b></li>";
		}
	} else {
		$out4.="<li>".__("Nobody<br/>...Can't find any powerful author today.","top-stories-plugin")."</li>";
	}

	$title = __( $days==20000 ? "Top stories stats ever" : 'Top stories on %1$s days' , "top-stories-plugin");
	$title = str_replace('%1$s',$days,$title);

	$authtitle = __( $days==20000 ? "Most powerful authors ever" : 'Most powerful authors on %1$s days' , "top-stories-plugin");
	$authtitle = str_replace('%1$s',$days,$authtitle);
	
	$out = "<div class='statblock'><h3>".$title."</h3><ul class='top-stories'>{$out}</ul></div>".
		"<div class='statblock last'><h3>".__("Most viral today","top-stories-plugin")."</h3><ul class='top-stories'>{$out2}</ul></div><br style='clear:both'/>".
		"<div class='statblock'><h3>".$authtitle."</h3><ol>{$out3}</ol></div>
		<div class='statblock last'><h3>".__("Most powerful authors today","top-stories-plugin")."</h3><ol>{$out4}</ol></div>
	<br style='clear:both'/><div id='chartpost'><iframe></iframe><a href='#'>X</a></div>";

	set_transient( $transient_key,$out,60 * 15); // quarto d'ora di cache

	return $out;
}
if(!function_exists("execute_row")) {
	function execute_row($sql) {
		global $wpdb;
		$sql = trim($sql);if(!preg_match("/(limit +0,1)$/i",$sql)) $sql.=" limit 0,1"; 
		$r = "";
		$rs = $wpdb->get_results($sql, ARRAY_A);
		if($rs) foreach ($rs as $r) return $r; else return $r;
	}
}






//---------------------------------------------
// Register Top Stories Widget
//---------------------------------------------
add_action('widgets_init', create_function('', 'return register_widget("top_stories_widget");'));
























add_action( 'wp_ajax_nopriv_save_data_sn', 'save_data_sn_callback' );

function save_data_sn_callback() {
	ob_clean();
	global $wpdb; // this is how you get access to the database

	$debug = false;

	if($debug) print_r($_POST);

	$id = isset($_POST['id']) ? (integer)$_POST['id'] : null;
	if($id) {
		$options = wp_parse_args(get_option('top_stories_settings'), top_stories_get_defaults());
		$force_data = isset($_POST['force']) ? $_POST['force'] : "";
		if($force_data) {
			$date = date_parse($force_data);
			if (checkdate($date["month"], $date["day"], $date["year"])) {
				$force_data = $date["year"]."-".str_pad($date["month"],2,"0",STR_PAD_LEFT)."-". str_pad($date["day"],2,"0",STR_PAD_LEFT);
			} else {
				$force_data = "";
			}
		}
		$shares = isset($_POST['shares']) ? (integer)$_POST['shares'] : 0;
		$tweet = isset($_POST['tweet']) ? (integer)$_POST['tweet'] : 0;
		$google = isset($_POST['google']) ? (integer)$_POST['google'] : 0;
		$linkedin = isset($_POST['linkedin']) ? (integer)$_POST['linkedin'] : 0;
		$pinterest = isset($_POST['pinterest']) ? (integer)$_POST['pinterest'] : 0;
		$vkontakte = isset($_POST['vk']) ? (integer)$_POST['vk'] : 0;

		if($shares>0 || $tweet>0 || $pinterest>0 || $linkedin>0 || $google>0 || $vkontakte>0 || $force_data) {

			$d = date("Y-m-d", strtotime(current_time( 'mysql', true)));

			/*
				daily counters are changed, modifiy historical table
			*/
			$rs = $wpdb->get_results("select * from ".$wpdb->prefix."top_stories where id_post='".$id."' and dt_day<='".$d."' order by dt_day desc limit 0,2",ARRAY_A);
			$check = ""; $ieri = array('facebook_shares'=>0,'facebook_shares'=>0,'google_shares'=>0,'linkedin_shares'=>0,'pinterest_shares'=>0,'vkontakte_shares'=>0);
			foreach($rs as $r) if($r["dt_day"]==$d) $check = $r; else {$ieri = $r;break;}

			$change = false;
			if(is_array($check)) {
				if($debug) echo  "need update?\n";
				// update
				if($shares>$check['facebook_shares'] || $tweet>$check['twitter_shares'] || $google>$check['google_shares']  
					|| $linkedin>$check['linkedin_shares']
					|| $pinterest>$check['pinterest_shares']
					|| $vkontakte>$check['vkontakte_shares']
				) {
					if($debug) echo "yes\n";

					$wpdb->query($sql = "update ".$wpdb->prefix."top_stories set facebook_shares='".$shares."',twitter_shares='".$tweet."',google_shares='".$google."',pinterest_shares='".$pinterest."',linkedin_shares='".$linkedin."',vkontakte_shares='".$vkontakte."' where id_post='".$id."' and dt_day='".$d."'");
					$change = true;
					if($debug) echo $sql."\n";
				}
				

			} else {
				// if it is the first call of today and it's a post published
				// before this plugin activation, write the same data at the
				// force_data record date.

				if($force_data) {
					if($debug) echo  "need force data\n";
					// insert
					$wpdb->query($sql = "insert ignore into ".$wpdb->prefix."top_stories (id_post,dt_day,facebook_shares,twitter_shares,google_shares,linkedin_shares,pinterest_shares,vkontakte_shares,facebook_shares_start,twitter_shares_start,google_shares_start,linkedin_shares_start,pinterest_shares_start,vkontakte_shares_start) values (
						'$id','{$force_data}','".$shares."','".$tweet."','".$google."','".$linkedin."','".$pinterest."','".$vkontakte."','0','0','0','0','0','0')");
					$ieri['facebook_shares']=$shares;
					$ieri['twitter_shares']=$tweet;
					$ieri['google_shares']=$google;
					$ieri['linkedin_shares']=$linkedin;
					$ieri['pinterest_shares']=$pinterest;
					$ieri['vkontakte_shares']=$vkontakte;
					if($debug) echo $sql."\n";
				}

				// insert
				$wpdb->query($sql = "insert ignore into ".$wpdb->prefix."top_stories (id_post,dt_day,facebook_shares,twitter_shares,google_shares,linkedin_shares,pinterest_shares,vkontakte_shares,facebook_shares_start,twitter_shares_start,google_shares_start,linkedin_shares_start,pinterest_shares_start,vkontakte_shares_start) values (
					'$id','".$d."','".$shares."','".$tweet."','".$google."','".$linkedin."','".$pinterest."','".$vkontakte."','".$ieri['facebook_shares']."','".$ieri['twitter_shares']."','".$ieri['google_shares']."','".$ieri['linkedin_shares']."','".$ieri['pinterest_shares']."','".$ieri['vkontakte_shares']."')");
				$change = true;
				if($debug) echo $sql."\n";
			}

			if($change && $options['top_stories_save_custom']) {
				if($debug) echo "save custom fields\n";
				$fb = update_post_meta($id, "facebook_shares", $shares);
				$tw = update_post_meta($id,"twitter_shares",$tweet);
				$go = update_post_meta($id,"google_shares",$google);
				$li = update_post_meta($id,"linkedin_shares",$linkedin);
				$pi = update_post_meta($id,"pinterest_shares",$pinterest);
				$vk = update_post_meta($id,"vkontakte_shares",$vkontakte);

				if($fb || $tw || $go || $li || $pi || $vk) {
					$user = execute_row("SELECT post_author FROM ".$wpdb->prefix."posts WHERE ID=".$id);
					if(isset($user["post_author"]) && $user["post_author"]>0) {
						$sum=execute_row("SELECT SUM((SELECT facebook_shares+twitter_shares+google_shares+linkedin_shares+pinterest_shares+vkontakte_shares 
							FROM ".$wpdb->prefix."top_stories 
							WHERE id_post=".$wpdb->prefix."posts.ID ORDER BY dt_day DESC LIMIT 0,1)) as a
							FROM ".$wpdb->prefix."posts 
							WHERE post_author='".$user["post_author"]."' AND
							post_status='publish'");
						if(isset($sum['a'])) {
							update_user_meta( $user["post_author"], 'top_stories_count', $sum['a'] );
						}
					}
				}
			}

		}

	}

	
	die(); // this is required to terminate immediately and return a proper response
}




?>