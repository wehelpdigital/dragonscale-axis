<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\EcomProductsShipping;
use App\Models\EcomProductsShippingOptions;
use App\Models\EcomOrder;

class ShippingAndOrdersHeatmapSeeder extends Seeder
{
    /**
     * All 81 Philippine provinces plus Metro Manila regions for heatmap testing.
     */
    private $provinces = [
        // Luzon - NCR
        'Metro Manila',
        // Luzon - CAR
        'Abra', 'Apayao', 'Benguet', 'Ifugao', 'Kalinga', 'Mountain Province',
        // Luzon - Ilocos Region
        'Ilocos Norte', 'Ilocos Sur', 'La Union', 'Pangasinan',
        // Luzon - Cagayan Valley
        'Batanes', 'Cagayan', 'Isabela', 'Nueva Vizcaya', 'Quirino',
        // Luzon - Central Luzon
        'Aurora', 'Bataan', 'Bulacan', 'Nueva Ecija', 'Pampanga', 'Tarlac', 'Zambales',
        // Luzon - CALABARZON
        'Batangas', 'Cavite', 'Laguna', 'Quezon', 'Rizal',
        // Luzon - MIMAROPA
        'Marinduque', 'Occidental Mindoro', 'Oriental Mindoro', 'Palawan', 'Romblon',
        // Luzon - Bicol Region
        'Albay', 'Camarines Norte', 'Camarines Sur', 'Catanduanes', 'Masbate', 'Sorsogon',
        // Visayas - Western Visayas
        'Aklan', 'Antique', 'Capiz', 'Guimaras', 'Iloilo', 'Negros Occidental',
        // Visayas - Central Visayas
        'Bohol', 'Cebu', 'Negros Oriental', 'Siquijor',
        // Visayas - Eastern Visayas
        'Biliran', 'Eastern Samar', 'Leyte', 'Northern Samar', 'Samar', 'Southern Leyte',
        // Mindanao - Zamboanga Peninsula
        'Zamboanga del Norte', 'Zamboanga del Sur', 'Zamboanga Sibugay',
        // Mindanao - Northern Mindanao
        'Bukidnon', 'Camiguin', 'Lanao del Norte', 'Misamis Occidental', 'Misamis Oriental',
        // Mindanao - Davao Region
        'Davao de Oro', 'Davao del Norte', 'Davao del Sur', 'Davao Occidental', 'Davao Oriental',
        // Mindanao - SOCCSKSARGEN
        'Cotabato', 'Sarangani', 'South Cotabato', 'Sultan Kudarat',
        // Mindanao - Caraga
        'Agusan del Norte', 'Agusan del Sur', 'Dinagat Islands', 'Surigao del Norte', 'Surigao del Sur',
        // Mindanao - BARMM
        'Basilan', 'Lanao del Sur', 'Maguindanao', 'Sulu', 'Tawi-Tawi',
    ];

