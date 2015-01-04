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
		$this->assertSame( '', trim( do_shortcode( '[template id="'.$post_id.'"]' ) ) );

		// allows to insert as shortcode.
		update_post_meta( $post_id, 'insert_as_shortcode', true );
		$this->assertSame( '<p>this is template</p>', trim( do_shortcode( '[template id="'.$post_id.'"]' ) ) );

		// post_id is not found
		$this->assertSame( '', trim( do_shortcode( '[template id="99999"]' ) ) );

		// post_id is not numeric
		$this->assertSame( '', trim( do_shortcode( '[template id="bar]' ) ) );
	}

	/**
	 * @test
	 */
	function filter_test()
	{
		$post_id = wp_insert_post( array(
			'post_title'   => 'template_test',
			'post_content' => '{$name} {$content}!',
			'post_type'    => 'tinymcetemplates',
			'post_status'  => 'publish',
		) );

		$this->assertTrue( is_integer( $post_id ) );
		update_post_meta( $post_id, 'insert_as_shortcode', true );

		add_filter( 'tinymce_templates_content', array( $this, 'tinymce_templates_content' ), 10, 3 );

		$this->assertSame( '<p>Hello World!</p>', trim( do_shortcode( '[template id="'.$post_id.'" name="Hello"]World[/template]' ) ) );
		$this->assertSame( '<p>Hello !</p>', trim( do_shortcode( '[template id="'.$post_id.'" name="Hello"]' ) ) );
	}

	/*
	 * Filters tinymce_templates_content
	 */
	public function tinymce_templates_content( $template, $attr, $content ){
		foreach ( $attr as $key => $value ) {
			$template = str_replace( '{$'.$key.'}', $value, $template );
		}

		$template = str_replace( '{$content}', $content, $template );
		return $template;
	}
}
