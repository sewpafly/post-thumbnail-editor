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

    name: '../apps/requirejs/almond',
    wrap: true,
    include: [
       'main',
       'cs!apps/pteApp',
       'cs!controllers/PteCtrl',
       'cs!controllers/TableCtrl',
       'cs!controllers/CropCtrl',
       'cs!controllers/ViewCtrl'
    ],
    insertRequire: ['main'],
    exclude: ['coffee-script']
})
