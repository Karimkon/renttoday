<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index()
    {
        $inventories = Inventory::all();
        return view('admin.inventory.index', compact('inventories'));
    }

    public function create()
    {
        return view('admin.inventory.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'item' => 'required|string|unique:inventories,item',
            'quantity' => 'required|integer|min:0',
            'location' => 'required|string',
        ]);

        Inventory::create($request->all());

        return redirect()->route('admin.inventory.index')->with('success','Inventory item added.');
    }

    public function edit(Inventory $inventory)
    {
        return view('admin.inventory.edit', compact('inventory'));
    }

    public function update(Request $request, Inventory $inventory)
    {
        $request->validate([
            'item' => 'required|string|unique:inventories,item,'.$inventory->id,
            'quantity' => 'required|integer|min:0',
            'location' => 'required|string',
        ]);

        $inventory->update($request->all());

        return redirect()->route('admin.inventory.index')->with('success','Inventory item updated.');
    }

    public function destroy(Inventory $inventory)
    {
        $inventory->delete();
        return redirect()->route('admin.inventory.index')->with('success','Inventory item removed.');
    }
}
