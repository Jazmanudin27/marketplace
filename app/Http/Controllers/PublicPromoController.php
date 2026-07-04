<?php

namespace App\Http\Controllers;

use App\Models\FlashSale;
use Illuminate\Http\Request;

class PublicPromoController extends Controller
{
    public function flashSaleCatalog($id)
    {
        $flashSale = FlashSale::with(['store.channel', 'items.masterProduct'])
            ->findOrFail($id);

        $compStatus = $flashSale->computed_status;

        // Allow public viewing for ACTIVE, UPCOMING, and ENDED
        $items = $flashSale->items;

        return view('public.flash_sale', compact('flashSale', 'items', 'compStatus'));
    }
}
