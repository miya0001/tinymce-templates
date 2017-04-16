<?php

class TinyMCE_Templates_Test extends WP_UnitTestCase
{
	/**
	 * @test
	 */
	public function get_template()
	{
		$tinymce_templates = new TinyMCE_Templates();
		$templates = $tinymce_templates->get_templates();

		$this->assertSame( array(), $templates ); // should be empty array

		$post_id = $this->factory->post->create( array(
			'post_type' => 'tinymcetemplates',
			'post_content' => 'Hello',
		) );
		$templates = $tinymce_templates->get_templates();

		// Find a template with the ID
		$found = false;
		foreach ( $templates as $tpl ) {
			if ( isset( $tpl['id'] ) && intval( $tpl['id'] ) == $post_id ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found );

		$_GET['template_id'] = $post_id;
		$templates = $tinymce_templates->get_templates();

		$this->assertSame( "<p>Hello</p>\n", $templates['content'] );
	}

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

	/**
	 * @test
	 */
	function admin_enqueue_scripts()
	{
		$tinymce_templates = new TinyMCE_Templates();

		// css and script shoud be not loading.
		$tinymce_templates->admin_enqueue_scripts( '' );
		$this->assertFalse( wp_style_is( 'tinymce-templates' ) );
		$this->assertFalse( wp_script_is( 'tinymce-templates' ) );

		// css and script shoud be loading.
		$tinymce_templates->admin_enqueue_scripts( 'post.php' );
		$this->assertTrue( wp_style_is( 'tinymce-templates' ) );
		$this->assertTrue( wp_script_is( 'tinymce-templates' ) );

		// css and script shoud be loading.
		$tinymce_templates->admin_enqueue_scripts( 'post-new.php' );
		$this->assertTrue( wp_style_is( 'tinymce-templates' ) );
		$this->assertTrue( wp_script_is( 'tinymce-templates' ) );
	}

	/**
	 * @test
	 */
	function media_buttons_01()
	{
		$tinymce_templates = new TinyMCE_Templates();
		$this->expectOutputRegex( '/id="button-tinymce-templates"/' );
		$tinymce_templates->media_buttons();
	}

	/**
	 * @test
	 */
	function media_buttons_02()
	{
		$tinymce_templates = new TinyMCE_Templates();
		$this->expectOutputRegex( '/id="button-tinymce-templates"/' );
		$tinymce_templates->media_buttons( 'content' );
	}

	/**
	 * @test
	 */
	function media_buttons_03()
	{
		$tinymce_templates = new TinyMCE_Templates();
		$this->expectOutputString( '' );
		$tinymce_templates->media_buttons( 'editor_id_is_not_content' );
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
