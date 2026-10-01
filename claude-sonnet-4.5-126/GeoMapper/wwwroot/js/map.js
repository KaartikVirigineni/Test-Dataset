window.initializeMap = function () {
    var map = L.map('map').setView([51.505, -0.09], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
    }).addTo(map);

    fetch('/api/locations')
        .then(response => response.json())
        .then(data => {
            data.forEach(location => {
                var marker = L.marker([location.latitude, location.longitude]).addTo(map);
                marker.bindPopup(`
                    <b>${location.name}</b><br>
                    ${location.description}<br>
                    <em>Category: ${location.category}</em><br>
                    <small>By: ${location.username}</small>
                `);
            });

            if (data.length > 0) {
                var bounds = L.latLngBounds(data.map(loc => [loc.latitude, loc.longitude]));
                map.fitBounds(bounds);
            }
        })
        .catch(error => {
            console.error('Error loading locations:', error);
        });
};