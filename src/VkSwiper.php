<?php
/**
 * VK Swiper
 *
 * @package vektor-inc/vk-swiper
 * @license GPL-2.0+
 *
 * @version 0.3.0
 */

namespace VektorInc\VK_Swiper;

// Set version number.
const SWIPER_VERSION = '9.3.2';

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
	 * Change Path Format
	 * 
	 * @param string File Path.
	 */
	public static function change_path( $path ) {
		// Windows 形式のパスを Linux 形式に変換
		$path = str_replace( ':', '', $path );
		$path = str_replace( '\\', '/', $path );

		return $path;
	}

	/**
	 * Load Swiper
	 */
	public static function register_swiper() {
		$current_path = self::change_path( dirname( __FILE__ ) );
		$current_url  = str_replace( self::change_path( ABSPATH ), site_url('/'), $current_path );
		wp_register_style( 'vk-swiper-style', $current_url . '/assets/css/swiper-bundle.min.css', array(), SWIPER_VERSION );
		wp_register_script( 'vk-swiper-script', $current_url . '/assets/js/swiper-bundle.min.js', array(), SWIPER_VERSION, true );
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
