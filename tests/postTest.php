<?php
/**
 * Test post filters.
 *
 * @group tln
 * @group tln_post
 */
class Tests_TLN_Post extends WP_UnitTestCase {

	static $normalizer_state = array();

	public static function wpSetUpBeforeClass() {
		global $tlnormalizer;
		self::$normalizer_state = array( $tlnormalizer->dont_js, $tlnormalizer->dont_filter, $tlnormalizer->no_normalizer );
		$tlnormalizer->dont_js = true;
		$tlnormalizer->dont_filter = false;
		$tlnormalizer->no_normalizer = true;

		global $pagenow;
		$pagenow = 'post.php';
		set_current_screen( $pagenow );
	}

	public static function wpTearDownAfterClass() {
		global $tlnormalizer;
		list( $tlnormalizer->dont_js, $tlnormalizer->dont_filter, $tlnormalizer->no_normalizer ) = self::$normalizer_state;
	}

    /**
	 * @ticket tln_post_post
     */
	function test_post() {
		$this->assertTrue( is_admin() ) ;

		do_action( 'init' );

		global $tlnormalizer;
		$this->assertArrayHasKey( 'post', $tlnormalizer->added_filters );

		$decomposed_str = "u\xCC\x88"; // u umlaut.

		$post = array(
			'post_status' => 'publish',
			'post_title' => 'Title' . $decomposed_str,
			'post_content' => 'Content' . $decomposed_str,
			'post_excerpt' => 'Excerpt' . $decomposed_str,
			'post_type' => 'post',
		);

		$id = wp_insert_post( $post );

		$this->assertTrue( is_numeric( $id ) );
		$this->assertTrue( $id > 0 );

		// fetch the post and make sure it matches
		$out = get_post( $id );

		$this->assertSame( TLN_Normalizer::normalize( $post['post_title'] ), $out->post_title );
		if ( class_exists( 'Normalizer' ) ) $this->assertSame( Normalizer::normalize( $post['post_title'] ), $out->post_title );
		$this->assertSame( TLN_Normalizer::normalize( $post['post_content'] ), $out->post_content );
		if ( class_exists( 'Normalizer' ) ) $this->assertSame( Normalizer::normalize( $post['post_content'] ), $out->post_content );
		$this->assertSame( TLN_Normalizer::normalize( $post['post_excerpt'] ), $out->post_excerpt );
		if ( class_exists( 'Normalizer' ) ) $this->assertSame( Normalizer::normalize( $post['post_excerpt'] ), $out->post_excerpt );

		$post['ID'] = $id;

		$id = wp_update_post( $post );

		$this->assertTrue( is_numeric( $id ) );
		$this->assertTrue( $id > 0 );

		// fetch the post and make sure it matches
		$out = get_post( $id );

		$this->assertSame( TLN_Normalizer::normalize( $post['post_content'] ), $out->post_content );
		if ( class_exists( 'Normalizer' ) ) $this->assertSame( Normalizer::normalize( $post['post_content'] ), $out->post_content );
		$this->assertSame( TLN_Normalizer::normalize( $post['post_title'] ), $out->post_title );
		if ( class_exists( 'Normalizer' ) ) $this->assertSame( Normalizer::normalize( $post['post_title'] ), $out->post_title );
	}

