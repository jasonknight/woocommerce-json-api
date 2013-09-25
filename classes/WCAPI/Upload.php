<?php 
namespace WCAPI;
require_once dirname( __FILE__ ) . '/BaseHelpers.php';
require_once ABSPATH . "wp-admin/includes/image.php";
class Upload {
	public $tmp_name;
	public $name;
	public $error;
	public $size;
	public $time;
	public $allowed_mimes;
	public $test_type = false;
	public $url;
	public $perms;
	public $stat;
	public $path;
	public function __construct($tname,$name,$error,$size,$time=null) {
		$this->tmp_name = $tname;
		$this->name = $name;
		$this->error = $error;
		$this->size = $size;
		$this->time = $time;
		$this->allowed_mimes = array(); // everything is allowed.
	}
	public function save() {
		Helpers::debug("Upload::save called");
		if ( ! ( ( $uploads = wp_upload_dir($this->time) ) && false === $uploads['error'] ) ) {
			throw new \Exception($uploads['error']);
		}
		$this->name = $this->getProperName();
		$filename = wp_unique_filename( $uploads['path'], $this->name, NULL );
		// Move the file to the uploads dir
		$new_file = $uploads['path'] . "/$filename";
		if ( false === @move_uploaded_file( $this->tmp_name, $new_file ) ) {
			throw new \Exception( sprintf( __('The uploaded file could not be moved to %s.' ), $uploads['path'] ) );
		}
		$this->path = $new_file;

		$this->stat = stat( dirname( $new_file ));
		$this->perms = $this->stat['mode'] & 0000666;
		@ chmod( $new_file, $this->perms );

		// Compute the URL
		$this->url = $uploads['url'] . "/$filename";

		if ( is_multisite() ) {
			delete_transient( 'dirsize_cache' );
		}
		Helpers::debug("Upload::save finished");
	}
	public function saveMediaAttachment() {
		Helpers::debug("Upload::saveMediaAttachment called");
		include WCAPIDIR . "/_mime_types.php";
		$this->time = current_time('mysql');
		$this->save();
		$name_parts = pathinfo($this->name);
		$name = trim( substr( $this->name, 0, -(1 + strlen($name_parts['extension'])) ) );

		$url = $this->url;
		if ( isset( $mime_types[ $name_parts['extension'] ] ) ) {
			$type =  $mime_types[ $name_parts['extension'] ];
		} else {
			throw new \Exception( __('unknown mime_type ' . $name_parts['extension'] ) );
		}
		$file = $this->path;
		$title = $name;
		$content = '';
		Helpers::debug("file is: $file and title is: $title");
		//use image exif/iptc data for title and caption defaults if possible
		if ( $image_meta = @wp_read_image_metadata($file) ) {
			if ( trim( $image_meta['title'] ) && ! is_numeric( sanitize_title( $image_meta['title'] ) ) )
				$title = $image_meta['title'];
			if ( trim( $image_meta['caption'] ) )
				$content = $image_meta['caption'];
		}

		// Construct the attachment array
		$attachment = array(
			'post_mime_type' => $type,
			'guid' => $url,
			'post_parent' => '',
			'post_title' => $title,
			'post_content' => $content,
		);
		Helpers::debug("Saving attachment: " . var_export($attachment,true) );
		$id = wp_insert_attachment($attachment, $file, NULL);
		if ( !is_wp_error($id) ) {
			wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) );
		} else {
			Helpers::debug("Failed to save attachment because of: " . $id->get_error_messages() );
			throw new \Exception( $id->get_error_messages() ); 
		}
		Helpers::debug("Upload::saveMediaAttachment done");
		return $id;
	}
	public function getProperName() {
		$wp_filetype = wp_check_filetype_and_ext( $this->tmp_name, $this->name, $this->allowed_mimes );

		extract( $wp_filetype );

		// Check to see if wp_check_filetype_and_ext() determined the filename was incorrect
		if ( $proper_filename ) {
			return $proper_filename;
		}
		return $this->name;
	}
}