<?php
namespace Tests\Feature;
use App\Models\{Category,Product,User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ScratchHeadTest extends TestCase {
    use RefreshDatabase;
    public function test_dump(): void {
        $store = User::create(['name'=>'S','first_name'=>'S','last_name'=>'S','email'=>'s'.uniqid().'@e.com','role'=>'store_owner','password'=>'password123']);
        Category::create(['name'=>'Nitrogen Fertilizer','slug'=>'nf-'.uniqid(),'parent_id'=>null]);
        $csv = "sku,name,category,price,stock_quantity,reorder_threshold,recommended_season,status\nA1,Urea,Nitrogen Fertilizer,100,10,2,both,active\n";
        $f = tempnam(sys_get_temp_dir(),'imp').'.csv'; file_put_contents($f,$csv);
        $this->actingAs($store)->post(route('store.products.import.preview'),['file'=>$f]);
        $html = $this->actingAs($store)->get(route('store.products.import'))->getContent();
        $head = substr($html, strpos($html,'<thead>'), strpos($html,'</thead>')-strpos($html,'<thead>'));
        preg_match_all('/<th[^>]*>(.*?)<\/th>/s',$head,$m);
        fwrite(STDERR, "TH(".count($m[1])."): ".implode(' | ',array_map(fn($x)=>trim(strip_tags($x)),$m[1]))."\n");
        $tb = substr($html, strpos($html,'<tbody>'), strpos($html,'</tr>')-strpos($html,'<tbody>'));
        preg_match_all('/<td[^>]*>/',$tb,$m2);
        fwrite(STDERR, "TD(".count($m2[0]).")\n");
        $this->assertTrue(true);
    }
}
