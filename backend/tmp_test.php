<?php
require __DIR__ . "/vendor/autoload.php";
$app = require __DIR__ . "/bootstrap/app.php";
$app->make(Illuminate\Contracts\Http\Kernel::class)->handle(Illuminate\Http\Request::capture());
$sql = App\Models\Equipment::whereNotNull("verification_frequency")->where(function($q){$q->orWhere(function($q2){$q2->where("verification_frequency","daily")->where(function($q3){$q3->whereDoesntHave("verifications")->orWhereHas("verifications",function($h){$h->select("equipment_id")->groupBy("equipment_id")->havingRaw("MAX(verified_at) < ?",[now()->subDay()]);});});});});
echo $sql->toSql() . "\n";