    /**
     * Municipalities per province (sample data for variety).
     */
    private $municipalities = [
        'Metro Manila' => ['Quezon City', 'Manila', 'Makati', 'Pasig', 'Taguig', 'Caloocan', 'Mandaluyong', 'Parañaque', 'Las Piñas', 'Marikina'],
        'Cebu' => ['Cebu City', 'Mandaue', 'Lapu-Lapu', 'Talisay', 'Consolacion', 'Danao', 'Minglanilla', 'Carcar', 'Toledo', 'Liloan'],
        'Davao del Sur' => ['Davao City', 'Digos', 'Bansalan', 'Magsaysay', 'Padada', 'Santa Cruz', 'Hagonoy', 'Matanao', 'Kiblawan', 'Sulop'],
        'Cavite' => ['Bacoor', 'Imus', 'Dasmariñas', 'General Trias', 'Cavite City', 'Tagaytay', 'Trece Martires', 'Silang', 'Kawit', 'Rosario'],
        'Laguna' => ['Santa Rosa', 'Calamba', 'San Pedro', 'Biñan', 'Cabuyao', 'Los Baños', 'San Pablo', 'Bay', 'Pagsanjan', 'Sta. Cruz'],
        'Bulacan' => ['Meycauayan', 'Malolos', 'San Jose del Monte', 'Marilao', 'Bocaue', 'Guiguinto', 'Balagtas', 'Plaridel', 'Baliuag', 'Hagonoy'],
        'Pampanga' => ['San Fernando', 'Angeles', 'Mabalacat', 'Mexico', 'Apalit', 'Guagua', 'Lubao', 'Porac', 'Magalang', 'Santa Rita'],
        'Rizal' => ['Antipolo', 'Cainta', 'Taytay', 'Angono', 'Binangonan', 'Rodriguez', 'San Mateo', 'Teresa', 'Tanay', 'Morong'],
        'Batangas' => ['Batangas City', 'Lipa', 'Tanauan', 'Santo Tomas', 'Nasugbu', 'Bauan', 'Lemery', 'San Juan', 'Rosario', 'Calaca'],
        'Negros Occidental' => ['Bacolod', 'Silay', 'Talisay', 'Kabankalan', 'Himamaylan', 'Sagay', 'San Carlos', 'Victorias', 'Cadiz', 'Bago'],
        'Iloilo' => ['Iloilo City', 'Pavia', 'Oton', 'Santa Barbara', 'San Miguel', 'Zarraga', 'Leganes', 'Cabatuan', 'Maasin', 'Dumangas'],
        'Pangasinan' => ['Dagupan', 'San Carlos', 'Urdaneta', 'Alaminos', 'Lingayen', 'Calasiao', 'Mangaldan', 'San Fabian', 'Binmaley', 'Rosales'],
        'Zambales' => ['Olongapo', 'Iba', 'San Antonio', 'Subic', 'Botolan', 'San Narciso', 'San Felipe', 'Candelaria', 'Masinloc', 'Castillejos'],
        'Nueva Ecija' => ['Cabanatuan', 'San Jose', 'Gapan', 'Palayan', 'Muñoz', 'Talavera', 'Guimba', 'San Leonardo', 'Sta. Rosa', 'Cabiao'],
        'Quezon' => ['Lucena', 'Tayabas', 'Pagbilao', 'Sariaya', 'Candelaria', 'Tiaong', 'San Antonio', 'Dolores', 'Gumaca', 'Lopez'],
    ];

    /**
     * Shipping price ranges by region (realistic pricing).
     */
    private $priceRanges = [
        'NCR' => ['min' => 99, 'max' => 150],
        'Luzon' => ['min' => 120, 'max' => 200],
        'Visayas' => ['min' => 180, 'max' => 280],
        'Mindanao' => ['min' => 200, 'max' => 350],
        'Island' => ['min' => 280, 'max' => 450],
    ];

    /**
     * Map provinces to regions for pricing.
     */
    private function getRegion($province)
    {
        $ncr = ['Metro Manila'];
        $visayas = ['Aklan', 'Antique', 'Capiz', 'Guimaras', 'Iloilo', 'Negros Occidental', 'Bohol', 'Cebu', 'Negros Oriental', 'Siquijor', 'Biliran', 'Eastern Samar', 'Leyte', 'Northern Samar', 'Samar', 'Southern Leyte'];
        $mindanao = ['Zamboanga del Norte', 'Zamboanga del Sur', 'Zamboanga Sibugay', 'Bukidnon', 'Camiguin', 'Lanao del Norte', 'Misamis Occidental', 'Misamis Oriental', 'Davao de Oro', 'Davao del Norte', 'Davao del Sur', 'Davao Occidental', 'Davao Oriental', 'Cotabato', 'Sarangani', 'South Cotabato', 'Sultan Kudarat', 'Agusan del Norte', 'Agusan del Sur', 'Dinagat Islands', 'Surigao del Norte', 'Surigao del Sur', 'Basilan', 'Lanao del Sur', 'Maguindanao', 'Sulu', 'Tawi-Tawi'];
        $island = ['Batanes', 'Palawan', 'Romblon', 'Marinduque', 'Catanduanes', 'Siquijor', 'Camiguin', 'Dinagat Islands', 'Basilan', 'Sulu', 'Tawi-Tawi'];

        if (in_array($province, $ncr)) return 'NCR';
        if (in_array($province, $island)) return 'Island';
        if (in_array($province, $visayas)) return 'Visayas';
        if (in_array($province, $mindanao)) return 'Mindanao';
        return 'Luzon';
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Shipping and Orders Heatmap Seeder...');

        // Step 1: Populate JNT Shipping Options (ID: 1)
        $this->populateShippingOptions(1, 'JNT');

        // Step 2: Populate LBC Shipping Options (ID: 4)
        $this->populateShippingOptions(4, 'LBC');

        // Step 3: Create Sample Orders for Heatmap Testing
        $this->createSampleOrders();

        $this->command->info('Seeding completed successfully!');
    }

