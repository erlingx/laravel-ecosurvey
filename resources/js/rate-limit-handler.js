/**
 * Rate Limit Modal Handler
 * Intercepts 429 responses and displays them as a modal overlay
 * allowing sidebar navigation to remain functional
 */

export function initRateLimitHandler() {
    // Intercept fetch requests
    const originalFetch = window.fetch;

    window.fetch = function(...args) {
        return originalFetch.apply(this, args)
            .then(response => {
                // If rate limited, show modal instead of replacing page
                if (response.status === 429) {
                    const request = args[0];
                    const isJsonRequest = args[1]?.headers?.['Content-Type']?.includes('application/json') ||
                                        args[1]?.headers?.['Accept']?.includes('application/json');

                    // Let JSON requests pass through (they get the JSON response)
                    if (isJsonRequest || (typeof request === 'string' && request.includes('/data-points'))) {
                        return response;
                    }

                    // For page requests, show modal overlay
                    response.clone().text().then(html => {
                        showRateLimitModal(html);
                    });
                }
                return response;
            })
            .catch(error => {
                throw error;
            });
    };

    // Also handle Livewire navigation responses
    if (window.Livewire) {
        window.addEventListener('livewire:navigating', function(e) {
            if (e.detail.response?.status === 429) {
                // Prevent navigation
                e.preventDefault();

                // Extract retry_after from response
                e.detail.response.json().then(data => {
                    const retryAfter = data.retry_after || 60;
                    showRateLimitModalContent(retryAfter, data.message);
                });
            }
        });
    }
}

/**
 * Display rate limit modal over current page
 */
function showRateLimitModal(html) {
    // Extract the modal content from the HTML response
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, 'text/html');
    const modal = doc.querySelector('[data-rate-limit-modal]') ||
                  doc.body.innerHTML; // Fallback to entire body

    // Remove any existing modal
    removeExistingModal();

    // Create container
    const container = document.createElement('div');
    container.id = 'rate-limit-modal-container';
    container.innerHTML = modal;

    // Extract and run any scripts
    const scripts = container.querySelectorAll('script');
    scripts.forEach(script => {
        const newScript = document.createElement('script');
        newScript.textContent = script.textContent;
        document.body.appendChild(newScript);
    });

    document.body.appendChild(container);
}

/**
 * Display rate limit modal with custom content
 */
function showRateLimitModalContent(retryAfter, message = 'Too many requests. Please slow down.') {
    removeExistingModal();

    // Create the overlay container
    const overlay = document.createElement('div');
    overlay.id = 'rate-limit-modal-container';
    overlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 40;
        pointer-events: none;
        padding: 1rem;
    `;

    // Create the semi-transparent backdrop (non-interactive)
    const backdrop = document.createElement('div');
    backdrop.style.cssText = `
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        pointer-events: none;
        z-index: -1;
    `;

    // Create the modal card (interactive)
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: relative;
        max-width: 28rem;
        width: 100%;
        background: white;
        border-radius: 0.5rem;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        padding: 2rem;
        pointer-events: auto;
        z-index: 41;
    `;

    // Add dark mode support
    if (document.documentElement.classList.contains('dark')) {
        modal.style.backgroundColor = '#1f2937';
    }

    modal.innerHTML = `
        <!-- Error Icon -->
        <div style="display: flex; justify-content: center; margin-bottom: 1.5rem;">
            <div style="border-radius: 9999px; background: rgba(254, 226, 226, 0.5); padding: 1rem; display: flex; align-items: center; justify-content: center;">
                <svg style="width: 3rem; height: 3rem; color: #dc2626;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        <!-- Error Message -->
        <h1 style="font-size: 1.5rem; font-weight: 700; text-align: center; color: #111827; margin-bottom: 0.5rem;">
            Too Many Requests
        </h1>
        <p style="text-align: center; color: #4b5563; margin-bottom: 1.5rem;">
            ${message}
        </p>

        <!-- Retry Information -->
        <div style="background: rgba(219, 234, 254, 0.5); border: 1px solid #bfdbfe; border-radius: 0.5rem; padding: 1rem; margin-bottom: 1.5rem;">
            <p style="font-size: 0.875rem; color: #1e40af;">
                <strong>Please wait <span id="countdownSeconds">${retryAfter}</span> seconds</strong> before trying again.
            </p>
            <p style="font-size: 0.75rem; color: #1e3a8a; margin-top: 0.5rem;">
                Your request limit will reset automatically.
            </p>
        </div>

        <!-- Rate Limit Info -->
        <div style="background: rgba(249, 250, 251, 0.5); border-radius: 0.5rem; padding: 1rem; margin-bottom: 1.5rem;">
            <h3 style="font-size: 0.875rem; font-weight: 600; color: #111827; margin-bottom: 0.5rem;">
                Rate Limits by Plan:
            </h3>
            <ul style="font-size: 0.875rem; color: #4b5563; list-style: none; padding: 0; margin: 0; line-height: 1.5;">
                <li>📊 Free Tier: 60 requests/hour</li>
                <li>📈 Pro Tier: 300 requests/hour</li>
                <li>🚀 Enterprise: 1000 requests/hour</li>
            </ul>
        </div>

        <!-- Actions -->
        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
            <button
                id="retryBtn"
                style="width: 100%; background: #2563eb; color: white; font-weight: 600; padding: 0.5rem 1rem; border-radius: 0.5rem; border: none; cursor: not-allowed; opacity: 0.5; transition: all 0.2s;"
                disabled
            >
                <span id="retryText">Retry in ${retryAfter} seconds</span>
            </button>
            <a
                href="/"
                style="display: block; text-align: center; background: #d1d5db; color: #111827; font-weight: 600; padding: 0.5rem 1rem; border-radius: 0.5rem; text-decoration: none; transition: all 0.2s;"
            >
                Back to Home
            </a>
        </div>

        <!-- Status Code -->
        <p style="text-align: center; color: #9ca3af; font-size: 0.75rem; margin-top: 1.5rem;">
            HTTP Status: 429 Too Many Requests
        </p>
    `;

    overlay.appendChild(backdrop);
    overlay.appendChild(modal);
    document.body.appendChild(overlay);

    // Start countdown
    startCountdown(retryAfter);
}

/**
 * Remove any existing rate limit modal
 */
function removeExistingModal() {
    const existing = document.getElementById('rate-limit-modal-container');
    if (existing) {
        existing.remove();
    }
}

/**
 * Start countdown timer
 */
function startCountdown(seconds) {
    const btn = document.getElementById('retryBtn');
    const text = document.getElementById('retryText');
    const countdownDisplay = document.getElementById('countdownSeconds');

    if (!btn || !text) return; // Modal not rendered yet

    let remaining = seconds;

    const interval = setInterval(() => {
        remaining--;
        text.textContent = `Retry in ${remaining} seconds`;
        if (countdownDisplay) countdownDisplay.textContent = remaining;

        if (remaining <= 0) {
            clearInterval(interval);
            btn.disabled = false;
            btn.textContent = 'Retry Now';
            btn.onclick = () => location.reload();
            btn.classList.remove('disabled:opacity-50', 'disabled:cursor-not-allowed');
        }
    }, 1000);
}

// Initialize on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initRateLimitHandler);
} else {
    initRateLimitHandler();
}
