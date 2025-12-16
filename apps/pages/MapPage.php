<?php

require_once ROOT_PATH . '/pages/BasePage.php';

class MapPage extends BasePage {

    public function show() {

        // Demo location data
        $locations = [
            [
                'lat' => 1.3521,
                'lng' => 103.8198,
                'name' => 'Singapore',
                'description' => 'City-state in Southeast Asia'
            ],
            [
                'lat' => 1.2868,
                'lng' => 103.8545,
                'name' => 'Marina Bay',
                'description' => 'Famous waterfront area'
            ],
            [
                'lat' => 1.3483,
                'lng' => 103.6831,
                'name' => 'Jurong',
                'description' => 'Industrial town in western Singapore'
            ],
        ];

        // Use Inertia to render React component
        return Inertia::render('Map', [
            'locations' => $locations,
            'mapboxToken' => env('MAPBOX_TOKEN', 'pk.your-mapbox-token-here')
        ]);
    }
}
