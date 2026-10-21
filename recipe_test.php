<?php
use App\Models\{Plugin, Device, DeviceModel, User};
use App\Services\{PluginImportService, ImageGenerationService};
use Illuminate\Http\UploadedFile;
use Carbon\Carbon;

Plugin::where('name', 'BookPoster (test)')->get()->each->delete();
$file = new UploadedFile('/tmp/bookposter.zip', 'bookposter.zip', 'application/zip', null, true);
$p = Plugin::find(app(PluginImportService::class)->importFromZip($file, User::find(1))->id);
$base = ['genre' => '1000000', 'pocket' => 'no'];
$p->update(['name' => 'BookPoster (test)', 'configuration' => $base + ['style' => 'eink', 'language' => 'fr', 'featured' => 'rotate']]);
Carbon::setTestNow(Carbon::create(2026, 10, 21, 10, 0, 0, 'UTC'));
echo "url=", explode("\n", $p->resolveLiquidVariables($p->polling_url))[0], "\n";

// Listes Place des Libraires (littérature, sans les poches) figées le 21 octobre 2026 :
// à paraître d'ici deux mois, parus ces deux dernières semaines, à paraître d'ici deux semaines
$feed = json_decode(file_get_contents('/tmp/test_payload.json'), true);
echo 'payload bytes: ', strlen(json_encode($feed)), ' / limit ', Plugin::maxDataPayloadBytesForWire() ?? 'none', "\n";
// le livre à paraître le plus attendu, sans couverture
$noimg = $feed; preg_match('#/livre/(\d{13})-#', $feed['IDX_0']['data'], $m);
$noimg['IDX_0']['data'] = str_replace($m[1], '9782000000999', $feed['IDX_0']['data']);
$down = array_fill_keys(['IDX_0', 'IDX_1', 'IDX_2'], ['error' => 'Failed to fetch data']);
$empty = array_fill_keys(array_keys($down), ['data' => '<html><body>Aucun produit trouvé</body></html>']);
// heure UTC du 21/10 dont le créneau horaire vaut $k modulo 9 (rotation : rubrique = k % 3, rang = k / 3)
$slot = fn($k) => (int) collect(range(0, 23))->first(fn($h) => intdiv(Carbon::create(2026, 10, 21, $h, 0, 0, 'UTC')->timestamp, 3600) % 9 == $k);
$cases = [
  // nom, taille, modèle, style, langue, affiche, données, heure (UTC), genre
  ['fr_color_hot', 'full', 55, 'color', 'fr', 'hot', $feed, 10],
  ['en_eink_hot', 'full', 1, 'eink', 'en', 'hot', $feed, 10],
  ['fr_color_now', 'full', 55, 'color', 'fr', 'now', $feed, 10],
  ['fr_eink_now', 'full', 1, 'eink', 'fr', 'now', $feed, 10],
  ['fr_color_upcoming', 'full', 55, 'color', 'fr', 'upcoming', $feed, 10],
  ['fr_x_upcoming', 'full', 14, 'eink', 'fr', 'upcoming', $feed, 10],
  ['fr_color_rotate_hot2', 'full', 55, 'color', 'fr', 'rotate', $feed, $slot(3)],
  ['en_color_rotate_now3', 'full', 55, 'color', 'en', 'rotate', $feed, $slot(7)],
  ['fr_eink_noimg', 'full', 1, 'eink', 'fr', 'hot', $noimg, 10],
  ['fr_hh', 'half_horizontal', 1, 'eink', 'fr', 'hot', $feed, 10],
  ['fr_hv', 'half_vertical', 1, 'eink', 'fr', 'now', $feed, 10],
  ['fr_q', 'quadrant', 1, 'eink', 'fr', 'upcoming', $feed, 10],
  ['en_q', 'quadrant', 1, 'eink', 'en', 'hot', $feed, 10],
  ['en_hh', 'half_horizontal', 1, 'eink', 'en', 'upcoming', $feed, 10],
  ['fr_error', 'full', 1, 'eink', 'fr', 'rotate', $down, 10],
  ['fr_empty', 'full', 55, 'color', 'fr', 'rotate', $empty, 10, '90000000'],
];
@mkdir('/tmp/bpr'); array_map('unlink', glob('/tmp/bpr/*.png'));
foreach ($cases as $c) {
  [$name, $size, $mid, $style, $lang, $feat, $payload, $h] = $c;
  Carbon::setTestNow(Carbon::create(2026, 10, 21, $h, 0, 0, 'UTC'));
  $p->configuration = ['genre' => $c[8] ?? '1000000', 'pocket' => 'no', 'style' => $style, 'language' => $lang, 'featured' => $feat];
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
// listes réelles, sur demande
if (getenv('LIVE')) {
  $p->configuration = ['genre' => '90000000', 'pocket' => 'no', 'language' => 'fr', 'featured' => 'rotate', 'style' => 'color'];
  $p->saveQuietly(); $p->updateDataPayload();
  $live = $p->fresh()->data_payload;
  echo 'live payload: ', implode(' | ', array_map(fn($w) => isset($w['data']) ? substr_count($w['data'], 'livre_titre') . ' livres, ' . strlen($w['data']) . ' o' : json_encode($w), $live)), "\n";
}
