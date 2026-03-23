<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\BusinessSetting;
use App\Models\Category;
use App\Models\Location;
use App\Models\Purchase;
use App\Models\PurchaseDocument;
use App\Models\PurchaseModifyingCost;
use App\Models\Sell;
use App\Models\SellDocument;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class FullSystemDemoSeeder extends Seeder
{
    private const LOCATIONS = [
        [
            'code' => 'MAIN',
            'name' => 'Main Branch',
            'email' => 'main@bikemartbd.com',
            'phone' => '01700-000000',
            'address' => 'Motijheel, Dhaka, Bangladesh',
            'notes' => 'Primary branch for admin and reconciliation.',
        ],
        [
            'code' => 'DHK-SHOWROOM',
            'name' => 'Dhaka Showroom',
            'email' => 'dhaka@bikemartbd.com',
            'phone' => '01711-100100',
            'address' => 'Mirpur, Dhaka, Bangladesh',
            'notes' => 'Main city showroom for retail activity.',
        ],
        [
            'code' => 'CTG-SHOWROOM',
            'name' => 'Chattogram Showroom',
            'email' => 'ctg@bikemartbd.com',
            'phone' => '01711-200200',
            'address' => 'Agrabad, Chattogram, Bangladesh',
            'notes' => 'Port city branch for coastal market coverage.',
        ],
        [
            'code' => 'KHL-OUTLET',
            'name' => 'Khulna Outlet',
            'email' => 'khulna@bikemartbd.com',
            'phone' => '01711-300300',
            'address' => 'Sonadanga, Khulna, Bangladesh',
            'notes' => 'Regional outlet for southwest operations.',
        ],
    ];

    private const BRANDS = [
        'Honda',
        'Yamaha',
        'Suzuki',
        'Hero',
        'Bajaj',
        'TVS',
        'Runner',
        'Lifan',
    ];

    private const CATEGORIES = [
        'Sports',
        'Commuter',
        'Scooter',
        'Naked',
        'Cruiser',
        'Electric',
    ];

    private const VEHICLES = [
        ['code' => 'DEMO-001', 'name' => 'Honda CB Hornet', 'model' => 'CBS', 'brand' => 'Honda', 'category' => 'Sports', 'color' => 'Red', 'year' => 2024],
        ['code' => 'DEMO-002', 'name' => 'Yamaha R15 V4', 'model' => 'Race Edition', 'brand' => 'Yamaha', 'category' => 'Sports', 'color' => 'Blue', 'year' => 2025],
        ['code' => 'DEMO-003', 'name' => 'Suzuki Gixxer SF', 'model' => 'ABS', 'brand' => 'Suzuki', 'category' => 'Sports', 'color' => 'Black', 'year' => 2024],
        ['code' => 'DEMO-004', 'name' => 'Hero Xpulse 200', 'model' => '4V', 'brand' => 'Hero', 'category' => 'Naked', 'color' => 'White', 'year' => 2023],
        ['code' => 'DEMO-005', 'name' => 'Bajaj Pulsar N160', 'model' => 'Dual Disc', 'brand' => 'Bajaj', 'category' => 'Commuter', 'color' => 'Grey', 'year' => 2025],
        ['code' => 'DEMO-006', 'name' => 'TVS Raider 125', 'model' => 'SmartXonnect', 'brand' => 'TVS', 'category' => 'Commuter', 'color' => 'Yellow', 'year' => 2024],
        ['code' => 'DEMO-007', 'name' => 'Runner Bullet', 'model' => 'V2', 'brand' => 'Runner', 'category' => 'Cruiser', 'color' => 'Matte Black', 'year' => 2022],
        ['code' => 'DEMO-008', 'name' => 'Lifan KPT 150', 'model' => 'Touring', 'brand' => 'Lifan', 'category' => 'Naked', 'color' => 'Green', 'year' => 2023],
        ['code' => 'DEMO-009', 'name' => 'Honda Dio', 'model' => 'Standard', 'brand' => 'Honda', 'category' => 'Scooter', 'color' => 'Orange', 'year' => 2025],
        ['code' => 'DEMO-010', 'name' => 'Yamaha E01', 'model' => 'Prototype', 'brand' => 'Yamaha', 'category' => 'Electric', 'color' => 'Silver', 'year' => 2026],
    ];

    private const STOCK_BLUEPRINTS = [
        ['purchases' => [2, 1], 'sells' => []],
        ['purchases' => [1, 3], 'sells' => [2]],
        ['purchases' => [2, 2], 'sells' => [1, 1]],
        ['purchases' => [3], 'sells' => [3]],
        ['purchases' => [4, 2], 'sells' => [2, 1]],
        ['purchases' => [1, 1], 'sells' => []],
        ['purchases' => [2], 'sells' => [2]],
        ['purchases' => [5], 'sells' => [4]],
        ['purchases' => [], 'sells' => []],
        ['purchases' => [], 'sells' => []],
    ];

    private const PLACEHOLDER_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO5WmXQAAAAASUVORK5CYII=';

    public function run()
    {
        $this->seedBusinessSetting();
        $locations = $this->seedLocations();

        $users = $this->seedUsers($locations);
        [$brands, $categories] = $this->seedCatalog();
        $vehicles = $this->seedVehicles($brands, $categories);

        $this->seedPurchasesAndSales($vehicles, $locations);
        $this->seedOperationalTables($users);
    }

    private function seedLocations(): Collection
    {
        return collect(self::LOCATIONS)->mapWithKeys(function (array $locationData) {
            $location = Location::updateOrCreate(
                ['code' => $locationData['code']],
                [
                    'name' => $locationData['name'],
                    'email' => $locationData['email'],
                    'phone' => $locationData['phone'],
                    'address' => $locationData['address'],
                    'is_active' => true,
                    'notes' => $locationData['notes'],
                ]
            );

            return [$locationData['code'] => $location];
        });
    }

    private function seedBusinessSetting(): void
    {
        if (BusinessSetting::query()->exists()) {
            return;
        }

        $logoPath = 'seeders/business/demo-logo.png';
        $this->storePlaceholderImage($logoPath);

        BusinessSetting::create([
            'business_name' => 'BikeMart POS Demo Showroom',
            'email' => 'info@bikemartbd.com',
            'phone' => '01711-223344',
            'address' => 'Demo House, Mirpur DOHS, Dhaka, Bangladesh',
            'website' => 'https://bikemartbd.com',
            'currency_code' => 'BDT',
            'timezone' => 'Asia/Dhaka',
            'invoice_footer' => 'Thank you for visiting BikeMart POS demo environment.',
            'logo_path' => $logoPath,
        ]);
    }

    private function seedUsers(Collection $locations): Collection
    {
        $users = collect([
            [
                'email' => 'manager.demo@bikemartbd.com',
                'name' => 'Demo Manager',
                'password' => 'manager123#',
                'role' => 'manager',
                'permissions' => [],
                'default_location_code' => 'DHK-SHOWROOM',
                'location_codes' => ['MAIN', 'DHK-SHOWROOM', 'CTG-SHOWROOM', 'KHL-OUTLET'],
            ],
            [
                'email' => 'purchase.demo@bikemartbd.com',
                'name' => 'Demo Purchase Operator',
                'password' => 'purchase123#',
                'role' => 'purchase-operator',
                'permissions' => [],
                'default_location_code' => 'DHK-SHOWROOM',
                'location_codes' => ['DHK-SHOWROOM', 'CTG-SHOWROOM'],
            ],
            [
                'email' => 'sales.demo@bikemartbd.com',
                'name' => 'Demo Sales Operator',
                'password' => 'sales123#',
                'role' => 'sales-operator',
                'permissions' => [],
                'default_location_code' => 'CTG-SHOWROOM',
                'location_codes' => ['CTG-SHOWROOM', 'KHL-OUTLET'],
            ],
            [
                'email' => 'auditor.demo@bikemartbd.com',
                'name' => 'Demo Auditor',
                'password' => 'auditor123#',
                'role' => null,
                'permissions' => ['view dashboard'],
                'default_location_code' => 'MAIN',
                'location_codes' => ['MAIN'],
            ],
        ])->mapWithKeys(function (array $userData) use ($locations) {
            $defaultLocation = $locations[$userData['default_location_code']] ?? $locations->first();
            $locationIds = collect($userData['location_codes'])
                ->map(fn (string $code) => $locations[$code]?->id)
                ->filter()
                ->values()
                ->all();

            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'default_location_id' => $defaultLocation?->id,
                    'email_verified_at' => now(),
                    'password' => Hash::make($userData['password']),
                ]
            );

            if ($userData['role']) {
                $user->syncRoles([$userData['role']]);
            } else {
                $user->syncRoles([]);
            }

            $user->locations()->sync($locationIds);
            $user->syncPermissions($userData['permissions']);

            return [$userData['email'] => $user];
        });

        return $users;
    }

    private function seedCatalog(): array
    {
        $brands = collect(self::BRANDS)->mapWithKeys(function (string $name) {
            $brand = Brand::firstOrCreate(
                ['name' => $name],
                ['notes' => "{$name} demo catalog brand."]
            );

            return [$name => $brand];
        });

        $categories = collect(self::CATEGORIES)->mapWithKeys(function (string $name) {
            $category = Category::firstOrCreate(
                ['name' => $name],
                ['notes' => "{$name} demo catalog category."]
            );

            return [$name => $category];
        });

        return [$brands, $categories];
    }

    private function seedVehicles(Collection $brands, Collection $categories): Collection
    {
        return collect(self::VEHICLES)->values()->map(function (array $vehicleData, int $index) use ($brands, $categories) {
            return Vehicle::updateOrCreate(
                ['code' => $vehicleData['code']],
                [
                    'brand_id' => $brands[$vehicleData['brand']]->id,
                    'category_id' => $categories[$vehicleData['category']]->id,
                    'name' => $vehicleData['name'],
                    'model' => $vehicleData['model'],
                    'registration_number' => 'DHAKA-' . str_pad((string) ($index + 101), 3, '0', STR_PAD_LEFT),
                    'engine_number' => 'ENG-' . str_pad((string) ($index + 2001), 4, '0', STR_PAD_LEFT),
                    'chassis_number' => 'CHS-' . str_pad((string) ($index + 3001), 4, '0', STR_PAD_LEFT),
                    'color' => $vehicleData['color'],
                    'year' => $vehicleData['year'],
                    'notes' => "{$vehicleData['name']} demo stock item for full-system testing.",
                ]
            );
        });
    }

    private function seedPurchasesAndSales(Collection $vehicles, Collection $locations): void
    {
        $costReasons = [
            'Service and tuning',
            'Document transfer support',
            'Body repair',
            'Paint correction',
            'Tyre replacement',
        ];
        $locationPool = $locations->values();

        $vehicles->values()->each(function (Vehicle $vehicle, int $vehicleIndex) use ($costReasons, $locationPool) {
            $blueprint = self::STOCK_BLUEPRINTS[$vehicleIndex] ?? ['purchases' => [], 'sells' => []];
            $location = $locationPool[$vehicleIndex % $locationPool->count()];

            collect($blueprint['purchases'])->values()->each(function (int $quantity, int $purchaseIndex) use ($vehicle, $vehicleIndex, $costReasons, $location) {
                $purchaseDate = now()->subDays(120 - ($vehicleIndex * 8) - ($purchaseIndex * 3))->toDateString();
                $payment = $this->makePaymentData(Purchase::PAYMENT_STATUSES, Purchase::PAYMENT_METHODS, $vehicleIndex + $purchaseIndex, 'purchase');

                $purchase = Purchase::updateOrCreate(
                    [
                        'location_id' => $location->id,
                        'vehicle_id' => $vehicle->id,
                        'name' => "{$vehicle->code} Owner " . ($purchaseIndex + 1),
                        'purchasing_date' => $purchaseDate,
                    ],
                    [
                        'father_name' => "Father " . ($purchaseIndex + 1),
                        'address' => "Demo owner address for {$vehicle->display_name}, {$location->name}.",
                        'mobile_number' => '017' . str_pad((string) ($vehicleIndex + 10000000 + $purchaseIndex), 8, '0', STR_PAD_LEFT),
                        'quantity' => $quantity,
                        'buying_price_from_owner' => 90000 + ($vehicleIndex * 7000) + ($purchaseIndex * 3500),
                        'payment_status' => $payment['payment_status'],
                        'payment_method' => $payment['payment_method'],
                        'payment_information' => $payment['payment_information'],
                        'extra_additional_note' => "Demo purchase entry {$vehicle->code} #" . ($purchaseIndex + 1),
                    ]
                );

                $this->seedPurchaseDocuments($purchase);

                $reasonsToUse = collect($costReasons)->take(($purchaseIndex % 2) + 1)->values();

                $reasonsToUse->each(function (string $reason, int $costIndex) use ($purchase) {
                    PurchaseModifyingCost::updateOrCreate(
                        [
                            'purchase_id' => $purchase->id,
                            'reason' => $reason,
                        ],
                        [
                            'cost' => 800 + ($costIndex * 450),
                        ]
                    );
                });
            });

            collect($blueprint['sells'])->values()->each(function (int $quantity, int $sellIndex) use ($vehicle, $vehicleIndex, $location) {
                $sellingDate = now()->subDays(60 - ($vehicleIndex * 4) - ($sellIndex * 2))->toDateString();
                $payment = $this->makePaymentData(Sell::PAYMENT_STATUSES, Sell::PAYMENT_METHODS, $vehicleIndex + $sellIndex + 2, 'sale');

                $sell = Sell::updateOrCreate(
                    [
                        'location_id' => $location->id,
                        'vehicle_id' => $vehicle->id,
                        'name' => "{$vehicle->code} Customer " . ($sellIndex + 1),
                        'selling_date' => $sellingDate,
                    ],
                    [
                        'father_name' => "Guardian " . ($sellIndex + 1),
                        'address' => "Demo customer address for {$vehicle->display_name}, {$location->name}.",
                        'mobile_number' => '018' . str_pad((string) ($vehicleIndex + 20000000 + $sellIndex), 8, '0', STR_PAD_LEFT),
                        'quantity' => $quantity,
                        'selling_price_to_customer' => 120000 + ($vehicleIndex * 9000) + ($sellIndex * 4000),
                        'payment_status' => $payment['payment_status'],
                        'payment_method' => $payment['payment_method'],
                        'payment_information' => $payment['payment_information'],
                        'extra_additional_note' => "Demo sale entry {$vehicle->code} #" . ($sellIndex + 1),
                    ]
                );

                $this->seedSellDocuments($sell);
            });
        });
    }

    private function seedPurchaseDocuments(Purchase $purchase): void
    {
        foreach ([1, 2] as $pictureIndex) {
            $path = "seeders/purchases/{$purchase->id}/picture-{$pictureIndex}.png";
            $this->storePlaceholderImage($path);

            PurchaseDocument::updateOrCreate(
                [
                    'purchase_id' => $purchase->id,
                    'type' => PurchaseDocument::TYPE_PICTURE,
                    'original_name' => "purchase-picture-{$pictureIndex}.png",
                ],
                [
                    'file_path' => $path,
                ]
            );
        }

        foreach (PurchaseDocument::SINGLE_TYPES as $type => $label) {
            $path = "seeders/purchases/{$purchase->id}/{$type}.png";
            $this->storePlaceholderImage($path);

            PurchaseDocument::updateOrCreate(
                [
                    'purchase_id' => $purchase->id,
                    'type' => $type,
                ],
                [
                    'file_path' => $path,
                    'original_name' => strtolower(str_replace(' ', '-', $label)) . '.png',
                ]
            );
        }
    }

    private function seedSellDocuments(Sell $sell): void
    {
        foreach (SellDocument::FILE_TYPES as $type => $label) {
            $path = "seeders/sells/{$sell->id}/{$type}.png";
            $this->storePlaceholderImage($path);

            SellDocument::updateOrCreate(
                [
                    'sell_id' => $sell->id,
                    'type' => $type,
                ],
                [
                    'file_path' => $path,
                    'original_name' => strtolower(str_replace(' ', '-', $label)) . '.png',
                ]
            );
        }
    }

    private function seedOperationalTables(Collection $users): void
    {
        $manager = $users['manager.demo@bikemartbd.com'];
        $sales = $users['sales.demo@bikemartbd.com'];
        $auditor = $users['auditor.demo@bikemartbd.com'];

        DB::table('password_resets')->updateOrInsert(
            ['email' => $sales->email],
            [
                'token' => Hash::make('demo-reset-token'),
                'created_at' => now(),
            ]
        );

        DB::table('personal_access_tokens')->updateOrInsert(
            ['token' => hash('sha256', 'demo-manager-api-token')],
            [
                'tokenable_type' => $manager->getMorphClass(),
                'tokenable_id' => $manager->id,
                'name' => 'Demo Manager API Token',
                'abilities' => json_encode(['*']),
                'last_used_at' => now()->subDay(),
                'expires_at' => now()->addYear(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('personal_access_tokens')->updateOrInsert(
            ['token' => hash('sha256', 'demo-auditor-api-token')],
            [
                'tokenable_type' => $auditor->getMorphClass(),
                'tokenable_id' => $auditor->id,
                'name' => 'Demo Auditor API Token',
                'abilities' => json_encode(['view dashboard']),
                'last_used_at' => now()->subDays(2),
                'expires_at' => now()->addMonths(6),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('failed_jobs')->updateOrInsert(
            ['uuid' => '00000000-0000-0000-0000-000000000001'],
            [
                'connection' => 'database',
                'queue' => 'default',
                'payload' => json_encode([
                    'displayName' => 'Demo\\Jobs\\InventorySyncJob',
                    'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
                    'maxTries' => null,
                ]),
                'exception' => 'Demo failed job row created by FullSystemDemoSeeder for database table testing only.',
                'failed_at' => now(),
            ]
        );
    }

    private function makePaymentData(array $statuses, array $methods, int $index, string $context): array
    {
        $statusKeys = array_keys($statuses);
        $methodKeys = array_keys($methods);

        $status = $statusKeys[$index % count($statusKeys)];

        if ($status === 'unpaid') {
            return [
                'payment_status' => $status,
                'payment_method' => null,
                'payment_information' => ucfirst($context) . ' payment is pending clearance.',
            ];
        }

        $method = $methodKeys[$index % count($methodKeys)];

        $information = match ($method) {
            'bank_transfer' => strtoupper($context) . '-BANK-' . str_pad((string) ($index + 101), 4, '0', STR_PAD_LEFT),
            'mobile_banking' => strtoupper($context) . '-MB-' . str_pad((string) ($index + 501), 4, '0', STR_PAD_LEFT),
            'card' => 'Card approval ref #' . str_pad((string) ($index + 7001), 6, '0', STR_PAD_LEFT),
            'cheque' => 'Cheque no. ' . str_pad((string) ($index + 801), 5, '0', STR_PAD_LEFT),
            'other' => 'Manual ' . $context . ' payment note for demo record.',
            default => 'Cash counter payment recorded in demo data.',
        };

        return [
            'payment_status' => $status,
            'payment_method' => $method,
            'payment_information' => $status === 'partial'
                ? "Partial settlement. {$information}"
                : $information,
        ];
    }

    private function storePlaceholderImage(string $path): void
    {
        Storage::disk('public')->put($path, base64_decode(self::PLACEHOLDER_PNG));
    }
}
