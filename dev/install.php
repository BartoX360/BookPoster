<?php
// Importe la recette comme « BookPoster » avec l'affichage de l'Echo Show
use App\Models\{Plugin, User};
use App\Services\PluginImportService;
use Illuminate\Http\UploadedFile;
Plugin::where('name', 'like', 'BookPoster%')->get()->each->delete();
$file = new UploadedFile('/tmp/bookposter.zip', 'bookposter.zip', 'application/zip', null, true);
$p = Plugin::find(app(PluginImportService::class)->importFromZip($file, User::find(1))->id);
$p->name = 'BookPoster';
$p->configuration = ['genre' => '1000000', 'pocket' => 'no', 'featured' => 'rotate', 'style' => 'color', 'language' => 'fr'];
$p->save();
$p->updateDataPayload();
$d = App\Models\Device::find(2);
$id = App\Services\ImageGenerationService::generateImage($p->fresh()->render('full', true, $d), 2, $p);
copy(storage_path("app/public/images/generated/$id.png"), '/tmp/bp_live.png');
echo "installed id {$p->id}: ", substr_count($p->fresh()->data_payload['IDX_0']['data'] ?? '', 'livre_titre'), " books coming\n";
