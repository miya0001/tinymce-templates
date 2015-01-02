<?php

class TinyMCE_Templates_Test extends WP_UnitTestCase {

	/**
	 * @test
	 */
	function shortcode_testing()
	{
		$post_id = wp_insert_post( array(
			'post_title'   => 'template_test',
			'post_content' => 'this is template',
			'post_type'    => 'tinymcetemplates',
			'post_status'  => 'publish',
		) );

		$this->assertTrue( is_integer( $post_id ) );

		// inserting as shortcode is not allowed.
		$this->assertEquals( null, trim( do_shortcode( '[template id="'.$post_id.'"]' ) ) );

		// allows to insert as shortcode.
		update_post_meta( $post_id, 'insert_as_shortcode', true );
		$this->assertEquals( '<p>this is template</p>', trim( do_shortcode( '[template id="'.$post_id.'"]' ) ) );

		// post_id is not found
		$this->assertEquals( null, trim( do_shortcode( '[template id="99999"]' ) ) );

		// post_id is not numeric
		$this->assertEquals( null, trim( do_shortcode( '[template id="bar]' ) ) );
	}
}
