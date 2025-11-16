<!-- ===== Bottom Navigation Bar ===== -->
<nav class="bottom-nav">
    <a href="index.php" class="nav-item" data-page="shots">
        <span class="nav-icon">🎬</span>
        <span class="nav-label">Shots</span>
    </a>
    <a href="movies.php" class="nav-item" data-page="movies">
        <span class="nav-icon">🎥</span>
        <span class="nav-label">Movies</span>
    </a>
    <a href="tv-series.php" class="nav-item" data-page="tv-series">
        <span class="nav-icon">📺</span>
        <span class="nav-label">TV Series</span>
    </a>
    <a href="collections.php" class="nav-item" data-page="collections">
        <span class="nav-icon">📚</span>
        <span class="nav-label">Collection</span>
    </a>
</nav>

<style>
/* ===== Bottom Navigation Bar (TikTok-style) ===== */
.bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    display: flex;
    justify-content: space-around;
    align-items: center;
    background: rgba(15, 16, 25, 0.95);
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    backdrop-filter: blur(14px) saturate(160%);
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.5);
    padding: 10px 0 calc(10px + env(safe-area-inset-bottom));
    z-index: 200;
}

.bottom-nav .nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 8px 16px;
    border-radius: 12px;
    transition: all 0.25s ease;
    min-width: 70px;
    text-align: center;
}

.bottom-nav .nav-icon {
    font-size: 24px;
    transition: transform 0.25s ease;
}

.bottom-nav .nav-label {
    font-size: 11px;
    font-weight: 600;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: 0.3px;
    transition: color 0.25s ease;
}

.bottom-nav .nav-item:hover {
    background: color-mix(in oklab, var(--primary) 10%, transparent);
}

.bottom-nav .nav-item:hover .nav-icon {
    transform: scale(1.1);
}

.bottom-nav .nav-item:hover .nav-label {
    color: var(--text);
}

.bottom-nav .nav-item.active {
    background: linear-gradient(135deg, rgba(42, 108, 255, 0.25), rgba(255, 51, 102, 0.2));
    border: 1px solid rgba(42, 108, 255, 0.3);
    box-shadow: 0 0 12px rgba(42, 108, 255, 0.25);
}

.bottom-nav .nav-item.active .nav-label {
    color: #fff;
}

.bottom-nav .nav-item.active .nav-icon {
    transform: scale(1.15);
}

/* Add bottom padding to body to prevent content from being hidden */
body {
    padding-bottom: 80px;
}

/* Responsive adjustments */
@media (min-width: 768px) {
    .bottom-nav {
        display: none; /* Hide on larger screens if desired, or adjust styling */
    }
    
    body {
        padding-bottom: 0;
    }
}

@media (max-width: 767px) {
    .bottom-nav .nav-item {
        padding: 6px 12px;
        min-width: 60px;
    }
    
    .bottom-nav .nav-icon {
        font-size: 22px;
    }
    
    .bottom-nav .nav-label {
        font-size: 10px;
    }
}
</style>

<script>
// ===== Bottom Navigation Active State Controller =====
(() => {
    document.addEventListener("DOMContentLoaded", () => {
        const navItems = document.querySelectorAll(".bottom-nav .nav-item");
        const currentPage = window.location.pathname.split("/").pop() || "index.php";
        
        navItems.forEach(item => {
            const page = item.getAttribute("data-page");
            
            // Match current page to nav item
            if (
                (page === "shots" && (currentPage === "index.php" || currentPage === "")) ||
                (page === "movies" && currentPage === "movies.php") ||
                (page === "tv-series" && currentPage === "tv-series.php") ||
                (page === "collections" && currentPage === "collections.php")
            ) {
                item.classList.add("active");
            }
        });
    });
})();
</script>
