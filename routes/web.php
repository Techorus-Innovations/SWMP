<?php

use Illuminate\Support\Facades\Route;
use App\Services\SapService;

Route::get('/', function () {
    return view('welcome');
});



Route::get('/test', function () {
    $sapService = new SapService();
    $materials = $sapService->getMaterials();
    return $materials;
});


// Route::get('/test2', function () {
//     $sapService=new SapService();
//     $materials = $sapService->createCustomer($request_id="6", $partner_group_code="CSW1", $name1="Test", $name2="Customer", $gln_loc_no_1="12345", $gln_loc_no_2="54321", $gln_check_digit="0", $city="Cairo", $street="10", $house_no="1", $postal_code="10001", $country_code="EG", $region_code="GZ", $tax_number="557766", $commercial_registration_number="557766", $industry_code="Z1", $customer_type_code="01", $tax_category="1", $mobile="+20123",$email="john.doe@example.com");
//     return $materials;
// });

Route::get('/test3', function () {
    $sapService = new SapService();
    $contracts = $sapService->getInboundRequests('1000000000', 10, 1);
    return $contracts;
});
Route::get('/test4', function () {
    $sapService = new SapService();
    $contracts = $sapService->getOutboundRequests('1000000000', 10, 1);
    return $contracts;
});


Route::get('/test5', function () {
    $sapService = new SapService();
    $stock = $sapService->getStock('1000000000', 10, 1);
    return $stock;
});


Route::get('/test6', function () {
    $sapService = new SapService();
    $stock = $sapService->createInbound("1000000000", "3010000000", ["000000000000000007" => 100, "000000000000000012" => 10], "2026-03-01", "13");
    return $stock;
});


Route::get('/test7', function () {
    $sapService = new SapService();
    $slots = $sapService->getDeliverySlotsReservedCounts("2026-03-01");
    return $slots;
});
Route::get('/t', function () {

    echo "Loaded php.ini: " . php_ini_loaded_file() . "\n";
    print_r(openssl_get_cert_locations());


});



Route::get('/test8', function () {
    $sapService = new SapService();
    $RequestDetails = $sapService->cancelInboundRequestBatch(1010001044);
    return $RequestDetails;
});

use Illuminate\Support\Facades\Hash;
Route::get('/test9', function () {
    dd(Hash::check('c3ebc5297439f464174670fb8becd220cc54e29233d7aa8de9cff84184b5db46', '$2y$12$XgyN06p91noLATgc//xUUu0hhJigLhUdH3iVTkVbVX48EEvmqJe56'));
    return 'test' . (string) Hash::check('185921febfc2ea1cc9dd02d7269f42589307b9c7296f14db2619eda380eccdfe', '$2y$12$ZNUe01hfhsDHVxNd9DdfX..JO/4xSM7hQ5YCjx5S8QIvO83ma98JW');
});

use App\Models\User;
use App\Models\PartnerRole;

Route::get('/test10', function () {
    $allPermissions = PartnerRole::all()->pluck('id')->toArray();
    // dd($allPermissions);
    dd(User::find('01kjmqhhkdxb8bv8cy7ggy2gnc')->partnerRoles()->sync($allPermissions));
    // return 'test' . (string) Hash::check('185921febfc2ea1cc9dd02d7269f42589307b9c7296f14db2619eda380eccdfe', '$2y$12$ZNUe01hfhsDHVxNd9DdfX..JO/4xSM7hQ5YCjx5S8QIvO83ma98JW');
});
