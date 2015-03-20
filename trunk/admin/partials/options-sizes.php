<table><tr><th><?php _e("Post Thumbnail", 'post-thumbnail-editor'); ?></th>
	<th><?php _e( "Hidden", 'post-thumbnail-editor' ); ?></th>
	</tr>
<?php
// End table header

/**
 * Get the size information
 *
 * Return an array of thumbnail objects describing the size information
 *
 * @since
 * @param  callback   $filter   filter results with this filter callback
 */
$sizes = apply_filters( 'pte_api_get_sizes', array(), null );

foreach ( $sizes as $thumbnail ){
	$hidden = ( in_array( $thumbnail->name, $options['pte_hidden_sizes'] ) ) ?
		"checked":"";

	print( "<tr><td><label for='{$thumbnail->name}'>{$thumbnail->label}</label></td>"
		. "<td><input type='checkbox' id='{$thumbnail->name}' name='pte-site-options[pte_hidden_sizes][]'"
		. " value='{$thumbnail->name}' {$hidden}></td>"
		. "</tr>"
	);
}
?>

</table>
