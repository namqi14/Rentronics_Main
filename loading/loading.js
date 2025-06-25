function showLoading() {
    document.getElementById('loadingOverlay').style.display = 'flex';
}

function hideLoading() {
    setTimeout(() => {
        document.getElementById('loadingOverlay').style.display = 'none';
    }, 1500);
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.sidebar-dropdown .sidebar-link').forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.getAttribute('href') && this.getAttribute('href') !== '#') {
                showLoading();
                setTimeout(hideLoading, 30000);
            }
        });
    });
});