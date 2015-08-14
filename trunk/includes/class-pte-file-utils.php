<?php

/**
 * The Post Thumbnail Editor File Utilities
 *
 * @link       http://sewpafly.github.io/post-thumbnail-editor
 * @since      3.0.0
 *
 * @package    Post_Thumbnail_Editor
 * @subpackage Post_Thumbnail_Editor/includes
 */

class PTE_File_Utils {

	/**
	 * Copy a file
	 *
	 * @return true on success
	 *
	 * @throws Exception on any sort of problem
	 */
	public static function copy_file ( $from, $to ) {

		if ( ! ( isset( $from ) && file_exists( $from ) ) ){
			throw new Exception(
				sprintf(
					__( 'Invalid file to copy: %s', 'post-thumbnail-editor' ),
					$from
				)
			);
		}

		wp_mkdir_p( dirname( $to ) );
		rename( $from, $to );

		return true;

	}

	/**
	 * Delete a directory
	 *
	 * If the filter `pte_delete_dir` returns FALSE, then the method will still
	 * succeed.
	 *
	 * @return status
	 */
	public static function delete_dir ( $dir ) {

		$dir = apply_filters( 'pte_delete_dir', $dir );

		if ($dir === FALSE)
			return true;

		if ( !is_dir( $dir ) || !preg_match( "/ptetmp/", $dir ) ){
			throw new Exception(
				__( "Tried to delete invalid directory: {$dir}", 'post-thumbnail-editor' )
			);
		}

		foreach ( scandir( $dir ) as $file ){
			if ( "." == $file || ".." == $file ) continue;
			$this->delete_file( $dir . DIRECTORY_SEPARATOR . $file );
		}
		rmdir( $dir );

		return true;
	}

	/**
	 * Delete a file, ensure that it exists first
	 *
	 * If the filter `pte_delete_file` returns FALSE, then the method will still
	 * succeed.
	 *
	 * @return bool   true if file was deleted, false otherwise
	 */
	public static function delete_file ( $file ) {

		$file = apply_filters( 'pte_delete_file', $file );
		if ($file === FALSE)
			return true;

		if ( isset( $file ) && @is_file( $file ) ) {
			@unlink( apply_filters( 'wp_delete_file', $file ) );
			return true;
		}

		return false;

	}

}
