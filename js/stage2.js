function stageTwo(pte){
	module("Stage 2");
	test("All/None javascript", function(){
		expect(4);
		var $menu = $('#pte-stage2-selectors');
		var $verify = $('.pte-confirm');
		ok($verify.size() > 0, "Thumbnails present");
		equal($verify.not(':checked').size(), 0, "All sizes should be checked");
		$menu.find('.none').click();
		stop();
		window.setTimeout(function(){
			equal($('.pte-confirm').filter(':checked').size(), 0, "No sizes should be checked");
			$menu.find('.all').click();
			window.setTimeout(function(){
				equal($verify.not(':checked').size(), 0, "All sizes should be checked");
				start();
			}, 200 );
		}, 200 );
	});

	// Test the submit button
	test("Submit button",function(){
		//expect(2);
		var $submit = $('#pte-confirm');
		ok($submit.is(':enabled'), "Submit button should be enabled"); 
		$('#pte-stage2-selectors').find('.none').click();
		//window.setTimeout(function(){
		//   ok($submit.is(':disabled'), "Submit button shouldn't be enabled");
		//   start(); 
		//}, 500 );
	});

	asyncTest("Test going back", function(){
		$('.stage-navigation').click();
		window.setTimeout(function(){
			ok($('#stage2').children().size() == 0, "Stage 2 unloaded");
			start();
			//window.setTimeout( function(){ stageOne(pte);}, 100 );
		}, 5000);
	});
}
