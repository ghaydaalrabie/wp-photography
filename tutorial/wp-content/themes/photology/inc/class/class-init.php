<?php
/**
 * Init Configuration
 *
 * @author Jegstudio
 * @package photology
 * @since 1.0.0
 */

namespace Photology;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Photology\Block_Patterns;
use Photology\Block_Styles;

/**
 * Init Class
 *
 * @package photology
 */
class Init {

	/**
	 * Instance variable
	 *
	 * @var $instance
	 */
	private static $instance;

	/**
	 * Class instance.
	 *
	 * @return Init
	 */
	public static function instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Class constructor.
	 */
	private function __construct() {
		$this->load_hooks();
	}

	/**
	 * Load initial hooks.
	 */
	private function load_hooks() {
		// actions.
		add_action( 'init', array( $this, 'add_theme_templates' ) );
		add_action( 'after_setup_theme', array( $this, 'theme_setup' ) );
		add_action( 'after_theme_setup', array( $this, 'content_width' ), 0 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_notices', array( $this, 'notice_install_plugin' ) );
		add_action( 'wp_ajax_photology_set_admin_notice_viewed', array( $this, 'notice_closed' ) );
		add_action( 'admin_init', array( $this, 'load_editor_styles' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'init', array( $this, 'register_block_patterns' ), 9 );
		add_action( 'init', array( $this, 'register_block_styles' ), 9 );

		add_action( 'admin_enqueue_scripts', array( $this, 'dashboard_scripts' ) );

		// filters.
		add_filter( 'the_category', array( $this, 'render_categories' ) );
		add_filter( 'excerpt_length', array( $this, 'excerpt_length' ) );
		add_filter( 'excerpt_more', array( $this, 'excerpt_elipsis' ) );

		add_filter( 'gutenverse_template_path', array( $this, 'template_path' ), null, 3 );
		add_filter( 'gutenverse_themes_template', array( $this, 'add_template' ), 10, 2 );
	}

	/**
	 * Add Template to Editor.
	 *
	 * @param array $template_files Path to Template File.
	 * @param array $template_type Template Type.
	 *
	 * @return array
	 */
	public function add_template( $template_files, $template_type ) {
		$directory = get_template_directory();

		if ( 'wp_template' === $template_type ) {
			$new_templates = array(
				'about',
				'contact',
				'portfolio-grid',
				'portfolio',
				'service',
				'single-portfolio',
			);

			foreach ( $new_templates as $template ) {
				$template_files[] = array(
					'slug'  => $template,
					'path'  => $directory . "/gutenverse-templates/templates/{$template}.html",
					'theme' => get_template(),
					'type'  => 'wp_template',
				);
			}
		}

		return $template_files;
	}

	/**
	 * Use gutenverse template file instead.
	 *
	 * @param string $template_file Path to Template File.
	 * @param string $theme_slug Theme Slug.
	 * @param string $template_slug Template Slug.
	 *
	 * @return string
	 */
	public function template_path( $template_file, $theme_slug, $template_slug ) {
		$directory = get_template_directory();

		switch ( $template_slug ) {
			case 'front-page':
				return $directory . '/gutenverse-templates/templates/front-page.html';
			case 'header':
				return $directory . '/gutenverse-templates/parts/header.html';
			case 'footer':
				return $directory . '/gutenverse-templates/parts/footer.html';
			case 'about':
				return $directory . '/gutenverse-templates/templates/about.html';
			case 'contact':
				return $directory . '/gutenverse-templates/templates/contact.html';
			case 'portfolio-grid':
				return $directory . '/gutenverse-templates/templates/portfolio-grid.html';
			case 'portfolio':
				return $directory . '/gutenverse-templates/templates/portfolio.html';
			case 'service':
				return $directory . '/gutenverse-templates/templates/service.html';
			case 'single-portfolio':
				return $directory . '/gutenverse-templates/templates/single-portfolio.html';
		}

		return $template_file;
	}

	/**
	 * Register Block Pattern.
	 */
	public function register_block_patterns() {
		new Block_Patterns();
	}

	/**
	 * Register Block Style.
	 */
	public function register_block_styles() {
		new Block_Styles();
	}

	/**
	 * Excerpt Elipsis.
	 *
	 * @param string $more .
	 *
	 * @return string
	 */
	public function excerpt_elipsis( $more ) {
		if ( is_admin() ) {
			return $more;
		}

		return '';
	}

	/**
	 * Excerpt Length.
	 *
	 * @param int $length .
	 *
	 * @return int
	 */
	public function excerpt_length( $length ) {
		if ( is_admin() ) {
			return $length;
		}

		return 100;
	}

	/**
	 * Render Categories.
	 *
	 * @param String $thelist String rendered.
	 *
	 * @return string
	 */
	public function render_categories( $thelist ) {
		return "<div>{$thelist}</div>";
	}

	/**
	 * Notice Closed
	 */
	public function notice_closed() {
		update_user_meta( get_current_user_id(), 'gutenverse_install_notice', 'true' );
		die;
	}

	/**
	 * Show notification to install Gutenverse Plugin.
	 */
	public function notice_install_plugin() {
		// Skip if gutenverse block activated.
		if ( defined( 'GUTENVERSE' ) ) {
			return;
		}

		// Skip if gutenverse pro activated.
		if ( defined( 'GUTENVERSE_PRO' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( isset( $screen->parent_file ) && 'plugins.php' === $screen->parent_file && 'update' === $screen->id ) {
			return;
		}

		if ( 'true' === get_user_meta( get_current_user_id(), 'gutenverse_install_notice', true ) ) {
			return;
		}

		$button_text = __( 'Check it Now!', 'photology' );
		$button_link = wp_nonce_url( self_admin_url( 'themes.php?page=photology-dashboard' ), 'install-plugin_gutenverse' );
		?>
		<style>
			.install-gutenverse-plugin-notice {
				border: 1px solid #E6E6EF;
				border-radius: 5px;
				padding: 20px;
				position: relative;
				overflow: hidden;
				background-image: url(<?php echo esc_url( PHOTOLOGY_URI . '/assets/images/mockup-2x.png' ); ?>);
				background-position: right top;
				background-repeat: no-repeat;
				border-left: 4px solid #5e81f4;
			}

			.install-gutenverse-plugin-notice .notice-dismiss {
				top: 20px;
				right: 20px;
				padding: 0;
			}

			.install-gutenverse-plugin-notice .notice-dismiss:before {
				content: "\f335";
				font-size: 17px;
				width: 25px;
				height: 25px;
				line-height: 25px;
				border: 1px solid #E6E6EF;
				border-radius: 3px;
			}

			.install-gutenverse-plugin-notice h3 {
				margin-top: 5px;
				font-weight: 700;
				font-size: 18px;
			}

			.install-gutenverse-plugin-notice p {
				font-size: 14px;
				font-weight: 300;
			}

			.install-gutenverse-plugin-notice .gutenverse-bottom {
				display: flex;
				align-items: center;
				margin-top: 20px;
			}

			.install-gutenverse-plugin-notice a {
				text-decoration: none;
				margin-right: 20px;
			}

			.install-gutenverse-plugin-notice a.gutenverse-button {
				font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
				text-decoration: none;
				cursor: pointer;
				font-size: 12px;
				line-height: 18px;
				border-radius: 17px;
				background: #5e81f4;
				color: #fff;
				padding: 8px 30px;
				font-weight: 300;
			}
		</style>
		<script>
		jQuery( function( $ ) {
			$( 'div.notice.install-gutenverse-plugin-notice' ).on( 'click', 'button.notice-dismiss', function( event ) {
				event.preventDefault();

				$.post( ajaxurl, {
					action: 'photology_set_admin_notice_viewed'
				} );
			} );
		} );
		</script>
		<div class="notice is-dismissible install-gutenverse-plugin-notice">
			<div class="gutenverse-notice-inner">
				<div class="gutenverse-notice-content">
					<h3><?php esc_html_e( 'Thank you for installing Photology!', 'photology' ); ?></h3>
					<p><?php esc_html_e( 'Photology theme work best with Gutenverse plugin. By installing Gutenverse plugin you may access Photology templates built with Gutenverse and get access to more than 40 free blocks.', 'photology' ); ?></p>
					<div class="gutenverse-bottom">
						<a class="gutenverse-button" href="<?php echo esc_url( $button_link ); ?>">
							<?php echo esc_html( $button_text ); ?>
						</a>
						<a target="__blank" href="https://gutenverse.com/">
							<?php esc_html_e( 'More Info', 'photology' ); ?>
							<span class="dashicons dashicons-arrow-right-alt"></span>
						</a>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Add Menu
	 */
	public function admin_menu() {
		add_theme_page(
			'Photology Template',
			'Photology Template',
			'read',
			'photology-dashboard',
			array( $this, 'load_photology_dashboard' ),
			1
		);
	}

	/**
	 * Photology Template page
	 */
	public function load_photology_dashboard() {
		?>
			<?php if ( defined( 'GUTENVERSE_VERSION' ) && version_compare( GUTENVERSE_VERSION, '1.1.1', '<=' ) ) { ?>
			<div class="notice is-dismissible">
				<span>
				<?php echo esc_html_e( 'Please install newer version of Gutenverse plugin! (v1.1.2 and above)', 'photology' ); ?>
				</span>
			</div>
			<?php } ?>
			<?php do_action( 'gutenverse_after_install_notice' ); ?>
			<div id="gutenverse-theme-dashboard"></div>
		<?php
	}

	/**
	 * Add theme template
	 */
	public function add_theme_templates() {
		add_editor_style( 'block-style.css' );
	}

	/**
	 * Theme setup.
	 */
	public function theme_setup() {
		load_theme_textdomain( 'photology', PHOTOLOGY_DIR . '/languages' );

		add_theme_support( 'wp-block-styles' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'editor-styles' );

		register_nav_menus(
			array(
				'primary' => esc_html__( 'Primary', 'photology' ),
			)
		);

		add_editor_style(
			array(
				'./assets/css/core-add.css',
			)
		);

		add_theme_support(
			'html5',
			array(
				'search-form',
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
				'style',
				'script',
			)
		);

		add_theme_support( 'customize-selective-refresh-widgets' );
	}

	/**
	 * Set the content width.
	 */
	public function content_width() {
		$GLOBALS['content_width'] = apply_filters( 'gutenverse_content_width', 960 );
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'photology-style', get_stylesheet_uri(), array(), PHOTOLOGY_VERSION );
		wp_add_inline_style( 'photology-style', $this->load_font_styles() );

		// enqueue additional core css.
		wp_enqueue_style( 'photology-core-add', PHOTOLOGY_URI . '/assets/css/core-add.css', array(), PHOTOLOGY_VERSION );

		// enqueue core animation.
		wp_enqueue_script( 'photology-animate', PHOTOLOGY_URI . '/assets/js/index.js', array(), PHOTOLOGY_VERSION, true );
		wp_enqueue_style( 'photology-animate', PHOTOLOGY_URI . '/assets/css/animation.css', array(), PHOTOLOGY_VERSION );

		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public function dashboard_scripts() {
		$screen = get_current_screen();

		if ( $screen->id === 'appearance_page_photology-dashboard' ) {
			// enqueue css.
			wp_enqueue_style(
				'photology-dashboard',
				PHOTOLOGY_URI . '/assets/css/dashboard.css',
				array(),
				PHOTOLOGY_VERSION
			);

			// enqueue js.
			wp_enqueue_script(
				'photology-dashboard',
				PHOTOLOGY_URI . '/assets/js/dashboard.js',
				array( 'wp-api-fetch' ),
				PHOTOLOGY_VERSION,
				true
			);

			wp_localize_script( 'photology-dashboard', 'GutenThemeConfig', $this->theme_config() );
		}
	}

	/**
	 * Register static data to be used in theme's js file
	 */
	public function theme_config() {
		return array(
			'images'       => PHOTOLOGY_URI . '/assets/img/',
			'title'        => esc_html__( 'Get Advanced Templates for Free!', 'photology' ),
			'description'  => esc_html__( 'By just installing and activate Gutenverse plugin you will be able to use this theme\'s advanced templates. With Gutenverse plugin installed, you gain access to both advance version of templates and block patterns for free which all built using Gutenverse blocks.', 'photology' ),
			'title2'       => esc_html__( 'Comparison Using Gutenverse vs WordPress Core', 'photology' ),
			'title3'       => esc_html__( 'Benefits for Installing Photology\'s Gutenverse Version', 'photology' ),
			'description3' => esc_html__( 'You can customize your website instantly with powerful and lightweight add-ons plugin for Gutenberg/FSE.', 'photology' ),
			'note'         => esc_html__( 'Note: Clicking the button will both install and activate Gutenverse plugin and templates for this theme. Please first backup your current templates if you have any changes to it.', 'photology' ),
			'note2'        => esc_html__( 'Note 2: (Gutenverse version 1.2.4 & above required!)', 'photology' ),
			'demo'         => esc_html__( 'View Live Demo →', 'photology' ),
			'demoUrl'      => esc_url( 'https:/gutenverse.com/demo?name=photology' ),
			'install'      => '',
			'installText'  => esc_html__( 'Install Gutenverse Plugin', 'photology' ),
			'activateText' => esc_html__( 'Activate Gutenverse Plugin', 'photology' ),
			'doneText'     => esc_html__( 'Gutenverse Plugin Installed', 'photology' ),
			'pages'        => array(
				'home'     => PHOTOLOGY_URI . '/assets/img/page-home.webp',
				'about'    => PHOTOLOGY_URI . '/assets/img/page-about.webp',
				'services' => PHOTOLOGY_URI . '/assets/img/page-services.webp',
				'contact'  => PHOTOLOGY_URI . '/assets/img/page-contact.webp',
				'port'     => PHOTOLOGY_URI . '/assets/img/page-single-portfolio.webp',
			),
			'table'        => array(
				'titles'   => array(
					null,
					esc_html__( 'Gutenverse (FREE)', 'photology' ),
					esc_html__( 'WordPress Core', 'photology' ),
				),
				'features' => array(
					esc_html__( 'Advanced Templates', 'photology' ),
					esc_html__( 'Responsive Styling', 'photology' ),
					esc_html__( 'Variety of Fonts', 'photology' ),
					esc_html__( 'Icon Library', 'photology' ),
					esc_html__( 'Animation Effects', 'photology' ),
					esc_html__( 'Form Builder', 'photology' ),
				),
			),
			'benefits'     => array(
				'title'    => esc_html__( 'Features', 'photology' ),
				'features' => array(
					esc_html__( 'Modern and clean design', 'photology' ),
					esc_html__( '5+ Ready to use templates', 'photology' ),
					esc_html__( '15+ template parts', 'photology' ),
					esc_html__( 'Fully responsive layout', 'photology' ),
					esc_html__( 'Fully customizable', 'photology' ),
				),
			),
		);
	}

	/**
	 * Load Font Styles
	 */
	public function load_font_styles() {
		$font_families = array(
			'Prata:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;1,100;1,200;1,300;1,400;1,500;1,600',
			'Heebo:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;1,100;1,200;1,300;1,400;1,500;1,600',
		);

		$fonts_url = add_query_arg(
			array(
				'family'  => implode( '&family=', $font_families ),
				'display' => 'swap',
			),
			'https://fonts.googleapis.com/css2'
		);

		$contents = wptt_get_webfont_url( esc_url_raw( $fonts_url ), 'woff' );

		return "@import url({$contents});";
	}

	/**
	 * Load Editor Styles
	 */
	public function load_editor_styles() {
		wp_add_inline_style( 'wp-block-library', $this->load_font_styles() );
	}
}
