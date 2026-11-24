// Both static dependencies finish before DOMContentLoaded, so the unchanged
// seller form sees Leaflet. Only product create/edit pages use this entry.
import './runtime/leaflet';
import './app';
