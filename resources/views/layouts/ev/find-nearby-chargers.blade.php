@extends('layouts.ev.app')

@section('title', 'Find Nearby Chargers')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-center mb-8 text-gray-800">Find Nearby EV Chargers</h1>
    
    <div class="max-w-2xl mx-auto mb-8">
        <div class="bg-white p-6 rounded-lg shadow-md">
            <div class="mb-4">
                <label for="addressInput" class="block text-gray-700 text-sm font-bold mb-2">
                    Enter Address or Location
                </label>
                <div class="flex">
                    <input 
                        type="text" 
                        id="addressInput" 
                        placeholder="Enter an address or location" 
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-green">
                    <button 
                        id="searchBtn" 
                        class="px-6 py-2 bg-green text-white rounded-r-lg hover:bg-green-dark transition-colors">
                        Search
                    </button>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                    Or use current location
                </label>
                <button 
                    id="useLocationBtn" 
                    class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                    Use My Current Location
                </button>
            </div>
            
            <div id="radiusInput" class="mb-4 hidden">
                <label for="radius" class="block text-gray-700 text-sm font-bold mb-2">
                    Search Radius (km)
                </label>
                <input 
                    type="range" 
                    id="radius" 
                    min="1" 
                    max="20" 
                    value="10" 
                    class="w-full">
                <div class="flex justify-between text-sm text-gray-500">
                    <span>1 km</span>
                    <span id="radiusValue">10 km</span>
                    <span>20 km</span>
                </div>
            </div>
        </div>
    </div>
    
    <div id="mapContainer" class="hidden">
        <div class="bg-white rounded-lg shadow-md p-4 mb-8">
            <h2 class="text-xl font-semibold mb-4">Map View</h2>
            <div id="map" class="h-96 rounded-lg" style="width: 100%;"></div>
        </div>
    </div>
    
    <div id="resultsContainer" class="hidden">
        <h2 class="text-2xl font-semibold mb-4 text-gray-800">Nearby Charging Stations</h2>
        <div id="chargersList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Charger results will be inserted here -->
        </div>
    </div>
    
    <div id="loadingSpinner" class="hidden flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-green"></div>
    </div>
    
    <div id="noResults" class="hidden text-center py-12">
        <h3 class="text-xl font-medium text-gray-900 mb-2">No charging stations found</h3>
        <p class="text-gray-500">Try increasing the search radius or searching in a different location.</p>
    </div>
</div>

<script>
    // Initialize variables
    let map = null;
    let markers = [];
    let userLocationMarker = null;
    
    // Update radius value display
    document.getElementById('radius').addEventListener('input', function() {
        document.getElementById('radiusValue').textContent = this.value + ' km';
    });
    
    // Search by address
    document.getElementById('searchBtn').addEventListener('click', function() {
        const address = document.getElementById('addressInput').value.trim();
        
        if (!address) {
            alert('Please enter an address or location');
            return;
        }
        
        // Show loading spinner and hide containers
        document.getElementById('loadingSpinner').classList.remove('hidden');
        document.getElementById('resultsContainer').classList.add('hidden');
        document.getElementById('noResults').classList.add('hidden');
        
        // Call the search address API
        fetch('{{ route("search.address") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ address: address })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show radius input and find nearby chargers
                document.getElementById('radiusInput').classList.remove('hidden');
                findNearbyChargers(data.data.latitude, data.data.longitude);
            } else {
                document.getElementById('loadingSpinner').classList.add('hidden');
                alert(data.message || 'Address not found');
            }
        })
        .catch(error => {
            document.getElementById('loadingSpinner').classList.add('hidden');
            console.error('Error:', error);
            alert('An error occurred while searching for the address');
        });
    });
    
    // Use current location
    document.getElementById('useLocationBtn').addEventListener('click', function() {
        if (!navigator.geolocation) {
            alert('Geolocation is not supported by your browser');
            return;
        }
        
        // Show loading spinner
        document.getElementById('loadingSpinner').classList.remove('hidden');
        document.getElementById('resultsContainer').classList.add('hidden');
        document.getElementById('noResults').classList.add('hidden');
        
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                // Show radius input and find nearby chargers
                document.getElementById('radiusInput').classList.remove('hidden');
                findNearbyChargers(lat, lng);
            },
            function(error) {
                document.getElementById('loadingSpinner').classList.add('hidden');
                console.error('Geolocation error:', error);
                alert('Unable to retrieve your location');
            }
        );
    });
    
    // Function to find nearby chargers
    function findNearbyChargers(lat, lng) {
        const radius = document.getElementById('radius').value;
        
        fetch('{{ route("find.nearby.chargers") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                latitude: lat,
                longitude: lng,
                radius: radius
            })
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('loadingSpinner').classList.add('hidden');
            
            if (data.success && data.data.length > 0) {
                displayChargersOnMap(lat, lng, data.data);
                displayChargerList(data.data);
                document.getElementById('mapContainer').classList.remove('hidden');
                document.getElementById('resultsContainer').classList.remove('hidden');
                document.getElementById('noResults').classList.add('hidden');
            } else {
                document.getElementById('mapContainer').classList.add('hidden');
                document.getElementById('resultsContainer').classList.add('hidden');
                document.getElementById('noResults').classList.remove('hidden');
            }
        })
        .catch(error => {
            document.getElementById('loadingSpinner').classList.add('hidden');
            console.error('Error:', error);
            alert('An error occurred while finding nearby chargers');
        });
    }
    
    // Display chargers on map - Placeholder functionality since Google Maps API is removed
    function displayChargersOnMap(userLat, userLng, chargers) {
        // Show a message that map functionality is temporarily unavailable
        const mapDiv = document.getElementById('map');
        if (mapDiv) {
            mapDiv.innerHTML = `
                <div class="flex flex-col items-center justify-center h-full p-4 text-center">
                    <div class="mb-4 text-4xl">🗺️</div>
                    <h3 class="text-lg font-semibold mb-2">Interactive Map Unavailable</h3>
                    <p class="text-gray-600 mb-4">We're working on implementing a new map solution that doesn't require external APIs.</p>
                    <p class="text-sm text-gray-500">In the meantime, use the list below to find nearby chargers.</p>
                </div>
            `;
        }
    }
    
    // Display charger list in HTML
    function displayChargerList(chargers) {
        const container = document.getElementById('chargersList');
        container.innerHTML = '';
        
        chargers.forEach(charger => {
            const chargerCard = document.createElement('div');
            chargerCard.className = 'bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow';
            
            chargerCard.innerHTML = `
                <div class="p-4">
                    <h3 class="font-semibold text-lg mb-2">${charger.name}</h3>
                    <p class="text-sm text-gray-600 mb-1"><strong>Provider:</strong> ${charger.provider?.name || 'N/A'}</p>
                    <p class="text-sm text-gray-600 mb-1"><strong>Location:</strong> ${charger.city?.name || 'N/A'}</p>
                    <p class="text-sm text-gray-600 mb-2"><strong>Distance:</strong> ${charger.distance.toFixed(2)} km</p>
                    <div class="mt-3">
                        <a href="/map?lat=${charger.latitude}&lng=${charger.longitude}" 
                           class="inline-block px-4 py-2 bg-green text-white rounded-lg hover:bg-green-dark transition-colors text-sm">
                            View on Map
                        </a>
                    </div>
                </div>
            `;
            
            container.appendChild(chargerCard);
        });
    }
</script>
@endsection