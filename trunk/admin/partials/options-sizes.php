<table><tr><th><?php _e("Post Thumbnail", 'post-thumbnail-editor'); ?></th>
	<th><?php _e( "Hidden", 'post-thumbnail-editor' ); ?></th>
	</tr>
<?php
// End table header

$sizes = PTE::api()->get_sizes(false);

foreach ( $sizes as $size => $size_data ){
	$hidden = ( in_array( $size, $options['pte_hidden_sizes'] ) ) ?
		"checked":"";

	$name = isset( $size_data['display_name'] )? $size_data['display_name'] : $size;

	print( "<tr><td><label for='{$size}'>{$name}</label></td>"
		. "<td><input type='checkbox' id='{$size}' name='pte-site-options[pte_hidden_sizes][]'"
		. " value='{$size}' {$hidden}></td>"
		. "</tr>"
	);
}
?>

</table>
