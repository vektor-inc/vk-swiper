
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

/**
 * VK Swiper
 */
function copy_swiper_css() {
  return gulp.src('./node_modules/swiper/swiper-bundle.min.css', { encoding: false })
      .pipe(gulp.dest('./src/assets/css/'));
}

function copy_swiper_js() {
  return gulp.src('./node_modules/swiper/swiper-bundle.min.js', { encoding: false })
      .pipe(gulp.dest('./src/assets/js/'));
}

gulp.task('copy_swiper', gulp.parallel(copy_swiper_css, copy_swiper_js));
