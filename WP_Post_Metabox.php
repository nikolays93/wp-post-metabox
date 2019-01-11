<?php

namespace NikolayS93;

// use NikolayS93\WP_Post_Metabox as Metabox;

if ( ! defined( 'ABSPATH' ) ) exit; // disable direct access

class WP_Post_Metabox
{
	private $output_function = '__return_false';
	private $box_name = 'Example title';
	private $side = false;
	private $priority;
	private $post_types = array('post', 'page');

	private $meta_fields = array('');

	private static $count = 0;

	/**
	 * @param string 	$name   Название бокса
	 * @param boolean 	$side   Показывать с боку / Нормально
	 */
	function __construct($name = false, $side = false, $priority = null)
	{
		if($name) $this->box_name = sanitize_text_field($name);

		$this->side = $side;
		$this->priority = $side; // ??

		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save' ) );
	}

	function no_content()
	{
		_e( 'Callbale function or method not found', 'wp-post-metabox' );
	}

	/**
	 * @param function $output_function Название callback функции
	 */
	public function set_content( $output_function )
	{
		if( is_callable( $output_function ) ) {
			$this->output_function = $output_function;
		}
		else {
			$this->output_function = array($this, 'no_content');
		}
	}

	/**
	 * @param mixed $post_types Типы записей на которых нужно добавить бокс
	 */
	public function set_type($post_types)
	{
		$post_types = (array) $post_types;
		if( !empty($post_types) ) $this->post_types = $post_types;
	}

	public function set_field($field_code)
	{
		array_push($this->meta_fields, esc_attr( $field_code ) );
	}

	/**
	 * Добавляет в массив значения которые нужно сохранять.
	 *
	 * @param string $field_code Название (name) значения.
	 */
	public function set_fields($field_codes)
	{
		$field_codes = (array) $field_codes;

		foreach ($field_codes as $field) {
			$this->set_field( $field );
		}
	}

	private static function get_security_string()
	{
		return apply_filters( 'WP_Post_Boxes::get_security_string', 'Secret' );
	}

	/**
	 * Обертка WP функции add_meta_box, добавляет метабокс по параметрам класса
	 *
	 * @param string $post_type Название используемого типа записи
	 * @access private
	 */
	function add_meta_box($post_type)
	{
		// get post types without WP default (for exclude menu, revision..)
		// $post_types = get_post_types(array('_builtin' => false));
		// $add = array('post', 'page');
		// $post_types = array_merge($post_types, $add);

		if(!empty($this->output_function) && !empty($this->box_name)) {
			$side = ($this->side) ? 'side' : 'advanced';

			add_meta_box(
				'custom-meta-box-' . ++self::$count,
				$this->box_name,
				array($this, 'callback_with_nonce'),
				$this->post_types,
				$side,
				null,
				array( self::get_security_string() )
			);
		}
	}

	function callback_with_nonce()
	{
		call_user_func( $this->output_function );
		wp_nonce_field( self::get_security_string(), $name = '_wp_metabox_nonce' );
	}

	/**
	 * Сохраняем данные при сохранении поста.
	 *
	 * @param int $post_id ID поста, который сохраняется.
	 * @access private
	 */
	function save($post_id) {
		if ( ! isset( $_POST['_wp_metabox_nonce'] ) || ! wp_verify_nonce( $_POST['_wp_metabox_nonce'], self::get_security_string() ) ) {
			return $post_id;
		}

		foreach ($this->meta_fields as $field) {
			if( ! empty($_POST[$field]) ) {
				$meta = is_array($_POST[$field]) ?
					array_filter($_POST[$field], 'sanitize_text_field') : sanitize_text_field( $_POST[$field] );

				update_post_meta( $post_id, $field, $meta );
			}
			else {
				delete_post_meta( $post_id, $field );
			}
		}
	}
}