    /**
	 * @ticket tln_post_meta
     */
	function test_meta() {
		$this->assertTrue( is_admin() ) ;

		wp_set_current_user( 1 ); // Need editor privileges.

		$decomposed_str = "u\xCC\x88"; // u umlaut.

		// Emulate "post-new.php".
		$post = get_default_post_to_edit( 'post', true ); // Auto-draft.
		$this->assertInstanceOf( 'WP_Post', $post );

		$id = $post->ID;
		$this->assertTrue( is_numeric( $id ) );
		$this->assertTrue( $id > 0 );

		// Emulate POST to "post.php".

		$tag1 = 'Tag1' . $decomposed_str;

		$_POST = array(
			'post_ID' => $id,
			'post_status' => 'publish',
			'post_title' => 'Title' . $decomposed_str,
			'post_type' => 'post',
			'meta_input' => array( // Note not used by core.
				'meta_input_key' => 'meta_input_value' . $decomposed_str,
			),
			'metakeyinput' => 'metakeyinput_key' . $decomposed_str,
			'metavalue' => 'metakeyinput_value' . $decomposed_str,
			'tax_input' => array(
				'post_tag' => $tag1,
			),
		);

		do_action( 'init' );

		global $tlnormalizer;
		$this->assertArrayHasKey( 'post', $tlnormalizer->added_filters );

		// Add (update auto-draft).
		$out = edit_post();
		$this->assertSame( $id, $out );

		// fetch the post and make sure it matches
		$out = get_post( $id );

		$this->assertSame( TLN_Normalizer::normalize( $_POST['post_title'] ), $out->post_title );
		if ( class_exists( 'Normalizer' ) ) $this->assertSame( Normalizer::normalize( $_POST['post_title'] ), $out->post_title );

		$out = get_post_meta( $id, 'meta_input_key', true );

		$this->assertSame( TLN_Normalizer::normalize( $_POST['meta_input']['meta_input_key'] ), $out );
		if ( class_exists( 'Normalizer' ) ) $this->assertSame( Normalizer::normalize( $_POST['meta_input']['meta_input_key'] ), $out );

		$out = get_post_meta( $id, 'metakeyinput_key' . $decomposed_str, true );

		$this->assertSame( TLN_Normalizer::normalize( $_POST['metavalue'] ), $out );
		if ( class_exists( 'Normalizer' ) ) $this->assertSame( Normalizer::normalize( $_POST['metavalue'] ), $out );

		$out = get_term_by( 'name', TLN_Normalizer::normalize( $tag1 ), 'post_tag' );
		$this->assertInstanceOf( 'WP_Term', $out );

		// Update.

		global $wpdb;

		$meta_input_key_id = $wpdb->get_var( $wpdb->prepare( "SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s", $id, 'meta_input_key' ) );
		$this->assertTrue( is_numeric( $meta_input_key_id ) );
		$this->assertTrue( $meta_input_key_id > 0 );

		$metakeyinput_id = $wpdb->get_var( $wpdb->prepare( "SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = %s", $id, 'metakeyinput_key' . $decomposed_str ) );
		$this->assertTrue( is_numeric( $metakeyinput_id ) );
		$this->assertTrue( $metakeyinput_id > 0 );

		// Emulate POST to "post.php".

		$_POST = array(
			'post_ID' => $id,
			'post_status' => 'publish',
			'post_title' => 'Title' . $decomposed_str,
			'post_type' => 'post',
			'meta' => array(
				$meta_input_key_id => array( 'key' => 'meta_input_key', 'value' => 'meta_input_value updated' . $decomposed_str ),
				$metakeyinput_id => array( 'key' => 'metakeyinput_key' . $decomposed_str, 'value' => 'metakeyinput_value updated' . $decomposed_str ),
			),
		);

		do_action( 'init' );

		$out = edit_post();
		$this->assertSame( $id, $out );

		$out = get_post_meta( $id, 'meta_input_key', true );

		$this->assertSame( TLN_Normalizer::normalize( $_POST['meta'][$meta_input_key_id]['value'] ), $out );
		if ( class_exists( 'Normalizer' ) ) $this->assertSame( Normalizer::normalize( $_POST['meta'][$meta_input_key_id]['value'] ), $out );

		$out = get_post_meta( $id, 'metakeyinput_key' . $decomposed_str, true );

		$this->assertSame( TLN_Normalizer::normalize( $_POST['meta'][$metakeyinput_id]['value'] ), $out );
		if ( class_exists( 'Normalizer' ) ) $this->assertSame( Normalizer::normalize( $_POST['meta'][$metakeyinput_id]['value'] ), $out );
	}

