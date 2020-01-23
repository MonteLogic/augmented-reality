<?php
/**
 * Tests for class Block.
 *
 * @package AugmentedReality
 */

namespace AugmentedReality;

use Brain\Monkey\Functions;

/**
 * Tests for class Block.
 */
class Test_Block extends \WP_UnitTestCase {

	/**
	 * Instance of Block.
	 *
	 * @var Block
	 */
	public $instance;

	/**
	 * Setup.
	 *
	 * @inheritdoc
	 */
	public function setUp() {
		parent::setUp();
		$plugin = new Plugin();
		$plugin->init();
		$this->instance = new Block( $plugin );
	}

	/**
	 * Test __construct().
	 *
	 * @covers __construct.
	 */
	public function test_construct() {
		$this->assertEquals( __NAMESPACE__ . '\\Plugin', get_class( $this->instance->plugin ) );
	}

	/**
	 * Test init().
	 *
	 * @covers init.
	 */
	public function test_init() {
		$this->instance->init();
		$this->assertEquals( 10, has_action( 'enqueue_block_editor_assets', [ $this->instance, 'block_editor_assets' ] ) );
		$this->assertEquals( 10, has_filter( 'wp_check_filetype_and_ext', [ $this->instance, 'check_filetype_and_ext' ] ) );
		$this->assertEquals( 10, has_action( 'init', [ $this->instance, 'register_block' ] ) );
	}

	/**
	 * Test block_editor_assets().
	 *
	 * @covers Plugin::block_editor_assets().
	 */
	public function test_block_editor_assets() {
		$this->instance->block_editor_assets();
		$scripts = wp_scripts();
		$slug    = Plugin::SLUG . '-' . Block::JS_FILE_NAME;
		$script  = $scripts->registered[ $slug ];
		$this->assertTrue( in_array( $slug, $scripts->queue, true ) );

		$this->assertEquals(
			[ 'wp-i18n', 'wp-element', 'wp-blocks', 'wp-components', 'wp-editor' ],
			$script->deps
		);
		$this->assertEmpty( $script->extra );
		$this->assertEquals( $slug, $script->handle );
		$this->assertContains( Plugin::SLUG . '/assets/js/blocks-compiled.js', $script->src );
		$this->assertEquals( Plugin::VERSION, $script->ver );
	}

	/**
	 * Test add_mime_types().
	 *
	 * @covers Plugin::add_mime_types().
	 */
	public function test_add_mime_types() {
		$original_mime_types = [ 'gif' => 'image/gif' ];
		$filtered_mimes      = $this->instance->add_mime_types( $original_mime_types );

		// The original mime types should still be present.
		$this->assertEmpty( array_diff( $original_mime_types, $filtered_mimes ) );
		$this->assertEquals( Block::OBJ_FILE_TYPE, $filtered_mimes['obj'] );
		$this->assertEquals( 'application/glb', $filtered_mimes['glb'] );
	}

	/**
	 * Test register_block().
	 *
	 * @covers Block::register_block().
	 */
	public function test_register_block() {
		Functions\expect( 'register_block_type' )->once()->andReturnUsing(
			function( $block_name, $block_args ) {
				$this->assertEquals( Block::BLOCK_NAME, $block_name );
				$this->assertEquals(
					[
						'attributes'      => [
							'objUrl' => [
								'type' => 'string',
							],
							'mtlUrl' => [
								'type' => 'string',
							],
						],
						'render_callback' => [ $this->instance, 'render_block' ],
					],
					$block_args
				);
			}
		);

		$this->instance->register_block();
	}

	/**
	 * Test render_block().
	 *
	 * @covers Block::render_block().
	 */
	public function test_render_block() {
		$obj_url = 'https://example.com/foo.obj';
		$mtl_url = 'https://example.com/baz.mtl';

		// If the $attributes argument is an empty array(), this should not output anything.
		$this->assertEmpty( $this->instance->render_block( [] ) );

		// If the $attributes argument only has an 'mtlUrl' value, this should not output anything.
		$this->assertEmpty( $this->instance->render_block( [ 'mtlUrl' => $mtl_url ] ) );

		// Now that both the 'objUrl' and 'mtlUrl' are present, this should render the block.
		$correct_args = [
			'objUrl' => $obj_url,
			'mtlUrl' => $mtl_url,
		];
		$markup       = $this->instance->render_block( $correct_args );

		$this->assertContains( $obj_url, $markup );
		$this->assertContains( $mtl_url, $markup );
		$this->assertContains( '<a class="enter-ar"', $markup );
		$this->assertContains( 'Your browser does not support AR features with WebXR.', $markup );

		$scripts = wp_scripts();
		$this->assertTrue( in_array( $this->instance->plugin->components->Asset->get_full_slug( 'app' ), $scripts->queue, true ) );
	}

	/**
	 * Test check_filetype_and_ext().
	 *
	 * @covers Plugin::check_filetype_and_ext().
	 */
	public function test_check_filetype_and_ext() {
		$initial_wp_check_filetype_and_ext = [
			'ext'             => false,
			'type'            => false,
			'proper_filename' => false,
		];
		$wrong_filename                    = 'baz.gif';
		$file                              = "example/$wrong_filename";

		// This isn't an .obj file, so it should return the same value that it's passed.
		$this->assertEquals(
			$initial_wp_check_filetype_and_ext,
			$this->instance->check_filetype_and_ext( $initial_wp_check_filetype_and_ext, $file, $wrong_filename )
		);

		$correct_filename = 'example.obj';
		$file             = dirname( __DIR__ ) . '/fixtures/' . $correct_filename;

		// This now passes an .obj file, so the filtered value should be different.
		$this->assertEquals(
			[
				'ext'             => 'obj',
				'type'            => 'application/obj',
				'proper_filename' => false,
			],
			$this->instance->check_filetype_and_ext( $initial_wp_check_filetype_and_ext, $file, $correct_filename )
		);

		$mtl_filename = 'baz.mtl';
		$file         = dirname( __DIR__ ) . '/fixtures/' . $mtl_filename;

		// This now passes an .obj file, so the filtered value should be different.
		$this->assertEquals(
			[
				'ext'             => 'mtl',
				'type'            => 'application/mtl',
				'proper_filename' => false,
			],
			$this->instance->check_filetype_and_ext( $initial_wp_check_filetype_and_ext, $file, $mtl_filename )
		);
	}
}
