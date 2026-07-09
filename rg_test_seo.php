<?php
use Illuminate\Support\Facades\DB;
$blocks = DB::table('rg_content_blocks')->where('owner_type','static_page')->where('owner_id',5)->orderBy('sort_order')->get();
$an = app(\App\Services\SeoAnalyzer::class);
$imgs = $an->extractImagesFromBlocks($blocks);
echo "HOMEPAGE images found: ".count($imgs)."\n";
$noAlt=0;$noTitle=0;
foreach($imgs as $im){ if(trim($im['alt'])==='')$noAlt++; if(trim($im['title'])==='')$noTitle++; }
echo "missing alt: $noAlt | missing title: $noTitle\n";
echo "--- sample (first 6) ---\n";
foreach(array_slice($imgs,0,6) as $im){ echo "  src=".substr($im['src'],0,42)." | alt=".($im['alt']!==''?'\"'.substr($im['alt'],0,28).'\"':'(none)')." | title=".($im['title']!==''?'set':'(none)')."\n"; }
echo "--- imageChecks() ---\n";
foreach($an->imageChecks($blocks) as $c){ echo "  [".$c['status']."] ".$c['label'].": ".$c['message']."\n"; }
