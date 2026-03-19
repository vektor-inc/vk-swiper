<?php
/**
 * VK Swiper
 *
 * @package vektor-inc/vk-swiper
 * @license GPL-2.0+
 *
 * @version 0.3.4
 */

namespace VektorInc\VK_Swiper;

// Set version number.
const SWIPER_VERSION = '11.2.10';

/**
 * VK Swiper
 */
class VkSwiper {
	/**
	 * Init
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_swiper' ) );
		add_filter( 'vk_css_simple_minify_handles', array( __CLASS__, 'css_simple_minify_handles' ) );
	}

	/**
	 * Change Path to URL
	 * 
	 * @param string $path File Path.
	 */
	public static function get_directory_uri( $path ) {

		$uri = '';

		// PATH を正規化
		$path = wp_normalize_path( $path );

		// プラグインやテーマのディレクトリがカスタマイズされている場合にも対応するため、
		// より具体的なディレクトリから順にマッチを試みる
		$bases = array(
			wp_normalize_path( WP_PLUGIN_DIR )   => plugins_url(),
			wp_normalize_path( WPMU_PLUGIN_DIR ) => WPMU_PLUGIN_URL,
			wp_normalize_path( get_theme_root() ) => get_theme_root_uri(),
			wp_normalize_path( WP_CONTENT_DIR )  => content_url(),
		);

		foreach ( $bases as $dir => $url ) {
			if ( strpos( $path, $dir ) === 0 ) {
				$relative = ltrim( substr( $path, strlen( $dir ) ), '/' );
				$uri      = trailingslashit( $url ) . ( $relative === '' ? '' : $relative . '/' );
				break;
			}
		}

		// どのベースディレクトリにもマッチしなかった場合のフォールバック
		if ( empty( $uri ) ) {
			$uri = content_url( '/' );
			// 解決できなかった場合のみデバッグ通知
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// translators: %s is the file path that could not be resolved to a URL.
				trigger_error( sprintf( esc_html__( 'VK Swiper: Could not resolve path to URL: %s', 'vk-swiper' ), esc_html( $path ) ), E_USER_NOTICE );
			}
		}

		return $uri;
	}

	/**
	 * Load Swiper
	 */
	public static function register_swiper() {
		$current_url  = self::get_directory_uri( dirname( __FILE__ ) );
		wp_register_style( 'vk-swiper-style', $current_url . 'assets/css/swiper-bundle.min.css', array(), SWIPER_VERSION );
		wp_register_script( 'vk-swiper-script', $current_url . 'assets/js/swiper-bundle.min.js', array(), SWIPER_VERSION, true );
	}

	/**
	 * Enque Swiper
	 * テーマなどの vk-swiper/config.php から必要に応じて読み込む
	 */
	public static function enqueue_swiper() {
		add_action(
			'wp_enqueue_scripts',
			function() {
				wp_enqueue_style( 'vk-swiper-style' );
				wp_enqueue_script( 'vk-swiper-script' );
			}
		);
	}

	/**
	 * Simple Minify Array
	 */
	public static function css_simple_minify_handles( $vk_css_simple_minify_handles ) {

		// Register common css.
		$vk_css_simple_minify_handles [] = 'vk-swiper-style';		
		return $vk_css_simple_minify_handles;

	}
}
