@extends('layouts.app')

@section('title', 'DICOM Viewer')

@push('styles')
<style>
    .viewer-container {
        background: #000;
        position: relative;
        height: calc(100vh - 200px);
        min-height: 500px;
        border-radius: 8px;
        overflow: hidden;
    }
    
    #dicomImage {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }
    
    .viewer-toolbar {
        background: linear-gradient(135deg, #1e3a5f 0%, #2d5f8d 100%);
        padding: 12px 20px;
        border-radius: 8px 8px 0 0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .tool-btn {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #fff;
        padding: 8px 16px;
        margin: 0 4px;
        border-radius: 6px;
        transition: all 0.3s;
        font-size: 0.9rem;
    }
    
    .tool-btn:hover {
        background: rgba(255, 255, 255, 0.2);
        border-color: rgba(255, 255, 255, 0.4);
        transform: translateY(-2px);
    }
    
    .tool-btn.active {
        background: #4CAF50;
        border-color: #4CAF50;
    }
    
    .series-list {
        max-height: calc(100vh - 200px);
        overflow-y: auto;
        background: #f8f9fa;
        border-radius: 8px;
        padding: 10px;
    }
    
    .series-item {
        background: #fff;
        padding: 12px;
        margin-bottom: 10px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s;
        border: 2px solid transparent;
    }
    
    .series-item:hover {
        background: #e3f2fd;
        transform: translateX(5px);
    }
    
    .series-item.active {
        background: #e3f2fd;
        border-color: #1e3a5f;
    }
    
    .series-thumbnail {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 4px;
        background: #000;
    }
    
    .image-info {
        position: absolute;
        top: 10px;
        left: 10px;
        color: #fff;
        background: rgba(0,0,0,0.7);
        padding: 10px 15px;
        border-radius: 6px;
        font-family: monospace;
        font-size: 0.85rem;
        z-index: 10;
    }
    
    .measurement-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
    }
    
    .windowing-slider {
        position: absolute;
        top: 50%;
        right: 20px;
        transform: translateY(-50%);
        background: rgba(0,0,0,0.8);
        padding: 20px 15px;
        border-radius: 8px;
        z-index: 10;
    }
    
    .slider-container {
        width: 200px;
    }
    
    .slider-label {
        color: #fff;
        font-size: 0.85rem;
        margin-bottom: 5px;
    }
    
    .slider-value {
        color: #4CAF50;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-1" style="color: #1e3a5f;">
                <i class="bi bi-eye"></i> DICOM Viewer
            </h1>
            <p class="text-secondary mb-0">{{ $study->patient->full_name ?? 'Patient' }} - {{ $study->study_description }}</p>
        </div>
        <div>
            <button class="btn btn-outline-secondary me-2" onclick="history.back()">
                <i class="bi bi-arrow-left"></i> Back
            </button>
            <a href="{{ route('radiology.reports.create', $study) }}" class="btn btn-success">
                <i class="bi bi-file-text"></i> Create Report
            </a>
        </div>
    </div>

    <!-- Patient & Study Info Card -->
    <div class="card mb-3">
        <div class="card-body py-2">
            <div class="row text-sm">
                <div class="col-md-3">
                    <strong>Patient:</strong> {{ $study->patient->full_name ?? 'N/A' }} ({{ $study->patient->patient_number ?? 'N/A' }})
                </div>
                <div class="col-md-2">
                    <strong>Study Date:</strong> {{ $study->study_date ? $study->study_date->format('M d, Y') : 'N/A' }}
                </div>
                <div class="col-md-2">
                    <strong>Modality:</strong> {{ $study->modality->name ?? 'N/A' }}
                </div>
                <div class="col-md-3">
                    <strong>Referring:</strong> Dr. {{ $study->request->doctor->first_name ?? '' }} {{ $study->request->doctor->last_name ?? 'N/A' }}
                </div>
                <div class="col-md-2">
                    <strong>Priority:</strong> 
                    <span class="badge bg-{{ $study->request->priority === 'stat' ? 'danger' : ($study->request->priority === 'urgent' ? 'warning' : 'primary') }}">
                        {{ strtoupper($study->request->priority ?? 'Routine') }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Series List Sidebar -->
        <div class="col-md-2">
            <div class="card">
                <div class="card-header" style="background: linear-gradient(135deg, #1e3a5f 0%, #2d5f8d 100%); color: #fff;">
                    <h6 class="mb-0">
                        <i class="bi bi-images"></i> Series ({{ $study->series->count() }})
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="series-list">
                        @forelse($study->series as $series)
                        <div class="series-item" data-series-id="{{ $series->id }}" onclick="loadSeries({{ $series->id }})">
                            <div class="d-flex align-items-center">
                                <div class="series-thumbnail me-2">
                                    @if($series->images->first())
                                    @php
                                        $thumbImage = $series->images->first();
                                        $thumbUrl = $thumbImage->getUrl();
                                    @endphp
                                    @if($thumbImage->exists() && $thumbImage->isDisplayableImage())
                                    <img src="{{ $thumbUrl }}" 
                                         alt="Series {{ $series->series_number }}" 
                                         class="series-thumbnail"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                    <div class="series-thumbnail d-flex align-items-center justify-content-center" style="display:none;">
                                        <i class="bi bi-file-earmark-medical text-muted" style="font-size: 2rem;"></i>
                                    </div>
                                    @else
                                    <div class="series-thumbnail d-flex align-items-center justify-content-center">
                                        <i class="bi bi-file-earmark-medical text-muted" style="font-size: 2rem;"></i>
                                    </div>
                                    @endif
                                    @else
                                    <div class="series-thumbnail d-flex align-items-center justify-content-center">
                                        <i class="bi bi-image text-muted" style="font-size: 2rem;"></i>
                                    </div>
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <div style="font-weight: 600; font-size: 0.9rem;">Series {{ $series->series_number }}</div>
                                    <div style="font-size: 0.75rem; color: #666;">{{ $series->series_description }}</div>
                                    <div style="font-size: 0.7rem; color: #999;">{{ $series->images->count() }} images</div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-4 text-muted">
                            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                            <p class="mt-2 mb-0">No series available</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Viewer -->
        <div class="col-md-10">
            <div class="card">
                <!-- Toolbar -->
                <div class="viewer-toolbar">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <button class="tool-btn" id="panBtn" onclick="activateTool('pan')">
                                <i class="bi bi-arrows-move"></i> Pan
                            </button>
                            <button class="tool-btn active" id="windowBtn" onclick="activateTool('window')">
                                <i class="bi bi-sliders"></i> Window
                            </button>
                            <button class="tool-btn" id="zoomBtn" onclick="activateTool('zoom')">
                                <i class="bi bi-zoom-in"></i> Zoom
                            </button>
                            <button class="tool-btn" id="lengthBtn" onclick="activateTool('length')">
                                <i class="bi bi-rulers"></i> Measure
                            </button>
                            <button class="tool-btn" id="angleBtn" onclick="activateTool('angle')">
                                <i class="bi bi-triangle"></i> Angle
                            </button>
                            <button class="tool-btn" id="annotateBtn" onclick="activateTool('annotate')">
                                <i class="bi bi-pencil"></i> Annotate
                            </button>
                        </div>
                        <div>
                            <button class="tool-btn" onclick="rotateImage(90)">
                                <i class="bi bi-arrow-clockwise"></i> Rotate
                            </button>
                            <button class="tool-btn" onclick="invertImage()">
                                <i class="bi bi-moon-stars"></i> Invert
                            </button>
                            <button class="tool-btn" onclick="resetView()">
                                <i class="bi bi-arrow-counterclockwise"></i> Reset
                            </button>
                            <button class="tool-btn" onclick="fullscreen()">
                                <i class="bi bi-fullscreen"></i> Fullscreen
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Viewer Canvas -->
                <div class="card-body p-0">
                    <div class="viewer-container" id="viewerContainer">
                        <!-- Image Info Overlay -->
                        <div class="image-info" id="imageInfo">
                            <div><strong>Patient:</strong> <span id="infoPatient">{{ $study->patient->full_name ?? 'N/A' }}</span></div>
                            <div><strong>Study:</strong> <span id="infoStudy">{{ $study->study_description }}</span></div>
                            <div><strong>Series:</strong> <span id="infoSeries">-</span></div>
                            <div><strong>Image:</strong> <span id="infoImage">1 / 1</span></div>
                            <div class="mt-2">
                                <strong>WW/WL:</strong> <span id="infoWindow">400 / 40</span>
                            </div>
                        </div>

                        <!-- Windowing Sliders -->
                        <div class="windowing-slider" id="windowingControls" style="display: none;">
                            <div class="slider-container mb-3">
                                <div class="slider-label">
                                    Window Width: <span class="slider-value" id="wwValue">400</span>
                                </div>
                                <input type="range" class="form-range" id="windowWidth" 
                                       min="1" max="4000" value="400" step="1">
                            </div>
                            <div class="slider-container">
                                <div class="slider-label">
                                    Window Level: <span class="slider-value" id="wlValue">40</span>
                                </div>
                                <input type="range" class="form-range" id="windowLevel" 
                                       min="-1000" max="1000" value="40" step="1">
                            </div>
                            <div class="mt-3">
                                <button class="btn btn-sm btn-light w-100 mb-1" onclick="setPreset('lung')">
                                    Lung Window
                                </button>
                                <button class="btn btn-sm btn-light w-100 mb-1" onclick="setPreset('bone')">
                                    Bone Window
                                </button>
                                <button class="btn btn-sm btn-light w-100 mb-1" onclick="setPreset('brain')">
                                    Brain Window
                                </button>
                                <button class="btn btn-sm btn-light w-100" onclick="setPreset('abdomen')">
                                    Abdomen Window
                                </button>
                            </div>
                        </div>

                        <!-- Main Image Display -->
                        <canvas id="dicomImage"></canvas>
                        
                        <!-- Measurement Overlay -->
                        <svg class="measurement-overlay" id="measurementOverlay"></svg>
                    </div>
                </div>

                <!-- Image Navigation -->
                <div class="card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <button class="btn btn-sm btn-outline-primary" onclick="previousImage()" id="prevBtn">
                                <i class="bi bi-chevron-left"></i> Previous
                            </button>
                            <button class="btn btn-sm btn-outline-primary" onclick="nextImage()" id="nextBtn">
                                Next <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                        <div>
                            <input type="range" class="form-range" id="imageSlider" 
                                   min="0" max="0" value="0" style="width: 300px;" 
                                   onchange="loadImageByIndex(this.value)">
                        </div>
                        <div>
                            <button class="btn btn-sm btn-outline-secondary" onclick="playImages()" id="playBtn">
                                <i class="bi bi-play"></i> Play
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="stopImages()" id="stopBtn" style="display:none;">
                                <i class="bi bi-stop"></i> Stop
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- Cornerstone.js DICOM Viewer -->
<script src="https://unpkg.com/cornerstone-core@2.6.1/dist/cornerstone.min.js"></script>
<script src="https://unpkg.com/cornerstone-math@0.1.10/dist/cornerstoneMath.min.js"></script>
<script src="https://unpkg.com/cornerstone-tools@6.0.7/dist/cornerstoneTools.min.js"></script>
<script src="https://unpkg.com/dicom-parser@1.8.13/dist/dicomParser.min.js"></script>
<script src="https://unpkg.com/cornerstone-wado-image-loader@4.1.3/dist/cornerstoneWADOImageLoader.bundle.min.js"></script>

<script>
// Simple, user-friendly DICOM viewer
let currentSeries = null;
let currentImages = [];
let currentImageIndex = 0;
let playInterval = null;
let activeTool = 'window';
let rotation = 0;
let isInverted = false;

// Initialize viewer
document.addEventListener('DOMContentLoaded', function() {
    const element = document.getElementById('dicomImage');
    cornerstone.enable(element);
    
    // Load first series automatically
    @if($study->series->first())
    loadSeries({{ $study->series->first()->id }});
    @endif
});

function loadSeries(seriesId) {
    // Highlight active series
    document.querySelectorAll('.series-item').forEach(item => {
        item.classList.remove('active');
    });
    document.querySelector(`[data-series-id="${seriesId}"]`).classList.add('active');
    
    // Fetch series images
    fetch(`/radiology/series/${seriesId}/images`)
        .then(response => response.json())
        .then(data => {
            currentSeries = seriesId;
            currentImages = data.images;
            currentImageIndex = 0;
            
            // Update slider
            const slider = document.getElementById('imageSlider');
            slider.max = currentImages.length - 1;
            slider.value = 0;
            
            // Update info
            document.getElementById('infoSeries').textContent = data.series_description;
            
            // Load first image
            if (currentImages.length > 0) {
                loadImage(0);
            }
        })
        .catch(error => {
            console.error('Error loading series:', error);
            showMessage('Error loading images for this series.', 'danger');
        });
}

function loadImage(index) {
    if (!currentImages[index]) return;
    
    const element = document.getElementById('dicomImage');
    const imageUrl = currentImages[index].file_url || `{{ url('/radiology/images') }}/${currentImages[index].id}/serve`;
    
    // For now, display as regular image (DICOM parsing would go here)
    const img = new Image();
    img.onload = function() {
        const canvas = element;
        const ctx = canvas.getContext('2d');
        
        canvas.width = element.parentElement.clientWidth;
        canvas.height = element.parentElement.clientHeight;
        
        // Calculate scaling to fit
        const scale = Math.min(
            canvas.width / img.width,
            canvas.height / img.height
        );
        
        const x = (canvas.width / 2) - (img.width / 2) * scale;
        const y = (canvas.height / 2) - (img.height / 2) * scale;
        
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Apply transformations
        ctx.save();
        ctx.translate(canvas.width / 2, canvas.height / 2);
        ctx.rotate(rotation * Math.PI / 180);
        ctx.translate(-canvas.width / 2, -canvas.height / 2);
        
        if (isInverted) {
            ctx.filter = 'invert(1)';
        }
        
        ctx.drawImage(img, x, y, img.width * scale, img.height * scale);
        ctx.restore();
    };
    img.src = imageUrl;
    
    currentImageIndex = index;
    
    // Update info
    document.getElementById('infoImage').textContent = `${index + 1} / ${currentImages.length}`;
}

function loadImageByIndex(index) {
    loadImage(parseInt(index));
}

function previousImage() {
    if (currentImageIndex > 0) {
        loadImage(currentImageIndex - 1);
        document.getElementById('imageSlider').value = currentImageIndex;
    }
}

function nextImage() {
    if (currentImageIndex < currentImages.length - 1) {
        loadImage(currentImageIndex + 1);
        document.getElementById('imageSlider').value = currentImageIndex;
    }
}

function playImages() {
    document.getElementById('playBtn').style.display = 'none';
    document.getElementById('stopBtn').style.display = 'inline-block';
    
    playInterval = setInterval(() => {
        if (currentImageIndex < currentImages.length - 1) {
            nextImage();
        } else {
            loadImage(0);
            document.getElementById('imageSlider').value = 0;
        }
    }, 200); // 5 fps
}

function stopImages() {
    clearInterval(playInterval);
    document.getElementById('playBtn').style.display = 'inline-block';
    document.getElementById('stopBtn').style.display = 'none';
}

function activateTool(tool) {
    // Deactivate all tools
    document.querySelectorAll('.tool-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Activate selected tool
    document.getElementById(tool + 'Btn').classList.add('active');
    activeTool = tool;
    
    // Show/hide windowing controls
    document.getElementById('windowingControls').style.display = 
        (tool === 'window') ? 'block' : 'none';
    
    showMessage(`${tool.charAt(0).toUpperCase() + tool.slice(1)} tool activated`, 'info');
}

function rotateImage(degrees) {
    rotation = (rotation + degrees) % 360;
    loadImage(currentImageIndex);
    showMessage(`Rotated ${degrees}°`, 'success');
}

function invertImage() {
    isInverted = !isInverted;
    loadImage(currentImageIndex);
    showMessage(isInverted ? 'Image inverted' : 'Inversion removed', 'success');
}

function resetView() {
    rotation = 0;
    isInverted = false;
    loadImage(currentImageIndex);
    showMessage('View reset', 'success');
}

function fullscreen() {
    const container = document.getElementById('viewerContainer');
    if (container.requestFullscreen) {
        container.requestFullscreen();
    } else if (container.webkitRequestFullscreen) {
        container.webkitRequestFullscreen();
    }
}

function setPreset(preset) {
    const presets = {
        lung: { ww: 1500, wl: -600 },
        bone: { ww: 2000, wl: 400 },
        brain: { ww: 80, wl: 40 },
        abdomen: { ww: 400, wl: 40 }
    };
    
    if (presets[preset]) {
        document.getElementById('windowWidth').value = presets[preset].ww;
        document.getElementById('windowLevel').value = presets[preset].wl;
        document.getElementById('wwValue').textContent = presets[preset].ww;
        document.getElementById('wlValue').textContent = presets[preset].wl;
        document.getElementById('infoWindow').textContent = 
            `${presets[preset].ww} / ${presets[preset].wl}`;
        
        showMessage(`${preset.charAt(0).toUpperCase() + preset.slice(1)} window preset applied`, 'success');
    }
}

// Window level sliders
document.getElementById('windowWidth').addEventListener('input', function() {
    document.getElementById('wwValue').textContent = this.value;
    document.getElementById('infoWindow').textContent = 
        `${this.value} / ${document.getElementById('windowLevel').value}`;
});

document.getElementById('windowLevel').addEventListener('input', function() {
    document.getElementById('wlValue').textContent = this.value;
    document.getElementById('infoWindow').textContent = 
        `${document.getElementById('windowWidth').value} / ${this.value}`;
});

function showMessage(message, type = 'success') {
    // Simple toast notification
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
    toast.style.zIndex = '9999';
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => toast.remove(), 2000);
}
</script>
@endpush
