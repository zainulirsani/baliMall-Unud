const gulp = require('gulp');
const clean = require('del');
const bundle = require('gulp-bundle-assets');
const babel = require('gulp-babel')
const concat = require('gulp-concat');
const uglify = require('gulp-uglify');
const replace = require('gulp-replace');

gulp.task('clean', function() {
  return clean(['public/dist/**/*']);
});

gulp.task('copy:fonts', function() {
  return gulp
    .src(['./assets/fonts/**/*'])
    .pipe(gulp.dest('./public/dist/fonts'));
});

gulp.task('copy:webfonts', function() {
  return gulp
    .src(['./public/balimall/webfonts/*'])
    .pipe(gulp.dest('./public/dist/webfonts'));
});

gulp.task('copy:images', function() {
  return gulp
    .src([
      './assets/img/**/*',
      './public/balimall/img/*'
    ])
    .pipe(gulp.dest('./public/dist/img'));
});

gulp.task('copy:others', function() {
  return gulp
    .src(['./assets/others/**/*'])
    .pipe(gulp.dest('./public/dist/compiled'));
});

gulp.task('build', [
  'clean',
  'copy:fonts',
  'copy:webfonts',
  'copy:images',
  'copy:others'
], function() {
  return gulp
    .src('./bundle.config.js')
    .pipe(bundle())
    .pipe(replace(/@(\d+\.\d+\.\d+)/g, '')) // Hapus format seperti "@1.2.3"
    .pipe(replace(/v(\d+\.\d+\.\d+)/g, '')) // Hapus format seperti "v1.2.3"
    .pipe(gulp.dest('./public/dist/compiled'));
});

gulp.task('compile', function() {

  gulp
    .src('./assets/js/site/user_store_register.js')
    .pipe(babel({
      "presets": [["@babel/preset-env", {
        "targets": ">0.25%"
      }]]
    }))
    .pipe(replace(/@(\d+\.\d+\.\d+)/g, '')) // Hapus format seperti "@1.2.3"
    .pipe(replace(/v(\d+\.\d+\.\d+)/g, '')) // Hapus format seperti "v1.2.3"
    .pipe(uglify())
    .pipe(gulp.dest('./public/dist/compiled'))

  return gulp
    .src('./bundle.config.js')
    .pipe(bundle())
    .pipe(gulp.dest('./public/dist/compiled'))
});

gulp.task('watch', function() {
  gulp.watch([
    './assets/css/**/*',
    './assets/js/**/*',
    './public/balimall/css/*',
    './public/balimall/js/*'
  ], ['compile']);
});
