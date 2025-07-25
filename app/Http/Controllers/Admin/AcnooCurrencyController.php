<?php

namespace App\Http\Controllers\Admin;

use App\Models\Currency;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;

class AcnooCurrencyController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:currencies-create')->only('create', 'store');
        $this->middleware('permission:currencies-read')->only('index');
        $this->middleware('permission:currencies-update')->only('edit', 'update', 'default');
        $this->middleware('permission:currencies-delete')->only('destroy');
    }

    public function index()
    {
        $currencies = Currency::orderBy('is_default', 'desc')->orderBy('status', 'desc')->paginate();
        return view('admin.currencies.index', compact('currencies'));
    }

    public function maanFilter(Request $request)
    {

        $currencies = Currency::orderBy('is_default', 'desc')->orderBy('status', 'desc')
            ->when(request('search'), function($q){
                    $q->where('name', 'like', '%'.request('search').'%')
                        ->orWhere('code', 'like', '%'.request('search').'%')
                        ->orWhere('symbol', 'like', '%'.request('search').'%');
                })

            ->latest()
            ->paginate($request->per_page ?? 20);

        if(request()->ajax()){
            return response()->json([
                'data' => view('admin.currencies.datas', compact('currencies'))->render()
            ]);
        }
        return redirect(url()->previous());
    }


    public function create()
    {
        return view('admin.currencies.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:currencies',
            'code' => 'required|string|unique:currencies',
            'rate' => 'nullable|numeric',
            'symbol' => 'nullable|string',
            'position' => 'nullable|string',
            'status' => 'required|integer',
            'is_default' => 'nullable|boolean',
        ]);

        Currency::create($request->all());
        Cache::forget('default_currency');

        return response()->json([
            'message'   => __('Currency updated successfully'),
            'redirect'  => route('admin.currencies.index')
        ]);
    }

    public function edit(Currency $currency)
    {
        return view('admin.currencies.edit',compact('currency'));
    }


    public function update(Request $request, Currency $currency)
    {
        $request->validate([
            'name' => 'required|string|unique:currencies,name,'.$currency->id,
            'code' => 'required|string|unique:currencies,code,'.$currency->id,
            'rate' => 'nullable|numeric',
            'symbol' => 'nullable|string',
            'position' => 'nullable|string',
            'status' => 'required|integer',
            'is_default' => 'nullable|boolean',
        ]);

        $currency->update($request->all());
        Cache::forget('default_currency');

        return response()->json([
            'message'   => __('Currency updated successfully'),
            'redirect'  => route('admin.currencies.index')
        ]);
    }


    public function default($id)
    {
        $currency = Currency::find($id);

        if ($currency) {
            Currency::where('id', '!=', $id)->update(['is_default' => 0]);
            $currency->update(['is_default' => 1]);
        }

        Cache::forget('default_currency');
        return redirect()->route('admin.currencies.index')->with('message', __('Default currency activated successfully'));
    }

    public function destroy(Currency $currency)
    {
        if ($currency->is_default) {
            return response()->json([
                'message' => 'The default currency can not be deleted',
            ], 403);
        }

        $currency->delete();

        return response()->json([
            'message' => 'Currency deleted successfully',
            'redirect' => route('admin.currencies.index')
        ]);
    }
}
