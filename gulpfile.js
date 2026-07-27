
/*-------------------------------------*/
/*  Copyright
/*-------------------------------------*/
/*  Font
/*-------------------------------------*/
/*  media posts
/*-------------------------------------*/
/*  term color
/*-------------------------------------*/
/*  Font Awesome
/*-------------------------------------*/
/*  vk-mobile-nav
/*-------------------------------------*/
/*  vk-mobile-fix-nav
/*-------------------------------------*/
/*  page-header
/*-------------------------------------*/


var gulp = require('gulp');
var fs = require('fs');
var Transform = require('stream').Transform;

/**
 * VK Swiper
 */

// ソースマップ参照コメントの目印。
var SOURCE_MAP_MARKER = Buffer.from('//# sourceMappingURL=');

// swiper-bundle.min.js の末尾にあるソースマップ参照コメントを除去するストリーム。
// .map ファイルは src/assets/ にコピーしていないため、
// 参照を残したままだとブラウザの開発者ツールで 404 が発生する。
function remove_source_map_comment() {
  return new Transform({
    objectMode: true,
    transform: function (file, encoding, callback) {
      // ストリーム（isStream）やディレクトリは対象外。
      if (!file.isBuffer()) {
        callback(null, file);
        return;
      }

      var contents = file.contents;
      var index = contents.lastIndexOf(SOURCE_MAP_MARKER);

      // 参照コメントが無ければそのまま通す。
      if (index === -1) {
        callback(null, file);
        return;
      }

      // 最終行に無い（後続に改行がある）場合は、コード中の文字列等の可能性があるため触らない。
      if (contents.indexOf(0x0a, index) !== -1) {
        callback(null, file);
        return;
      }

      // 参照コメント本体と、その直前の改行をまとめて落とす。
      var end = index;
      while (end > 0 && (contents[end - 1] === 0x0a || contents[end - 1] === 0x0d)) {
        end--;
      }
      file.contents = contents.subarray(0, end);

      callback(null, file);
    },
  });
}

function copy_swiper_css() {
  return gulp.src('./node_modules/swiper/swiper-bundle.min.css', { encoding: false })
      .pipe(remove_source_map_comment())
      .pipe(gulp.dest('./src/assets/css/'));
}

function copy_swiper_js() {
  return gulp.src('./node_modules/swiper/swiper-bundle.min.js', { encoding: false })
      .pipe(remove_source_map_comment())
      .pipe(gulp.dest('./src/assets/js/'));
}

// src/VkSwiper.php の SWIPER_VERSION を、実際にインストールされている Swiper のバージョンに揃える。
// package.json は "^14.0.6" のような範囲指定のため、
// 手書きの定数のままだと npm run update で入った新しいバージョンと乖離し、
// enqueue 時のキャッシュバスター（ver= クエリ）が機能しなくなる。
function sync_swiper_version(done) {
  var swiper_package_path = './node_modules/swiper/package.json';
  var vk_swiper_path = './src/VkSwiper.php';

  // Swiper 未インストール時は、原因が分かる形で失敗させる。
  if (!fs.existsSync(swiper_package_path)) {
    done(new Error(swiper_package_path + ' が見つかりません。npm install を実行してください。'));
    return;
  }

  var swiper_version = JSON.parse(fs.readFileSync(swiper_package_path, 'utf8')).version;
  var source = fs.readFileSync(vk_swiper_path, 'utf8');
  var pattern = /(const SWIPER_VERSION = ')[^']*(';)/;

  // 定数の定義が見つからない場合は、黙って素通りさせず失敗させる。
  if (!pattern.test(source)) {
    done(new Error(vk_swiper_path + ' に SWIPER_VERSION の定義が見つかりません。'));
    return;
  }

  var updated = source.replace(pattern, '$1' + swiper_version + '$2');

  // 差分がある時だけ書き込み、不要なファイル更新を避ける。
  if (updated !== source) {
    fs.writeFileSync(vk_swiper_path, updated);
    console.log('SWIPER_VERSION を ' + swiper_version + ' に更新しました。');
  }

  done();
}

gulp.task('copy_swiper', gulp.parallel(copy_swiper_css, copy_swiper_js, sync_swiper_version));
