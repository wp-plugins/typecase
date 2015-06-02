<?php
/*
Plugin Name: Typecase
Plugin URI: http://wordpress.org/plugins/typecase/
Description: A plugin that makes it dead simple to add custom webfonts to your website.
Version: 1.1.2
Author: UpThemes
Author URI: https://upthemes.com
License: GPL2
*/

// don't call the file directly
if ( !defined( 'ABSPATH' ) )
	return;

$typecase_file = __FILE__;

if (isset($plugin)) {
	$typecase_file = $plugin;
}
else if (isset($mu_plugin)) {
	$typecase_file = $mu_plugin;
}
else if (isset($network_plugin)) {
	$typecase_file = $network_plugin;
}

define('TYPECASE_FILE', $typecase_file);
define('TYPECASE_PATH', WP_PLUGIN_DIR.'/'.basename(dirname($typecase_file)));

/**
 * Typecase class
 *
 * @class Typecase	The class that holds the entire Typecase plugin
 */
class Typecase {

	/**
	 * @var $name				Variable for Typecase used throughout the plugin
	 */
	protected $name = "Typecase";

	/**
	 * @var $api_url		Our google web font URL
	 */
	protected $api_url = "//fonts.googleapis.com/css?family=";

	/**
	 * @var $nonce_key	A security key used internally by the plugin
	 */
	protected $nonce_key = '+Y|*Ec/-\s3';

	/**
	 * Constructor for the Typecase class
	 *
	 * Sets up all the appropriate hooks and actions
	 * within our plugin.
	 *
	 * @uses register_activation_hook()
	 * @uses register_deactivation_hook()
	 * @uses is_admin()
	 * @uses add_action()
	 *
	 */
	public function __construct() {
		register_activation_hook( TYPECASE_FILE, array(&$this, 'activate' ) );
		register_deactivation_hook( TYPECASE_FILE, array(&$this, 'deactivate' ) );

		add_action('init',array(&$this,'localization_setup'));

		if ( is_admin() ){
			add_action('admin_menu',array(&$this,'admin_menu'));
			add_action('wp_ajax_saveFonts',array(&$this,'ajax_save_fonts'));
			add_action('wp_ajax_getFonts',array(&$this,'ajax_get_fonts'));
			add_action('wp_ajax_clear_firsttimer',array(&$this,'ajax_clear_firsttimer'));
		}else{
			add_action('wp_head',array(&$this,'display_frontend'));
		}

		add_action('after_setup_theme', array($this, 'default_theme_support'), -1 );

		add_action('after_setup_theme', array($this, 'customizer_init') );



		add_action('wp_ajax_reloadFontPreview',array(&$this,'ajax_reload_font_preview'));
		add_action('init',array(&$this,'set_pro_filters'),1);

		if( isset($_GET['front_end_editor']) && !is_admin() ){
			remove_action('wp_head',array(&$this,'display_frontend'));
			add_action('init',array(&$this,'set_front_end_filters'),1);
			add_action('init',array(&$this,'admin_styles'));
			add_action('wp_head',array(&$this,'frontend_js_globals'));
			add_action('wp_enqueue_scripts', array(&$this,'frontend_scripts'));
			add_action('wp_footer',array(&$this,'ui'));
		}
	}

	/**
	 * AJAX function for saving fonts
	 *
	 *
	 * @uses Typecase::verify_nonce()
	 * @uses update_option()
	 * @uses json_encode()
	 * @uses header()
	 * @return JSON object with fonts and font metadata
	 *
	 */
	public function ajax_save_fonts(){

		$nonce_verify = $this->verify_nonce($_REQUEST['_nonce']);

		// get the submitted parameters
		$fonts = $_REQUEST['json'];

		$verified = $nonce_verify ? true : false;

		$fonts = update_option('typecase_fonts',$fonts);

		$new_nonce = array( 'nonce' => wp_create_nonce($this->nonce_key) );

		$response = json_encode( array( '_new_nonce' => $new_nonce, 'success' => $verified, 'fonts' => $fonts ) );

		header( "Content-Type: application/json" );
		echo $response;

		exit;

	}

