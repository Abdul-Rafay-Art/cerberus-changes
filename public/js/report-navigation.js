document.addEventListener('DOMContentLoaded', () => {
    const backButton = document.getElementById('report-back-button');

    if (!backButton) {
        return;
    }

    backButton.addEventListener('click', (event) => {
        if (window.history.length > 1) {
            event.preventDefault();
            window.history.back();
        }
    });
});
