document.addEventListener('DOMContentLoaded', function() {
    // Get remaining time from PHP session (passed via data attribute)
    let timeRemaining = parseInt(document.getElementById('session-timer-container').getAttribute('data-remaining-time'));
    const timerDisplay = document.getElementById('session-timer');
    const timerContainer = document.getElementById('session-timer-container');
    
    function updateTimer() {
        if (timeRemaining <= 0) {
            // Time's up - redirect to login page
            window.location.href = 'index.php?timeout=1';
            return;
        }
        
        // Calculate minutes and seconds
        const minutes = Math.floor(timeRemaining / 60);
        const seconds = timeRemaining % 60;
        
        // Display time in MM:SS format
        timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        
        // Show warning when less than 2 minutes remain
        if (timeRemaining <= 120) {
            timerContainer.classList.add('session-expiring');
        }
        
        // Decrease the timer
        timeRemaining--;
    }
    
    // Update timer every second
    updateTimer();
    const intervalId = setInterval(updateTimer, 1000);
    
    // Reset timer on user activity
    const resetTimer = function() {
        // Send AJAX request to refresh session
        fetch('refresh_session.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    timeRemaining = 900; // Reset to 15 minutes
                    timerContainer.classList.remove('session-expiring');
                }
            });
    };
    
    // Monitor user activity
    ['click', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(function(event) {
        document.addEventListener(event, resetTimer, false);
    });
});