	/**
	 * AJAX function for retrieving fonts
	 *
	 *
	 * @uses Typecase::verify_nonce()
	 * @uses get_option()
	 * @uses json_encode()
	 * @uses header()
	 * @return JSON object with fonts and font metadata
	 *
	 */
	public function ajax_get_fonts(){

		$this->verify_nonce($_GET['_nonce']);

		$fonts = get_option('typecase_fonts');

		$gotten = $fonts ? true : false;

		$new_nonce = array( 'nonce' => wp_create_nonce($this->nonce_key) );

		$response = json_encode( array( '_new_nonce' => $new_nonce, 'success' => $gotten, 'fonts' => $fonts ) );

		header( "Content-Type: application/json" );
		echo $response;

		exit;

	}

	/**
	 * AJAX function to remove yellow instructional box
	 *
	 * @uses Typecase::verify_nonce()
	 * @uses update_option()
	 * @uses json_encode()
	 * @uses header()
	 * @return JSON object with fonts and font metadata
	 *
	 */
	public function ajax_clear_firsttimer(){

		$this->verify_nonce($_GET['_nonce']);

		$firsttimer_update = update_option('typecase_firsttimer','disabled');

		$new_nonce = array( 'nonce' => wp_create_nonce($this->nonce_key) );

		$response = json_encode( array( '_new_nonce' => $new_nonce, 'success' => $firsttimer_update ) );

		header( "Content-Type: application/json" );
		echo $response;

		exit;

	}

	/**
	 * Placeholder for activation function
	 *
	 * Nothing being called here yet.
	 */
	public function activate() {}

	/**
	 * Placeholder for deactivation function
	 *
	 * Nothing being called here yet.
	 */
	public function deactivate() {}

	/**
	 * Initialize plugin for localization
	 *
	 * @uses load_plugin_textdomain()
	 *
	 */
	public function localization_setup() {
		load_plugin_textdomain( 'typecase', false, dirname( plugin_basename( TYPECASE_FILE ) ) . '/languages/' );
	}

	/**
	 * Initialize admin menu
	 *
	 * @uses Typecase::load_menu()
	 *
	 */
	public function admin_menu() {
		$this->load_menu();
	}

	/**
	 * Initialize admin menu
	 *
	 * @uses Typecase::load_menu()
	 *
	 */
	public function load_menu() {
		$hook = add_menu_page( $this->name, $this->name, 'manage_options', 'typecase', array(&$this, 'ui' ), plugins_url( 'images/ico_typecase.png', TYPECASE_FILE ) );
		add_action( 'admin_print_styles-' . $hook, array($this,'admin_styles'));
	}

	/**
	 * Enqueue admin styles and scripts
	 *
	 * Also creates a nonce for saving font data.
	 *
	 * @uses current_user_can()
	 * @uses wp_create_nonce()
	 * @uses wp_enqueue_script()
	 * @uses wp_enqueue_style()
	 * @uses wp_localize_script()
	 *
	 */
	public function admin_styles() {
		if ( !current_user_can( 'manage_options' ) )
			return;

		$nonce = array(  );

		wp_enqueue_script('json2');
		wp_enqueue_script('selectivizr', plugins_url( 'scripts/selectivizr-min.js', TYPECASE_FILE ), array('json2'), date( 'Ymd' ) );
		wp_enqueue_script('google-api', 'https://www.google.com/jsapi', array('selectivizr'), date( 'Ymd' ) );
		wp_enqueue_script('typecase', plugins_url( 'scripts/main.js', TYPECASE_FILE ), array('jquery','google-api'), date( 'Ymd' ) );
		wp_enqueue_style('typecase', plugins_url( 'styles/main.css', TYPECASE_FILE ), false, date( 'Ymd' ) );
		wp_enqueue_style('journal-font', plugins_url( 'fonts/journal/journal.css', TYPECASE_FILE ), false, date( 'Ymd' ) );

		wp_localize_script( 'typecase', 'typecase', array( 'nonce' => wp_create_nonce($this->nonce_key), 'loading_gif' => plugins_url( 'images/loading.gif', TYPECASE_FILE ) ) );
	}

