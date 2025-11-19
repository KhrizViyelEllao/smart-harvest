document.addEventListener("DOMContentLoaded", () => {
    loadCropOverview();
    loadActivities();
    loadHarvestReadiness();
    loadSoilWater();
    loadPestAlerts();
    loadNotifications();
    loadWeather();
});

// Example: Crop Overview
function loadCropOverview() {
    fetch("../backend/api/getCrops.php")
        .then(res => res.json())
        .then(data => {
            console.log("Crops:", data);
            let container = document.getElementById("cropOverview");
            container.innerHTML = "";

            if (data.length === 0) {
                container.innerHTML = "<p>No crops found.</p>";
                return;
            }

            data.forEach(crop => {
                container.innerHTML += `
                    <div class="card m-2 p-3">
                        <h5>${crop.crop_name} (${crop.variety})</h5>
                        <p>Status: <b>${crop.status}</b></p>
                        <p>Planted: ${crop.planting_date}</p>
                        <p>Expected Harvest: ${crop.expected_harvest_date}</p>
                    </div>
                `;
            });
        })
        .catch(err => console.error("Error loading crops:", err));
}

// assets/js/dashboard.js
function dashboardInit() {
    console.log("✅ dashboardInit() is running — dashboard.js loaded correctly.");

    // Example: show a small welcome or update widgets later
        const dashboardContainer = document.querySelector("#main-content");
        if (dashboardContainer) {
        dashboardContainer.innerHTML = `
            <div class="container-fluid py-4">
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm p-4">
                            <h3 class="text-success mb-3"><i class="bi bi-speedometer2 me-2"></i>Dashboard Overview</h3>
                            <p class="text-muted">Dashboard successfully loaded.</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
}

document.addEventListener('DOMContentLoaded',()=>{
  if(typeof loadActivities==='function'){ loadActivities(); }
});
