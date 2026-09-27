import Chart from 'chart.js/auto';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import markerIcon from 'leaflet/dist/images/marker-icon.png?url';
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png?url';
import markerShadow from 'leaflet/dist/images/marker-shadow.png?url';
import Cropper from 'cropperjs';
import 'cropperjs/dist/cropper.css';

// Explicit Vite URLs avoid Leaflet's CSS-path detection with hashed assets.
L.Icon.Default.imagePath = '';
L.Icon.Default.mergeOptions({
    iconUrl: markerIcon,
    iconRetinaUrl: markerIcon2x,
    shadowUrl: markerShadow,
});

// Existing Blade handlers use these globals after DOMContentLoaded or Alpine init.
window.Chart = Chart;
window.L = L;
window.Cropper = Cropper;
