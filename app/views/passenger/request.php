<h1>Solicitar viaje</h1>
<p class="muted">Haz clic en el mapa para fijar el <strong>origen</strong>, luego el <strong>destino</strong>.</p>

<div id="map" style="height:420px;border-radius:12px;"></div>

<form method="post" action="/passenger/request" id="ride-form" class="card mt">
    <?= csrf_field() ?>
    <input type="hidden" name="origin_lat"      id="origin_lat">
    <input type="hidden" name="origin_lng"      id="origin_lng">
    <input type="hidden" name="destination_lat" id="destination_lat">
    <input type="hidden" name="destination_lng" id="destination_lng">

    <div class="grid-2">
        <label>Dirección de origen
            <input type="text" name="origin_address"      id="origin_address"      required placeholder="Ej. Casa, oficina...">
        </label>
        <label>Dirección de destino
            <input type="text" name="destination_address" id="destination_address" required placeholder="Ej. Aeropuerto, Centro...">
        </label>
    </div>

    <div class="grid-2">
        <label>Categoría
            <select name="category" id="category">
                <option value="economy">Economy</option>
                <option value="comfort">Comfort</option>
                <option value="xl">XL</option>
            </select>
        </label>
        <label>Pago
            <select name="payment_method">
                <option value="cash">Efectivo</option>
                <option value="card">Tarjeta</option>
            </select>
        </label>
    </div>

    <div id="estimate" class="estimate muted">Selecciona origen y destino para ver la tarifa estimada.</div>

    <button class="btn btn-primary" type="submit" id="request-btn" disabled>Solicitar viaje</button>
</form>

<script>
(function(){
    const map = L.map('map').setView([19.4326, -99.1332], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap', maxZoom: 19
    }).addTo(map);

    let originMarker = null, destMarker = null;
    let stage = 'origin';

    map.on('click', e => {
        const { lat, lng } = e.latlng;
        if (stage === 'origin') {
            if (originMarker) map.removeLayer(originMarker);
            originMarker = L.marker([lat,lng], {title:'Origen'}).addTo(map).bindPopup('Origen').openPopup();
            document.getElementById('origin_lat').value = lat;
            document.getElementById('origin_lng').value = lng;
            stage = 'destination';
        } else {
            if (destMarker) map.removeLayer(destMarker);
            destMarker = L.marker([lat,lng], {title:'Destino'}).addTo(map).bindPopup('Destino').openPopup();
            document.getElementById('destination_lat').value = lat;
            document.getElementById('destination_lng').value = lng;
            stage = 'origin';
            recalcEstimate();
        }
    });

    async function recalcEstimate(){
        const oLat = parseFloat(document.getElementById('origin_lat').value);
        const oLng = parseFloat(document.getElementById('origin_lng').value);
        const dLat = parseFloat(document.getElementById('destination_lat').value);
        const dLng = parseFloat(document.getElementById('destination_lng').value);
        if (!oLat || !dLat) return;

        const cat = document.getElementById('category').value;
        const r = await fetch('/api/fare-estimate', {
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body: JSON.stringify({origin_lat:oLat, origin_lng:oLng, destination_lat:dLat, destination_lng:dLng, category:cat})
        });
        if (!r.ok) return;
        const data = await r.json();
        document.getElementById('estimate').innerHTML =
            `🚗 <strong>${data.distance_km} km</strong> · ⏱️ ${data.duration_min} min · 💰 <strong>$${data.fare} ${data.currency}</strong>`;
        document.getElementById('request-btn').disabled = false;
    }

    document.getElementById('category').addEventListener('change', recalcEstimate);
})();
</script>