    /**
	 * @ticket tln_post_attachment
     */
	function test_attachment() {
		$this->assertTrue( is_admin() ) ;

		wp_set_current_user( 1 ); // Need editor privileges.

		$decomposed_str = "u\xCC\x88"; // u umlaut.

		// Emulate "post-new.php".
		$post = get_default_post_to_edit( 'attachment', true ); // Auto-draft.
		$this->assertInstanceOf( 'WP_Post', $post );

		$id = $post->ID;
		$this->assertTrue( is_numeric( $id ) );
		$this->assertTrue( $id > 0 );

		// Emulate POST to "post.php".

		$_POST = array(
			'post_ID' => $id,
			'post_status' => 'publish',
			'post_title' => 'Title' . $decomposed_str,
			'post_content' => 'Content' . $decomposed_str,
			'post_excerpt' => 'Excerpt' . $decomposed_str,
			'post_type' => 'attachment',
			'_wp_attachment_image_alt' => 'Alt' . $decomposed_str,
		);

		do_action( 'init' );

		global $tlnormalizer;
		$this->assertArrayHasKey( 'post', $tlnormalizer->added_filters );

		// Add (update auto-draft).
		$out = edit_post();
		$this->assertSame( $id, $out );

		$out = get_post_meta( $id, '_wp_attachment_image_alt', true );

		$this->assertSame( TLN_Normalizer::normalize( $_POST['_wp_attachment_image_alt'] ), $out );
		if ( class_exists( 'Normalizer' ) ) $this->assertSame( Normalizer::normalize( $_POST['_wp_attachment_image_alt'] ), $out );
	}

    /**
	 * @ticket tln_post_media
     */
	function test_media() {
		$this->assertTrue( is_admin() ) ;

		wp_set_current_user( 1 ); // Need editor privileges.

		$decomposed_str = "u\xCC\x88"; // u umlaut.

		$post = array(
			'post_status' => 'publish',
			'post_title' => 'Title' . $decomposed_str,
			'post_content' => 'Content' . $decomposed_str,
			'post_excerpt' => 'Excerpt' . $decomposed_str,
			'post_type' => 'attachment',
			'post_mime_type' => 'audio/mpeg',
		);

		$id = wp_insert_attachment( $post );

		$this->assertTrue( is_numeric( $id ) );
		$this->assertTrue( $id > 0 );

		$out = get_post( $id );
		$this->assertInstanceOf( 'WP_Post', $out );
		$this->assertSame( $id, $out->ID );

		// Emulate POST to "post.php".

		$_POST = array(
			'post_ID' => $id,
			'post_status' => 'publish',
			'post_title' => 'Title' . $decomposed_str,
			'post_content' => 'Content' . $decomposed_str,
			'post_excerpt' => 'Excerpt' . $decomposed_str,
			'post_type' => 'attachment',
			'post_mime_type' => 'audio/mpeg',
		);

		$id3_keys = wp_get_attachment_id3_keys( null, 'edit' );
		foreach ( $id3_keys as $key => $label ) {
			$_POST[ 'id3_' . $key ] = $label . $decomposed_str;
		}

		do_action( 'init' );

		global $tlnormalizer;
		$this->assertArrayHasKey( 'post', $tlnormalizer->added_filters );

		// Update.
		$out = edit_post();
		$this->assertSame( $id, $out );

		$out = get_post_meta( $id, '_wp_attachment_metadata', true );
		$this->assertInternalType( 'array', $out );

		foreach ( $id3_keys as $key => $label ) {
			$this->assertSame( TLN_Normalizer::normalize( $_POST[ 'id3_' . $key ] ), $out[ $key ] );
			if ( class_exists( 'Normalizer' ) ) $this->assertSame( Normalizer::normalize( $_POST[ 'id3_' . $key ] ), $out[ $key ] );
		}
	}
}