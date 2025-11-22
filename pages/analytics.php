<!-- Plant Disease Detection Section -->


    <style>
        /* Custom Styles for Clean Design */
        .analytics-tab .nav-link { 
            border-radius: 12px 12px 0 0; 
            font-weight: 500; 
            border: 1px solid #dee2e6;
        }
        .analytics-tab .nav-link.active { 
            background: #10b981; /* Green theme */
            color: white; 
            border-color: #10b981;
        }
        .symptom-checkbox { 
            margin-right: 1rem; 
            margin-bottom: 0.5rem; 
            display: inline-block;
        }
        .recommendation-item { 
            border-left: 4px solid #f59e0b; /* Orange accent */
            padding: 1rem; 
            margin-bottom: 1rem; 
            background: rgba(255, 255, 255, 0.8); 
            border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .activity-modal .modal-header { 
            background: #10b981; 
            color: white; 
            border-radius: 12px 12px 0 0; 
        }
        #preview, #resultPreview { 
            max-height: 300px; 
            border-radius: 12px; 
            box-shadow: 0 4px 12px rgba(0,0,0,0.15); 
        }
        .confidence-bar { 
            height: 8px; 
            background: #e2e8f0; 
            border-radius: 4px; 
            overflow: hidden; 
        }
        .confidence-fill { 
            height: 100%; 
            background: #10b981; 
            transition: width 0.3s ease; 
        }
        .harvest-card {
            border: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-radius: 12px;
        }
        .harvest-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        @media (max-width: 768px) { 
            .symptom-group { flex-direction: column; } 
            .symptom-checkbox { margin-right: 0; width: 100%; } 
            .nav-tabs { flex-wrap: wrap; }
            .recommendation-item { margin-bottom: 1rem; }
        }

        .harvest-card {
        border-radius: 12px;
        transition: transform 0.2s ease-in-out;
        }
        .harvest-card:hover {
        transform: translateY(-3px);
        }
        #smartCareOutput p {
        font-size: 0.95rem;
        line-height: 1.5;
        }

    </style>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <h2 class="mb-3 text-success"><i class="fas fa-microscope me-2"></i> Plant Disease Analytics</h2>
                <p class="text-secondary">Detect diseases using AI image analysis or manual symptoms. Get tailored recommendations and schedule treatments in your activity log.</p>
            </div>
        </div>

        <!-- Tabs for Sections -->
        <ul class="nav nav-tabs analytics-tab mb-4" id="analyticsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="ai-tab" data-bs-toggle="tab" data-bs-target="#ai-section" type="button" role="tab">
                    <i class="fas fa-camera me-1"></i> AI Image Detection
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual-section" type="button" role="tab">
                    <i class="fas fa-stethoscope me-1"></i> Crop Recommendation
                </button>
            </li>
        </ul>

        <div class="tab-content" id="analyticsTabContent">

            <!-- Section 1: AI Image Upload (Your Original UI Enhanced) -->
            <div class="tab-pane fade show active" id="ai-section" role="tabpanel">
                <div class="card shadow-sm harvest-card">
                    <div class="card-body">
                        <h5 class="text-success mb-3"><i class="fas fa-camera me-2"></i>Upload Plant Image</h5>
                        <p class="text-secondary mb-3">Upload a clear photo of the affected plant/leaf for AI-powered disease detection.</p>

                            <form id="diseaseForm" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="image" class="form-label">Upload Plant Image:</label>
                                    <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                                </div>

                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-search me-2"></i>Analyze
                                </button>
                            </form>
                        <div id="aiResultAlert" class="alert mt-3" style="display:none;"></div>
                        <img id="preview" src="" alt="Preview" class="img-fluid mt-3" style="display:none; border-radius:8px;">
                    </div>
                </div>
            </div>

                
     
            <!-- SMART CARE RECOMMENDER SECTION TAB 2 -->

            <div class="tab-pane fade" id="manual-section" role="tabpanel">
            <div class="row g-3">

                <!-- 🟢 Input Section -->
                <div class="col-md-6">
                <div class="card shadow-sm harvest-card h-100">
                    <div class="card-body">
                    <h4 class="mb-3"><i class="fas fa-seedling text-success me-2"></i>Soil & Environment Data</h4>

                    <!-- Soil nutrients -->
                    <div class="mb-3">
                        <label for="N" class="form-label fw-bold">Nitrogen (N)</label>
                        <input type="number" id="N" class="form-control" placeholder="e.g. 90" step="1" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="P" class="form-label fw-bold">Phosphorus (P)</label>
                        <input type="number" id="P" class="form-control" placeholder="e.g. 40" step="1" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="K" class="form-label fw-bold">Potassium (K)</label>
                        <input type="number" id="K" class="form-control" placeholder="e.g. 45" step="1" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label for="ph" class="form-label fw-bold">Soil pH</label>
                        <input type="number" id="ph" class="form-control" placeholder="e.g. 6.5" step="0.1" min="0" max="14" required>
                    </div>

                    <hr>

                    <!-- Weather info (auto-fetched) -->
                    <h5 class="text-primary mb-3"><i class="fas fa-cloud-sun me-2"></i>Live Weather (Balayan, Batangas)</h5>

                    <div class="row">
                        <div class="col-4 mb-3">
                        <label class="form-label fw-bold">Temp (°C)</label>
                        <input type="text" id="temperature" class="form-control bg-light" readonly>
                        </div>
                        <div class="col-4 mb-3">
                        <label class="form-label fw-bold">Humidity (%)</label>
                        <input type="text" id="humidity" class="form-control bg-light" readonly>
                        </div>
                        <div class="col-4 mb-3">
                        <label class="form-label fw-bold">Rainfall (mm)</label>
                        <input type="text" id="rainfall" class="form-control bg-light" readonly>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Location</label>
                        <input type="text" id="city" class="form-control bg-light" value="Balayan, Batangas" readonly>
                    </div>

                    <button id="runSmartCareBtn" class="btn btn-success w-100 py-2">
                        <i class="fas fa-brain me-2"></i>Run Smart Care AI
                    </button>

                    <div id="smartCareResult" class="alert mt-3 text-center fw-bold" style="display:none;"></div>
                    </div>
                </div>
                </div>

                <!-- 🟡 Output Section -->
                <div class="col-md-6">
                <div class="card shadow-sm harvest-card h-100">
                    <div class="card-body">
                    <h4 class="mb-3"><i class="fas fa-lightbulb text-warning me-2"></i>AI Recommendation</h4>
                    <div id="smartCareOutput" class="p-3 border rounded bg-light" style="min-height: 220px;">
                        <p class="text-muted mb-0">Fill in the soil details and run Smart Care AI to get the recommendation.</p>
                    </div>
                    </div>
                </div>
                </div>

            </div>
            </div>








        </div>
    </div>

    <!-- Activity Scheduling Modal -->
    <div class="modal fade" id="activityModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content activity-modal">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-calendar-plus me-2"></i>Add Recommendation to Activity Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="activityForm">
                        <input type="hidden" id="activityCrop" name="crop">
                        <input type="hidden" id="activityTask" name="task">
                        <div class="mb-3">
                            <label for="activityDate" class="form-label">Scheduled Date</label>
                            <input type="date" class="form-control" id="activityDate" name="date" value="" required>
                        </div>
                        <div class="mb-3">
                            <label for="activityNotes" class="form-label">Additional Notes</label>
                            <input type="text" class="form-control" id="activityNotes" name="notes" placeholder="e.g., Apply to all tomato plants in row 3">
                        </div>
                        <div class="alert alert-info">
                            <small><strong>Pre-filled Task:</strong> <span id="previewTask"></span></small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="saveActivityBtn">
                        <i class="fas fa-save me-1"></i> Save to Activities
                    </button>


                </div>
            </div>
        </div>
    </div>



