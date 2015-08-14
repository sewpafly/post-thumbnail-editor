<?php

/**
 * Class PteTestHooker extends PTE_Hooker
 * @author Brent Nef
 */
class PteTestHooker extends PTE_Hooker
{
	protected $arg_keys = array(
		'c' => array('c_arg'),
	);

	function a()
	{
		return 'a';
	}

	function b($param)
	{
		return $param;
	}

	function c($c)
	{
		return array('c_arg' => 'c', 'value' => $c);
	}
}

class PTEHookerTest extends WP_UnitTestCase {

	function test_simple_hook() {
		$hooker = new PteTestHooker();
		add_filter('a_hook_test', array($hooker, 'a_hook'));
		$this->assertEquals( 'a', apply_filters('a_hook_test','') );
	}

	function test_hook_with_args() {
		$hooker = new PteTestHooker();
		add_filter('b_hook_test', array($hooker, 'b_hook'), 10, 2);
		$return_value = 'test';
		$this->assertEquals( $return_value, apply_filters('b_hook_test','', $return_value) );
	}

	function test_ahook()
	{
		$hooker = new PteTestHooker();
		add_filter('c_ahook_test', array($hooker, 'c_ahook'), 10, 2);
		$return_value = apply_filters('c_ahook_test', array('initial' => 0, 'c_arg' => ''));
		//fwrite(STDERR, "\n".var_dump($return_value));
		$this->assertArrayHasKey('initial', $return_value);
		$this->assertArrayHasKey('value', $return_value);
		$this->assertArrayHasKey('c_arg', $return_value);
		$this->assertEquals(0, $return_value['initial']);
		$this->assertEquals('', $return_value['value']);
		$this->assertEquals('c', $return_value['c_arg']);
	}

	function test_invalid_argument_key()
	{
		$hooker = new PteTestHooker();
		add_filter('c_ahook_test', array($hooker, 'c_ahook'), 10, 2);

		try {
			$return_value = apply_filters('c_ahook_test', array());
			//fwrite(STDERR, "\n".var_dump($return_value));
		} catch (InvalidArgumentException $e) {
			$this->assertContains('Key not found', $e->getMessage());
			return;
		}
		$this->assertTrue(FALSE, 'No InvalidArgumentException thrown');
	}

	function test_invalid_argument()
	{
		$hooker = new PteTestHooker();
		add_filter('c_ahook_test', array($hooker, 'c_ahook'), 10, 2);

		try {
			$return_value = apply_filters('c_ahook_test', '');
			//fwrite(STDERR, "\n".var_dump($return_value));
		} catch (InvalidArgumentException $e) {
			$this->assertContains('invalid argument', $e->getMessage());
			//fwrite(STDERR, "\n".var_dump($e));
			return;
		}
		$this->assertTrue(FALSE, 'No InvalidArgumentException thrown');
	}

}

