<?php

namespace Database\Seeders;

use App\Models\Archipelago;
use App\Models\Hub;
use App\Models\User;
use App\Models\Order;
use App\Models\CoverageArea;
use App\Models\Bin;
use App\Models\Rider;
use App\Models\RiderPerformance;
use App\Models\ReturnRecord;
use App\Models\OrderStatusHistory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles
        $roles = ['Admin', 'Hub Manager', 'Rider', 'Client'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // 2. Archipelagos
        $luzon = Archipelago::firstOrCreate(['code' => 'LUZ'], ['name' => 'Luzon']);
        $visayas = Archipelago::firstOrCreate(['code' => 'VIS'], ['name' => 'Visayas']);
        $mindanao = Archipelago::firstOrCreate(['code' => 'MIN'], ['name' => 'Mindanao']);

        // 3. Main Hubs (1 National Sorting / Gateway per Archipelago)
        $hubsData = [
            [
                'archipelago' => $luzon,
                'main' => ['name' => 'Luzon Central Gateway', 'code' => 'HUB-LUZ-CTR', 'type' => 'national_sorting', 'capacity' => 50000],
                'regionals' => [
                    ['name' => 'Northern Luzon Regional Hub', 'code' => 'HUB-LUZ-NL', 'type' => 'regional', 'capacity' => 15000],
                    ['name' => 'Southern Luzon Regional Hub', 'code' => 'HUB-LUZ-SL', 'type' => 'regional', 'capacity' => 15000],
                    ['name' => 'NCR Central Sorting Hub', 'code' => 'HUB-LUZ-NCR', 'type' => 'regional', 'capacity' => 25000],
                ]
            ],
            [
                'archipelago' => $visayas,
                'main' => ['name' => 'Visayas Gateway Hub', 'code' => 'HUB-VIS-GW', 'type' => 'gateway', 'capacity' => 30000],
                'regionals' => [
                    ['name' => 'Cebu Regional Hub', 'code' => 'HUB-VIS-CEB', 'type' => 'regional', 'capacity' => 12000],
                    ['name' => 'Iloilo Regional Hub', 'code' => 'HUB-VIS-ILO', 'type' => 'regional', 'capacity' => 10000],
                    ['name' => 'Bacolod Regional Hub', 'code' => 'HUB-VIS-BCD', 'type' => 'regional', 'capacity' => 10000],
                ]
            ],
            [
                'archipelago' => $mindanao,
                'main' => ['name' => 'Mindanao Core Gateway', 'code' => 'HUB-MIN-CG', 'type' => 'gateway', 'capacity' => 30000],
                'regionals' => [
                    ['name' => 'Davao Regional Hub', 'code' => 'HUB-MIN-DVO', 'type' => 'regional', 'capacity' => 12000],
                    ['name' => 'Cagayan de Oro Regional Hub', 'code' => 'HUB-MIN-CDO', 'type' => 'regional', 'capacity' => 10000],
                    ['name' => 'Zamboanga Regional Hub', 'code' => 'HUB-MIN-ZAM', 'type' => 'regional', 'capacity' => 8000],
                ]
            ],
        ];

        foreach ($hubsData as $group) {
            $mainHub = Hub::firstOrCreate(['code' => $group['main']['code']], [
                'name' => $group['main']['name'], 'type' => $group['main']['type'],
                'archipelago_id' => $group['archipelago']->id, 'parent_hub_id' => null,
                'capacity' => $group['main']['capacity'], 'current_utilization' => 0,
            ]);

            foreach ($group['regionals'] as $regional) {
                Hub::firstOrCreate(['code' => $regional['code']], [
                    'name' => $regional['name'],
                    'type' => $regional['type'],
                    'archipelago_id' => $group['archipelago']->id,
                    'parent_hub_id' => $mainHub->id,
                    'capacity' => $regional['capacity'],
                    'current_utilization' => 0,
                ]);
            }
        }

        // 4. Admin User
        $admin = User::firstOrCreate(['email' => 'admin@logistics.local'], [
            'name' => 'System Administrator',
            'password' => Hash::make('password123'),
        ]);
        $admin->assignRole('Admin');

        $hubs = Hub::query()->pluck('id')->values();
        Hub::query()->each(function (Hub $hub) {
            $hub->update(['hub_type' => ['national_sorting' => 'national', 'gateway' => 'gateway', 'regional' => 'regional'][$hub->type] ?? 'regional', 'current_stock' => 0]);
        });
            $statuses = ['pending', 'received', 'pending', 'delivered', 'in_hub', 'flagged'];
        for ($index = 1; $index <= 20; $index++) {
            Order::updateOrCreate(
                ['awb_number' => 'LCR-2026-'.str_pad((string) $index, 4, '0', STR_PAD_LEFT)],
                [
                    'hub_id' => $hubs[($index - 1) % $hubs->count()],
                    'current_hub_id' => $hubs[($index - 1) % $hubs->count()],
                    'status' => $statuses[($index - 1) % count($statuses)],
                    'sender_name' => 'Sender '.$index,
                    'recipient_name' => 'Recipient '.$index,
                    'recipient_address' => $index.' Logistics Avenue, Philippines',
                    'weight_kg' => 1.25 + ($index * 0.4),
                    'flag_reason' => $index % 6 === 5 ? 'Package Crushed: Outer packaging damaged' : null,
                    'scanned_at' => $index % 6 === 0 ? now()->subDays($index) : null,
                    'hub_scanned_at' => in_array($statuses[($index - 1) % count($statuses)], ['in_hub']) ? now()->subDays($index) : null,
                    'created_at' => now()->subDays($index),
                    'updated_at' => now()->subDays($index),
                ]
            );
        }
        foreach (Hub::all() as $hub) {
            $hub->update(['current_stock' => Order::where('current_hub_id', $hub->id)->where('status', 'in_hub')->count()]);
        }

        foreach ([
            [3, 'failed_delivery_3x', 'Three delivery attempts failed'],
            [6, 'customer_refused', 'Recipient refused the package'],
            [9, 'incorrect_address', 'Address could not be verified'],
            [12, 'damaged_goods', 'Package arrived visibly damaged'],
        ] as [$orderId, $reason, $note]) {
            $order = Order::find($orderId);
            if (! $order) continue;
            $order->update(['status' => 'in_return_queue']);
            ReturnRecord::updateOrCreate(['order_id' => $order->id], ['return_reason' => $reason, 'status' => 'in_reverse_queue', 'action_notes' => $note]);
            OrderStatusHistory::firstOrCreate(['order_id' => $order->id, 'status' => 'in_return_queue'], ['location_hub_id' => $order->current_hub_id ?? $order->hub_id, 'performed_by_user_id' => $admin->id, 'notes' => $note, 'created_at' => now()->subDays(2)]);
        }

        $regionalHubs = Hub::where('type', 'regional')->get();
        $ncr = Hub::where('code', 'HUB-LUZ-NCR')->first();
        $cebu = Hub::where('code', 'HUB-VIS-CEB')->first();
        $davao = Hub::where('code', 'HUB-MIN-DVO')->first();
        foreach ([
            ['province' => 'Metro Manila', 'city_municipality' => 'NCR', 'hub' => $ncr],
            ['province' => 'Cavite', 'city_municipality' => 'Tagaytay', 'hub' => $ncr],
            ['province' => 'Laguna', 'city_municipality' => 'Santa Rosa', 'hub' => $ncr],
            ['province' => 'Cebu', 'city_municipality' => 'Cebu City', 'hub' => $cebu],
            ['province' => 'Bohol', 'city_municipality' => 'Tagbilaran', 'hub' => $cebu],
            ['province' => 'Davao del Sur', 'city_municipality' => 'Davao City', 'hub' => $davao],
            ['province' => 'Misamis Oriental', 'city_municipality' => 'Cagayan de Oro', 'hub' => Hub::where('code', 'HUB-MIN-CDO')->first()],
        ] as $area) {
            CoverageArea::updateOrCreate(['province' => $area['province'], 'city_municipality' => $area['city_municipality']], ['hub_id' => $area['hub']->id]);
        }
        foreach (Hub::all() as $origin) {
            foreach ($regionalHubs as $target) {
                Bin::firstOrCreate(['bin_code' => 'BIN-'.$target->code.'-01-'.$origin->id], ['hub_id' => $origin->id, 'capacity' => 50, 'current_count' => 0, 'target_hub_id' => $target->id, 'status' => 'active']);
            }
        }

        foreach ([['Maya Santos', 'maya.rider@logistics.local', 'motorcycle', 'RDR-001', 'available'], ['Paolo Reyes', 'paolo.rider@logistics.local', 'van', 'RDR-002', 'on_delivery'], ['Lina Cruz', 'lina.rider@logistics.local', 'tricycle', 'RDR-003', 'off_duty']] as $index => $riderData) {
            $user = User::firstOrCreate(['email' => $riderData[1]], ['name' => $riderData[0], 'password' => Hash::make('password123'), 'hub_id' => $hubs[$index % $hubs->count()]]);
            $rider = Rider::updateOrCreate(['user_id' => $user->id], ['hub_id' => $user->hub_id, 'vehicle_type' => $riderData[2], 'plate_number' => $riderData[3], 'status' => $riderData[4], 'phone_number' => '0917-555-000'.($index + 1)]);
            RiderPerformance::updateOrCreate(['rider_id' => $rider->id], ['total_assigned' => 120 + ($index * 20), 'total_completed' => 110 + ($index * 15), 'total_failed' => $index, 'on_time_rate' => 91.5 + $index, 'rating' => 4.5 + ($index / 10)]);
        }
    }
}