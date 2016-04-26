<?php

/**
 * Test user filters
 *
 * @group tln
 * @group tln_user
 */
class Tests_TLN_Normalizer_User extends WP_UnitTestCase {

	static $normalizer_state = array();

	public static function wpSetUpBeforeClass() {
		global $tlnormalizer;
		self::$normalizer_state = array( $tlnormalizer->dont_js, $tlnormalizer->dont_filter );
		$tlnormalizer->dont_js = true;
		$tlnormalizer->dont_filter = false;

		global $pagenow;
		$pagenow = 'user.php';
		set_current_screen( $pagenow );
	}

	public static function wpTearDownAfterClass() {
		global $tlnormalizer;
		list( $tlnormalizer->dont_js, $tlnormalizer->dont_filter ) = self::$normalizer_state;
	}

    /**
     * @covers TLNormalizer::init
     */
	function test_user() {
		$this->assertTrue( is_admin() ) ;

		do_action( 'init' );

		global $tlnormalizer;
		$this->assertTrue( $tlnormalizer->added_filters['user'] );

		$decomposed_str = "u\xCC\x88"; // u umlaut.

		$_POST = $_GET = $_REQUEST = array();
		$_POST['role'] = 'subscriber';
		$_POST['email'] = 'user1@example.com';
		$_POST['user_login'] = 'user_login1'/* . $decomposed_str*/; // Can't use in user_login as validate_username() strips to ASCII.
		$_POST['first_name'] = 'first_name1' . $decomposed_str;
		$_POST['last_name'] = 'last_name1' . $decomposed_str;
		$_POST['nickname'] = 'nickname1' . $decomposed_str;
		$_POST['display_name'] = 'display_name1' . $decomposed_str;
		$_POST['pass1'] = $_POST['pass2'] = 'password' . $decomposed_str;

		$user_id = edit_user();

		$this->assertInternalType( 'int', $user_id );

		$user = get_user_by( 'ID', $user_id );

		$this->assertInstanceOf( 'WP_User', $user );
		$this->assertEquals( Normalizer::normalize( $_POST['first_name'] ), $user->first_name );
		$this->assertEquals( Normalizer::normalize( $_POST['last_name'] ), $user->last_name );
		$this->assertEquals( Normalizer::normalize( $_POST['nickname'] ), $user->nickname );
		$this->assertEquals( Normalizer::normalize( $_POST['display_name'] ), $user->display_name );
		$this->assertTrue( wp_check_password( $_POST['pass1'], $user->user_pass ) ); // Not normalized.
		$this->assertFalse( wp_check_password( Normalizer::normalize( $_POST['pass1'] ), $user->user_pass ) ); // Not normalized.
	}
}
