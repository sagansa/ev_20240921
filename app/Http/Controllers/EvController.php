<?php

namespace App\Http\Controllers;

use App\Models\Charger;
use App\Models\ChargerLocation;
use App\Models\Product;
use App\Models\Provider;
use App\Models\CurrentCharger;
use App\Models\PowerCharger;
use App\Models\TypeCharger;
use App\Models\Province;
use App\Models\City;
use Illuminate\Http\Request;

class EvController extends Controller
{
    public function index()
    {
        // Arahkan ke halaman map atau halaman lain yang Anda inginkan
        return view('layouts.ev.home');
    }

    public function map()
    {
        $chargerLocations = ChargerLocation::with([
            'provider',
            'city',
            'chargers.powerCharger',
            'chargers.currentCharger',
            'chargers.typeCharger',
            'user' // Tambahkan ini untuk memuat relasi user
        ])
            ->where('location_on', ['1', '3'])
            ->where('status', '<>', 3)  // Tambahkan kondisi ini
            ->get();

        $providers = Provider::has('chargerLocations')->orderBy('name', 'asc')->get();
        $currentChargers = CurrentCharger::orderBy('name', 'asc')->get();

        return view('layouts.ev.map', compact('chargerLocations', 'providers', 'currentChargers'));
    }

    public function providers(Request $request)
    {
        $query = Provider::query()
            ->where('status', 1)  // Hanya provider dengan status aktif
            ->where('public', 1)  // Hanya provider yang bersifat publik
            ->withCount('chargerLocations');  // Menambahkan hitungan charger_locations

        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        // Jika tidak ada pencarian atau pengurutan yang diminta, gunakan pengurutan acak
        if (!$request->search && !$request->sort) {
            $providers = $query->inRandomOrder()->get();
        } else {
            // Jika ada pencarian atau pengurutan, gunakan logika yang sudah ada
            $providers = $query->when($request->sort, function ($query, $sort) use ($request) {
                $query->orderBy($sort, $request->direction ?? 'asc');
            })->get();
        }

        return view('layouts.ev.providers', compact('providers'));
    }

    public function products()
    {
        $products = Product::where('online_category_id', '31')->orderBy('name', 'asc')->get();
        return view('layouts.ev.products', compact('products'));
    }

    public function contact()
    {
        return view('layouts.ev.contact');
    }

    public function chargers(Request $request)
    {
        $query = Charger::with(['chargerLocation.province', 'chargerLocation.city', 'powerCharger', 'currentCharger', 'typeCharger', 'merkCharger', 'chargerLocation.provider'])
            ->whereHas('chargerLocation', function ($q) {
                $q->whereIn('location_on', [1, 3])
                    ->where('status', 2);
            });

        // Jika semua filter kosong, anggap sebagai reset filter
        $allFiltersEmpty = !$request->filled('search') &&
            !$request->filled('province') &&
            !$request->filled('city') &&
            !$request->filled('current') &&
            !$request->filled('type') &&
            !$request->filled('power') &&
            !$request->filled('provider') &&
            !$request->filled('rest_area');

        if (!$allFiltersEmpty) {
            // Aplikasikan filter hanya jika ada filter yang diisi
            // ... kode filter lainnya ...
        }

        // Logika pencarian
        if ($request->has('search')) {
            $searchTerm = $request->input('search');
            $query->whereHas('chargerLocation', function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                    ->where('status', 2); // Pastikan kondisi status = 2 juga diterapkan saat pencarian
            });
        }

        // Logika filter
        if ($request->filled('province')) {
            $query->whereHas('chargerLocation.province', function ($q) use ($request) {
                $q->where('id', $request->input('province'));
            });
        }

        if ($request->filled('city')) {
            $query->whereHas('chargerLocation.city', function ($q) use ($request) {
                $q->where('id', $request->input('city'));
            });
        }

        if ($request->filled('power')) {
            $query->where('power_charger_id', $request->input('power'));
        }

        if ($request->filled('current')) {
            $query->where('current_charger_id', $request->input('current'));
        }

        if ($request->filled('type')) {
            $query->where('type_charger_id', $request->input('type'));
        }

        if ($request->filled('provider')) {
            $query->whereHas('chargerLocation.provider', function ($q) use ($request) {
                $q->where('id', $request->input('provider'));
            });
        }