	/**
	 * Creates the user interface markup
	 *
	 * Also contains strings that are being localized.
	 *
	 * @uses __()
	 * @uses get_option()
	 * @uses apply_filters()
	 *
	 */
	public function ui(){

		if( !current_user_can('manage_options') )
			return;

		$title 					= __('Typecase','typecase');
		$tagline 				= __('Beautiful web fonts for WordPress','typecase');
		$collection 			= __('Your Collection','typecase');
		$nofonts 				= __('You have no fonts in your collection!','typecase');
		$addfonts 				= sprintf( __('Browse the "Available Fonts" below and click %s to add fonts here.','typecase'), '<span class="add"><span></span></span>' );
		$selectors				= __('Selectors','typecase');
		$newselector			= __('New Selector...','typecase');
		$includedvariants 		= __('Included Variants','typecase');
		$charactersets		 	= __('Character Sets','typecase');
		$availablefonts			= __('Available Fonts','typecase');
		$noresultstitle			= __('No Results!','typecase');
		$searchgoogle			= __('Search Google Webfonts','typecase');
		$noresultsdesc			= __('There are no fonts with that name in the Google Font Library.','typecase');
		$showmorefonts			= __('Load More Fonts','typecase');
		$copyright				= sprintf( __("Copyright %s <a href=\"http://upthemes.com\">UpThemes</a>. All Rights Reserved.","typecase"), date('Y') );
		$welcome				= __("Welcome! Here’s the rundown on using typecase.","typecase");
		$step_1					= __("Browse or search for fonts in the \"Available Fonts\" box","typecase");
		$step_2					= sprintf(__("Click the %s icon to add the font to \"Your Collection\"","typecase"),'<span class="add"><span></span></span>');
		$step_3					= __("Add CSS selectors and font weights in the right column","typecase");
		$hidehelp_btn			= __("Close &amp; Don't Show Again","typecase");

		$firsttimer = '';

		if( isset($_GET['front_end_editor']) || get_option('typecase_firsttimer') != 'disabled' ){

			$firsttimer = '
			<div id="firsttimer">
				<h1>' . $welcome . '</h1>
				<ol>
					<li><span class="list-item"></span><strong>' . $step_1 . '</strong></li>
					<li><span class="list-item"></span><strong>' . $step_2 . '</strong></li>
					<li><span class="list-item"></span><strong>' . $step_3 . '</strong></li>
				</ol>
				<div class="buttons">
					<a id="kill" class="typecase-btn primary large" href="">' . $hidehelp_btn . '</a>
				</div>
			</div>';

		}

		$classname = '';
		$front_end_editor_ui = '';
		$buttons = '<div class="buttons"><span>' . __("live front-end editor &amp; lifetime support","typecase") . '</span> <a class="typecase-btn primary" href="http://upthemes.com/plugins/typecase/" target="_blank">' . __("Upgrade to Pro","typecase") . '</a></div>';

		$classname = apply_filters('typecase-classname',$classname);
		$buttons = apply_filters('typecase-buttons',$buttons);
		$front_end_editor = apply_filters('typecase-front-end-editor',$front_end_editor_ui);

		echo <<<EOT
		<div id="typecase"$classname>
			<header id="masthead">
				<h1>
					<strong>$title</strong>
					<span>$tagline</span>
					$buttons
				</h1>
			</header>
			$firsttimer
			$front_end_editor
			<div id="your-collection-wrap">
			<iframe></iframe>
		  <div id="your-collection">
		    <header>
		      <h1>$collection</h1>
		    </header><!--/header-->
		    <div class="content-wrap">
		      <div class="no-fonts">
		      	<div class="no-fonts-content">
			        <h2>$nofonts</h2>
			        <h4>$addfonts</h4>
		        </div><!--/.no-fonts-content-->
		      </div><!--/.no-fonts-->
		      <div class="font-list-wrap">
		        <div class="font-list">
		        </div><!--/.font-list-->
		      </div><!--/.font-list-wrap-->
		      <div class="sidebar">
		        <h2>$selectors</h2>
		        <ul id="selectors">
		          <li class="add-new">
		            <form id="new-selector-form">
		              <input type="text" name="new-selector" id="new-selector" placeholder="$newselector"/>
		              <input type="submit" id="submit-selector" value=""/>
		            </form>
		          </li>
		          <span class="new-selector-width"></span>
		        </ul><!--/#selectors-->
		        <div class="clear"></div>
		        <h2>$includedvariants</h2>
		        <form id="variants-form"></form>
		        <h2>$charactersets</h2>
		        <form id="subsets-form"></form>
		        <button id="save-fonts" class="typecase-btn primary">Save Fonts</button>
		      </div><!--/.sidebar-->
		      <div class="clear"></div>
		    </div><!--/.content-wrap-->
		  </div><!--/#your-collection-->
		  <span class="arrow-down"></span>
		  </div><!--/#your-collection-wrap-->
		  <div id="available-fonts-wrap">
		  <iframe></iframe>
		  <div id="available-fonts">
		    <header>
		      <h1>$availablefonts</h1>
		    </header><!--/header-->
		    <form id="search">
		      <input type="text" name="search-input" id="search-input" placeholder="$searchgoogle"/>
		      <input type="submit" id="search-submit" value=""/>
		    </form><!--/#search-->
		    <div class="content-wrap">
		      <div class="font-list-wrap">
		        <div class="no-results">
		        	<div class="no-results-content">
			          <h2>$noresultstitle</h2>
			          <h4>$noresultsdesc</h4>
		         	</div><!--/.no-results-content-->
		        </div><!--/.no-results-->
		        <div class="font-list" id="loaded-fonts">
		        </div><!--/.font-list#loaded-fonts-->
		        <div class="font-list" id="search-results">
		        </div><!--/.font-list#search-results-->
		      </div><!--/.font-list-wrap-->
		    </div><!--/.content-wrap-->
		    <a id="more-fonts" class="typecase-btn primary" href=""><span>$showmorefonts</span></a>
		  </div><!--/#available-fonts-->
		  <span class="arrow-down"></span>
		  </div><!--/#available-fonts-wrap-->
		</div><!--/#typecase-->
EOT;
	}

