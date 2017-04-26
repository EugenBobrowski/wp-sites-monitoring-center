module.exports = function (grunt) {
	grunt.initConfig({
		less: {
			development: {
				options: {
					compress: false,
					yuicompress: true,
					optimization: 2
					},
				files: {
					"css/style.css": "css/style.less"
				}
			}
		},
		watch: {
			styles: {
				files: ['css/**/*.less'], // which files to watch
				tasks: ['less'],
				options: {
					nospawn: true
				}
			}
		}
	});

	grunt.loadNpmTasks('grunt-contrib-less');
	grunt.loadNpmTasks('grunt-contrib-watch');

	grunt.registerTask('default', ['watch']);
};