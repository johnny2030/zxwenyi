module.exports = function(grunt) {
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),

    typescript: {
      build: {
        src: ["./ts/main.ts", "./ts/**/*.ts"],
        option: {
          module: 'amd', //or commonjs
          target: 'es5', //or es3
          sourceMap: true,
          declaration: false
        },
        dest: "./temp/main.js"
      },
    },
    ngtemplates: {
      app: {
        src: ["./ts/**/*.tpl.html"],
        dest: "./temp/myAppHTMLCache.js",
        options: {
          module: 'RongWebIMWidget', //name of our app
          htmlmin: {
            collapseBooleanAttributes: true,
            collapseWhitespace: true,
            removeAttributeQuotes: true,
            removeComments: true,
            removeEmptyAttributes: true,
            removeRedundantAttributes: true,
            removeScriptTypeAttributes: true,
            removeStyleLinkTypeAttributes: true
          }
        }
      }
    },
    concat: {
      build: {
        src: ['./temp/main.js','./temp/myAppHTMLCache.js'],
        dest: './js/RongIMWidget-h5.js'
      }
    },
    clean: {
      temp:{
        src:['./temp']
      }
    },
    connect: {
      demo: {
        options: {
          port: 2014,
          hostname: '*',
          open: true,
          keepalive: true,
          base: ['./']
        }
      }
    },
    watch: {
      demo: {
        options: {
          spawn: false
        },
        files: ["./ts/**", './css/**'],
        tasks: ["build"]
      }
    },
    uglify:{
      release:{
        src:'./js/RongIMWidget-h5.js',
        dest:'./js/RongIMWidget-h5.min.js'
      }
    },
    cssmin:{
      release:{
        src:'./css/RongIMWidget-h5.css',
        dest:'./css/RongIMWidget-h5.min.css',
      }
    }
  });

  grunt.loadNpmTasks('grunt-contrib-copy');
  grunt.loadNpmTasks("grunt-contrib-clean");
  grunt.loadNpmTasks('grunt-typescript');
  grunt.loadNpmTasks('grunt-contrib-connect');
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-contrib-concat');
  grunt.loadNpmTasks('grunt-angular-templates');
  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks('grunt-contrib-cssmin');

  grunt.registerTask("build", ['clean','typescript','ngtemplates','concat']);

  grunt.registerTask('release', ['build', 'uglify', 'cssmin'])

}
