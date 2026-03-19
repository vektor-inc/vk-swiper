<?php
/**
 * Tests for VkSwiper::get_directory_uri()
 */

use VektorInc\VK_Swiper\VkSwiper;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

class VkSwiperTest extends TestCase {

	/**
	 * デフォルト構成: WP_CONTENT_DIR 配下（plugins/themes 以外）のパスから content_url() ベースの URL が生成される
	 */
	public function test_get_directory_uri_default_content_dir() {
		$path = wp_normalize_path( WP_CONTENT_DIR . '/uploads/vk-swiper/src' );
		$uri  = VkSwiper::get_directory_uri( $path );

		$this->assertStringStartsWith( content_url(), $uri );
		$this->assertStringContainsString( '/uploads/vk-swiper/src/', $uri );
		$this->assertStringEndsWith( '/', $uri );
	}

	/**
	 * WP_PLUGIN_DIR 配下のパスから plugins_url() ベースの URL が生成される
	 */
	public function test_get_directory_uri_plugin_dir() {
		$path = wp_normalize_path( WP_PLUGIN_DIR . '/my-plugin/vendor/vektor-inc/vk-swiper/src' );
		$uri  = VkSwiper::get_directory_uri( $path );

		$this->assertStringStartsWith( plugins_url(), $uri );
		$this->assertStringContainsString( '/my-plugin/vendor/vektor-inc/vk-swiper/src/', $uri );
		$this->assertStringEndsWith( '/', $uri );
	}

	/**
	 * テーマディレクトリ配下のパスから get_theme_root_uri() ベースの URL が生成される
	 */
	public function test_get_directory_uri_theme_root() {
		$path = wp_normalize_path( get_theme_root() . '/my-theme/vendor/vektor-inc/vk-swiper/src' );
		$uri  = VkSwiper::get_directory_uri( $path );

		$this->assertStringStartsWith( get_theme_root_uri(), $uri );
		$this->assertStringContainsString( '/my-theme/vendor/vektor-inc/vk-swiper/src/', $uri );
		$this->assertStringEndsWith( '/', $uri );
	}

	/**
	 * mu-plugins ディレクトリ配下のパスから WPMU_PLUGIN_URL ベースの URL が生成される
	 */
	public function test_get_directory_uri_mu_plugins() {
		$path = wp_normalize_path( WPMU_PLUGIN_DIR . '/vk-swiper/src' );
		$uri  = VkSwiper::get_directory_uri( $path );

		$this->assertStringStartsWith( WPMU_PLUGIN_URL, $uri );
		$this->assertStringContainsString( '/vk-swiper/src/', $uri );
		$this->assertStringEndsWith( '/', $uri );
	}

	/**
	 * マッチしないパスではフォールバックが動作し、空文字列にならない
	 */
	public function test_get_directory_uri_fallback_not_empty() {
		$path = '/some/completely/unrelated/path';
		$uri  = VkSwiper::get_directory_uri( $path );

		$this->assertEquals( content_url( '/' ), $uri );
		$this->assertStringEndsWith( '/', $uri );
	}

	/**
	 * 実際の __FILE__ パスで URL が生成できる
	 */
	public function test_get_directory_uri_with_real_file_path() {
		$path = dirname( ( new ReflectionClass( VkSwiper::class ) )->getFileName() );
		$uri  = VkSwiper::get_directory_uri( $path );

		$this->assertNotEmpty( $uri );
		$this->assertStringEndsWith( '/', $uri );
		$this->assertStringContainsString( 'vk-swiper/src/', $uri );
	}
}
