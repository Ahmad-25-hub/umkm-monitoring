<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\UpdateEmployeeMembershipRequest;
use App\Models\BusinessMembership;
use Illuminate\Http\RedirectResponse;

class EmployeeMembershipController extends Controller
{
    public function update(UpdateEmployeeMembershipRequest $request, BusinessMembership $membership): RedirectResponse
    {
        $membership->update($request->safe()->only('status'));

        return redirect()->to(route('overview').'#team')->with(
            'success',
            $membership->status === BusinessMembership::STATUS_ACTIVE
                ? 'Akses karyawan berhasil diaktifkan kembali.'
                : 'Akses karyawan berhasil dinonaktifkan. Riwayat tugas dan penjualan tetap tersimpan.',
        );
    }
}
