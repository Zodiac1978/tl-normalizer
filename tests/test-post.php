<?php

/**
 * Test post filters
 *
 * @group tln
 * @group tln_post
 */
class Tests_TLN_Normalizer_Post extends WP_UnitTestCase {

	static $normalizer_state = array();

	public static function wpSetUpBeforeClass() {
		global $tlnormalizer;
		self::$normalizer_state = array( $tlnormalizer->dont_js, $tlnormalizer->dont_filter );
		$tlnormalizer->dont_js = true;
		$tlnormalizer->dont_filter = false;

		global $pagenow;
		$pagenow = 'post.php';
		set_current_screen( $pagenow );
	}

	public static function wpTearDownAfterClass() {
		global $tlnormalizer;
		list( $tlnormalizer->dont_js, $tlnormalizer->dont_filter ) = self::$normalizer_state;
	}

    /**
     * @covers TLNormalizer::init
     */
	function test_post() {
		$this->assertTrue( is_admin() ) ;

		do_action( 'init' );

		global $tlnormalizer;
		$this->assertTrue( $tlnormalizer->added_filters['post'] );

		$decomposed_str = "u\xCC\x88"; // u umlaut.

		$post = array(
			'post_status' => 'publish',
			'post_title' => 'Title' . $decomposed_str,
			'post_content' => 'Content' . $decomposed_str,
			'post_type' => 'post',
		);

		$id = wp_insert_post( $post );

		$this->assertTrue( is_numeric( $id ) );
		$this->assertTrue( $id > 0 );

		// fetch the post and make sure it matches
		$out = get_post( $id );

		$this->assertEquals( Normalizer::normalize( $post['post_content'] ), $out->post_content );
		$this->assertEquals( Normalizer::normalize( $post['post_title'] ), $out->post_title );

		$post['ID'] = $id;

		$id = wp_update_post( $post );

		$this->assertTrue( is_numeric( $id ) );
		$this->assertTrue( $id > 0 );

		// fetch the post and make sure it matches
		$out = get_post( $id );

		$this->assertEquals( Normalizer::normalize( $post['post_content'] ), $out->post_content );
		$this->assertEquals( Normalizer::normalize( $post['post_title'] ), $out->post_title );
	}
}
