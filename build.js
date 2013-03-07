({
    //appDir: '.',
    baseUrl: 'js',

    //Uncomment to turn off uglify minification.
    optimize: 'none',
    //dir: 'js-build',
    out: "js-build/main.js",

    //Stub out the cs module after a build since
    //it will not be needed.
    stubModules: ['cs'],

    mainConfigFile: 'js/main.js',
    //paths: {
    //    'angular': '../../angular.min',
    //    'angular-resource': '../../angular-resource.min',
    //    'cs' :'../../cs',
    //    'coffee-script': '../../coffee-script'
    //},

    name: 'main',
    exclude: ['coffee-script']
    //modules: [
    //    {
    //        name: 'main',
    //        //The optimization will load CoffeeScript to convert
    //        //the CoffeeScript files to plain JS. Use the exclude
    //        //directive so that the coffee-script module is not included
    //        //in the built file.
    //        exclude: ['coffee-script']
    //        //exclude: ['coffee-script','angular', 'angular-resource']
    //    }
    //]
})
