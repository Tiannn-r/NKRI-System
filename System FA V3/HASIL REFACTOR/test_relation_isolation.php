<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Controllers\MasterStockController;

echo "=== TEST CLEAN-SLATE RELATION ISOLATION ===\n";

$stockTersedia = DB::table('master_stocks')->where('status', 'Tersedia')->first();
if (!$stockTersedia) {
    echo "Tidak ada part dengan status Tersedia!\n";
    exit(1);
}

echo "1. Menguji Part Tersedia: SN {$stockTersedia->sn} (Tipe {$stockTersedia->part_type})\n";

$ctrl = app()->make(MasterStockController::class);

// 1. getConnectedSet
$req = Request::create('/api/master/get-connected-set', 'GET', ['sn' => $stockTersedia->sn]);
$res = $ctrl->getConnectedSet($req);
$data = $res->getData();
echo "   - getConnectedSet result: " . json_encode($data->data) . "\n";
assert($data->data === null, "getConnectedSet HARUS null untuk part Tersedia!");

// 2. partDetail
$reqDetail = Request::create('/api/master/part-detail', 'GET', ['sn' => $stockTersedia->sn]);
$resDetail = $ctrl->partDetail($reqDetail);
$detailData = $resDetail->getData()->data;
echo "   - partDetail tray: " . json_encode($detailData->tray) . "\n";
echo "   - partDetail assembly: " . json_encode($detailData->assembly) . "\n";
assert($detailData->tray === null, "tray HARUS null untuk part Tersedia!");
assert($detailData->assembly === null, "assembly HARUS null untuk part Tersedia!");

// 3. stocksList display_status
$reqList = Request::create('/api/master/stocks_list', 'GET', ['q' => $stockTersedia->sn]);
$resList = $ctrl->stocksList($reqList);
$listData = $resList->getData()->data;
if (count($listData) > 0) {
    echo "   - stocksList display_status: " . $listData[0]->display_status . "\n";
    echo "   - stocksList tray_code: " . json_encode($listData[0]->tray_code) . "\n";
    echo "   - stocksList set_number: " . json_encode($listData[0]->set_number) . "\n";
    assert($listData[0]->display_status === 'Tersedia', "display_status HARUS Tersedia!");
    assert($listData[0]->tray_code === null, "tray_code HARUS null untuk part Tersedia!");
    assert($listData[0]->set_number === null, "set_number HARUS null untuk part Tersedia!");
}

echo "\n[PASS] SELURUH PENGUJIAN RELASI BERSIH (CLEAN-SLATE) LULUS 100%!\n";
