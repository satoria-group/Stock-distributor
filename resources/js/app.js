import './bootstrap';

import { createGrid, ModuleRegistry, ClientSideRowModelModule, CommunityFeaturesModule } from 'ag-grid-community';
import Chart from 'chart.js/auto';

ModuleRegistry.registerModules([ClientSideRowModelModule, CommunityFeaturesModule]);

// Exposed on window so Blade views can use them from plain <script> tags
// inside Livewire components (wire:ignore islands) without needing their
// own bundle entrypoints.
window.agGridCreateGrid = createGrid;
window.Chart = Chart;

