<?php

namespace Database\Seeders;

use App\Models\AdminDevice;
use App\Models\AdminDeviceRequest;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminDeviceLogSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $now = Carbon::now();

            // 1) Get active users; if none, make a fallback approver
            $users = User::query()->where('status', 1)->get();

            if ($users->isEmpty()) {
                $users = collect([
                    User::updateOrCreate(
                        ['email' => 'device-approver@example.com'],
                        [
                            'name'         => 'Device Approver',
                            'phone_number' => '01700000000',
                            'password'     => Hash::make('password'),
                            'status'       => 1,
                        ]
                    ),
                ]);
            }

            // 2) Choose an approver and persist permission list in settings
            $approverId = (int) $users->first()->id;
            Setting::updateOrCreate(
                ['name' => 'admin_device_verification_permissions'],
                ['value' => json_encode([$approverId])]
            );

            // 3) UA presets
            $uaBrowser = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';
            $uaAndroid = 'Mozilla/5.0 (Linux; Android 12; Pixel 5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Mobile Safari/537.36';
            $uaIOS     = 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/16.5 Mobile/15E148 Safari/604.1';

            foreach ($users as $user) {
                // Idempotency: if this user already has devices, skip to avoid duplicates.
                if (AdminDevice::where('user_id', $user->id)->exists()) {
                    continue;
                }

                // 3a) ACTIVE verified Browser device
                $verifiedAtA  = $now->copy()->subDays(rand(10, 30))->startOfDay()->addHours(rand(9, 22));
                $lastUsedAtA  = $verifiedAtA->copy()->addDays(rand(1, 9))->addMinutes(rand(1, 59));
                $deviceA = AdminDevice::create([
                    'user_id'     => $user->id,
                    'uuid'        => (string) Str::uuid(),
                    'device_type' => 'Browser',
                    'user_agent'  => $uaBrowser,
                    'last_used_at'=> $lastUsedAtA,
                    'verified_at' => $verifiedAtA,
                    'expired_at'  => null,
                ]);

                AdminDeviceRequest::create([
                    'user_device_id' => $deviceA->id,
                    'user_id'        => $user->id,
                    'reason'         => 'New browser login',
                    'type'           => 1, // accepted/approve
                    'note'           => 'Auto-verified by seeder',
                    'accept_at'      => $verifiedAtA,
                    'accept_by'      => $approverId,
                ]);

                // 3b) PENDING Android device (request awaiting approval)
                $lastUsedAtB = $now->copy()->subDays(rand(1, 7))->addMinutes(rand(1, 59));
                $deviceB = AdminDevice::create([
                    'user_id'     => $user->id,
                    'uuid'        => (string) Str::uuid(),
                    'device_type' => 'Android',
                    'user_agent'  => $uaAndroid,
                    'last_used_at'=> $lastUsedAtB,
                    'verified_at' => null,
                    'expired_at'  => null,
                ]);

                AdminDeviceRequest::create([
                    'user_device_id' => $deviceB->id,
                    'user_id'        => $user->id,
                    'reason'         => 'Logging in from Android app',
                    'type'           => 1, // still a verification request; pending because accept_at is null
                    'note'           => null,
                    'accept_at'      => null,
                    'accept_by'      => null,
                ]);

                // 3c) EXPIRED iOS device (was accepted before, now expired)
                $verifiedAtC = $now->copy()->subDays(rand(25, 60))->startOfDay()->addHours(rand(8, 21));
                $expiredAtC  = $verifiedAtC->copy()->addDays(rand(3, 20));
                $lastUsedAtC = $expiredAtC->copy()->subHours(rand(1, 24));
                $deviceC = AdminDevice::create([
                    'user_id'     => $user->id,
                    'uuid'        => (string) Str::uuid(),
                    'device_type' => 'iOS',
                    'user_agent'  => $uaIOS,
                    'last_used_at'=> $lastUsedAtC,
                    'verified_at' => $verifiedAtC,
                    'expired_at'  => $expiredAtC,
                ]);

                // Accepted log when it was verified
                AdminDeviceRequest::create([
                    'user_device_id' => $deviceC->id,
                    'user_id'        => $user->id,
                    'reason'         => 'New iOS login',
                    'type'           => 1,
                    'note'           => 'Initial approval',
                    'accept_at'      => $verifiedAtC,
                    'accept_by'      => $approverId,
                ]);

                // (Optional) a later “cancellation” style log (type=0) marking admin action
                AdminDeviceRequest::create([
                    'user_device_id' => $deviceC->id,
                    'user_id'        => $user->id,
                    'reason'         => 'Device expired/revoked',
                    'type'           => 0, // cancelled/revoked
                    'note'           => 'Revoked by admin',
                    'accept_at'      => $expiredAtC,
                    'accept_by'      => $approverId,
                ]);
            }
        });
    }
}
