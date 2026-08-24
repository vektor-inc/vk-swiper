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
	 * ディレクトリ境界を考慮せずに前方一致してしまうと、/wp-content/plugins-extra のような
	 * 別ディレクトリが /wp-content/plugins に誤マッチしてしまう（issue #13）。
	 * 誤マッチせず、より広い WP_CONTENT_DIR 側に正しくマッチすることを確認する。
	 */
	public function test_get_directory_uri_does_not_match_similar_directory_name() {
		$content_dir = wp_normalize_path( WP_CONTENT_DIR );
		// WP_PLUGIN_DIR と同じ階層に、名前が似ているだけの別ディレクトリ（plugins-extra）を想定する.
		$path = $content_dir . '/plugins-extra/some-plugin/vendor/vektor-inc/vk-swiper/src';
		$uri  = VkSwiper::get_directory_uri( $path );

		// WP_CONTENT_DIR 配下として、plugins-extra を含んだ相対パスのまま content_url() ベースの URL になる。
		// （誤って WP_PLUGIN_DIR にマッチすると、相対パスの先頭が壊れて "-extra/..." になってしまう）.
		$expected = trailingslashit( content_url() ) . 'plugins-extra/some-plugin/vendor/vektor-inc/vk-swiper/src/';
		$this->assertEquals( $expected, $uri );
	}

	/**
	 * マッチしないパスではフォールバックが動作し、空文字列にならない。
	 * あわせて、WP_DEBUG が無効な本番環境でも原因に気づけるよう、error_log() で
	 * サーバーのエラーログへ常に記録が残ることを確認する（issue #13 のフォールバック仕様変更）。
	 */
	public function test_get_directory_uri_fallback_not_empty() {
		$path = '/some/completely/unrelated/path';

		// error_log() の書き込み先を一時ファイルへ差し替え、テスト後に必ず元へ戻す.
		$original_error_log_ini = ini_get( 'error_log' );
		$tmp_log_file           = tempnam( sys_get_temp_dir(), 'vk-swiper-error-log-' );
		ini_set( 'error_log', $tmp_log_file ); // phpcs:ignore WordPress.PHP.IniSet.Risky -- error_log() の記録先をテスト用ファイルへ一時的に差し替えるため.

		$uri = VkSwiper::get_directory_uri( $path );

		ini_set( 'error_log', $original_error_log_ini ); // phpcs:ignore WordPress.PHP.IniSet.Risky -- テスト後に元の設定へ戻すため.

		// 空文字ではなく content_url() ベースの URL が返る（ドメイン直結の壊れた URL を避ける）.
		$this->assertEquals( trailingslashit( content_url() ), $uri );
		$this->assertNotEmpty( $uri );
		$this->assertStringEndsWith( '/', $uri );

		// WP_DEBUG が無効でもエラーログに解決できなかったパスの記録が残っていること.
		$log_contents = file_get_contents( $tmp_log_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- ローカルの一時ログファイル読み込みのため.
		unlink( $tmp_log_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- テスト用一時ファイルの後片付けのため.
		$this->assertStringContainsString( $path, $log_contents, 'WP_DEBUG が無効でも error_log() で記録が残ること' );
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

	/**
	 * get_directory_uri() : シンボリックリンクで WordPress が配置された環境（AWS Bitnami 等）の再現テスト。
	 *
	 * __FILE__ / __DIR__ はシンボリックリンクを辿って実体のパスを返す一方、WordPress の定数
	 * （WP_PLUGIN_DIR 等）はシンボリックリンクを辿らない文字列のままのため、単純な前方一致では
	 * どの基準ディレクトリにも一致せず、URL が壊れる不具合の再現テスト（issue #13）。
	 * WordPress 本体が持つ実体パス→論理パスの対応表（$wp_plugin_paths。plugin_basename() が使っているもの）を
	 * シミュレートして渡し、正しい URL に解決されることを確認する。
	 */
	public function test_get_directory_uri_with_symlinked_plugin_paths() {
		global $wp_plugin_paths;
		// テスト終了後に元へ戻すため退避しておく.
		$original_wp_plugin_paths = $wp_plugin_paths;

		// Bitnami 環境を模したシンボリックリンクの対応（論理パス（定数ベース） => 実体パス）.
		$symlinked_plugin_dir    = wp_normalize_path( WP_PLUGIN_DIR );
		$real_plugin_dir         = '/bitnami/wordpress/wp-content/plugins';
		$symlinked_mu_plugin_dir = wp_normalize_path( WPMU_PLUGIN_DIR );
		$real_mu_plugin_dir      = '/bitnami/wordpress/wp-content/mu-plugins';

		$tests = array(
			array(
				'test_condition_name' => 'プラグイン配下がシンボリックリンク構成の場合 => plugins_url() ベースの正しい URL になる',
				'wp_plugin_paths'     => array(
					$symlinked_plugin_dir => $real_plugin_dir,
				),
				'path'                => $real_plugin_dir . '/vk-blocks-pro/vendor/vektor-inc/vk-swiper/src',
				'correct'             => plugins_url() . '/vk-blocks-pro/vendor/vektor-inc/vk-swiper/src/',
			),
			array(
				'test_condition_name' => 'mu-plugins 配下がシンボリックリンク構成の場合 => WPMU_PLUGIN_URL ベースの正しい URL になる',
				'wp_plugin_paths'     => array(
					$symlinked_mu_plugin_dir => $real_mu_plugin_dir,
				),
				'path'                => $real_mu_plugin_dir . '/vk-swiper/src',
				'correct'             => WPMU_PLUGIN_URL . '/vk-swiper/src/',
			),
			array(
				'test_condition_name' => '対応表に無関係な（今回のパスに関係しない）エントリしか無く、通常構成のパスを渡した場合 => 従来どおり解決される（既存挙動への回帰が無いこと）',
				'wp_plugin_paths'     => array(
					$symlinked_plugin_dir => $real_plugin_dir,
				),
				'path'                => WP_PLUGIN_DIR . '/vk-blocks-pro/vendor/vektor-inc/vk-swiper/src',
				'correct'             => plugins_url() . '/vk-blocks-pro/vendor/vektor-inc/vk-swiper/src/',
			),
		);

		foreach ( $tests as $case ) {
			$wp_plugin_paths = $case['wp_plugin_paths'];
			$actual          = VkSwiper::get_directory_uri( $case['path'] );
			$this->assertEquals( $case['correct'], $actual, $case['test_condition_name'] );
		}

		// グローバル変数を元に戻す.
		$wp_plugin_paths = $original_wp_plugin_paths;
	}

	/**
	 * resolve_symlinked_plugin_path() : WordPress 本体の $wp_plugin_paths
	 * （論理パス（定数ベース）=> 実体パス（realpath）の対応表）を使って、
	 * 実体パス表記のパスを論理パス表記へ変換できることを確認する。
	 */
	public function test_resolve_symlinked_plugin_path() {
		global $wp_plugin_paths;
		$original_wp_plugin_paths = $wp_plugin_paths;

		$tests = array(
			array(
				'test_condition_name' => '対応表に一致するエントリがある場合 => 実体パスの接頭辞を論理パスへ変換する',
				'wp_plugin_paths'     => array(
					'/opt/bitnami/wordpress/wp-content/plugins' => '/bitnami/wordpress/wp-content/plugins',
				),
				'path'                => '/bitnami/wordpress/wp-content/plugins/vk-blocks-pro/vendor/vektor-inc/vk-swiper/src',
				'correct'             => '/opt/bitnami/wordpress/wp-content/plugins/vk-blocks-pro/vendor/vektor-inc/vk-swiper/src',
			),
			array(
				'test_condition_name' => '対応表に複数エントリがある場合 => より長い（より具体的な）実体パスに優先的にマッチする',
				'wp_plugin_paths'     => array(
					'/opt/bitnami/wordpress/wp-content/plugins'                       => '/bitnami/wordpress/wp-content/plugins',
					'/opt/bitnami/wordpress/wp-content/plugins/vk-blocks-pro/vendor' => '/bitnami/wordpress/wp-content/plugins/vk-blocks-pro/vendor-real',
				),
				'path'                => '/bitnami/wordpress/wp-content/plugins/vk-blocks-pro/vendor-real/vektor-inc/vk-swiper/src',
				'correct'             => '/opt/bitnami/wordpress/wp-content/plugins/vk-blocks-pro/vendor/vektor-inc/vk-swiper/src',
			),
			array(
				'test_condition_name' => '対応表が空の場合 => $path をそのまま返す（境界値）',
				'wp_plugin_paths'     => array(),
				'path'                => '/bitnami/wordpress/wp-content/plugins/vk-blocks-pro/src',
				'correct'             => '/bitnami/wordpress/wp-content/plugins/vk-blocks-pro/src',
			),
			array(
				'test_condition_name' => '対応表に一致するエントリが無い場合 => $path をそのまま返す（境界値）',
				'wp_plugin_paths'     => array(
					'/opt/bitnami/wordpress/wp-content/plugins' => '/bitnami/wordpress/wp-content/plugins',
				),
				'path'                => '/var/www/other/vk-blocks-pro/src',
				'correct'             => '/var/www/other/vk-blocks-pro/src',
			),
		);

		foreach ( $tests as $case ) {
			$wp_plugin_paths = $case['wp_plugin_paths'];
			$actual          = VkSwiper::resolve_symlinked_plugin_path( $case['path'] );
			$this->assertEquals( $case['correct'], $actual, $case['test_condition_name'] );
		}

		$wp_plugin_paths = $original_wp_plugin_paths;
	}

	/**
	 * realpath_directories() : 基準ディレクトリの dir 側を realpath() で実体パスへ解決できること、
	 * 存在しないディレクトリ（realpath() が false を返すケース）は候補から除外されることを確認する。
	 */
	public function test_realpath_directories() {
		// テスト用に実体ディレクトリとそのシンボリックリンクを用意する.
		$real_dir = wp_normalize_path( sys_get_temp_dir() ) . '/vk-swiper-test-real-' . uniqid();
		mkdir( $real_dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir -- テスト用一時ディレクトリの作成.

		$symlink_dir     = wp_normalize_path( sys_get_temp_dir() ) . '/vk-swiper-test-symlink-' . uniqid();
		$symlink_created = @symlink( $real_dir, $symlink_dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- symlink() が使えない実行環境向けの判定のため.

		if ( ! $symlink_created || ! is_link( $symlink_dir ) ) {
			rmdir( $real_dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
			$this->markTestSkipped( 'このテスト環境では symlink() を作成できませんでした。' );
			return;
		}

		$nonexistent_dir = wp_normalize_path( sys_get_temp_dir() ) . '/vk-swiper-test-missing-' . uniqid();

		// 後片付け前に期待値を確定させておく.
		$expected_real_dir = wp_normalize_path( realpath( $real_dir ) );

		$directories = array(
			// 正常系1: シンボリックリンクを realpath() で実体パスへ解決できる.
			array(
				'dir' => $symlink_dir,
				'url' => 'https://example.com/symlinked',
			),
			// 正常系2: 元々シンボリックリンクでない実体ディレクトリはそのまま解決される.
			array(
				'dir' => $real_dir,
				'url' => 'https://example.com/real',
			),
			// 異常系（境界値）: 存在しないディレクトリは realpath() が false を返すため候補から除外される.
			array(
				'dir' => $nonexistent_dir,
				'url' => 'https://example.com/missing',
			),
		);

		$result = VkSwiper::realpath_directories( $directories );

		// テスト用ファイルの後片付け.
		unlink( $symlink_dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- テスト用シンボリックリンクの後片付けのため.
		rmdir( $real_dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir

		$this->assertCount( 2, $result, '存在しないディレクトリは候補から除外され、2件だけ残ること' );
		$this->assertEquals( $expected_real_dir, $result[0]['dir'], 'シンボリックリンクが realpath() で実体パスへ解決されること' );
		$this->assertEquals( 'https://example.com/symlinked', $result[0]['url'], 'url はそのまま保持されること（シンボリックリンク側）' );
		$this->assertEquals( $expected_real_dir, $result[1]['dir'], '元々実体ディレクトリだったパスは変化しないこと' );
		$this->assertEquals( 'https://example.com/real', $result[1]['url'], 'url はそのまま保持されること（実体ディレクトリ側）' );
	}

	/**
	 * match_directory_uri() : $path が基準ディレクトリの前方一致で判定され、URL に変換されること、
	 * ディレクトリ境界の誤マッチ（例: plugins と plugins-extra）が起きないことを確認する。
	 */
	public function test_match_directory_uri() {
		$directories = array(
			array(
				'dir' => '/wp-content/plugins',
				'url' => 'https://example.com/wp-content/plugins',
			),
			array(
				'dir' => '/wp-content',
				'url' => 'https://example.com/wp-content',
			),
		);

		$tests = array(
			array(
				'test_condition_name' => '基準ディレクトリ配下のパスは URL に変換される',
				'path'                => '/wp-content/plugins/sample-plugin/src',
				'correct'             => 'https://example.com/wp-content/plugins/sample-plugin/src/',
			),
			array(
				'test_condition_name' => '基準ディレクトリそのものを渡した場合も URL に変換される',
				'path'                => '/wp-content/plugins',
				'correct'             => 'https://example.com/wp-content/plugins/',
			),
			array(
				'test_condition_name' => '境界判定: /wp-content/plugins-extra は /wp-content/plugins に誤マッチせず、より広い /wp-content にマッチする',
				'path'                => '/wp-content/plugins-extra/sample-plugin/src',
				'correct'             => 'https://example.com/wp-content/plugins-extra/sample-plugin/src/',
			),
			array(
				'test_condition_name' => 'どの基準ディレクトリにも一致しない場合は空文字を返す（境界値）',
				'path'                => '/opt/custom/path',
				'correct'             => '',
			),
		);

		foreach ( $tests as $case ) {
			$actual = VkSwiper::match_directory_uri( $case['path'], $directories );
			$this->assertEquals( $case['correct'], $actual, $case['test_condition_name'] );
		}
	}
}
