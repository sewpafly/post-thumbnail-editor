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
	 * Look for a function call that ends with `_hook` and attempt to call the
	 * regular function call without the first default argument and return the
	 * result.
	 *
	 * Throw a bad method call exception otherwise
	 *
	 * @return void
	 */
	public function __call ( $name, $arguments ) {

		if ( preg_match( '/(.*)_hook$/', $name, $matches ) ) {

			array_shift( $arguments );
			return call_user_func_array( array( $this, $matches[1] ), $arguments);

		}
		return null;
	}
	
}
