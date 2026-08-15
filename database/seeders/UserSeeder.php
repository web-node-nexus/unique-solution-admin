<?php

namespace Database\Seeders;

use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        $staffUsers = [
            [
                'name' => 'Unique Admin',
                'email' => 'admin@uniquesolution.com',
                'phone' => '+91 9876500001',
                'address' => 'Kargil Chowk, Megha Road, Kurud - 493663',
                'role' => 'admin',
                'spatie_role' => 'Super Admin',
            ],
            [
                'name' => 'Shop Manager',
                'email' => 'manager@uniquesolution.com',
                'phone' => '+91 9876500002',
                'address' => 'Kargil Chowk, Megha Road, Kurud - 493663',
                'role' => 'manager',
                'spatie_role' => 'Manager',
            ],
            [
                'name' => 'Store Staff',
                'email' => 'staff@uniquesolution.com',
                'phone' => '+91 9876500003',
                'address' => 'Kargil Chowk, Megha Road, Kurud - 493663',
                'role' => 'staff',
                'spatie_role' => 'Staff',
            ],
            [
                'name' => 'Support Agent',
                'email' => 'support@uniquesolution.com',
                'phone' => '+91 9876500004',
                'address' => 'Kargil Chowk, Megha Road, Kurud - 493663',
                'role' => 'support',
                'spatie_role' => 'Support',
            ],
        ];

        foreach ($staffUsers as $data) {
            $user = User::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'address' => $data['address'],
                    'password' => $password,
                    'role' => $data['role'],
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles([$data['spatie_role']]);
        }

        $customers = [
            [
                'name' => 'Rahul Verma',
                'email' => 'customer1@example.com',
                'phone' => '+91 9811111101',
                'address' => '12 Nehru Nagar, Raipur, Chhattisgarh 492001',
                'city' => 'Raipur',
                'state' => 'Chhattisgarh',
                'pincode' => '492001',
            ],
            [
                'name' => 'Priya Sharma',
                'email' => 'customer2@example.com',
                'phone' => '+91 9811111102',
                'address' => '45 Civil Lines, Durg, Chhattisgarh 491001',
                'city' => 'Durg',
                'state' => 'Chhattisgarh',
                'pincode' => '491001',
            ],
            [
                'name' => 'Amit Patel',
                'email' => 'customer3@example.com',
                'phone' => '+91 9811111103',
                'address' => '78 Station Road, Bilaspur, Chhattisgarh 495001',
                'city' => 'Bilaspur',
                'state' => 'Chhattisgarh',
                'pincode' => '495001',
            ],
            [
                'name' => 'Sneha Gupta',
                'email' => 'customer4@example.com',
                'phone' => '+91 9811111104',
                'address' => '9 Market Complex, Kurud, Chhattisgarh 493663',
                'city' => 'Kurud',
                'state' => 'Chhattisgarh',
                'pincode' => '493663',
            ],
            [
                'name' => 'Vikram Singh',
                'email' => 'customer5@example.com',
                'phone' => '+91 9811111105',
                'address' => '33 Ring Road, Bhilai, Chhattisgarh 490006',
                'city' => 'Bhilai',
                'state' => 'Chhattisgarh',
                'pincode' => '490006',
            ],
        ];

        foreach ($customers as $data) {
            $user = User::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'address' => $data['address'],
                    'password' => $password,
                    'role' => 'customer',
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            // Customers must not have Spatie roles.
            $user->syncRoles([]);

            CustomerAddress::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'label' => 'Home',
                ],
                [
                    'address' => $data['address'],
                    'city' => $data['city'],
                    'state' => $data['state'],
                    'pincode' => $data['pincode'],
                    'is_default' => true,
                ]
            );
        }
    }
}
