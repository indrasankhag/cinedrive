// =================== Admin Utilities ===================
document.addEventListener("DOMContentLoaded", () => {

  // Genre toggle buttons
  document.querySelectorAll(".genre-buttons button").forEach(btn => {
    btn.addEventListener("click", () => {
      btn.classList.toggle("active");
      const checkbox = document.getElementById(btn.dataset.target);
      if (checkbox) checkbox.checked = btn.classList.contains("active");
    });
  });

  // Live image preview
  const coverInput = document.querySelector('input[type="file"][name="cover"]');
  const previewImg = document.getElementById("coverPreview");
  if (coverInput && previewImg) {
    coverInput.addEventListener("change", e => {
      const file = e.target.files[0];
      if (file) previewImg.src = URL.createObjectURL(file);
    });
  }

  // Auto-hide alerts
  setTimeout(() => {
    document.querySelectorAll(".alert").forEach(a => a.remove());
  }, 4000);
});

document.addEventListener("DOMContentLoaded", () => {
  const input = document.querySelector('input[name="cover"]');
  const preview = document.getElementById("coverPreview");
  if (input && preview) {
    input.addEventListener("change", e => {
      const file = e.target.files[0];
      if (file) {
        preview.src = URL.createObjectURL(file);
        preview.style.display = "block";
      }
    });
  }
});

