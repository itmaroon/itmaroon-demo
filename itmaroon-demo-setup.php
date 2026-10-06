<?php
/**
 * Plugin Name: ITmaroon Demo Setup
 * Description: Registers the post types, taxonomies and custom fields that the demo theme expects.
 *              Used only by the WordPress Playground demo (a must-use plugin). Not for production sites.
 */

if (! defined('ABSPATH')) {
	exit;
}

add_action('init', function () {
	register_post_type('works', array(
		'label'        => '作品',
		'labels'       => array('singular_name' => '作品', 'add_new_item' => '作品を追加'),
		'public'       => true,
		'has_archive'  => true,
		'show_in_rest' => true,
		'menu_icon'    => 'dashicons-products',
		'supports'     => array('title', 'editor', 'thumbnail', 'custom-fields', 'excerpt'),
		'rewrite'      => array('slug' => 'works'),
		'taxonomies'   => array('creator', 'kind'),
	));
	register_post_type('concept', array(
		'label'        => 'コンセプト',
		'labels'       => array('singular_name' => 'コンセプト', 'add_new_item' => 'コンセプトを追加'),
		'public'       => true,
		'has_archive'  => true,
		'show_in_rest' => true,
		'menu_icon'    => 'dashicons-id-alt',
		'supports'     => array('title', 'editor', 'thumbnail', 'author', 'custom-fields', 'excerpt'),
		'rewrite'      => array('slug' => 'concept'),
	));
	// 予約の対象（教室）。公開ページは持たない（管理画面と REST だけ）
	register_post_type('lesson_resource', array(
		'label'              => '教室',
		'labels'             => array('singular_name' => '教室', 'add_new_item' => '教室を追加'),
		'public'             => false,
		'publicly_queryable' => false,
		'show_ui'            => true,
		'show_in_rest'       => true,
		'menu_icon'          => 'dashicons-welcome-learn-more',
		'supports'           => array('title', 'editor', 'thumbnail', 'excerpt'),
	));
	register_taxonomy('creator', array('post', 'works'), array(
		'label'             => '作者',
		'hierarchical'      => true,
		'public'            => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
		'rewrite'           => array('slug' => 'creator'),
	));
	register_taxonomy('kind', array('works'), array(
		'label'             => '種別',
		'hierarchical'      => true,
		'public'            => true,
		'show_in_rest'      => true,
		'show_admin_column' => true,
		'rewrite'           => array('slug' => 'kind'),
	));
}, 5);

// カスタムフィールド（Secure Custom Fields）: 作品の画像ギャラリー
add_action('acf/init', function () {
	if (! function_exists('acf_add_local_field_group')) {
		return;
	}
	acf_add_local_field_group(array(
		'key'      => 'group_demo_works',
		'title'    => '作品情報',
		'fields'   => array(
			array('key' => 'field_demo_gallery', 'label' => 'ギャラリー', 'name' => 'gallery', 'type' => 'gallery', 'return_format' => 'array'),
			array(
				'key'        => 'field_demo_option_img_group',
				'label'      => '画像オプション',
				'name'       => 'option_img_group',
				'type'       => 'group',
				'sub_fields' => array(
					array('key' => 'field_demo_option_1', 'label' => 'オプション1', 'name' => 'option_1', 'type' => 'image'),
					array('key' => 'field_demo_option_2', 'label' => 'オプション2', 'name' => 'option_2', 'type' => 'image'),
				),
			),
		),
		'location' => array(array(array('param' => 'post_type', 'operator' => '==', 'value' => 'works'))),
	));
});