	/**
	 * Displays CSS on the front-end for web fonts
	 *
	 * The font data array is parsed and we load in the required
	 * weights, variants, and characters sets.
	 *
	 * @uses get_option()
	 * @uses get_option()
	 * @uses apply_filters()
	 *
	 * @todo Parse and load variants properly.
	 * @todo Parse and load charsets at all.
	 *
	 */
	public function display_frontend(){
		$fonts = get_option('typecase_fonts');

		if( $fonts[0] ){

			$apiUrl = &$this->api_url;
			$import_url = '';
			$font_styles = '';

			foreach($fonts as $font){

				$family = explode("|",$font[0]);
				$family = $family[0];
				$selectors = substr( $font[1], 1);
				$weights = substr( $font[2], 1);
				$charsets = substr( $font[3], 1);

				if( $import_url != '' )
					$import_url .= '|';

				$import_url .= str_replace(" ","+",$family).$this->stringify_font_part($weights).$this->stringify_font_part($charsets);

				$selectors = explode("|",$selectors);

				foreach( $selectors as $i => $selector){
					if($i>0)
						$font_styles .= ",";
					$font_styles .= $selector;
				}

				$font_styles .= "{ font-family: \"$family\"; }\n";

			}

			$import_fonts = "@import url($apiUrl$import_url);\n";

echo "\n<style type=\"text/css\">\n";
echo $import_fonts;
echo $font_styles;
echo "</style>\n";
echo "<!--==-- End Typecase Font Declarations --==-->\n\n";

		}
	}

	protected function stringify_font_part($parts){

		$parts = explode("|",$parts);
		$string = '';

		foreach( $parts as $i => $part ){

			if( $i == 0 ){
				$count = 0;
			}

			// split font weight into pairs
			$part = explode('&',$part);

			// assign
			$part_id = $part[0];
			$part_status = $part[1];

			if( $part_status ){
				$count++;

				if( $count == 1 ){
					$string .= ":".$part_id;
				} else {
					$string .= ",".$part_id;
				}

			}

		}

		return $string;

	}

	/**
	 * Verify nonces against our $nonce_key var
	 *
	 * @uses esc_attr()
	 * @uses wp_verify_nonce()
	 *
	 */
	protected function verify_nonce($nonce){
		$nonce = esc_attr($nonce);
		if(! wp_verify_nonce($nonce, $this->nonce_key))
			die('Security check failed.');
		else
			return true;
	}

	/**
	 * Load customizer if theme support for typcase and file exist
	 *
	 * @uses require_if_theme_supports
	 *
	 */
	public function customizer_init(){
		$customizer_file = dirname( TYPECASE_FILE ) . '/customizer.php';

		if( file_exists( $customizer_file ) ){
			require_if_theme_supports( 'typecase', $customizer_file );
		}

	}

