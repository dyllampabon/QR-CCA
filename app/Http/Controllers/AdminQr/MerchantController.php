<?php

namespace App\Http\Controllers\AdminQr;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use Illuminate\Http\Request;

class MerchantController extends Controller
{
    public function index()
    {
        return view('adminqr.merchants.index');
    }

    public function ajaxList(Request $request)
    {
        $query = Merchant::query();

        // BUSQUEDA
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('rzsocial', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%")
                  ->orWhere('name', 'like', "%{$s}%")
                  ->orWhere('nit', 'like', "%{$s}%");
            });
        }

        // FILTROS
        if ($request->filled('merchant_type')) {
            switch ($request->merchant_type) {
                case 'active':
                    $query->where('is_active', 1);
                    break;
                case 'vip':
                    $query->where('is_vip', 1);
                    break;
                case 'ally':
                    $query->where('is_ally', 1);
                    break;
            }
        }

        $merchants = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'table' => view('adminqr.merchants.partials.table', compact('merchants'))->render(),
            'pagination' => view('adminqr.merchants.partials.pagination', compact('merchants'))->render()
        ]);
    }

    /* ----------------- CRUD NORMAL ----------------- */

    public function create()
    {
        return view('adminqr.merchants.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'rzsocial' => 'required|string|max:255',
            'email' => 'required|email|unique:merchants,email',
            'nit' => 'required|string|max:50|unique:merchants,nit',
        ]);

        Merchant::create([
            'rzsocial' => $request->rzsocial,
            'email' => $request->email,
            'nit' => $request->nit,
            'is_active' => $request->boolean('is_active', false),
        ]);

        return redirect()->route('adminqr.merchants.index')
            ->with('success', '✅ Comerciante agregado exitosamente.');
    }

    public function edit(Merchant $merchant)
    {
        return view('adminqr.merchants.edit', compact('merchant'));
    }

    public function update(Request $request, Merchant $merchant)
    {
        $request->validate([
            'is_ally' => 'nullable|boolean',
            'discount_common' => 'nullable|integer|min:0|max:100',
            'discount_vip' => 'nullable|integer|min:0|max:100',
        ]);

        // ACTUALIZACIÓN
        $merchant->is_ally = $request->boolean('is_ally');

        if ($merchant->is_ally) {
            $merchant->discount_common = $request->discount_common ?? 0;
            $merchant->discount_vip = $request->discount_vip ?? 0;
        } else {
            $merchant->discount_common = 0;
            $merchant->discount_vip = 0;
        }

        $merchant->save();

        return redirect()->route('adminqr.merchants.index')
            ->with('success', '✅ Comerciante actualizado exitosamente.');
    }

    public function destroy(Merchant $merchant)
    {
        $merchant->delete();
        return redirect()->route('adminqr.merchants.index')
            ->with('success', '🗑️ Comerciante eliminado exitosamente.');
    }

    public function downloadQr(Merchant $merchant)
    {
        $qr = $merchant->qrCode;

        if (!$qr || !$qr->filename || !\Storage::disk('public')->exists($qr->filename)) {
            return back()->with('error', 'El QR no está disponible para este comerciante.');
        }

        $nombreArchivo = \Str::slug($merchant->rzsocial ?? $merchant->name, '_') . '_qr.png';

        return \Storage::disk('public')->download($qr->filename, $nombreArchivo);
    }
}
