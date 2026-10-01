<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Inventory\SpkController as BaseSpkController;
use Illuminate\Http\Request;

class SpkController extends BaseSpkController
{
    /**
     * Display V2 SPK Produksi Index List.
     */
    public function index(Request $request)
    {
        $response = parent::index($request);

        if ($response instanceof \Illuminate\View\View) {
            return view('v2.spk.index', $response->getData());
        }

        return $response;
    }

    /**
     * Display V2 SPK Produksi Create Form.
     */
    public function create(Request $request)
    {
        $response = parent::create($request);

        if ($response instanceof \Illuminate\View\View) {
            if (view()->exists('v2.spk.create')) {
                return view('v2.spk.create', $response->getData());
            }
            // Fallback to V1 view if v2 create template is not yet initialized
            return $response;
        }

        return $response;
    }

    /**
     * Display V2 SPK Produksi Show/Detail Page.
     */
    public function show($spk)
    {
        $response = parent::show($spk);

        if ($response instanceof \Illuminate\View\View) {
            if (view()->exists('v2.spk.show')) {
                return view('v2.spk.show', $response->getData());
            }
            // Fallback to V1 view if v2 show template is not yet initialized
            return $response;
        }

        return $response;
    }

    /**
     * Display V2 SPK Payments Page.
     */
    public function paymentsIndex(Request $request)
    {
        $response = parent::paymentsIndex($request);

        if ($response instanceof \Illuminate\View\View) {
            if (view()->exists('v2.spk.payments')) {
                return view('v2.spk.payments', $response->getData());
            }
            return $response;
        }

        return $response;
    }
}