	/**
	 * Detect if a default theme is used
	 *
	 * @uses require_if_theme_supports
	 *
	 * @return str, false returns theme directory name if default theme is active, otherwise return false
	 *
	 */
	public function default_theme_support(){

		// list default themes
		$default_themes = array(
			'twentyten',
			'twentyeleven',
			'twentytwelve',
			'twentythirteen',
			'twentyfourteen',
			'twentyfifteen',
		);

		// get the active theme
		$active_theme = wp_get_theme();

		// only need the slug
		$active_theme = $active_theme->template;

		// bail if the active theme is not a default theme
		if( !in_array( $active_theme, $default_themes ) ){
			return;
		}

		$font_locations = array();

		switch( $active_theme ):

			case 'twentyten':

				$font_locations = array(array(
						'label' => 'Body Copy',
						'selector' => 'body',
						'default' => 'Georgia, "Bitstream Charter", serif',
					),
					array(
						'label' => 'Headings (H1-H6)',
						'selector' => 'h1, h2, h3, h4, h5, h6',
						'default' => 'Georgia, "Bitstream Charter", serif',
					),
					array(
						'label' => 'Article Title',
						'selector' => 'h1.entry-title',
						'default' => '"Helvetica Neue", Arial, Helvetica, "Nimbus Sans L", sans-serif',
					),
					array(
						'label' => 'Block Quote',
						'selector' => 'blockquote',
						'default' => 'Georgia, "Bitstream Charter", serif',
					),
					array(
						'label' => 'List Items (Bulleted and Numbered)',
						'selector' => 'ul, ol',
						'default' => 'Georgia, "Bitstream Charter", serif',
					),
				);

				break;

			case 'twentyeleven':

				$font_locations = array(
					array(
						'label' => 'Body Copy',
						'selector' => 'body',
						'default' => '"Helvetica Neue", Helvetica, Arial, sans-serif',
					),
					array(
						'label' => 'Headings (H1-H6)',
						'selector' => 'h1, h2, h3, h4, h5, h6',
						'default' => '"Helvetica Neue", Helvetica, Arial, sans-serif',
					),
					array(
						'label' => 'Article Title',
						'selector' => 'h1.entry-title',
						'default' => '"Helvetica Neue", Helvetica, Arial, sans-serif',
					),
					array(
						'label' => 'Block Quote',
						'selector' => 'blockquote',
						'default' => '"Helvetica Neue", Helvetica, Arial, sans-serif',
					),
					array(
						'label' => 'List Items (Bulleted and Numbered)',
						'selector' => 'ul, ol',
						'default' => '"Helvetica Neue", Helvetica, Arial, sans-serif',
					),
				);

				break;

			case 'twentytwelve':

				$font_locations = array(
					array(
						'label' => 'Body Copy',
						'selector' => 'body',
						'default' => '"Open Sans", Helvetica, Arial, sans-serif',
					),
					array(
						'label' => 'Headings (H1-H6)',
						'selector' => 'h1, h2, h3, h4, h5, h6',
						'default' => '"Open Sans", Helvetica, Arial, sans-serif',
					),
					array(
						'label' => 'Article Title',
						'selector' => 'h1.entry-title',
						'default' => '"Open Sans", Helvetica, Arial, sans-serif',
					),
					array(
						'label' => 'Block Quote',
						'selector' => 'blockquote',
						'default' => '"Open Sans", Helvetica, Arial, sans-serif',
					),
					array(
						'label' => 'List Items (Bulleted and Numbered)',
						'selector' => 'ul, ol',
						'default' => '"Open Sans", Helvetica, Arial, sans-serif',
					),
				);

				break;

			case 'twentythirteen':

				$font_locations = array(
					array(
						'label' => 'Body Copy',
						'selector' => 'body',
						'default' => '"Source Sans Pro", Helvetica, sans-serif',
					),
					array(
						'label' => 'Headings (H1-H6)',
						'selector' => 'h1, h2, h3, h4, h5, h6',
						'default' => 'Bitter, Georgia, serif',
					),
					array(
						'label' => 'Article Title',
						'selector' => 'h1.entry-title',
						'default' => 'Bitter, Georgia, serif',
					),
					array(
						'label' => 'Block Quote',
						'selector' => 'blockquote',
						'default' => '"Source Sans Pro", Helvetica, sans-serif',
					),
					array(
						'label' => 'List Items (Bulleted and Numbered)',
						'selector' => 'ul, ol',
						'default' => '"Source Sans Pro", Helvetica, sans-serif',
					),
				);

				break;

			case 'twentyfourteen':


				$font_locations = array(
					'simple' => array(
						array(
							'label' => 'Body Copy',
							'selector' => 'body',
							'default' => 'Lato, sans-serif',
							'description' => 'Applies site-wide to content and sidebar',
						),
						array(
							'label' => 'Headings (H1-H6)',
							'selector' => 'h1, h2, h3, h4, h5, h6',
							'default' => 'Lato, sans-serif',
							'description' => 'Applies site-wide to content and sidebar',
						),
						array(
							'label' => 'Article Title',
							'selector' => 'h1.entry-title',
							'default' => 'Lato, sans-serif',
							'description' => 'Large heading at the top of pages and posts',
						),
					),
					'advanced' => array(
						'general' => array(
							array(
								'label' => 'Site Title',
								'selector' => 'h1.site-title',
								'default' => 'Lato, sans-serif',
							),
							array(
								'label' => 'Site Description',
								'selector' => 'h2.site-description',
								'default' => 'Lato, sans-serif',
							),
						),
						'content' => array(
							array(
								'label' => 'Article Title',
								'selector' => 'h1.entry-title',
								'default' => 'Lato, sans-serif',
								'description' => 'Large heading at the top of pages and posts',
							),
							array(
								'label' => 'Block Quote',
								'selector' => 'blockquote',
								'default' => 'Lato, sans-serif',
							),
							array(
								'label' => 'List Items (Bulleted and Numbered)',
								'selector' => 'ul, ol',
								'default' => 'Lato, sans-serif',
							),
						),
						'sidebar' => array(
							array(
								'label' => 'Widget Content',
								'selector' => '.widget',
								'default' => 'Lato, sans-serif',
							),
							array(
								'label' => 'Widget Titles',
								'selector' => '.widget-title',
								'default' => 'Lato, sans-serif',
							),
						),
					),
				);

				break;

			case 'twentyfifteen':


				$font_locations = array(
					'simple' => array(
						array(
							'label' => 'Body Copy',
							'selector' => 'body',
							'default' => '"Noto Serif", serif',
							'description' => 'Applies site-wide to content and sidebar',
						),
						array(
							'label' => 'Headings (H1-H6)',
							'selector' => 'h1, h2, h3, h4, h5, h6',
							'default' => '"Noto Serif", serif',
							'description' => 'Applies site-wide to content and sidebar',
						),
						array(
							'label' => 'Article Title',
							'selector' => 'h1.entry-title',
							'default' => '"Noto Serif", serif',
							'description' => 'Large heading at the top of pages and posts',
						),
					),
					'advanced' => array(
						'general' => array(
							array(
								'label' => 'Site Title',
								'selector' => '.site-title',
								'default' => '"Noto Serif", serif',
							),
							array(
								'label' => 'Site Description',
								'selector' => '.site-description',
								'default' => '"Noto Serif", serif',
							),
						),
						'content' => array(
							array(
								'label' => 'Article Title',
								'selector' => 'h1.entry-title',
								'default' => '"Noto Serif", serif',
								'description' => 'Large heading at the top of pages and posts',
							),
							array(
								'label' => 'Block Quote',
								'selector' => 'blockquote',
								'default' => '"Noto Serif", serif',
							),
							array(
								'label' => 'List Items (Bulleted and Numbered)',
								'selector' => 'ul, ol',
								'default' => '"Noto Serif", serif',
							),
						),
						'sidebar' => array(
							array(
								'label' => 'Widget Content',
								'selector' => '.widget',
								'default' => '"Noto Serif", serif',
							),
							array(
								'label' => 'Widget Titles',
								'selector' => 'h2.widget-title',
								'default' => '"Noto Serif", serif',
							),
						),
					),
				);

				break;

		endswitch;

		// bail if no font locations defined
		if( empty( $font_locations ) ){
			return;
		}

		// add theme support for typecase with defined font locations
		add_theme_support( 'typecase', $font_locations );

	}


