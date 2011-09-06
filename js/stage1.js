function stageOne(pte){
	module("Stage 1");
	test("All/None javascript", function() {
		$menu = $('#pte-selectors');
		$menu.find('.all').click();
		equal($('.pte-size').not(':checked').size(), 0, "All sizes should be clicked");
		$menu.find('.none').click();
		equal($('.pte-size').filter(':checked').size(), 0, "No sizes should be clicked");
	});

	test("DetermineAspectRatio", function(){
		ok(pte.functions.determineAspectRatio, "determineAspectRatio function exists");
		equal(pte.functions.determineAspectRatio(null, {crop: 1, width: 10, height: 10}),
			"1:1", 
			"10x10");
		equal(pte.functions.determineAspectRatio(null, {crop: 1, width: 20, height: 10}),
			"2:1", 
			"20x10");
		equal(pte.functions.determineAspectRatio(null, {crop: 0, width: 20, height: 10}),
			null, 
			"20x10 - crop set to 0");
		equal(pte.functions.determineAspectRatio("20:1", {crop: 0, width: 20, height: 10}),
			'20:1',
			"20x10 - crop set to 0, previous AR set");
		equal(pte.functions.determineAspectRatio("2:1", {crop: 1, width: 20, height: 10}),
			"2:1", 
			"20x10 - previous AR set to 2:1");
		raises(function(){ pte.functions.determineAspectRatio("20:1", {crop: 1, width: 20, height: 10});},
			"20x10 - previous AR set to something different");
	});

	test("ImgAreaSelect", function(){
		ok(pte.ias, "ImgAreaSelect plugin activated");
		ok(pte.functions.iasSetAR, "iasSetAR function exists");
		getImgAreaSelect = function(){ return pte.ias.getOptions()['aspectRatio']; };
		equal(getImgAreaSelect(), udf, "No aspect ratio should be set");
		pte.functions.iasSetAR("1:1");
		equal(getImgAreaSelect(), "1:1", "aspect ratio should be set");
	});

	test("Submit button",function(){
		$submit = $('#pte-submit');
		ok($submit.is(':disabled'), "Submit button shouldn't be enabled");
		$('#pte-selectors').find('.all').click();
		ok($submit.is(':disabled'), "Submit button shouldn't be enabled");
		//pte.functions.iasSetAR("1:1");
		// Select the whole image
		var $img = $('#pte-preview');
		//pte.ias.setSelection(0,0,$img.width(), $img.height());
		pte.ias.setOptions({ x1: 0
			, x2: $img.width() 
			, y1: 0
			, y2: $img.height()
			, show: true 
		});
		pte.ias.update();
		stop();
		window.setTimeout( function(){ 
			ok($submit.is(':enabled'), "Submit button should be enabled"); 
			$('#pte-selectors').find('.none').click();
			window.setTimeout( function(){ 
				ok($submit.is(':disabled'), "Submit button shouldn't be enabled");
				start(); 
			}, 500 );
		}, 500 );
	});

	test("Go to Stage 2", function(){
		stop();
		$('#pte-selectors').find('.all').click();
		$('#pte-submit').click()
		window.setTimeout(function(){
			ok($('#stage2').children().size() > 0, "Stage 2 loaded");
			start();
			window.setTimeout( function(){ stageTwo(pte);}, 100 );
		}, 30000);
	});
}
