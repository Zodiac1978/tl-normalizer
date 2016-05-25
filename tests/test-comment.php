<?php
/**
 * Test comment filters.
 *
 * @group tln
 * @group tln_comment
 */
class Tests_TLN_Comment extends WP_UnitTestCase {

	static $normalizer_state = array();

	public static function wpSetUpBeforeClass() {
		global $tlnormalizer;
		self::$normalizer_state = array( $tlnormalizer->dont_js, $tlnormalizer->dont_filter, $tlnormalizer->no_normalizer );
		$tlnormalizer->dont_js = true;
		$tlnormalizer->dont_filter = false;
		$tlnormalizer->no_normalizer = true;

		global $pagenow;
		$pagenow = 'comment.php';
		set_current_screen( $pagenow );
	}

	public static function wpTearDownAfterClass() {
		global $tlnormalizer;
		list( $tlnormalizer->dont_js, $tlnormalizer->dont_filter, $tlnormalizer->no_normalizer ) = self::$normalizer_state;
	}

    /**
	 * @ticket tln_comment_comment
     */
	function test_comment() {
		$this->assertTrue( is_admin() ) ;

		do_action( 'init' );

		global $tlnormalizer;
		$this->assertArrayHasKey( 'comment', $tlnormalizer->added_filters );

		$decomposed_str = "u\xcc\x88"; // u umlaut.

		$post = self::factory()->post->create_and_get( array( 'post_title' => 'some-post', 'post_type' => 'post' ) );
		$this->assertInstanceOf( 'WP_Post', $post );
		$comment_id = self::factory()->comment->create( array( 'comment_post_ID' => $post->post_id ) );

		$updated_comment_text = 'Comment text' . $decomposed_str;
		$update = wp_update_comment( array( 'comment_ID' => $comment_id, 'comment_content' => $updated_comment_text ) );

		$this->assertSame( 1, $update );

		$comment = get_comment( $comment_id );
		$this->assertInstanceOf( 'WP_Comment', $comment );
		$this->assertSame( TLN_Normalizer::normalize( $updated_comment_text ), $comment->comment_content );
		if ( class_exists( 'Normalizer' ) ) $this->assertSame( Normalizer::normalize( $updated_comment_text ), $comment->comment_content );
	}
}
