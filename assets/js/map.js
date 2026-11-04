/**
 * map.js
 * -----------------------------------------------------------------------
 * Initializes a Leaflet.js map centered on Manolo Fortich, Bukidnon and
 * drops a colored pin for every vendor. Colors follow the same status
 * legend used across the app: green = active, amber = expiring soon,
 * red = expired.
 *
 * Vendor coordinates come from the `latitude` / `longitude` columns of the
 * `vendors` table, passed in by each page as the `vendors` array.
 * -----------------------------------------------------------------------
 */
function initPuestoMap(containerId, vendors, options) {
  options = options || {};
  var center = options.center || [8.3706, 124.8681]; // Manolo Fortich, Bukidnon
  var zoom = options.zoom || 13;

  var map = L.map(containerId).setView(center, zoom);

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

  var statusColor = {
    active: '#2E8B57',
    expiring: '#E8871E',
    expired: '#C1443C'
  };

  function makePinIcon(color) {
    var svg =
      '<svg width="30" height="40" viewBox="0 0 24 32" xmlns="http://www.w3.org/2000/svg">' +
      '<path d="M12 0C5.4 0 0 5.4 0 12c0 9 12 20 12 20s12-11 12-20C24 5.4 18.6 0 12 0z" fill="' + color + '"/>' +
      '<circle cx="12" cy="12" r="5" fill="#fff"/>' +
      '</svg>';
    return L.divIcon({
      html: svg,
      className: 'pm-map-pin',
      iconSize: [30, 40],
      iconAnchor: [15, 40],
      popupAnchor: [0, -36]
    });
  }

  var markers = [];
  vendors.forEach(function (v) {
    var color = statusColor[v.permit_status] || '#0F3D3E';
    var marker = L.marker([v.lat, v.lng], { icon: makePinIcon(color) }).addTo(map);

    var statusLabel = v.permit_status.charAt(0).toUpperCase() + v.permit_status.slice(1);
    marker.bindPopup(
      '<div style="font-family: Inter, sans-serif; min-width:190px;">' +
      '<strong style="font-family: \'Space Grotesk\', sans-serif;">' + v.name + '</strong><br>' +
      '<span style="color:#5B6B67;font-size:.8rem;">' + v.category + '</span><br>' +
      '<span style="font-size:.8rem;">Barangay ' + v.barangay + '</span><br>' +
      '<span style="font-size:.8rem;">Permit: <code>' + v.permit_no + '</code></span><br>' +
      '<span style="display:inline-block;margin-top:.35rem;padding:.15rem .5rem;border-radius:999px;font-size:.72rem;font-weight:600;background:' + color + '22;color:' + color + ';">' + statusLabel + '</span>' +
      '</div>'
    );
    marker._vendorId = v.id;
    markers.push(marker);
  });

  return { map: map, markers: markers };
}

/**
 * pmFilterMapBySearch
 * Very simple client-side search used on the "Map Search" (UAT-04)
 * feature: hides/shows markers whose vendor name matches the query.
 */
function pmFilterMapBySearch(mapHandle, vendors, query) {
  query = (query || '').toLowerCase().trim();
  mapHandle.markers.forEach(function (marker, i) {
    var v = vendors[i];
    var matches = !query || v.name.toLowerCase().indexOf(query) !== -1 || v.barangay.toLowerCase().indexOf(query) !== -1;
    if (matches) {
      if (!mapHandle.map.hasLayer(marker)) marker.addTo(mapHandle.map);
    } else {
      mapHandle.map.removeLayer(marker);
    }
  });
}
