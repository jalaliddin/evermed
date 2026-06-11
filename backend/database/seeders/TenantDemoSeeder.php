<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\InventoryItem;
use App\Models\Patient;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        $admin = User::updateOrCreate(
            ['email' => 'admin@demo.com'],
            [
                'name' => 'Demo Admin',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'phone' => '+998901234567',
                'is_active' => true,
            ]
        );

        // Receptionist
        User::updateOrCreate(
            ['email' => 'reception@demo.com'],
            [
                'name' => 'Nodira Xoliqova',
                'password' => Hash::make('password'),
                'role' => 'receptionist',
                'is_active' => true,
            ]
        );

        // Doctors
        $doctorUsers = [
            ['name' => 'Sardor Karimov', 'email' => 'karimov@demo.com', 'spec' => 'Terapevt', 'price' => 80000],
            ['name' => 'Malika Yusupova', 'email' => 'yusupova@demo.com', 'spec' => 'Kardiolog', 'price' => 120000],
            ['name' => 'Bobur Rahimov', 'email' => 'rahimov@demo.com', 'spec' => 'Nevropatolog', 'price' => 100000],
        ];

        $doctors = [];
        foreach ($doctorUsers as $i => $du) {
            $user = User::updateOrCreate(
                ['email' => $du['email']],
                [
                    'name' => $du['name'],
                    'password' => Hash::make('password'),
                    'role' => 'admin',
                    'is_active' => true,
                ]
            );
            $doctors[] = Doctor::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'specialization' => $du['spec'],
                    'room_number' => (string)($i + 1),
                    'consultation_price' => $du['price'],
                    'is_active' => true,
                    'schedule' => [
                        'monday' => ['start' => '09:00', 'end' => '17:00'],
                        'tuesday' => ['start' => '09:00', 'end' => '17:00'],
                        'wednesday' => ['start' => '09:00', 'end' => '17:00'],
                        'thursday' => ['start' => '09:00', 'end' => '17:00'],
                        'friday' => ['start' => '09:00', 'end' => '16:00'],
                    ],
                ]
            );
        }

        // Service Categories
        $categories = [
            ['name' => 'Konsultatsiya', 'color' => '#1565C0', 'icon' => 'mdi-stethoscope'],
            ['name' => 'Laboratoriya', 'color' => '#00BFA5', 'icon' => 'mdi-flask'],
            ['name' => 'UZI & Diagnostika', 'color' => '#7B1FA2', 'icon' => 'mdi-ultrasound'],
            ['name' => 'Protseduralar', 'color' => '#E65100', 'icon' => 'mdi-needle'],
            ['name' => 'Fizioterapiya', 'color' => '#2E7D32', 'icon' => 'mdi-therapy'],
        ];

        $cats = [];
        foreach ($categories as $cat) {
            $cats[] = ServiceCategory::updateOrCreate(['name' => $cat['name']], $cat);
        }

        // Services
        $services = [
            ['name' => 'Boshlang\'ich konsultatsiya', 'cat' => 0, 'price' => 80000, 'dur' => 30],
            ['name' => 'Takroriy konsultatsiya', 'cat' => 0, 'price' => 50000, 'dur' => 20],
            ['name' => 'Kardiolog konsultatsiyasi', 'cat' => 0, 'price' => 120000, 'dur' => 45],
            ['name' => 'Qon tahlili (umumiy)', 'cat' => 1, 'price' => 25000, 'dur' => 15],
            ['name' => 'Qon tahlili (biokimyo)', 'cat' => 1, 'price' => 45000, 'dur' => 15],
            ['name' => 'Siydik tahlili', 'cat' => 1, 'price' => 20000, 'dur' => 10],
            ['name' => 'Qorin bo\'shlig\'i UZI', 'cat' => 2, 'price' => 80000, 'dur' => 20],
            ['name' => 'Yurak EXO', 'cat' => 2, 'price' => 120000, 'dur' => 30],
            ['name' => 'EKG', 'cat' => 2, 'price' => 40000, 'dur' => 15],
            ['name' => 'Dropper', 'cat' => 3, 'price' => 35000, 'dur' => 60],
            ['name' => 'Ukol', 'cat' => 3, 'price' => 10000, 'dur' => 10],
            ['name' => 'Massaj (1 seans)', 'cat' => 4, 'price' => 50000, 'dur' => 40],
            ['name' => 'Elektroforez', 'cat' => 4, 'price' => 30000, 'dur' => 20],
        ];

        $createdServices = [];
        foreach ($services as $svc) {
            $createdServices[] = Service::updateOrCreate(
                ['name' => $svc['name']],
                [
                    'category_id' => $cats[$svc['cat']]->id,
                    'price' => $svc['price'],
                    'duration_minutes' => $svc['dur'],
                    'is_active' => true,
                ]
            );
        }

        // Patients (50 ta)
        $patientNames = [
            'Abdullayev Jasur', 'Toshmatov Sarvar', 'Rahimova Dilnoza', 'Yusupov Sherzod',
            'Karimova Gulnora', 'Mirzayev Otabek', 'Hasanova Feruza', 'Qodirov Alisher',
            'Nazarova Mohira', 'Ergashev Ulugbek', 'Ismoilova Barno', 'Xolmatov Davron',
            'Tursunova Nilufar', 'Normatov Behruz', 'Rашидova Adolat', 'Salimov Jamshid',
            'Haydarova Zulfiya', 'Umarov Nodir', 'Baxtiyorova Nasiba', 'Rajabov Ibrohim',
            'Mansurova Ozoda', 'Jumayev Sanjar', 'Tojimatova Sabohat', 'Xo\'jayev Mansur',
            'Nishonova Kamola', 'Azimov Temur', 'Yunusova Maftuna', 'Holiqov Shamsiddin',
            'Qurbonova Lobar', 'Mustafoyev Murod', 'Sharipova Dildora', 'Artikov Firdavs',
            'Mamatova Hulkar', 'Ibragimov Zafar', 'Sotvoldiyeva Iroda', 'Pardayev Laziz',
            'Xasanova Shahnoza', 'Norqo\'ziyev Elbek', 'Raximova Sarvinoz', 'Botirov Jasurbek',
            'Turayeva Manzura', 'Usmonov Bobur', 'Valiyeva Shahlo', 'Tillayev Hamza',
            'Islamova Nozima', 'Ergashev Doniyor', 'Xudoyberdiyeva Gavhar', 'Qosimov Sirojiddin',
            'Tursunboyeva Marhabo', 'Zokirov Akbar',
        ];

        $patients = [];
        foreach ($patientNames as $i => $name) {
            $gender = $i % 3 === 0 ? 'female' : 'male';
            $patients[] = Patient::updateOrCreate(
                ['full_name' => $name],
                [
                    'birth_date' => now()->subYears(rand(18, 75))->subDays(rand(0, 365)),
                    'gender' => $gender,
                    'phone' => '+998' . rand(900000000, 999999999),
                    'blood_type' => collect(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'])->random(),
                ]
            );
        }

        // Inventory items
        $inventoryItems = [
            ['name' => 'Steril qo\'lqop', 'cat' => 'Sarflov', 'unit' => 'juft', 'qty' => 100, 'min' => 20, 'price' => 3000],
            ['name' => 'Shpris 5ml', 'cat' => 'Sarflov', 'unit' => 'dona', 'qty' => 200, 'min' => 50, 'price' => 500],
            ['name' => 'Shpris 10ml', 'cat' => 'Sarflov', 'unit' => 'dona', 'qty' => 150, 'min' => 30, 'price' => 700],
            ['name' => 'Paxta', 'cat' => 'Sarflov', 'unit' => 'gramm', 'qty' => 1000, 'min' => 200, 'price' => 100],
            ['name' => 'Spirt 70%', 'cat' => 'Dezinfektsiya', 'unit' => 'ml', 'qty' => 2000, 'min' => 500, 'price' => 50],
            ['name' => 'Yod', 'cat' => 'Dezinfektsiya', 'unit' => 'ml', 'qty' => 500, 'min' => 100, 'price' => 80],
            ['name' => 'Kapelnitsa', 'cat' => 'Sarflov', 'unit' => 'dona', 'qty' => 50, 'min' => 10, 'price' => 5000],
            ['name' => 'Bandaj', 'cat' => 'Sarflov', 'unit' => 'dona', 'qty' => 80, 'min' => 15, 'price' => 2000],
            ['name' => 'Dorixona qog\'ozi', 'cat' => 'Ofis', 'unit' => 'varaq', 'qty' => 500, 'min' => 100, 'price' => 100],
            ['name' => 'Chek lentasi', 'cat' => 'Ofis', 'unit' => 'dona', 'qty' => 10, 'min' => 3, 'price' => 15000],
        ];

        foreach ($inventoryItems as $item) {
            InventoryItem::updateOrCreate(
                ['name' => $item['name']],
                [
                    'category' => $item['cat'],
                    'unit' => $item['unit'],
                    'quantity' => $item['qty'],
                    'min_quantity' => $item['min'],
                    'price_per_unit' => $item['price'],
                ]
            );
        }

        // Create appointments and visits for last 30 days
        $now = now();
        for ($day = 29; $day >= 0; $day--) {
            $date = $now->copy()->subDays($day);
            $dailyCount = rand(8, 20);

            for ($j = 0; $j < $dailyCount; $j++) {
                $patient = $patients[array_rand($patients)];
                $doctor = $doctors[array_rand($doctors)];
                $hour = rand(9, 17);
                $minute = [0, 30][rand(0, 1)];

                $scheduledAt = $date->copy()->setTime($hour, $minute);
                $status = $day > 0 ? 'completed' : (rand(0, 3) > 0 ? 'confirmed' : 'pending');

                $appointment = Appointment::create([
                    'patient_id' => $patient->id,
                    'doctor_id' => $doctor->id,
                    'service_id' => $createdServices[rand(0, count($createdServices) - 1)]->id,
                    'scheduled_at' => $scheduledAt,
                    'status' => $status,
                    'created_by' => $admin->id,
                ]);

                if ($status === 'completed') {
                    $svc1 = $createdServices[rand(0, count($createdServices) - 1)];
                    $svc2 = rand(0, 1) ? $createdServices[rand(0, count($createdServices) - 1)] : null;

                    $totalAmount = $svc1->price + ($svc2 ? $svc2->price : 0);
                    $discount = rand(0, 1) ? rand(5, 15) * 1000 : 0;
                    $paidAmount = $totalAmount - $discount;

                    $visit = Visit::create([
                        'patient_id' => $patient->id,
                        'doctor_id' => $doctor->id,
                        'appointment_id' => $appointment->id,
                        'visited_at' => $scheduledAt->addMinutes(rand(5, 20)),
                        'diagnosis' => null,
                        'total_amount' => $totalAmount,
                        'discount' => $discount,
                        'paid_amount' => $paidAmount,
                        'payment_method' => collect(['cash', 'card'])->random(),
                        'is_paid' => true,
                    ]);

                    VisitService::create([
                        'visit_id' => $visit->id,
                        'service_id' => $svc1->id,
                        'quantity' => 1,
                        'price' => $svc1->price,
                        'total' => $svc1->price,
                    ]);

                    if ($svc2 && $svc2->id !== $svc1->id) {
                        VisitService::create([
                            'visit_id' => $visit->id,
                            'service_id' => $svc2->id,
                            'quantity' => 1,
                            'price' => $svc2->price,
                            'total' => $svc2->price,
                        ]);
                    }
                }
            }
        }
    }
}