	/**
	 * Set up Typecase Pro admin filters
	 *
	 * Sets up all the appropriate filters for Pro version
	 * within the admin section of plugin.
	 *
	 * @uses add_filter()
	 *
	 */
	public function set_pro_filters(){
			add_filter('typecase-buttons',array(&$this,'buttons_replace'));
	}

	/**
	 * Set up Typecase Pro front-end filters
	 *
	 * Sets up all the appropriate filters for Pro version
	 * within front-end of plugin.
	 *
	 * @uses add_filter()
	 *
	 */
	public function set_front_end_filters(){
		add_filter('typecase-front-end-editor',array(&$this,'front_end_editor_ui'));
		add_filter('typecase-classname',array(&$this,'front_end_classname'));
	}

	/**
	 * Set up global variables for our Javascript on the frontend.
	 *
	 * @uses admin_url()
	 *
	 */
	public function frontend_js_globals() {
		$admin_url = admin_url('admin-ajax.php');
		$output = "
			<script type='text/javascript'>
				var ajaxurl = '$admin_url';
				var frontend = true;
			</script>";
		echo $output;
	}

	/**
	 * Enqueues scripts/styles for front-end editor
	 *
	 * @uses wp_enqueue_style()
	 * @uses wp_enqueue_script()
	 * @uses plugins_url()
	 *
	 */
	public function frontend_scripts(){
		wp_enqueue_style('front-end-editor', plugins_url( 'styles/front_end_editor.css', TYPECASE_FILE ), false, date( 'Ymd' ) );
		wp_enqueue_script('front-end-editor', plugins_url( 'scripts/pro.js', TYPECASE_FILE ), false, date( 'Ymd' ) );
	}

