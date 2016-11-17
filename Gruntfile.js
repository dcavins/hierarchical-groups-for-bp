'use strict';
module.exports = function(grunt) {

	// load all grunt tasks matching the `grunt-*` pattern
	require('load-grunt-tasks')(grunt);

	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		// Watch for changes and trigger less, jshint, uglify and livereload
		watch: {
			options: {
				livereload: true
			},
			scripts: {
				files: ['public/js/src/*.js','admin/js/src/*.js'],
				tasks: ['jshint', 'uglify']
			},
			styles: {
				files: ['public/less/*.less'],
				tasks: ['less:convertcss', 'postcss']
			}
		},

		less: {
			convertcss: {
				files: {
					'public/css/public.css': 'public/less/public.less',
				}
			}
		},

		// PostCSS handles post-processing on CSS files. Used here to autoprefix and minify.
		postcss: {
			options: {
				map: {
					inline: false, // save all sourcemaps as separate files...
					annotation: 'public/css/' // ...to the specified directory
				},
				processors: [
					require('autoprefixer')(),
					require('cssnano')
				]
			},
			dist: {
				src: 'public/css/*.css'
			}
		},

		// JavaScript linting with jshint
		jshint: {
			all: [
				'public/js/src/*.js',
				'admin/js/src/*.js'
				]
		},

		// Uglify to concat, minify, and make source maps
		uglify: {
			options: {
				banner: '/*! <%= pkg.name %> - v<%= pkg.version %> - ' +
						'<%= grunt.template.today("yyyy-mm-dd") %> */'
			},
			common: {
				files: {
					'public/js/public.min.js': ['public/js/src/*.js'],
					'admin/js/public.min.js': ['admin/js/src/*.js']
				}
			}
		},

		// Image optimization
		imagemin: {
			dist: {
				options: {
					optimizationLevel: 7,
					progressive: true,
					interlaced: true
				},
				files: [{
					expand: true,
					cwd: 'public/images/',
					src: ['**/*.{png,jpg,gif}'],
					dest: 'public/images/'
				}]
			}
		}

	});

	// Register tasks
	// Typical run, cleans up css and js, starts a watch task.
	grunt.registerTask('default', ['less:convertcss', 'postcss', 'jshint', 'uglify:common', 'watch']);

	// Before releasing a build, do above plus minimize all images.
	grunt.registerTask('build', ['less:convertcss', 'postcss',  'jshint', 'uglify:common', 'imagemin']);

};