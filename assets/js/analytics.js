function analyticsInit() {
  const form = document.getElementById("diseaseForm");
  const fileInput = document.getElementById("image");

  if (!form || !fileInput) {
    console.warn("⚠️ Missing required elements (diseaseForm or image). Cannot initialize analytics.");
    return;
  }

  let resultDiv = document.getElementById("result") || document.getElementById("aiResultAlert");
  if (!resultDiv) {
    resultDiv = document.createElement("div");
    resultDiv.id = "aiResultAlert";
    resultDiv.className = "alert mt-3";
    resultDiv.style.display = "none";
    form.parentNode.appendChild(resultDiv);
  }

  console.log("✅ analyticsInit initialized...");

  const loadImageBase64 = (file) => {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.readAsDataURL(file);
      reader.onload = () => resolve(reader.result.split(",")[1]);
      reader.onerror = (error) => reject(error);
    });
  };

  form.addEventListener("submit", async (e) => {
    e.preventDefault();

    const file = fileInput.files[0];
    if (!file) {
      alert("Please upload an image first.");
      return;
    }

    resultDiv.style.display = "block";
    resultDiv.className = "alert alert-info";
    resultDiv.textContent = "🧠 Analyzing image... please wait...";

    try {
      const imageBase64 = await loadImageBase64(file);

      const response = await fetch(
        "https://serverless.roboflow.com/plants-diseases-detection-and-classification/12?api_key=lty0TJAy6einUxkz4XEd",
        {
          method: "POST",
          body: imageBase64,
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
        }
      );

      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
      const data = await response.json();

      if (!data.predictions || data.predictions.length === 0) {
        resultDiv.className = "alert alert-warning";
        resultDiv.textContent = "⚠️ No disease detected.";
        return;
      }

      const top = data.predictions[0];
      const diseaseName = top.class;
      const confidence = (top.confidence * 100).toFixed(2);

      // ✅ Fetch disease info (recommendations + causes)
      const infoResponse = await fetch(`/backend/api/diseaseReco.php?disease=${encodeURIComponent(diseaseName)}`);
      const infoData = await infoResponse.json();

      let html = `
        <h5>🩺 Disease: <strong>${diseaseName}</strong></h5>
        <p><strong>Confidence:</strong> ${confidence}%</p>
      `;

      if (infoData.success) {
        html += `
          <h6 class="mt-3 text-success">Recommended Treatments:</h6>
          <ul>${infoData.treatments.map(t => `<li>${t}</li>`).join("")}</ul>
          <h6 class="mt-3 text-primary">Prevention Tips:</h6>
          <ul>${infoData.prevention.map(p => `<li>${p}</li>`).join("")}</ul>
        `;
      } else {
        html += `<p class="text-muted">No detailed info available for this disease.</p>`;
      }

      resultDiv.className = "alert alert-success";
      resultDiv.innerHTML = html;

    } catch (err) {
      console.error("🔥 Request failed:", err);
      resultDiv.className = "alert alert-danger";
      resultDiv.textContent = "❌ Failed to analyze image. Check network or console.";
    }
  });
}