<script>




// with eather, soil logs reco 
function analyticsInit() {
  console.log("✅ analyticsInit() initialized properly from analytics.js");

  const diseaseForm = document.getElementById("diseaseForm");
  if (!diseaseForm) {
    console.warn("⚠️ diseaseForm not found — page not ready yet");
    return;
  }

  diseaseForm.addEventListener("submit", async function (e) {
    e.preventDefault();

    const fileInput = document.getElementById("image");
    const file = fileInput.files[0];
    const resultDiv = document.getElementById("aiResultAlert");
    const preview = document.getElementById("preview");

    if (!file) {
      alert("Please upload an image first.");
      return;
    }

    // Show loading state
    resultDiv.style.display = "block";
    resultDiv.className = "alert alert-info";
    resultDiv.innerHTML = "🧠 Analyzing image... please wait...";
    preview.style.display = "none";

    const formData = new FormData();
    formData.append("file", file);

    try {
      const response = await fetch("backend/detectDisease.php", {
        method: "POST",
        body: formData
      });

      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

      const data = await response.json();
      console.log("✅ AI Detection response:", data);

      // Show image preview
      preview.src = URL.createObjectURL(file);
      preview.style.display = "block";

      // Display results
      if (data.predictions && data.predictions.length > 0) {
        const disease = data.predictions[0].class;
        const confidence = (data.predictions[0].confidence * 100).toFixed(2);

        // Update disease result section
        document.getElementById("diseaseTitle").innerHTML = `<i class="fas fa-bug me-2"></i>${disease}`;
        document.getElementById("confidenceSection").style.display = "block";
        document.getElementById("confidencePct").textContent = confidence;
        document.getElementById("confidenceFill").style.width = `${confidence}%`;

        resultDiv.className = "alert alert-success";
        resultDiv.innerHTML = `
          <strong>Disease:</strong> ${disease}<br>
          <strong>Confidence:</strong> ${confidence}%`;

        // ✅ Fetch Recommendations
        fetch(`/backend/api/diseaseReco.php?disease=${encodeURIComponent(disease)}`)
          .then(res => res.json())
          .then(info => {
            const recommendationsDiv = document.getElementById("recommendationsList");
            if (info.success) {
              let html = `
                <div class="mb-3">
                  <h6 class="text-danger"><i class="fas fa-syringe me-2"></i>Treatments</h6>
                  <ul>${info.treatments.map(t => `<li>${t}</li>`).join("")}</ul>
                </div>
                <div>
                  <h6 class="text-success"><i class="fas fa-shield-alt me-2"></i>Prevention</h6>
                  <ul>${info.prevention.map(p => `<li>${p}</li>`).join("")}</ul>
                </div>
              `;
              recommendationsDiv.innerHTML = html;
            } else {
              recommendationsDiv.innerHTML = `<p class="text-muted">No specific recommendations found for this disease.</p>`;
            }
          })
          .catch(err => {
            console.error("⚠️ Error loading recommendations:", err);
            document.getElementById("recommendationsList").innerHTML = `<p class="text-danger">Error loading recommendations.</p>`;
          });

      } else {
        resultDiv.className = "alert alert-warning";
        resultDiv.innerHTML = "⚠️ No disease detected or unclear image.";
      }

    } catch (err) {
      console.error("🔥 Error:", err);
      resultDiv.className = "alert alert-danger";
      resultDiv.innerHTML = "❌ Error analyzing image. Please try again.";
    }
  });
}


  //js for tab 2 - smart care recommender
  document.addEventListener("DOMContentLoaded", () => {
    const API_KEY = "4cac84b627ac52ac5a76e3b3e2349132";
    const lat = 13.9449;
    const lon = 120.7517;
    const city = "Balayan, Batangas";

    const tempEl = document.getElementById("temperature");
    const humidityEl = document.getElementById("humidity");
    const rainEl = document.getElementById("rainfall");
    const btn = document.getElementById("runSmartCareBtn");
    const resultBox = document.getElementById("smartCareResult");
    const outputBox = document.getElementById("smartCareOutput");

    // 🌦 Fetch live weather from OpenWeather
    async function loadWeather() {
      try {
        const res = await fetch(`https://api.openweathermap.org/data/2.5/weather?lat=${lat}&lon=${lon}&appid=${API_KEY}&units=metric`);
        if (!res.ok) throw new Error('Weather API returned ' + res.status);
        const data = await res.json();

        const temperature = data.main?.temp || 'N/A';
        const humidity = data.main?.humidity || 'N/A';
        const rainfall = data.rain ? (data.rain["1h"] || data.rain["3h"] || 0) : 0;

        if (tempEl) tempEl.value = temperature;
        if (humidityEl) humidityEl.value = humidity;
        if (rainEl) rainEl.value = typeof rainfall === 'number' ? rainfall.toFixed(2) : rainfall;

        console.log("✅ Weather loaded:", { temperature, humidity, rainfall });
      } catch (err) {
        console.error("❌ Weather fetch failed:", err);
        if (tempEl) tempEl.value = "N/A";
        if (humidityEl) humidityEl.value = "N/A";
        if (rainEl) rainEl.value = "0";
      }
    }

    loadWeather(); // auto-load weather data on page load

    // 🌾 Run Smart Care AI
    if (btn) {
      btn.addEventListener("click", async () => {
        const N = parseFloat(document.getElementById("N")?.value);
        const P = parseFloat(document.getElementById("P")?.value);
        const K = parseFloat(document.getElementById("K")?.value);
        const ph = parseFloat(document.getElementById("ph")?.value);

        if (isNaN(N) || isNaN(P) || isNaN(K) || isNaN(ph)) {
          if (resultBox) {
            resultBox.style.display = "block";
            resultBox.className = "alert alert-danger text-center";
            resultBox.textContent = "⚠️ Please fill in all soil values (N, P, K, pH).";
          }
          return;
        }

        if (resultBox) {
          resultBox.style.display = "block";
          resultBox.className = "alert alert-info text-center";
          resultBox.textContent = "🔄 Analyzing soil and weather conditions... Please wait";
        }

        if (outputBox) {
          outputBox.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-success" role="status"><span class="visually-hidden">Loading...</span></div></div>';
        }

        try {
          const base = window.location.origin;
          const response = await fetch(BASE + "/backend/api/analytics/recommendCrop.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ N, P, K, ph, city: "Balayan" })
          });

          const text = await response.text();
          console.log("Backend raw response:", text);

          if (!text || text.trim().length === 0) {
            throw new Error('Empty response from backend');
          }

          let data;
          try {
            data = JSON.parse(text);
          } catch (e) {
            console.error("Invalid JSON:", text);
            throw new Error('Backend returned invalid JSON. See console for raw response.');
          }

          if (data.error) {
            throw new Error(data.error);
          }

          if (!response.ok) {
            throw new Error(data.message || 'Backend returned HTTP ' + response.status);
          }

          // Success - display results
          const crop = data.recommended_crop;
          const probs = data.probabilities || {};
          const featureInfo = data.features || {};

          if (resultBox) {
            resultBox.className = "alert alert-success text-center fw-bold";
            resultBox.textContent = `✅ Recommended Crop: ${crop.toUpperCase()}`;
          }

          // Build probability list with progress bars
          let probHTML = '<h6 class="text-secondary mb-3"><i class="fas fa-chart-bar me-2"></i>Prediction Confidence:</h6>';
          const sortedProbs = Object.entries(probs).sort((a, b) => b[1] - a[1]);
          
          probHTML += '<div class="mb-3">';
          sortedProbs.forEach(([label, val]) => {
            const percent = (val * 100).toFixed(2);
            const barWidth = Math.max(percent, 5); // minimum 5% for visibility
            const barColor = percent > 50 ? 'bg-success' : (percent > 20 ? 'bg-warning' : 'bg-secondary');
            
            probHTML += `
              <div class="mb-2">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <strong class="small">${label}</strong>
                  <span class="badge ${barColor}">${percent}%</span>
                </div>
                <div class="progress" style="height: 18px;">
                  <div class="progress-bar ${barColor}" role="progressbar" style="width: ${barWidth}%" 
                       aria-valuenow="${percent}" aria-valuemin="0" aria-valuemax="100">
                  </div>
                </div>
              </div>
            `;
          });
          probHTML += '</div>';

          if (outputBox) {
            outputBox.innerHTML = `
              <div class="alert alert-success border-success">
                <h5 class="mb-0">
                  <i class="fas fa-check-circle me-2"></i>Recommended: <strong>${crop.toUpperCase()}</strong>
                </h5>
              </div>
              <hr>
              ${probHTML}
              <hr>
              <h6 class="text-secondary mt-3">
                <i class="fas fa-cloud-sun me-2"></i>Environmental Data (${city})
              </h6>
              <div class="row g-2 small">
                <div class="col-6">
                  <div class="p-2 bg-light rounded">
                    <strong>🌡️ Temperature:</strong><br>
                    ${featureInfo.temperature || tempEl.value} °C
                  </div>
                </div>
                <div class="col-6">
                  <div class="p-2 bg-light rounded">
                    <strong>💧 Humidity:</strong><br>
                    ${featureInfo.humidity || humidityEl.value}%
                  </div>
                </div>
                <div class="col-6">
                  <div class="p-2 bg-light rounded">
                    <strong>☔ Rainfall:</strong><br>
                    ${featureInfo.rainfall || rainEl.value} mm
                  </div>
                </div>
                <div class="col-6">
                  <div class="p-2 bg-light rounded">
                    <strong>🧪 Soil pH:</strong><br>
                    ${featureInfo.ph || ph}
                  </div>
                </div>
              </div>
              <div class="mt-3 p-2 bg-light rounded small">
                <strong>🌾 N-P-K Values:</strong> 
                ${featureInfo.N || N} - ${featureInfo.P || P} - ${featureInfo.K || K}
              </div>
            `;
          }

          console.log("✅ Smart Care prediction successful:", data);

        } catch (err) {
          console.error("❌ Smart Care error:", err);
          
          if (resultBox) {
            resultBox.className = "alert alert-danger text-center";
            resultBox.textContent = "❌ Error: " + err.message;
          }
          
          if (outputBox) {
            outputBox.innerHTML = `
              <div class="alert alert-danger">
                <h6 class="mb-2"><i class="fas fa-exclamation-triangle me-2"></i>Processing Error</h6>
                <p class="mb-2">${err.message}</p>
                <hr>
                <small class="text-muted">
                  💡 <strong>Troubleshooting:</strong><br>
                  • Check browser console (F12) for detailed logs<br>
                  • Verify Python model is trained (run train_crop_model.py)<br>
                  • Ensure OpenWeather API key is set in server environment<br>
                  • Check backend/api/analytics/debug_php.log for PHP errors
                </small>
              </div>
            `;
          }
        }
      });
    }

    console.log("✅ Smart Care AI integration initialized");
  });






</script>

