<?php
use App\Models\{Plugin, Device, DeviceModel, User};
use App\Services\{PluginImportService, ImageGenerationService};
use Illuminate\Http\UploadedFile;
use Carbon\Carbon;

Plugin::where('name', 'BookPoster (test)')->get()->each->delete();
$file = new UploadedFile('/tmp/bookposter.zip', 'bookposter.zip', 'application/zip', null, true);
$p = Plugin::find(app(PluginImportService::class)->importFromZip($file, User::find(1))->id);
$base = ['genre' => ['1000000'], 'pocket' => 'no'];
$p->update(['name' => 'BookPoster (test)', 'configuration' => $base + ['style' => 'eink', 'language' => 'fr', 'featured' => 'rotate']]);
Carbon::setTestNow(Carbon::create(2026, 10, 21, 10, 0, 0, 'UTC'));
$urls = fn() => array_values(array_filter(array_map('trim', explode("\n", $p->fresh()->resolveLiquidVariables($p->polling_url)))));
echo "url=", $urls()[0], "\n";
$p->configuration = ['genre' => ['1000000', '90000000', '91000000', '92000000', '2000000', '9000000']] + $base; $p->saveQuietly();
echo "url=", count($urls()), " urls for 6 genres\n";

// Listes Place des Libraires (sans les poches) figées le 21 octobre 2026, pour la littérature puis le polar :
// à paraître d'ici six semaines, parus ces deux dernières semaines
$both = json_decode(file_get_contents('/tmp/test_payload.json'), true);
$feed = array_slice($both, 0, 2);
// Réponse Hardcover (littérature, en anglais) figée le 21 octobre 2026 ; une seule URL, donc pas de clé IDX_
$en = json_decode(file_get_contents('/tmp/test_payload_en.json'), true);
echo 'payload bytes: ', strlen(json_encode($both)), ' / limit ', Plugin::maxDataPayloadBytesForWire() ?? 'none', "\n";
// le livre à paraître le plus attendu, sans couverture
$noimg = $feed; preg_match('#/livre/(\d{13})-#', $feed['IDX_0']['data'], $m);
$noimg['IDX_0']['data'] = str_replace($m[1], '9782000000999', $feed['IDX_0']['data']);
$down = array_fill_keys(['IDX_0', 'IDX_1'], ['error' => 'Failed to fetch data']);
$empty = array_fill_keys(array_keys($down), ['data' => '<html><body>Aucun produit trouvé</body></html>']);
// heure UTC du 21/10 dont le créneau horaire vaut $k modulo 9 (rotation : rubrique = k % 3, rang = k / 3)
$slot = fn($k) => (int) collect(range(0, 23))->first(fn($h) => intdiv(Carbon::create(2026, 10, 21, $h, 0, 0, 'UTC')->timestamp, 3600) % 9 == $k);
$cases = [
  // nom, taille, modèle, style, langue, affiche, données, heure (UTC), genre
  ['fr_color_hot', 'full', 55, 'color', 'fr', 'hot', $feed, 10],
  ['en_eink_hot', 'full', 1, 'eink', 'en', 'hot', $en, 10],
  ['fr_color_now', 'full', 55, 'color', 'fr', 'now', $feed, 10],
  ['fr_eink_now', 'full', 1, 'eink', 'fr', 'now', $feed, 10],
  ['fr_color_upcoming', 'full', 55, 'color', 'fr', 'upcoming', $feed, 10],
  ['fr_x_upcoming', 'full', 14, 'eink', 'fr', 'upcoming', $feed, 10],
  ['fr_color_rotate_hot2', 'full', 55, 'color', 'fr', 'rotate', $feed, $slot(3)],
  ['en_color_rotate_now3', 'full', 55, 'color', 'en', 'rotate', $en, $slot(7)],
  ['fr_color_multi_hot2', 'full', 55, 'color', 'fr', 'rotate', $both, $slot(3), ['1000000', '90000000']],
  ['fr_eink_multi_upcoming', 'full', 1, 'eink', 'fr', 'upcoming', $both, 10, ['1000000', '90000000']],
  ['fr_q_multi3', 'quadrant', 1, 'eink', 'fr', 'now', $both + ['IDX_4' => ['error' => 'Failed to fetch data']], 10, ['1000000', '90000000', '91000000']],
  ['fr_eink_noimg', 'full', 1, 'eink', 'fr', 'hot', $noimg, 10],
  ['fr_hh', 'half_horizontal', 1, 'eink', 'fr', 'hot', $feed, 10],
  ['fr_hv', 'half_vertical', 1, 'eink', 'fr', 'now', $feed, 10],
  ['fr_q', 'quadrant', 1, 'eink', 'fr', 'upcoming', $feed, 10],
  ['en_q', 'quadrant', 1, 'eink', 'en', 'hot', $en, 10],
  ['en_hh', 'half_horizontal', 1, 'eink', 'en', 'upcoming', $en, 10],
  ['fr_error', 'full', 1, 'eink', 'fr', 'rotate', $down, 10],
  ['fr_empty', 'full', 55, 'color', 'fr', 'rotate', $empty, 10, ['90000000']],
  ['en_color_upcoming', 'full', 55, 'color', 'en', 'upcoming', $en, 10],
  ['en_error', 'full', 1, 'eink', 'en', 'rotate', ['error' => 'Failed to fetch data'], 10],
  ['en_nokey', 'full', 1, 'eink', 'en', 'rotate', ['error' => 'Failed to fetch data'], 10, null, ''],
];
@mkdir('/tmp/bpr'); array_map('unlink', glob('/tmp/bpr/*.png'));
foreach ($cases as $c) {
  [$name, $size, $mid, $style, $lang, $feat, $payload, $h] = $c;
  Carbon::setTestNow(Carbon::create(2026, 10, 21, $h, 0, 0, 'UTC'));
  $p->configuration = ['genre' => $c[8] ?? ['1000000'], 'hardcover_token' => $c[9] ?? 'test', 'pocket' => 'no', 'style' => $style, 'language' => $lang, 'featured' => $feat];
  $p->data_payload = $payload; $p->saveQuietly();
  $model = DeviceModel::find($mid);
  $dev = new Device(['name' => 'test']); $dev->setRelation('deviceModel', $model);
  $html = $p->fresh()->render($size, true, $dev);
  if ($name == 'fr_color_hot') file_put_contents('/tmp/bpr/sample.html', $html);
  $pal = $mid == 55 ? App\Models\DevicePalette::find(12) : null;
  $id = ImageGenerationService::generateImageFromModel(markup: $html, deviceModel: $model, palette: $pal);
  copy(storage_path("app/public/images/generated/$id.png"), "/tmp/bpr/$name.png");
  echo "$name ok\n";
}
Carbon::setTestNow();
// listes réelles, sur demande ; Hardcover avec le jeton passé dans HC_TOKEN
if (getenv('HC_TOKEN')) {
  $p->configuration = ['genre' => ['90000000', '91000000'], 'hardcover_token' => getenv('HC_TOKEN'), 'pocket' => 'no', 'language' => 'en', 'featured' => 'rotate', 'style' => 'color'];
  $p->saveQuietly(); $p->updateDataPayload();
  $live = $p->fresh()->data_payload;
  echo 'live payload en: ', isset($live['data']) ? count($live['data']['coming']) . ' coming, ' . count($live['data']['recent']) . ' recent, first ' . ($live['data']['coming'][0]['title'] ?? '') : json_encode($live), "\n";
}
if (getenv('LIVE')) {
  // quatre rayons : la plus grosse charge possible, qui doit rester sous la limite de LaraPaper
  $p->configuration = ['genre' => ['1000000', '90000000', '91000000', '3000000-3040000'], 'pocket' => 'no', 'language' => 'fr', 'featured' => 'rotate', 'style' => 'color'];
  $p->saveQuietly(); $p->updateDataPayload();
  $live = $p->fresh()->data_payload;
  echo 'live payload: ', strlen(json_encode($live, JSON_PRETTY_PRINT)), ' bytes pretty · ', implode(' | ', array_map(fn($w) => isset($w['data']) ? substr_count($w['data'], 'livre_titre') . ' livres, ' . strlen($w['data']) . ' o' : json_encode($w), $live)), "\n";
}
