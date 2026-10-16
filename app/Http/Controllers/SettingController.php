<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SettingController extends Controller
{
    public function index()
    {
        $staffUsers = User::where('role', 'staff')->orderBy('name')->get();
        $categories = Category::withCount('products')->orderBy('name')->get();

        return view('settings.index', compact('staffUsers', 'categories'));
    }

    public function storeStaff(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'pin' => 'required|digits:6',
        ], [
            'pin.digits' => 'PIN harus 6 digit angka.',
        ]);

        $staff = User::create([
            'name' => trim($validated['name']),
            'role' => 'staff',
            'pin_hash' => Hash::make($validated['pin']),
            'is_active' => true,
        ]);

        AuditLog::log('create_staff', 'users', $staff->id, null, ['name' => $staff->name]);

        return redirect()->route('settings.index')->with('success', "Pegawai '{$staff->name}' berhasil ditambahkan.");
    }

    public function resetStaffPin(Request $request, $id)
    {
        $staff = User::where('role', 'staff')->findOrFail($id);

        $validated = $request->validate([
            'pin' => 'required|digits:6',
        ], [
            'pin.digits' => 'PIN baru harus 6 digit angka.',
        ]);

        $staff->update([
            'pin_hash' => Hash::make($validated['pin']),
            'failed_attempts' => 0,
            'locked_until' => null,
        ]);

        AuditLog::log('reset_pin', 'users', $staff->id);

        return redirect()->route('settings.index')->with('success', "PIN pegawai '{$staff->name}' berhasil diatur ulang.");
    }

    public function toggleStaffStatus(Request $request, $id)
    {
        $staff = User::where('role', 'staff')->findOrFail($id);
        $newStatus = !$staff->is_active;

        $staff->update(['is_active' => $newStatus]);

        AuditLog::log('toggle_staff_status', 'users', $staff->id, ['is_active' => !$newStatus], ['is_active' => $newStatus]);

        $statusText = $newStatus ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('settings.index')->with('success', "Pegawai '{$staff->name}' berhasil {$statusText}.");
    }

    public function updateCategory(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'lead_time_days' => 'required|integer|min:0',
            'safety_stock' => 'required|numeric|min:0',
            'expiry_warning_days' => 'required|integer|min:1',
            'overstock_days' => 'required|integer|min:1',
        ]);

        $before = $category->toArray();
        $category->update($validated);

        AuditLog::log('update_category', 'categories', $category->id, $before, $category->toArray());

        return redirect()->route('settings.index')->with('success', "Kategori '{$category->name}' berhasil diperbarui.");
    }
}