    /**
     * Populate shipping options for all provinces.
     */
    private function populateShippingOptions($shippingId, $name)
    {
        $this->command->info("Populating {$name} shipping options for all provinces...");

        // Get existing provinces for this shipping method
        $existingProvinces = EcomProductsShippingOptions::where('shippingId', $shippingId)
            ->where('deleteStatus', 1)
            ->pluck('provinceTarget')
            ->toArray();

        $added = 0;
        foreach ($this->provinces as $province) {
            if (in_array($province, $existingProvinces)) {
                continue; // Skip if already exists
            }

            $region = $this->getRegion($province);
            $priceRange = $this->priceRanges[$region];

            // JNT is slightly cheaper than LBC
            $basePrice = rand($priceRange['min'], $priceRange['max']);
            $price = $shippingId == 1 ? $basePrice : $basePrice + rand(10, 30);

            EcomProductsShippingOptions::create([
                'shippingId' => $shippingId,
                'provinceTarget' => $province,
                'maxQuantity' => rand(5, 20),
                'shippingPrice' => $price,
                'isActive' => 1,
                'deleteStatus' => 1,
            ]);

            $added++;
        }

        $this->command->info("  Added {$added} new province options for {$name}");
    }

    /**
     * Create sample orders distributed across provinces for heatmap testing.
     */
    private function createSampleOrders()
    {
        $this->command->info('Creating sample orders for heatmap testing...');

        // Define order distribution - more orders in urban areas
        $distribution = [
            'Metro Manila' => 50,      // High concentration
            'Cebu' => 30,
            'Davao del Sur' => 25,
            'Cavite' => 20,
            'Laguna' => 20,
            'Bulacan' => 18,
            'Pampanga' => 15,
            'Rizal' => 15,
            'Batangas' => 12,
            'Negros Occidental' => 10,
            'Iloilo' => 10,
            'Pangasinan' => 8,
            'Zambales' => 7,
            'Nueva Ecija' => 7,
            'Quezon' => 6,
        ];

        // Add random orders to other provinces (1-5 each)
        foreach ($this->provinces as $province) {
            if (!isset($distribution[$province])) {
                $distribution[$province] = rand(1, 5);
            }
        }

        $totalOrders = 0;
        $userId = 1; // Admin user
        $shippingTypes = ['JNT Shipping', 'LBC Shipping'];

        foreach ($distribution as $province => $count) {
            for ($i = 0; $i < $count; $i++) {
                $municipality = $this->getRandomMunicipality($province);
                $shippingName = $shippingTypes[array_rand($shippingTypes)];

                // Random values for realistic orders
                $subtotal = rand(500, 5000);
                $shippingTotal = rand(99, 350);
                $discountTotal = rand(0, 200);
                $grandTotal = $subtotal + $shippingTotal - $discountTotal;

                // Random date within last 6 months
                $daysAgo = rand(0, 180);
                $createdAt = now()->subDays($daysAgo)->setTime(rand(8, 22), rand(0, 59), rand(0, 59));

                // Create the order
                EcomOrder::create([
                    'usersId' => $userId,
                    'orderNumber' => 'ORD-' . date('Ymd', $createdAt->timestamp) . '-' . strtoupper(substr(uniqid(), -4)) . $totalOrders,
                    'orderStatus' => 'complete', // Enum: pending, paid, complete, cancelled, refunded
                    'shippingStatus' => 'shipped', // Enum: pending, shipped, not_applicable
                    'trackingNumber' => 'TRK' . rand(100000000, 999999999),
                    'clientId' => null,
                    'clientFirstName' => $this->getRandomFirstName(),
                    'clientMiddleName' => $this->getRandomMiddleInitial(),
                    'clientLastName' => $this->getRandomLastName(),
                    'clientPhone' => '09' . rand(100000000, 999999999),
                    'clientEmail' => 'customer' . $totalOrders . '@example.com',
                    'shippingType' => 'Cash on Delivery',
                    'shippingName' => $shippingName,
                    'shippingFirstName' => $this->getRandomFirstName(),
                    'shippingMiddleName' => $this->getRandomMiddleInitial(),
                    'shippingLastName' => $this->getRandomLastName(),
                    'shippingPhone' => '09' . rand(100000000, 999999999),
                    'shippingEmail' => 'recipient' . $totalOrders . '@example.com',
                    'shippingHouseNumber' => rand(1, 999) . ' ' . $this->getRandomStreetType(),
                    'shippingStreet' => $this->getRandomStreetName(),
                    'shippingZone' => 'Barangay ' . rand(1, 50),
                    'shippingMunicipality' => $municipality,
                    'shippingProvince' => $province,
                    'shippingZipCode' => rand(1000, 9999),
                    'subtotal' => $subtotal,
                    'shippingTotal' => $shippingTotal,
                    'discountTotal' => $discountTotal,
                    'grandTotal' => $grandTotal,
                    'affiliateCommissionTotal' => 0,
                    'netRevenue' => $grandTotal,
                    'orderNotes' => 'Sample order for heatmap testing - ' . $province,
                    'isPackage' => false,
                    'deleteStatus' => 1,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                $totalOrders++;
            }
        }

        $this->command->info("  Created {$totalOrders} sample orders distributed across all provinces");
    }

    /**
     * Get random municipality for a province.
     */
    private function getRandomMunicipality($province)
    {
        if (isset($this->municipalities[$province])) {
            $cities = $this->municipalities[$province];
            return $cities[array_rand($cities)];
        }
        // Generic municipality name
        return ucfirst($province) . ' City';
    }

    /**
     * Random Filipino first names.
     */
    private function getRandomFirstName()
    {
        $names = ['Juan', 'Maria', 'Jose', 'Ana', 'Pedro', 'Rosa', 'Antonio', 'Carmen', 'Francisco', 'Elena', 'Miguel', 'Luz', 'Carlos', 'Teresa', 'Manuel', 'Isabel', 'Ricardo', 'Gloria', 'Eduardo', 'Cristina', 'Roberto', 'Angela', 'Fernando', 'Patricia', 'Rafael', 'Lourdes', 'Daniel', 'Rosario', 'Ernesto', 'Dolores', 'Mark', 'Jennifer', 'John', 'Michelle', 'Michael', 'Nicole', 'Christian', 'Angelica', 'Joshua', 'Katherine'];
        return $names[array_rand($names)];
    }

    /**
     * Random middle initials.
     */
    private function getRandomMiddleInitial()
    {
        return chr(rand(65, 90)) . '.';
    }

    /**
     * Random Filipino last names.
     */
    private function getRandomLastName()
    {
        $names = ['Santos', 'Reyes', 'Cruz', 'Garcia', 'Torres', 'Mendoza', 'Rivera', 'Flores', 'Gonzales', 'Lopez', 'Martinez', 'Hernandez', 'Perez', 'Rodriguez', 'Ramos', 'Castro', 'Bautista', 'Villanueva', 'Fernandez', 'De Leon', 'Aquino', 'Morales', 'Dela Cruz', 'Pascual', 'Gutierrez', 'Soriano', 'Manalo', 'Navarro', 'Aguilar', 'Valdez', 'Diaz', 'Enriquez', 'Salazar', 'Ignacio', 'Mercado', 'Miranda', 'Ocampo', 'Padilla', 'Santiago', 'Tolentino'];
        return $names[array_rand($names)];
    }

    /**
     * Random street types.
     */
    private function getRandomStreetType()
    {
        $types = ['Street', 'Avenue', 'Road', 'Drive', 'Lane', 'Boulevard', 'Extension', 'Highway'];
        return $types[array_rand($types)];
    }

    /**
     * Random street names.
     */
    private function getRandomStreetName()
    {
        $names = ['Rizal', 'Mabini', 'Bonifacio', 'Aguinaldo', 'Luna', 'Del Pilar', 'Quezon', 'Roxas', 'Magsaysay', 'Laurel', 'Marcos', 'Recto', 'España', 'Taft', 'EDSA', 'Commonwealth', 'Katipunan', 'Aurora', 'C5', 'Ortigas', 'Makati', 'Shaw', 'Pioneer', 'Kalayaan', 'Libertad', 'Congressional', 'Visayas', 'Mindanao', 'Luzon'];
        return $names[array_rand($names)] . ' ' . $this->getRandomStreetType();
    }
}
