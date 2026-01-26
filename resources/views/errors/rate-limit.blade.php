<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rate Limit Warning</title>
</head>
<body>
    <script>
        // Show rate limit banner directly on this page (no redirect)
        window.addEventListener('DOMContentLoaded', function() {
            const retryAfter = {{ $retryAfter }};
            const message = "{{ $message ?? 'Too many requests. Please slow down.' }}";

            showRateLimitBanner(retryAfter, message);
        });

        function showRateLimitBanner(retryAfter, message) {
            // Create warning banner
            const banner = document.createElement('div');
            banner.id = 'rate-limit-banner';
            banner.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                color: white;
                padding: 1rem;
                z-index: 99999;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                animation: slideDown 0.3s ease-out;
            `;

            banner.innerHTML = `
                <div style="flex: 1; display: flex; align-items: center; gap: 1rem;">
                    <svg style="width: 2rem; height: 2rem; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <div style="flex: 1;">
                        <div style="font-weight: 700; font-size: 1rem; margin-bottom: 0.25rem;">
                            Rate Limit Exceeded
                        </div>
                        <div style="font-size: 0.875rem; opacity: 0.95;">
                            ${message} Please wait <span id="countdown-timer" style="font-weight: 600;">${retryAfter}</span> seconds before trying again.
                        </div>
                        <div style="font-size: 0.75rem; opacity: 0.85; margin-top: 0.25rem;">
                            📊 Free: 60/hour | 📈 Pro: 300/hour | 🚀 Enterprise: 1000/hour
                        </div>
                    </div>
                </div>
                <button
                    id="dismiss-banner"
                    style="background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); color: white; padding: 0.5rem 1rem; border-radius: 0.375rem; cursor: pointer; font-weight: 600; font-size: 0.875rem; transition: all 0.2s; white-space: nowrap;"
                >
                    Dismiss
                </button>
            `;

            document.body.insertBefore(banner, document.body.firstChild);

            // Add styles for animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideDown {
                    from { transform: translateY(-100%); }
                    to { transform: translateY(0); }
                }
                body {
                    padding-top: 5rem !important;
                }
            `;
            document.head.appendChild(style);

            // Countdown timer
            let remaining = retryAfter;
            const timerElement = document.getElementById('countdown-timer');
            const interval = setInterval(() => {
                remaining--;
                if (timerElement) {
                    timerElement.textContent = remaining;
                }

                if (remaining <= 0) {
                    clearInterval(interval);
                    banner.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
                    banner.innerHTML = `
                        <div style="flex: 1; display: flex; align-items: center; gap: 1rem;">
                            <svg style="width: 2rem; height: 2rem; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <div style="flex: 1;">
                                <div style="font-weight: 700; font-size: 1rem;">
                                    Rate Limit Reset
                                </div>
                                <div style="font-size: 0.875rem; opacity: 0.95;">
                                    You can now retry your request.
                                </div>
                            </div>
                        </div>
                        <button
                            id="reload-banner"
                            style="background: white; color: #059669; border: none; padding: 0.5rem 1rem; border-radius: 0.375rem; cursor: pointer; font-weight: 600; font-size: 0.875rem;"
                        >
                            Reload Page
                        </button>
                    `;

                    document.getElementById('reload-banner').addEventListener('click', () => {
                        location.reload();
                    });
                }
            }, 1000);

            // Dismiss button
            const dismissBtn = document.getElementById('dismiss-banner');
            if (dismissBtn) {
                dismissBtn.addEventListener('click', function() {
                    banner.style.transform = 'translateY(-100%)';
                    banner.style.transition = 'transform 0.3s ease-out';
                    setTimeout(() => {
                        banner.remove();
                        document.body.style.paddingTop = '0';
                    }, 300);
                });
            }
        }
    </script>

    <!-- Show the actual page content here -->
    <div style="max-width: 64rem; margin: 0 auto; padding: 2rem;">
        <div style="background: white; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 2rem;">
            <h1 style="font-size: 1.5rem; font-weight: 700; margin-bottom: 1rem;">This Page is Currently Rate Limited</h1>
            <p style="color: #6b7280; margin-bottom: 1rem;">
                You have exceeded the request limit for this resource. The warning banner above shows when you can retry.
            </p>
            <p style="color: #6b7280;">
                While you wait, you can navigate to other pages using the sidebar.
            </p>
        </div>
    </div>
</body>
</html>