        if ($request->filled('rest_area')) {
            $query->whereHas('chargerLocation', function ($q) use ($request) {
                $q->where('is_rest_area', $request->input('rest_area'));
            });
        }

        // Logika pengurutan
        $sort = $request->input('sort', 'location');
        $direction = $request->input('direction', 'asc');

        switch ($sort) {
            case 'location':
                $query->join('charger_locations', 'chargers.charger_location_id', '=', 'charger_locations.id')
                    ->orderBy('charger_locations.name', $direction);
                break;
            case 'province':
                $query->join('charger_locations', 'chargers.charger_location_id', '=', 'charger_locations.id')
                    ->join('provinces', 'charger_locations.province_id', '=', 'provinces.id')
                    ->orderBy('provinces.name', $direction);
                break;
            case 'city':
                $query->join('charger_locations', 'chargers.charger_location_id', '=', 'charger_locations.id')
                    ->join('cities', 'charger_locations.city_id', '=', 'cities.id')
                    ->orderBy('cities.name', $direction);
                break;
            case 'power':
                $query->join('power_chargers', 'chargers.power_charger_id', '=', 'power_chargers.id')
                    ->orderBy('power_chargers.name', $direction);
                break;
            case 'current':
                $query->join('current_chargers', 'chargers.current_charger_id', '=', 'current_chargers.id')
                    ->orderBy('current_chargers.name', $direction);
                break;
            case 'type':
                $query->join('type_chargers', 'chargers.type_charger_id', '=', 'type_chargers.id')
                    ->orderBy('type_chargers.name', $direction);
                break;
            case 'provider':
                $query->join('charger_locations', 'chargers.charger_location_id', '=', 'charger_locations.id')
                    ->join('providers', 'charger_locations.provider_id', '=', 'providers.id')
                    ->orderBy('providers.name', $direction);
                break;
            case 'rest_area':
                $query->join('charger_locations', 'chargers.charger_location_id', '=', 'charger_locations.id')
                    ->orderBy('charger_locations.is_rest_area', $direction);
                break;
            default:
                $query->orderBy('id', $direction);
        }

        $chargers = $query->paginate(10)->appends($request->except('page'));

        // Ambil data untuk filter
        $provinces = Province::orderBy('name')->get();
        $cities = collect();

        if ($request->filled('province')) {
            $cities = City::where('province_id', $request->input('province'))->orderBy('name')->get();
        }

        $powerChargers = PowerCharger::orderBy('name')->get();
        $currentChargers = CurrentCharger::orderBy('name')->get();
        $typeChargers = TypeCharger::orderBy('name')->get();
        $providers = Provider::orderBy('name')->get();

        $currentChargers = CurrentCharger::orderBy('name')->get();
        $typeChargers = collect();
        $powerChargers = collect();

        if ($request->filled('current')) {
            $typeChargers = TypeCharger::where('current_charger_id', $request->input('current'))->orderBy('name')->get();
        }

        if ($request->filled('type')) {
            $powerChargers = PowerCharger::where('type_charger_id', $request->input('type'))->orderBy('name')->get();
        }

        return view('layouts.ev.chargers', compact('chargers', 'provinces', 'cities', 'powerChargers', 'currentChargers', 'typeChargers', 'providers'));
    }

    public function getCities(Request $request)
    {
        $cities = City::where('province_id', $request->province_id)
            ->orderBy('name')
            ->get();

        return response()->json($cities);
    }

    public function getTypeChargers(Request $request)
    {
        $typeChargers = TypeCharger::where('current_charger_id', $request->current_id)
            ->orderBy('name')
            ->get();

        return response()->json($typeChargers);
    }

    public function getPowerChargers(Request $request)
    {
        $powerChargers = PowerCharger::where('type_charger_id', $request->type_id)
            ->orderBy('name')
            ->get();

        return response()->json($powerChargers);
    }

    public function getProviderDetails(Provider $provider)
    {
        if ($provider->status != 1 || $provider->public != 1) {
            return response()->json(['error' => 'Provider details are not available'], 403);
        }

        return response()->json([
            'name' => $provider->name,
            'contact' => $provider->contact,
            'email' => $provider->email,
            'web' => $provider->web,
            'google' => $provider->google,
            'ios' => $provider->ios,
            'image' => $provider->image ? asset('storage/' . $provider->image) : null,
            'price' => $provider->price,
            'tax' => $provider->tax,
            'admin_fee' => $provider->admin_fee,
        ]);
    }
}
