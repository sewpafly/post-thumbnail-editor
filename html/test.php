<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" 
						  "http://www.w3.org/TR/html4/loose.dtd">
<html>
	<head>
		<title>QUnit Tests</title>
		<script src="http://code.jquery.com/jquery-latest.js"></script>
		<link rel="stylesheet" href="http://code.jquery.com/qunit/git/qunit.css" type="text/css" media="screen" />
		<script type="text/javascript" src="http://code.jquery.com/qunit/git/qunit.js"></script>

		<script type="text/javascript">
			//set jquery to no conflict, so we do not have a problem with the version from in the page
		var $$ = jQuery.noConflict(true);
		var $ = jQuery = null; //we will be using the normal jquery vars soon enough

</script>

<script type="text/javascript" src="<?php echo( PTE_PLUGINURL . "js/stage2.js" ); ?>"></script>
<script type="text/javascript" src="<?php echo( PTE_PLUGINURL . "js/stage1.js" ); ?>"></script>


</head>
<body>
	<h1 id="qunit-header">QUnit Tests</h1>

	<div style="width: 100%; height: 550px; float: left">
		<iframe style="width: 100%; height: 100%;" id="pteFrame"></iframe>
	</div>

	<h2 id="qunit-banner"></h2>
	<div id="qunit-testrunner-toolbar"></div>
	<h2 id="qunit-userAgent"></h2>
	<ol id="qunit-tests"></ol>
	<div id="qunit-fixture"></div>
</body>
<script>
	$$('#pteFrame').load(function(){
		udf = function(a){ return a; }();
		//grab jQuery from inside the document
		$ = jQuery = window.frames[0].jQuery;

		//turn off async so tests will wait for ajax results
		//$.ajaxSetup({ async: false });

		//turn off animations so they do not bork tests
		//$.fx.off = true;

		//test("a basic test example", function() {
			//	ok( true, "this test is fine" );
			//	var value = "hello";
			//	equal( value, "hello", "We expect value to be hello" );
			//});
		var pte = window.frames[0].pte;
		stageOne(pte);
		/* TODO: Regression Going back and forth between stages 1 & 2 */
		// Call stage2
		// Redo tests for stage1 and 2
	});

	$$('#pteFrame').attr('src', '<?php echo( $testurl ); ?>');

</script>

</html>