	/**
	 * Live font preview updater.
	 *
	 * Regenerates the CSS for our fonts every time something gets saved.
	 *
	 * @uses get_option()
	 * @uses explode()
	 * @uses substr()
	 * @uses strpos()
	 * @uses mb_substr()
	 * @uses str_replace()
	 * @uses json_encode()
	 * @uses header()
	 *
	 */
	public function ajax_reload_font_preview(){
		$fonts = get_option('typecase_fonts');

		if( $fonts[0] ){

			$apiUrl = &$this->api_url;
			$import_url = '';
			$font_styles = '';

			foreach($fonts as $font){

				$family = explode("|",$font[0]);
				$family = $family[0];
				$selectors = substr( $font[1], 1);
				$weights = substr( $font[2], 1);

				$import_url .= str_replace(" ","+",$family).$this->stringify_font_part($weights).$this->stringify_font_part($charsets);

				$selectors = explode("|",$selectors);

				foreach( $selectors as $i => $selector){
					if($i>0)
						$font_styles .= ",";
					$font_styles .= $selector;
				}

				$font_styles .= "{ font-family: \"$family\"; }\n";

			}

			$import_fonts = "@import url($apiUrl$import_url);\n";

			$font_css = "\n\n<!--====== Typecase Font Declarations ======-->";
			$font_css .= "\n<style type=\"text/css\">\n";
			$font_css .= $import_fonts;
			$font_css .= $font_styles;
			$font_css .= "</style>\n";
			$font_css .= "<!--==-- End Typecase Font Declarations --==-->\n\n";

			$new_nonce = array( 'nonce' => wp_create_nonce($this->nonce_key) );

			$response = json_encode( array( '_new_nonce' => $new_nonce, 'success' => true, 'css' => $font_css ) );

			header( "Content-Type: application/json" );
			echo $response;
			exit;

		} else {

			$response = json_encode( array( 'success' => false, 'error' => __('There were no fonts defined','typecase') ) );

			header( "Content-Type: application/json" );
			echo $response;
			exit;

		}

	}

	/**
	 * Modifies admin header buttons and returns Typecase Pro buttons
	 *
	 * @return $buttons	New set of buttons
	 *
	 */
	public function buttons_replace($buttons){

		$buttons = '<div class="buttons"><a class="typecase-btn primary" href="' . get_bloginfo('url') . '/?front_end_editor=1" target="_blank">' . __("Open Live Editor","typecase") . '</a> <a class="typecase-btn" href="http://upthemes.com/" target="_blank">WordPress Themes by UpThemes</a></div>';

		return $buttons;

	}

	/**
	 * Modifies front-end editor UI and returns new buttons
	 *
	 * @return $editor_ui Modified editor UI
	 *
	 */
	public function front_end_editor_ui($editor_ui){
			$front_end_editor = '<iframe></iframe><button class="collection typecase-btn" id="your-collection-toggle" data-target="your-collection"><i class="badge"></i>' . __("View Your Collection","typecase") . '</button> <button class="available typecase-btn" id="available-fonts-toggle" data-target="available-fonts">' . __("Find New Fonts","typecase") . '</button>';
			$editor_ui = $editor_ui.$front_end_editor;
			return $editor_ui;
	}

	/**
	 * Returns front-end editor classname
	 *
	 * @return $classname Modified classname for front-end #typecase container
	 *
	 */
	public function front_end_classname($classname){
			$classname = ' class="front_end_editor"';

			return $classname;
	}

}

$typecase = new Typecase();