<?php
/*
Plugin Name: Caching Compatible Cookie Optin and JavaScript
Plugin URI: https://www.falkemedia.at
Description: Cookie Opt-In and asynchronous JavaScript solution with full compatibility for server-side caching plugins
Version:     0.0.10
Author:      Matthias Wagner - FALKEmedia
Author URI:  https://www.matthias-wagner.at
Text Domain: falke_ccco
Domain Path: /languages
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/
if (!defined('ABSPATH')) { exit; }

if( !class_exists( 'FALKE_CachingCompatibleCookieOptinAndJS' ) ){
	class FALKE_CachingCompatibleCookieOptinAndJS{

		//The unique instance of the plugin.
    private static $instance;

    //Gets an instance of our plugin.
    public static function get_instance(){
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    //variables
    private $scripts = null;
    private $settings = null;

    //constructor
	  private function __construct(){
      //backend
	  	add_action( 'plugins_loaded', array( $this, 'set_textdomain' ) );
			add_action( 'admin_init', array( $this, 'register_translation' ) );
			add_action( 'init', array( $this, 'register_shortcodes' ) );
			add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
      add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

      //retrieve options
			$this->setScripts(get_option('falke_ccco_scripts'));
			$this->setSettings(get_option('falke_ccco_settings'));

      //frontend
      add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_scripts' ) );
      add_action( 'wp_footer', array( $this, 'frontend_noscript_hint' ) );
    }

    //setters/getters
		private function setScripts($scripts){
			$this->scripts = $scripts;
		}
		public function getScripts(){
			return empty($this->scripts) ? array() : apply_filters( 'cccof_scripts', $this->scripts );
		}
    private function setSettings($settings){
			$this->settings = $settings;
		}
		public function getSettings(){
      $settings = empty($this->settings) ? array() : $this->settings;

      //return default values if not set
      if(!isset($settings['text']['bannertext']) || empty($settings['text']['bannertext'])) $settings['text']['bannertext'] = __('May we ask you to opt-in to our cookies?', 'falke_ccco');
      if(!isset($settings['text']['accepttext']) || empty($settings['text']['accepttext'])) $settings['text']['accepttext'] = __('Yes, accept all cookies', 'falke_ccco');
      if(!isset($settings['text']['refusetext']) || empty($settings['text']['refusetext'])) $settings['text']['refusetext'] = __('Proceed with necessary cookies', 'falke_ccco');
      if(!isset($settings['text']['linktext']) || empty($settings['text']['linktext'])) $settings['text']['linktext'] = __('Visit privacy page', 'falke_ccco');

			if(!isset($settings['behaviour']['linkpage'])) $settings['behaviour']['linkpage'] = 0;
        $settings['behaviour']['linkpageurl'] = $settings['behaviour']['linkpage'] == 0 ? '' : get_permalink( $settings['behaviour']['linkpage'] );
      if(!isset($settings['behaviour']['noscript'])) $settings['behaviour']['noscript'] = 1;
      if(!isset($settings['behaviour']['bannertype']) || empty($settings['behaviour']['bannertype'])) $settings['behaviour']['bannertype'] = 'information';

			if(!isset($settings['text']['revoke_noselection']) || empty($settings['text']['revoke_noselection'])) $settings['text']['revoke_noselection'] = __('So far, no decision regarding the use of third-party cookies has been made and only necessary cookies are being used.', 'falke_ccco');
			if(!isset($settings['text']['revoke_baseselection']) || empty($settings['text']['revoke_baseselection'])) $settings['text']['revoke_baseselection'] = __('According to your decision, only necessary cookies are used on this device and in this browser.', 'falke_ccco');
			if(!isset($settings['text']['revoke_allselection']) || empty($settings['text']['revoke_allselection'])) $settings['text']['revoke_allselection'] = __('According to your decision, all cookies are used on this device and in this browser.', 'falke_ccco');
			if(!isset($settings['text']['revoke_button']) || empty($settings['text']['revoke_button'])) $settings['text']['revoke_button'] = __('Revoke decision', 'falke_ccco');

			if(!isset($settings['text']['inline_warning']) || empty($settings['text']['inline_warning'])) $settings['text']['inline_warning'] = __('This part of content is dependent on third-party cookies. In order to display it, we need your consent to use those cookies.', 'falke_ccco');

      if(!isset($settings['styling']['bannercolor']) || empty($settings['styling']['bannercolor'])) $settings['styling']['bannercolor'] = '#eeeeee';
      if(!isset($settings['styling']['buttoncolor']) || empty($settings['styling']['buttoncolor'])) $settings['styling']['buttoncolor'] = '#000000';
      if(!isset($settings['styling']['bannertextcolor']) || empty($settings['styling']['bannertextcolor'])) $settings['styling']['bannertextcolor'] = '#000000';

			if(!isset($settings['cookies']['unset_on_revoke']) || empty($settings['cookies']['unset_on_revoke'])) $settings['cookies']['unset_on_revoke'] = '';

			return apply_filters('cccof_settings', $settings);
		}
		public function getTranslatedSettings(){
			$settings = $this->getSettings();
			if(!empty($settings['text'])){
				foreach($settings['text'] as $key => $text){
					//polylang
					if(function_exists('pll__')) $settings['text'][$key] = pll__($text);
				}
			}

			return apply_filters('cccof_translatedSettings', $settings);
		}

    //set textdomain
	  public function set_textdomain(){
			load_plugin_textdomain( 'falke_ccco', false, dirname( plugin_basename( plugin_basename(__FILE__) ) ) . '/languages/' );
	  }

		//register user-strings in polylang
		public function register_translation(){
			$settings = $this->getSettings();

			if(!empty($settings['text'])){
				foreach($settings['text'] as $key => $text){
					//polylang
					if(function_exists('pll_register_string')) pll_register_string($key, $text, 'caching-compatible-cookie-optin-and-javascript');
				}
			}
		}

		//add our shortcodes
		public function register_shortcodes(){
			add_shortcode( 'ccco-revoke', array( $this, 'ccco_shortcode_revoke' ) );
			add_shortcode( 'ccco-inline', array( $this, 'ccco_shortcode_inline' ) );
		}

		//revoke shortcode
		public function ccco_shortcode_revoke($atts = [], $content = null, $tag = ''){
			return '<span class="ccco-revoke-button-wrap"></span>';
		}

		//inline script shortcode
		public function ccco_shortcode_inline($atts = [], $content = null, $tag = ''){
			$rS = '<div class="ccco-inline-container" ';
				$rS .= (isset($atts['type']) && ($atts['type'] == 'immediate' || $atts['type'] == 'button')) ? 'data-type="'.$atts['type'].'" ' : 'data-type="immediate" ';
				$rS .= (isset($atts['buttontext'])) ? 'data-buttontext="'.$atts['buttontext'].'" ' : 'data-buttontext="" ';
				$rS .= (isset($atts['permission']) && ($atts['permission'] == 'base' || $atts['permission'] == 'optin')) ? 'data-permission="'.$atts['permission'].'" ' : 'data-permission="base" ';
				$rS .= (isset($atts['fallbackurl'])) ? 'data-fallbackurl="'.$atts['fallbackurl'].'" ' : 'data-fallbackurl="" ';
				$rS .= (isset($atts['fallbacktext'])) ? 'data-fallbacktext="'.$atts['fallbacktext'].'" ' : 'data-fallbacktext="" ';
				$rS .= !empty($content) ? 'data-html="'.htmlspecialchars($content).'"' : 'data-html=""';
			$rS .= '></div>';

			return $rS;
		}

    //frontend scripts and styles
    public function enqueue_frontend_scripts(){
      //some base styling
      wp_enqueue_style( 'cccoFrontendStyle', plugin_dir_url( __FILE__ ) . 'assets/css/cccoFrontendStyle.css' );
			wp_add_inline_style( 'cccoFrontendStyle', '
				.ccco-banner-buttons a[data-ccco="accept"],
				.ccco-inline-container .ccco-inline-button,
				.ccco-inline-appendbutton{
					color:'.$this->getSettings()['styling']['bannercolor'].';
					background-color:'.$this->getSettings()['styling']['buttoncolor'].';
				}
				.ccco-banner .ccco-banner-inner,
				.ccco-noscript .ccco-noscript-inner,
				.ccco-inline-container.ccco-inline-warning,
				.ccco-revoke-button{
					color:'.$this->getSettings()['styling']['bannertextcolor'].';
					background-color:'.$this->getSettings()['styling']['bannercolor'].';
				}
				.ccco-banner .ccco-banner-inner a:not([data-ccco="accept"]),
				.ccco-inline-container .ccco-inline-link{
					color:'.$this->getSettings()['styling']['bannertextcolor'].';
				}
			' );

      //our file that will handle those saved script tags
      wp_register_script( 'cccoFrontendScript', plugin_dir_url( __FILE__ ) . 'assets/js/cccoFrontendScript.js', array( 'jquery' ), false, true );
      $jsSettings = array(
        'scripts' => $this->getScripts(),
        'settings' => $this->getTranslatedSettings()
      );
      if($this->getSettings()['behaviour']['linkpage'] != 0 && is_page($this->getSettings()['behaviour']['linkpage'])){
        $jsSettings['showBanner'] = 'false';
      }else{
        $jsSettings['showBanner'] = 'true';
      }
      wp_localize_script( 'cccoFrontendScript', 'cccoSettings', $jsSettings );
      wp_enqueue_script('cccoFrontendScript');
    }

    //backend scripts and styles
    public function enqueue_admin_scripts(){
			wp_enqueue_style( 'wp-color-picker' );
      wp_enqueue_script( 'cccoAdminScript', plugin_dir_url( __FILE__ ) . 'assets/js/cccoAdminScript.js', array( 'jquery', 'wp-color-picker' ), false, true );
    }

    public function frontend_noscript_hint(){
      if($this->getSettings()['behaviour']['noscript'] == 1){
        echo '<noscript><div class="ccco-noscript ccco-bannertype-'.$this->getSettings()['behaviour']['bannertype'].'"><div class="ccco-noscript-inner">';
          echo '<span class="ccco-noscript-text">'.__('This website needs JavaScript enabled to work properly. Please enable JavaScript in your browser.', 'falke_ccco').'</span>';
          echo '<span class="ccco-noscript-buttons"><a href="https://enablejavascript.co/" target="_blank">'.__('Find out how to enable JavaScript here', 'falke_ccco').'</a></span>';
        echo '</div></div></noscript>';
      }
    }

    //generate menu entry
		public function add_menu_page(){
			// check user capabilities
	    if (!current_user_can('manage_options')) {
	        return;
	    }
			add_submenu_page( 'tools.php', esc_html__('Caching Compatible Cookie Opt-In and JavaScript', 'falke_ccco'), esc_html__('Cookie-Optin & JS', 'falke_ccco'), 'manage_options', plugin_basename(__FILE__), array( $this, 'output_menu_page') );
			$this->register_settings();
		}
    //generate menu page output
		public function output_menu_page(){
			// check user capabilities
	    if (!current_user_can('manage_options')) {
	        return;
	    }

      //find out active tab
			$active_tab = (isset($_GET['tab']) && ($_GET['tab'] == 'settings' || $_GET['tab'] == 'help' )) ? $_GET['tab'] : 'scripts';
			$active_tab_name = (isset($_GET['tab']) && ($_GET['tab'] == 'settings' || $_GET['tab'] == 'help' )) ? ucfirst($_GET['tab']) : esc_html__('Scripts', 'falke_ccco');

      echo '<div class="wrap falke_ccco_wrap">';

				//page title
				echo '<h1>' . get_admin_page_title() . '</h1>';

				//updated notices
				if ( isset( $_GET['settings-updated'] ) ) {
					add_settings_error( 'falke_ccco_messages', 'falke_ccco_message', sprintf(esc_html__( '%s saved. Do not forget to clear your site cache now :)', 'falke_ccco' ), $active_tab_name), 'updated' );

          //delete some caches here, since scripts have changed
          if(function_exists('wpfc_clear_all_cache')) wpfc_clear_all_cache(true); //wp fastest cache
				}
				settings_errors( 'falke_ccco_messages' );

				//page intro
				echo sprintf('<p>%s <a title="%s" target="_blank" href="https://www.matthias-wagner.at/">%s</a>.</p>', esc_html__( 'If you enjoy this plugin and especially if you use it for commercial projects, please help us maintain support and development with', 'falke_ccco' ), esc_html__('Donations', 'falke_ccco'), esc_html__('a small donation', 'falke_ccco'));

				//tabs
				echo '<h2 class="nav-tab-wrapper">';
					echo '<a href="?page='. plugin_basename(__FILE__) .'&amp;tab=scripts" class="nav-tab ' . ($active_tab == 'scripts' ? 'nav-tab-active ' : '') . '">' . esc_html__('Scripts', 'falke_ccco') . '</a>';
					echo '<a href="?page='. plugin_basename(__FILE__) .'&amp;tab=settings" class="nav-tab ' . ($active_tab == 'settings' ? 'nav-tab-active ' : '') . '">' . esc_html__('Settings', 'falke_ccco') . '</a>';
					echo '<a href="?page='. plugin_basename(__FILE__) .'&amp;tab=help" class="nav-tab ' . ($active_tab == 'help' ? 'nav-tab-active ' : '') . '">' . esc_html__('Help', 'falke_ccco') . '</a>';
				echo '</h2>';

				//main form
				echo '<form action="options.php" method="post">';

					//inputs based on current tab
					switch($active_tab){
            case 'settings':{
              //all banner text-inputs
							add_settings_section(
								'falke_ccco_section_settings_text',
								esc_html__('Banner Wording', 'falke_ccco'),
								array($this, 'section_settings_text_callback'),
								plugin_basename(__FILE__)
							);
              // provide settings for each and every word here, with multilang in mind
							add_settings_field(
								'falke_ccco_field_settings_text_bannertext',
								esc_html__('Text to show in Opt-In banner:', 'falke_ccco'),
								array($this, 'field_settings_text_bannertext_callback'),
								plugin_basename(__FILE__),
								'falke_ccco_section_settings_text'
							);
							add_settings_field(
								'falke_ccco_field_settings_text_inline_warning',
								esc_html__('Text to show in inline-script warning banner:', 'falke_ccco'),
								array($this, 'field_settings_text_inline_warning_callback'),
								plugin_basename(__FILE__),
								'falke_ccco_section_settings_text'
							);
              add_settings_field(
								'falke_ccco_field_settings_text_accepttext',
								esc_html__('Text to show in Accept-Button:', 'falke_ccco'),
								array($this, 'field_settings_text_accepttext_callback'),
								plugin_basename(__FILE__),
								'falke_ccco_section_settings_text'
							);
              add_settings_field(
								'falke_ccco_field_settings_text_refusetext',
								esc_html__('Text to show in Refuse-Button:', 'falke_ccco'),
								array($this, 'field_settings_text_refusetext_callback'),
								plugin_basename(__FILE__),
								'falke_ccco_section_settings_text'
							);
              add_settings_field(
								'falke_ccco_field_settings_text_linktext',
								esc_html__('Text to show in Link:', 'falke_ccco'),
								array($this, 'field_settings_text_linktext_callback'),
								plugin_basename(__FILE__),
								'falke_ccco_section_settings_text'
							);

							//all behaviour-inputs
							add_settings_section(
								'falke_ccco_section_settings_behaviour',
								esc_html__('Banner Behaviour', 'falke_ccco'),
								array($this, 'section_settings_behaviour_callback'),
								plugin_basename(__FILE__)
							);
              add_settings_field(
								'falke_ccco_field_settings_behaviour_linkpage',
								esc_html__('Link target page.', 'falke_ccco'),
								array($this, 'field_settings_behaviour_linkpage_callback'),
								plugin_basename(__FILE__),
								'falke_ccco_section_settings_behaviour'
							);
              add_settings_field(
								'falke_ccco_field_settings_behaviour_noscript',
								esc_html__('Show &lt;noscript&gt; warning?', 'falke_ccco'),
								array($this, 'field_settings_behaviour_noscript_callback'),
								plugin_basename(__FILE__),
								'falke_ccco_section_settings_behaviour'
							);
              add_settings_field(
								'falke_ccco_field_settings_behaviour_bannertype',
								esc_html__('Choose banner type', 'falke_ccco'),
								array($this, 'field_settings_behaviour_bannertype_callback'),
								plugin_basename(__FILE__),
								'falke_ccco_section_settings_behaviour'
							);

							//all revoke-button-inputs
							add_settings_section(
								'falke_ccco_section_settings_revoke',
								esc_html__('Revoke Button', 'falke_ccco'),
								array($this, 'section_settings_revoke_callback'),
								plugin_basename(__FILE__)
							);
							add_settings_field(
								'falke_ccco_field_settings_revoke_noselection',
								esc_html__('Text to show before revoke button if no selection has been made yet:', 'falke_ccco'),
								array($this, 'field_settings_revoke_noselection_callback'),
								plugin_basename(__FILE__),
								'falke_ccco_section_settings_revoke'
							);
							add_settings_field(
								'falke_ccco_field_settings_revoke_baseselection',
								esc_html__('Text to show before revoke button if only necessary cookies have been chosen:', 'falke_ccco'),
								array($this, 'field_settings_revoke_baseselection_callback'),
								plugin_basename(__FILE__),
								'falke_ccco_section_settings_revoke'
							);
							add_settings_field(
								'falke_ccco_field_settings_revoke_allselection',
								esc_html__('Text to show before revoke button if all cookies have been chosen:', 'falke_ccco'),
								array($this, 'field_settings_revoke_allselection_callback'),
								plugin_basename(__FILE__),
								'falke_ccco_section_settings_revoke'
							);
							add_settings_field(
								'falke_ccco_field_settings_revoke_button',
								esc_html__('Text to show in revoke button:', 'falke_ccco'),
								array($this, 'field_settings_revoke_button_callback'),
								plugin_basename(__FILE__),
								'falke_ccco_section_settings_revoke'
							);
							add_settings_field(
								'falke_ccco_field_settings_revoke_deletecookies',
								esc_html__('Cookies to unset when clicking the revoke button:', 'falke_ccco'),
								array($this, 'field_settings_revoke_deletecookies_callback'),
								plugin_basename(__FILE__),
								'falke_ccco_section_settings_revoke'
							);

              //all styling-inputs
							add_settings_section(
								'falke_ccco_section_settings_styling',
								esc_html__('Styling', 'falke_ccco'),
								array($this, 'section_settings_styling_callback'),
								plugin_basename(__FILE__)
							);
              add_settings_field(
								'falke_ccco_field_settings_styling_buttoncolor',
								esc_html__('Button color:', 'falke_ccco'),
								array($this, 'field_settings_styling_buttoncolor_callback'),
								plugin_basename(__FILE__),
								'falke_ccco_section_settings_styling'
							);
              add_settings_field(
								'falke_ccco_field_settings_styling_bannercolor',
								esc_html__('Banner Background and button text color:', 'falke_ccco'),
								array($this, 'field_settings_styling_bannercolor_callback'),
								plugin_basename(__FILE__),
								'falke_ccco_section_settings_styling'
							);
              add_settings_field(
								'falke_ccco_field_settings_styling_bannertextcolor',
								esc_html__('Banner text color:', 'falke_ccco'),
								array($this, 'field_settings_styling_bannertextcolor_callback'),
								plugin_basename(__FILE__),
								'falke_ccco_section_settings_styling'
							);

							settings_fields('falke_ccco_settings_group');
							do_settings_sections( plugin_basename(__FILE__) );
							break 1;
						}
						case 'help':{
              echo '<h2>'.__('Why another cookie notice plugin?', 'falke_ccco') . '</h2>';
              echo '<p>'.__('Please have a look at our plugin description on <a href="https://wordpress.org/plugins/caching-compatible-cookie-optin-and-javascript/" target="_blank">wordpress.org</a>.', 'falke_ccco').'</p>';
							echo '<h2>'.__('How to use the plugin? What settings and options are available?', 'falke_ccco') . '</h2>';
              echo '<p>'.__('Please have a look at our <a href="https://www.notion.so/falkemedia/Caching-Compatible-Cookie-Opt-In-and-JavaScript-4375c9b364b04b51bbd396e1514f105b" target="_blank">plugin documentation</a>.', 'falke_ccco').'</p>';
							break 1;
						}
						default:{
              //base scripts inputs
							add_settings_section(
								'falke_ccco_section_scripts_base',
								esc_html__('Base Scripts', 'falke_ccco'),
								array($this, 'section_scripts_base_callback'),
								plugin_basename(__FILE__)
							);
							add_settings_field(
								'falke_ccco_field_scripts_base_htmlhead',
								esc_html__('Place your base <head> scripts here:', 'falke_ccco'),
								array($this, 'field_scripts_base_htmlhead_callback'),
								plugin_basename(__FILE__),
								'falke_ccco_section_scripts_base'
							);
              add_settings_field(
								'falke_ccco_field_scripts_base_htmlbody',
								esc_html__('Place your base <body> scripts here:', 'falke_ccco'),
								array($this, 'field_scripts_base_htmlbody_callback'),
								plugin_basename(__FILE__),
								'falke_ccco_section_scripts_base'
							);

              //optin scripts inputs
              add_settings_section(
								'falke_ccco_section_scripts_optin',
								esc_html__('Opt-In Scripts', 'falke_ccco'),
								array($this, 'section_scripts_optin_callback'),
								plugin_basename(__FILE__)
							);
              add_settings_field(
								'falke_ccco_field_scripts_optin_htmlhead',
								esc_html__('Place your Opt-In <head> scripts here:', 'falke_ccco'),
								array($this, 'field_scripts_optin_htmlhead_callback'),
								plugin_basename(__FILE__),
								'falke_ccco_section_scripts_optin'
							);
              add_settings_field(
								'falke_ccco_field_scripts_optin_htmlbody',
								esc_html__('Place your Opt-In <body> scripts here:', 'falke_ccco'),
								array($this, 'field_scripts_optin_htmlbody_callback'),
								plugin_basename(__FILE__),
								'falke_ccco_section_scripts_optin'
							);

							settings_fields('falke_ccco_scripts_group');
							do_settings_sections( plugin_basename(__FILE__) );
							break 1;
						}
					}

					//dynamic submit button
					if($active_tab != 'help'){
						submit_button(sprintf(esc_html__('Save %s', 'falke_ccco'), $active_tab_name));
					}

				echo '</form>';
			echo '</div>';
    }

    //register settings
		private function register_settings(){
			register_setting( 'falke_ccco_settings_group', 'falke_ccco_settings', array(
				'sanitize_callback' => array($this, 'sanitize_settings_group'),
				'show_in_rest' => true
			) );
			register_setting( 'falke_ccco_scripts_group', 'falke_ccco_scripts', array(
				'sanitize_callback' => array($this, 'sanitize_scripts_group'),
				'show_in_rest' => true
			) );
		}

    //generate options fields output for the settings tab
		public function section_settings_text_callback(){
			echo esc_html__('Define texts for your banner and buttons here.', 'falke_ccco');
		}
		public function section_settings_revoke_callback(){
			echo esc_html__('Define texts and control behaviour of the revoke button here.', 'falke_ccco');
		}
    public function section_settings_behaviour_callback(){
			echo esc_html__('Control behaviour of banner here.', 'falke_ccco');
		}
    public function section_settings_styling_callback(){
			echo esc_html__('Style your banner here.', 'falke_ccco');
		}

    //generate options fields output for each settings field
    public function field_settings_text_bannertext_callback(){
      $options = $this->getSettings();
      echo '<p><textarea rows="3" cols="80" style="width:100%;" name="falke_ccco_settings[text][bannertext]">'.$options['text']['bannertext'].'</textarea></p>';
    }
		public function field_settings_text_inline_warning_callback(){
      $options = $this->getSettings();
      echo '<p><textarea rows="3" cols="80" style="width:100%;" name="falke_ccco_settings[text][inline_warning]">'.$options['text']['inline_warning'].'</textarea></p>';
    }
    public function field_settings_text_accepttext_callback(){
      $options = $this->getSettings();
      echo '<p><input type="text" style="width:100%;" name="falke_ccco_settings[text][accepttext]" value="'.$options['text']['accepttext'].'" /></p>';
    }
    public function field_settings_text_refusetext_callback(){
      $options = $this->getSettings();
      echo '<p><input type="text" style="width:100%;" name="falke_ccco_settings[text][refusetext]" value="'.$options['text']['refusetext'].'" /></p>';
    }
    public function field_settings_text_linktext_callback(){
      $options = $this->getSettings();
      echo '<p><input type="text" style="width:100%;" name="falke_ccco_settings[text][linktext]" value="'.$options['text']['linktext'].'" /></p>';
    }

		public function field_settings_revoke_noselection_callback(){
			$options = $this->getSettings();
      echo '<p><input type="text" style="width:100%;" name="falke_ccco_settings[text][revoke_noselection]" value="'.$options['text']['revoke_noselection'].'" /></p>';
		}
		public function field_settings_revoke_baseselection_callback(){
			$options = $this->getSettings();
      echo '<p><input type="text" style="width:100%;" name="falke_ccco_settings[text][revoke_baseselection]" value="'.$options['text']['revoke_baseselection'].'" /></p>';
		}
		public function field_settings_revoke_allselection_callback(){
			$options = $this->getSettings();
      echo '<p><input type="text" style="width:100%;" name="falke_ccco_settings[text][revoke_allselection]" value="'.$options['text']['revoke_allselection'].'" /></p>';
		}
		public function field_settings_revoke_button_callback(){
			$options = $this->getSettings();
      echo '<p><input type="text" style="width:100%;" name="falke_ccco_settings[text][revoke_button]" value="'.$options['text']['revoke_button'].'" /></p>';
		}
		public function field_settings_revoke_deletecookies_callback(){
			$options = $this->getSettings();
			echo '<p>'.__('Sometimes it is necessary to delete cookies which have been placed by scripts when the revoke button is clicked. Use the following input to define those cookies, separated by comma.', 'falke_ccco').'</p>';
      echo '<p><input type="text" style="width:100%;" name="falke_ccco_settings[cookies][unset_on_revoke]" value="'.$options['cookies']['unset_on_revoke'].'" placeholder="__utma,__ga,..." /></p>';
		}

    public function field_settings_behaviour_linkpage_callback(){
      $options = $this->getSettings();
      echo '<p>'.__('If you select a page here, the banner will automatically include a link to that page. Typically this is used for the privacy page. The banner wont be visible on that page.', 'falke_ccco').'</p>';
      echo '<p><select style="width:100%; max-width:100%;" name="falke_ccco_settings[behaviour][linkpage]">';
        echo '<option value="0">---</option>';
        foreach(get_pages() as $page){
          echo '<option value="'.$page->ID.'"'.($page->ID == $options['behaviour']['linkpage'] ? ' selected="selected"' : '').'>'.$page->post_title.'</option>';
        }
      echo '</p>';
    }
    public function field_settings_behaviour_noscript_callback(){
      $options = $this->getSettings();
      echo '<p>'.__('Since the whole plugin is intended to execute JavaScript in your page, we recommend to display a warning with a &lt;noscript&gt; tag if JavaScript is disabled.', 'falke_ccco').'</p>';
      echo '<p><select style="width:100%; max-width:100%;" name="falke_ccco_settings[behaviour][noscript]">';
        echo '<option value="1"'.(1 == $options['behaviour']['noscript'] ? ' selected="selected"' : '').'>'.__('Yes', 'falke_ccco').'</option>';
        echo '<option value="0"'.(0 == $options['behaviour']['noscript'] ? ' selected="selected"' : '').'>'.__('No', 'falke_ccco').'</option>';
      echo '</p>';
    }
    public function field_settings_behaviour_bannertype_callback(){
      $options = $this->getSettings();
      echo '<p>'.__('Choose type of banner. &raquo;Blocking&laquo; will prevent users from interacting with your site while &raquo;Information&laquo; will be less annoying at the bottom of the page (but lead to less opt-ins!).', 'falke_ccco').'</p>';
      echo '<p><select style="width:100%; max-width:100%;" name="falke_ccco_settings[behaviour][bannertype]">';
        echo '<option value="information"'.('information' == $options['behaviour']['bannertype'] ? ' selected="selected"' : '').'>'.__('Information', 'falke_ccco').'</option>';
        echo '<option value="blocking"'.('blocking' == $options['behaviour']['bannertype'] ? ' selected="selected"' : '').'>'.__('Blocking', 'falke_ccco').'</option>';
      echo '</p>';
    }
    public function field_settings_styling_buttoncolor_callback(){
      $options = $this->getSettings();
      echo '<p><input type="text" class="falke_ccco_colorinput" style="width:100%;" name="falke_ccco_settings[styling][buttoncolor]" value="'.$options['styling']['buttoncolor'].'" /></p>';
    }
    public function field_settings_styling_bannercolor_callback(){
      $options = $this->getSettings();
      echo '<p><input type="text" class="falke_ccco_colorinput" style="width:100%;" name="falke_ccco_settings[styling][bannercolor]" value="'.$options['styling']['bannercolor'].'" /></p>';
    }
    public function field_settings_styling_bannertextcolor_callback(){
      $options = $this->getSettings();
      echo '<p><input type="text" class="falke_ccco_colorinput" style="width:100%;" name="falke_ccco_settings[styling][bannertextcolor]" value="'.$options['styling']['bannertextcolor'].'" /></p>';
    }

    //generate options fields output for the scripts tab
    public function section_scripts_base_callback(){
      echo esc_html__('Here you can place your JS code for different parts of the page, which will always be executed.', 'falke_ccco');
    }
    public function section_scripts_optin_callback(){
      echo esc_html__('Here you can place your JS code for different parts of the page, which will be executed only after Opt-In.', 'falke_ccco');
    }
    //generate options fields output for each scripts field
    public function field_scripts_base_htmlhead_callback(){
      $options = $this->getScripts();
      echo '<p><textarea rows="8" cols="80" style="width:100%;" name="falke_ccco_scripts[base][htmlhead]" placeholder="<script type=&quot;text/javascript&quot;>...</script>">'.$options['base']['htmlhead'].'</textarea></p>';
    }
    public function field_scripts_base_htmlbody_callback(){
      $options = $this->getScripts();
      echo '<p><textarea rows="8" cols="80" style="width:100%;" name="falke_ccco_scripts[base][htmlbody]" placeholder="<script type=&quot;text/javascript&quot;>...</script>">'.$options['base']['htmlbody'].'</textarea></p>';
    }
    public function field_scripts_optin_htmlhead_callback(){
      $options = $this->getScripts();
      echo '<p><textarea rows="8" cols="80" style="width:100%;" name="falke_ccco_scripts[optin][htmlhead]" placeholder="<script type=&quot;text/javascript&quot;>...</script>">'.$options['optin']['htmlhead'].'</textarea></p>';
    }
    public function field_scripts_optin_htmlbody_callback(){
      $options = $this->getScripts();
      echo '<p><textarea rows="8" cols="80" style="width:100%;" name="falke_ccco_scripts[optin][htmlbody]" placeholder="<script type=&quot;text/javascript&quot;>...</script>">'.$options['optin']['htmlbody'].'</textarea></p>';
    }

    //sanitize user inputs
    public function sanitize_scripts_group($options){
      //do nothing on empty input
			if(empty($options)){ return $options; }

      //TODO: sanitize
      return $options;
    }
    public function sanitize_settings_group($options){
      //do nothing on empty input
			if(empty($options)){ return $options;	}

      //TODO: sanitize
      return $options;
    }

  }

  $FALKE_CachingCompatibleCookieOptinAndJS = FALKE_CachingCompatibleCookieOptinAndJS::get_instance();
}

// plugin uninstallation
register_uninstall_hook( __FILE__, 'falke_ccco_uninstall' );
function falke_ccco_uninstall() {
	delete_option( 'falke_ccco_scripts' );
	delete_option( 'falke_ccco_settings' );
}
