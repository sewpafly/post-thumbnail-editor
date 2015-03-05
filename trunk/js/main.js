require({
  paths: {
    'angular':          '../apps/angular/angular.min',
    'angular-resource': '../apps/angular/angular-resource.min',
    'cs':               '../apps/requirejs/cs',
    'coffee-script':    '../apps/coffee-script',
    'jcrop':            '../apps/jcrop/js/jquery.Jcrop.min',
 },
 shim: {
    'angular': {
       exports: 'angular'
    },
    'angular-resource': {
       exports: 'angular',
       deps: ['angular']
    },
    'jcrop':['cs!jquery']
 }
}, ['cs!csmain']);
