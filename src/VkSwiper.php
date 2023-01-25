<?php
/**
 * VK Swiper
 *
 * @package vektor-inc/vk-swiper
 * @license GPL-2.0+
 *
 * @version 0.1.0
 */

namespace VektorInc\VK_Swiper;

// Set version number.
define( 'SWIPER_VERSION', '6.8.4' );

/**
 * VK Swiper
 */
class VKSwiper {
	/**
	 * Init
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_swiper' ) );
		add_filter( 'vk_css_simple_minify_handle', array( __CLASS__, 'css_simple_minify_handle' ) );
	}

	/**
	 * Load Swiper
	 */
	public static function register_swiper() {
		global $vk_swiper_url;
		wp_register_style( 'vk-swiper-style', $vk_swiper_url . 'assets/css/swiper-bundle.min.css', array(), SWIPER_VERSION );
		wp_register_script( 'vk-swiper-script', $vk_swiper_url . 'assets/js/swiper-bundle.min.js', array(), SWIPER_VERSION, true );
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
	public static function css_simple_minify_handle( $vk_css_simple_minify_handle ) {

		// Register common css.
		$vk_css_simple_minify_handle = array_merge(
			$vk_css_simple_minify_handle,
			array(
				'vk-swiper-style'
			)
		);
		
		return $vk_css_simple_minify_handle;

	}
}
