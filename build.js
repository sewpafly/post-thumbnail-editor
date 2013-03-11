({
    //appDir: '.',
    baseUrl: 'js',

    //Uncomment to turn off uglify minification.
    //optimize: 'none',
    out: "js-build/main.js",

    //Stub out the cs module after a build since
    //it will not be needed.
    stubModules: ['cs'],

    mainConfigFile: 'js/main.js',

    name: 'main',
    exclude: ['coffee-script']
})
