<?php
/**
 * デモの見本の中身を作る（Blueprint の runPHP から、1回だけ実行する）。
 * seed.json と、wp-content/uploads に展開された画像を使う。
 * 同じ ID で作る（投稿・メディア・予約リソースの ID を、テンプレートが参照しているため）。
 */
require_once dirname(__DIR__, 2) . '/wp-load.php'; // wp-content/itmaroon-demo/seed.php の2つ上が、WordPress のルート
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
wp_set_current_user(1);

$seed   = json_decode(file_get_contents(__DIR__ . '/seed.json'), true);
$report = array();

update_option('blogname', $seed['options']['blogname']);
update_option('blogdescription', $seed['options']['blogdescription']);
update_option('permalink_structure', '/%postname%/');
update_option('show_on_front', 'posts');

// 1) 添付ファイル（同じ ID）
$uploads = wp_upload_dir();
$n_att   = 0;
foreach ($seed['attachments'] as $a) {
	$path = $uploads['basedir'] . '/' . $a['file'];
	if (! is_file($path)) {
		continue;
	}
	$id = wp_insert_attachment(
		array(
			'import_id'      => (int) $a['id'],
			'post_title'     => $a['title'],
			'post_mime_type' => $a['mime'],
			'post_status'    => 'inherit',
			'guid'           => $uploads['baseurl'] . '/' . $a['file'],
		),
		$path
	);
	if ($id && ! is_wp_error($id)) {
		wp_update_attachment_metadata($id, wp_generate_attachment_metadata($id, $path));
		if ($a['alt']) {
			update_post_meta($id, '_wp_attachment_image_alt', $a['alt']);
		}
		++$n_att;
	}
}
$report[] = "attachments: $n_att";
// ロゴ（サイトロゴのブロックが使う）と、サイトアイコン
if (! empty($seed['options']['custom_logo'])) {
	set_theme_mod('custom_logo', (int) $seed['options']['custom_logo']);
}
if (! empty($seed['options']['site_icon'])) {
	update_option('site_icon', (int) $seed['options']['site_icon']);
}

// 2) タクソノミーの項目
foreach ($seed['terms'] as $tax => $items) {
	foreach ($items as $slug => $name) {
		if (! term_exists($slug, $tax)) {
			wp_insert_term($name, $tax, array('slug' => $slug));
		}
	}
}

// 3) 作品・コンセプト
$insert = function (string $type, array $row) {
	$id = wp_insert_post(
		array(
			'post_type'    => $type,
			'post_status'  => 'publish',
			'post_title'   => $row['title'],
			'post_name'    => $row['slug'],
			'post_date'    => $row['date'],
			'post_excerpt' => $row['excerpt'],
			'post_content' => $row['content'],
		)
	);
	if ($id && ! is_wp_error($id)) {
		if (! empty($row['thumb'])) {
			set_post_thumbnail($id, (int) $row['thumb']);
		}
		foreach ($row['terms'] ?? array() as $tax => $slugs) {
			wp_set_object_terms($id, $slugs, $tax);
		}
	}
	return $id;
};
foreach ($seed['works'] as $w) {
	$id = $insert('works', $w);
	if ($id && ! empty($w['gallery'])) {
		update_post_meta($id, 'gallery', array_map('strval', $w['gallery']));
		update_post_meta($id, '_gallery', 'field_demo_gallery');
	}
}
foreach ($seed['concepts'] as $c) {
	$insert('concept', $c);
}
$report[] = 'works: ' . count($seed['works']) . ', concepts: ' . count($seed['concepts']);

// 4) 固定ページ（予約、お問い合わせ）と、このデモには含めないページ（案内だけ）
foreach ($seed['pages'] as $p) {
	$id = wp_insert_post(array('post_type' => 'page', 'post_status' => 'publish', 'post_title' => $p['title'], 'post_name' => $p['slug'], 'post_content' => $p['content']));
	if ($id && $p['template']) {
		update_post_meta($id, '_wp_page_template', $p['template']);
	}
}
foreach ($seed['stubs'] as $s) {
	wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $s['title'],
			'post_name'    => $s['slug'],
			'post_content' => '<!-- wp:paragraph --><p>このページは、デモには含まれていません。 / This page is not part of the demo.</p><!-- /wp:paragraph -->',
		)
	);
}

// 5) 予約のリソース（同じ ID）と、今日から先の枠
update_option('itmaroon_booking_resource_post_type', 'lesson_resource');
$rid = wp_insert_post(array('import_id' => (int) $seed['resource']['id'], 'post_type' => 'lesson_resource', 'post_status' => 'publish', 'post_title' => $seed['resource']['title']));
$report[] = 'resource: #' . $rid;
$slots = '\\Itmar\\BookingClassPackage\\Reservation\\SlotsAPI';
if ($rid && class_exists($slots)) {
	if (method_exists($slots, 'create_tables')) {
		$slots::create_tables();
	}
	$slots::save_resource_units(
		(int) $rid,
		array_map(
			static fn($u) => array('name' => $u['name'], 'min' => (int) $u['min_capacity'], 'max' => (int) $u['max_capacity']),
			$seed['resource']['units']
		)
	);
	// 今日から 5 週間分の、火〜土曜日（毎回、今日を基準に作る。古くならない）
	$dates = array();
	for ($d = 1; $d <= 35; $d++) {
		$ts = strtotime("+$d day", current_time('timestamp'));
		if (in_array((int) gmdate('w', $ts), array(2, 3, 4, 5, 6), true)) {
			$dates[] = gmdate('Y-m-d', $ts);
		}
	}
	$req = new WP_REST_Request('POST', '/itmar/v1/slots/bulk');
	$req->set_param('resource_id', (int) $rid);
	$req->set_param('dates', $dates);
	$req->set_param('isAllday', false);
	$req->set_param('startTime', '10:00');
	$req->set_param('endTime', '16:00');
	$req->set_param('timeTravel', 60);
	$res = $slots::bulk_upsert_slots($req);
	$report[] = 'slots: ' . count($dates) . ' days ' . (is_wp_error($res) ? 'ERROR ' . $res->get_error_message() : 'ok');
} else {
	$report[] = 'slots: SlotsAPI not available';
}

update_option('itmaroon_demo_seeded', gmdate('c'));
flush_rewrite_rules();
file_put_contents(__DIR__ . '/seed-report.txt', implode("\n", $report));
