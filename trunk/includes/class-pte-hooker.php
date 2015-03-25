<?php

/**
 * Class PTE_Hooker
 *
 * This class defines a catch all function to overload function calls to any
 * object that extends a hooker.  If you pass the object to wordpress'
 * add_filter, but don't really care about the default value, this will change
 * the value from my_function_hook to my_function, allowing you to keep code
 * concise and pithy without making wrapper methods.
 *
 * For example, When getting the client url you call the `pte_client_url` hook
 * which the loader class defines the filter as `PTE_Client::url_hook`. This
 * method doesn't exist as the default url isn't needed in determining the value
 * of the url, so we throw it away and simply call `PTE_Client::url` instead.
 *
 * @author sewpafly
 */
class PTE_Hooker {
	/**
	 * arg_keys array holds a mapping from hook names to keys that it should
	 * filter
	 *
	 * @var array $arg_keys
	 */
	protected $arg_keys = array();

	/**
	 * Look for a function call that ends with `_hook` and attempt to call the
	 * regular function call without the first default argument and return the
	 * result.
	 *
	 * Throw a bad method call exception otherwise
	 *
	 * @return void
	 */
	public function __call ( $name, $arguments ) {

		if ( preg_match( '/(?P<hook>.*)_ahook$/', $name, $m ) ) {

			/**
			 * Cherry pick the values from $arguments[1], and merge with
			 * arguments[0]
			 */
			$results = $arguments[0];
			$args = $this->filter($results, $this->get_arg_keys( $m['hook'] ) );

			return array_merge(
				$results, 
				call_user_func_array( array( $this, $m['hook'] ), $args )
		   	);

		}
		else if ( preg_match( '/(.*)_hook$/', $name, $matches ) ) {

			array_shift( $arguments );
			return call_user_func_array( array( $this, $matches[1] ), $arguments);

		}
		return null;
	}

	/**
	 * Look in property $arg_keys and return $arg_keys['hook'], else throw
	 * exception
	 *
	 * @since 3.0.0
	 *
	 * @param string  $hook  The hook function being called, should map to a
	 *                       list of values on $arg_keys.
	 *
	 * @return array keys
	 */
	private function get_arg_keys ( $hook ) {
		 
		if ( ! isset ( $this->arg_keys[$hook] ) ) {
			throw new Exception( sprintf(
				__( 'Unable to find keys for hook: %s', 'post-thumbnail-editor' ),
				$hook
			) );
		}
		return $this->arg_keys[$hook];

	}
	
	/**
	 * Filter and return an array such that it contains only the values from the
	 * array of keys passed in, by the second parameter.  They should be in the
	 * same order as the keys.
	 *
	 * @since 3.0.0
	 * @throws Exception if a key can't be found in the filter array
	 *
	 * @param array  $array_to_filter
	 * @param array  $array_keys
	 *
	 * @return array elements filtered by keys
	 */
	private function filter ( $array_to_filter, $array_keys ) {

		if ( ! is_array( $array_to_filter ) ) {
			throw new Exception( __( 'invalid argument, array expected', 'post-thumbnail-editor' ) );
		}

		if ( ! is_array( $array_keys ) ) {
			$array_keys = array( $array_keys );
		}

		$results = array();

		foreach ( $array_keys as $key ) {
			if ( ! isset( $array_to_filter[$key] ) ) {
				throw new Exception( sprintf(
					__( 'Key not found: [%s]', 'post-thumbnail-editor' ),
					$key
			   	) );
			}
			$results[] = $array_to_filter[$key];
		}

		return $results;

	}
	
}
