module.exports = function(grunt) {

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        copy: {
            // templates: {
            //     files: [{
            //         expand: true,
            //         flatten: true,
            //         src: ['src/templates/**/*.html'],
            //         dest: 'dist/templates',
            //         filter: 'isFile'
            //     }]
            // },

            images: {
                files: [{
                    expand: true,
                    flatten: true,
                    src: ['./src/images/*'],
                    dest: './public/images',
                    filter: 'isFile'
                }]
            }
        },

        less: {
            development: {
                options: {
                    compress: true,
                    yuicompress: true,
                    optimization: 2
                },

                files: {
                    "./public/css/main.css": "./src/less/main.less"
                }
            }

        },

        concat: {
            options: {
                separator: '\n'
            },

            dist: {
                src: [
                    './node_modules/jquery/dist/jquery.js',
                    './node_modules/underscore/underscore.js',
                    './node_modules/backbone/backbone.js',

                    './src/scripts/main.js',
                    './src/scripts/helpers/**/*.js',
                    './src/scripts/routers/**/*.js',
                    './src/scripts/views/**/*.js',
                    './src/scripts/application-controller.js',

                    './src/scripts/**/*.js'
                ],

                dest: './public/js/main.min.js'
            }
        },

        uglify: {
            options: {
                banner: '/*! <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd") %> */\n'
            },

            build: {
                src: './public/js/main.min.js',
                dest: './public/js/main.min.js'
            }
        },

        watch: {
            files: ['Gruntfile.js', './src/scripts/**/*.js', 'src/less/**/*.less', 'src/templates/**/*.html'],
            tasks: ['less', 'concat', 'copy']
        }
    });

    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-less');
    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-contrib-copy');

    grunt.registerTask('dev', ['less', 'concat', 'copy', 'watch']);

};