<?php
/**
 * VK Swiper
 *
 * @package vektor-inc/vk-swiper
 * @license GPL-2.0+
 *
 * @version 0.3.6
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
	 * ファイルパスを URL へ変換する
	 *
	 * AWS Bitnami 等、シンボリックリンクで WordPress が配置された環境では、
	 * 引数 $path（呼び出し元は __FILE__ / __DIR__ ベース。シンボリックリンクを辿って実体パスを返す）と
	 * WP_PLUGIN_DIR 等の WordPress の定数（シンボリックリンクを辿らない文字列のまま）の表記が食い違い、
	 * 単純な前方一致では基準ディレクトリのどれにも一致しないことがある（issue #13）。
	 * そのため、次の3段階で解決を試みる。
	 * 1. WordPress 本体の対応表（$wp_plugin_paths）による変換を先に試す。
	 * 2. それでも一致しなければ、基準ディレクトリ側を realpath() で実体パスへ変換して再度突き合わせる。
	 * 3. それでも解決できなければ、空文字（ドメイン直結の壊れた URL の原因になる）を返さず、
	 *    content_url() を最後の手段として返す（詳細は各処理のコメントを参照）。
	 *
	 * @param string $path 変換対象のファイルパス.
	 * @return string 変換後の URL（末尾スラッシュ付き）.
	 */
	public static function get_directory_uri( $path ) {

		// PATH を正規化.
		$path = wp_normalize_path( $path );

		// WP_PLUGIN_DIR / WPMU_PLUGIN_DIR / テーマルート / WP_CONTENT_DIR は
		// それぞれ独立にカスタマイズ可能なため、より具体的なディレクトリから順にマッチさせて URL を生成する.
		// 配列のキーにディレクトリを使うと、カスタマイズでキーが重複した際に後勝ちで上書きされてしまうため、
		// dir / url の組の配列にしている.
		$directories = array(
			array(
				'dir' => wp_normalize_path( WP_PLUGIN_DIR ),
				'url' => plugins_url(),
			),
			array(
				'dir' => wp_normalize_path( WPMU_PLUGIN_DIR ),
				'url' => WPMU_PLUGIN_URL,
			),
			array(
				'dir' => wp_normalize_path( get_theme_root() ),
				'url' => get_theme_root_uri(),
			),
			array(
				'dir' => wp_normalize_path( WP_CONTENT_DIR ),
				'url' => content_url(),
			),
		);

		// (1) WordPress 本体が持つシンボリックリンク対応表（plugin_basename() が内部で使っているもの）による変換を先に試す.
		$resolved_path = self::resolve_symlinked_plugin_path( $path );
		$uri           = self::match_directory_uri( $resolved_path, $directories );

		// (2) (1) で一致しなかった場合、基準ディレクトリ側を realpath() で実体パスへ解決してから、
		// 変換前の $path（シンボリックリンクを辿った実体パスのまま）と改めて突き合わせる.
		if ( '' === $uri ) {
			$uri = self::match_directory_uri( $path, self::realpath_directories( $directories ) );
		}

		if ( '' !== $uri ) {
			return $uri;
		}

		// (3) (1)(2) のいずれでも解決できなかったときの最後の手段.
		// 空文字を返すと、呼び出し元 register_swiper() で 'assets/css/...' という相対パスになり、
		// wp_register_style() 等がサイト URL（末尾スラッシュ無し）を前置してドメインと直結した壊れた URL になる（issue #13）。
		// content_url() はファイルシステムとの突き合わせを行わない純粋な URL 生成関数のため、
		// このケースでも安全にサイト内の絶対 URL を返せる。実際に Swiper が置かれたディレクトリと
		// 一致しない可能性はある（読み込むファイルが 404 になりうる）が、ドメイン直結の壊れた URL は避けられる。
		//
		// あわせて、WP_DEBUG が無効な本番環境でも原因に気づけるよう、error_log() で
		// サーバーのエラーログへ常に記録を残す（従来の trigger_error() は WP_DEBUG が有効なときしか
		// 通知が出ず、本番環境では手がかりが何も残らなかったため）。
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- 本番環境でも原因追跡できるよう意図的に使用している.
		error_log( sprintf( 'VK Swiper: Could not resolve path to URL: %s', $path ) );

		// WP_DEBUG 有効時は、開発中にも気づけるよう画面向けの通知もあわせて出す.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// translators: %s is the file path that could not be resolved to a URL.
			trigger_error( sprintf( esc_html__( 'VK Swiper: Could not resolve path to URL: %s', 'vk-swiper' ), esc_html( $path ) ), E_USER_NOTICE );
		}

		return trailingslashit( content_url() );
	}

	/**
	 * シンボリックリンク対応の変換
	 *
	 * WordPress 本体が持つグローバル変数 $wp_plugin_paths（論理パス（定数ベース）→実体パス（realpath）の
	 * 対応表。plugin_basename() が内部で使っているもの）を使って、実体パス表記の $path を
	 * 論理パス（定数ベース）表記へ変換する。対応表に一致するエントリが無ければ $path をそのまま返す。
	 *
	 * この対応表を直接参照し plugin_basename() 自体は呼び出していない。plugin_basename() は
	 * WP_PLUGIN_DIR / WPMU_PLUGIN_DIR からの相対パス（basename）へ変換してしまうため、
	 * テーマ配下・wp-content 直下など他の基準ディレクトリの判定に使い回せなくなるためである。
	 * 対応表の構造・突き合わせ方（値が長いものを優先する arsort）は plugin_basename() の実装に合わせている。
	 *
	 * @param string $path 変換対象のパス（wp_normalize_path 済み）.
	 * @return string 変換後のパス（対応表に一致しなければ $path のまま）.
	 */
	public static function resolve_symlinked_plugin_path( $path ) {
		global $wp_plugin_paths;

		if ( empty( $wp_plugin_paths ) || ! is_array( $wp_plugin_paths ) ) {
			return $path;
		}

		// 値（実体パス）が長いものから優先的に評価する。plugin_basename() と同じ arsort().
		$plugin_paths = $wp_plugin_paths;
		arsort( $plugin_paths );

		foreach ( $plugin_paths as $dir => $realdir ) {
			// $dir = 論理パス（定数ベース）, $realdir = 実体パス（realpath）.
			$realdir = wp_normalize_path( $realdir );
			if ( 0 === strpos( $path, $realdir ) ) {
				return wp_normalize_path( $dir ) . substr( $path, strlen( $realdir ) );
			}
		}

		return $path;
	}

	/**
	 * 基準ディレクトリの配列（dir/url の組）を、dir 側を realpath() で実体パスへ解決した配列に変換する
	 *
	 * realpath() は存在しないパスに false を返すため、その場合はマッチ対象から除外する
	 * （false のまま突き合わせに使うと strpos() 等で意図しない挙動になるため）。
	 *
	 * @param array $directories dir/url の組の配列.
	 * @return array realpath 解決後の dir/url の組の配列（解決できなかったものは除外済み）.
	 */
	public static function realpath_directories( $directories ) {
		$resolved = array();
		foreach ( $directories as $directory ) {
			$real_dir = realpath( $directory['dir'] );
			if ( false === $real_dir ) {
				continue;
			}
			$resolved[] = array(
				'dir' => wp_normalize_path( $real_dir ),
				'url' => $directory['url'],
			);
		}
		return $resolved;
	}

	/**
	 * $path が $directories（dir/url の組の配列）のいずれかの dir と前方一致するか調べ、
	 * 一致したら URL を組み立てて返す。一致しなければ空文字を返す
	 *
	 * @param string $path 判定対象のパス.
	 * @param array  $directories dir/url の組の配列.
	 * @return string 一致した場合は URL（末尾スラッシュ付き）、しなければ空文字.
	 */
	public static function match_directory_uri( $path, $directories ) {
		foreach ( $directories as $directory ) {
			if ( empty( $directory['dir'] ) ) {
				continue;
			}
			// ディレクトリ境界を正しく判定するため、末尾にスラッシュを付与して比較する.
			// 例: /wp-content/plugins が /wp-content/plugins-extra に誤マッチしないようにする.
			$dir_with_slash = rtrim( $directory['dir'], '/' ) . '/';
			if ( strpos( $path, $dir_with_slash ) === 0 || $path === $directory['dir'] ) {
				$relative_path = ltrim( substr( $path, strlen( $directory['dir'] ) ), '/' );
				return trailingslashit( $directory['url'] ) . ( '' === $relative_path ? '' : $relative_path . '/' );
			}
		}
		return '';
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
