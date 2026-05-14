/**
 * LinkedIn Activity Popup & Visitor Tracker
 * Amin Adineh Website
 */

(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        linkedinProfile: 'aminadineh',
        linkedinUrl: 'https://linkedin.com/in/aminadineh',
        trackerEndpoint: 'contact_form/visitor_tracker.php',
        // Sample activities - replace with real LinkedIn API data if you have access
        activities: [
            {
                type: 'post',
                title: 'Quantum Transport Research',
                description: 'Excited to share our latest findings on magnetotransport measurements at ultra-low temperatures...',
                time: '2 hours ago',
                link: 'https://linkedin.com/in/aminadineh'
            },
            {
                type: 'article',
                title: 'AI in Scientific Research',
                description: 'How machine learning is revolutionizing data analysis in physics experiments...',
                time: '1 day ago',
                link: 'https://linkedin.com/in/aminadineh'
            },
            {
                type: 'share',
                title: 'Core Energy Book Launch',
                description: 'Thrilled to announce my new publication on renewable energy fundamentals...',
                time: '3 days ago',
                link: 'https://linkedin.com/in/aminadineh'
            },
            {
                type: 'post',
                title: 'Python Automation Scripts',
                description: 'Released 5 new automation scripts for lab data processing. Check them out!',
                time: '1 week ago',
                link: 'https://linkedin.com/in/aminadineh'
            }
        ]
    };

    // Track visitor on page load
    function trackVisitor() {
        const visitorData = {
            page: window.location.pathname,
            referrer: document.referrer || '',
            screen_width: window.screen.width,
            screen_height: window.screen.height,
            language: navigator.language || navigator.userLanguage || '',
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone || ''
        };

        fetch(CONFIG.trackerEndpoint + '?action=track', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(visitorData)
        }).catch(function(err) {
            // Silently fail - don't affect user experience
            console.log('Tracking info stored locally');
        });
    }

    // Create the popup HTML structure
    function createPopupHTML() {
        // Check if merged button already exists (created by books-popup.js)
        let popupContainer = document.getElementById('linkedin-popup-container');
        if (!popupContainer) {
            popupContainer = document.createElement('div');
            popupContainer.id = 'linkedin-popup-container';
            document.body.appendChild(popupContainer);
        }

        // Only create popup panel, not the trigger button (merged button is created by books-popup.js)
        popupContainer.innerHTML = `
            <!-- Popup Panel -->
            <div class="linkedin-popup" id="linkedin-popup">
                <div class="linkedin-popup-header">
                    <h3><i class="fab fa-linkedin"></i> Latest Activity</h3>
                    <button class="linkedin-popup-close" id="linkedin-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="linkedin-popup-content" id="linkedin-content">
                    <!-- Activities will be inserted here -->
                </div>

                <div class="linkedin-popup-footer">
                    <a href="${CONFIG.linkedinUrl}" target="_blank" rel="noopener" class="linkedin-follow-btn">
                        <i class="fab fa-linkedin"></i>
                        Connect on LinkedIn
                    </a>
                </div>
            </div>
        `;

        return popupContainer;
    }

    // Get icon class based on activity type
    function getActivityIcon(type) {
        const icons = {
            'post': 'fas fa-file-alt',
            'article': 'fas fa-newspaper',
            'share': 'fas fa-share-alt',
            'like': 'fas fa-heart'
        };
        return icons[type] || 'fas fa-comment';
    }

    // Render activities in the popup
    function renderActivities() {
        const content = document.getElementById('linkedin-content');
        if (!content) return;

        let html = '';
        CONFIG.activities.forEach(function(activity) {
            html += `
                <a href="${activity.link}" target="_blank" rel="noopener" class="linkedin-activity-item">
                    <div class="linkedin-activity-icon ${activity.type}">
                        <i class="${getActivityIcon(activity.type)}"></i>
                    </div>
                    <div class="linkedin-activity-text">
                        <h4>${escapeHtml(activity.title)}</h4>
                        <p>${escapeHtml(activity.description)}</p>
                        <span class="linkedin-activity-time">
                            <i class="far fa-clock"></i> ${escapeHtml(activity.time)}
                        </span>
                    </div>
                </a>
            `;
        });

        content.innerHTML = html;
    }

    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Toggle popup visibility
    function togglePopup() {
        const popup = document.getElementById('linkedin-popup');
        const booksPopup = document.getElementById('books-popup');
        const mergedTrigger = document.getElementById('merged-popup-trigger');
        
        if (popup) {
            // Close books popup if open
            if (booksPopup && booksPopup.classList.contains('active')) {
                booksPopup.classList.remove('active');
            }
            
            popup.classList.toggle('active');
            
            // Hide notification badge once opened
            if (mergedTrigger) {
                const badge = mergedTrigger.querySelector('.notification-badge');
                if (badge && popup.classList.contains('active')) {
                    badge.style.opacity = '0.5';
                    localStorage.setItem('linkedin_popup_seen', 'true');
                } else if (badge && !popup.classList.contains('active')) {
                    badge.style.opacity = '1';
                }
            }
        }
    }
    
    // Export toggle function for use by merged button
    window.toggleLinkedInPopup = togglePopup;

    // Close popup
    function closePopup() {
        const popup = document.getElementById('linkedin-popup');
        if (popup) {
            popup.classList.remove('active');
        }
    }

    // Handle email subscription
    function handleSubscribe(e) {
        e.preventDefault();
        
        const emailInput = document.getElementById('subscribe-email');
        const form = document.getElementById('subscribe-form');
        const subscribeDiv = document.getElementById('linkedin-subscribe');
        
        if (!emailInput || !emailInput.value) return;

        const email = emailInput.value.trim();
        
        // Basic email validation
        if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
            showSubscribeMessage(subscribeDiv, 'Please enter a valid email', 'error');
            return;
        }

        // Send to server
        fetch(CONFIG.trackerEndpoint + '?action=subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email: email })
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                showSubscribeMessage(subscribeDiv, '✓ Subscribed! Thank you!', 'success');
                emailInput.value = '';
                localStorage.setItem('linkedin_subscribed', 'true');
            } else {
                showSubscribeMessage(subscribeDiv, data.message || 'Something went wrong', 'error');
            }
        })
        .catch(function() {
            showSubscribeMessage(subscribeDiv, 'Network error. Try again!', 'error');
        });
    }

    // Show subscribe message
    function showSubscribeMessage(container, message, type) {
        const existingMsg = container.querySelector('.subscribe-message');
        if (existingMsg) existingMsg.remove();

        const msgDiv = document.createElement('p');
        msgDiv.className = 'subscribe-message';
        msgDiv.style.cssText = `
            font-size: 12px;
            text-align: center;
            margin-top: 8px;
            color: ${type === 'success' ? '#16a34a' : '#dc2626'};
            font-weight: 500;
        `;
        msgDiv.textContent = message;
        container.appendChild(msgDiv);

        // Auto-remove after 4 seconds
        setTimeout(function() {
            if (msgDiv.parentNode) {
                msgDiv.remove();
            }
        }, 4000);
    }

    // Check if user already subscribed
    function checkSubscriptionStatus() {
        if (localStorage.getItem('linkedin_subscribed') === 'true') {
            const subscribeDiv = document.getElementById('linkedin-subscribe');
            if (subscribeDiv) {
                subscribeDiv.innerHTML = '<p style="text-align: center; color: #16a34a; font-weight: 500;">✓ You\'re subscribed!</p>';
            }
        }
    }

    // Auto-show popup after delay (optional - for first-time visitors)
    function autoShowPopup() {
        if (localStorage.getItem('linkedin_popup_seen') !== 'true') {
            setTimeout(function() {
                const popup = document.getElementById('linkedin-popup');
                if (popup && !popup.classList.contains('active')) {
                    popup.classList.add('active');
                }
            }, 15000); // Show after 15 seconds
        }
    }

    // Close popup when clicking outside
    function handleOutsideClick(e) {
        const popup = document.getElementById('linkedin-popup');
        const mergedTrigger = document.getElementById('merged-popup-trigger');
        
        if (popup && popup.classList.contains('active')) {
            if (!popup.contains(e.target) && (!mergedTrigger || !mergedTrigger.contains(e.target))) {
                closePopup();
            }
        }
    }

    // Initialize everything
    function init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setup);
        } else {
            setup();
        }
    }

    function setup() {
        // Track visitor
        trackVisitor();
        
        // Export activity count for merged button
        window.LINKEDIN_ACTIVITY_COUNT = CONFIG.activities.length;

        // Create popup
        createPopupHTML();

        // Render activities
        
        // Update notification badge if merged button exists
        setTimeout(function() {
            const badge = document.getElementById('merged-notification-badge');
            if (badge) {
                badge.textContent = CONFIG.activities.length;
            }
        }, 100);
        
        renderActivities();

        // Check subscription status
        checkSubscriptionStatus();

        // Event listeners
        const closeBtn = document.getElementById('linkedin-close');
        const subscribeForm = document.getElementById('subscribe-form');

        if (closeBtn) {
            closeBtn.addEventListener('click', closePopup);
        }

        if (subscribeForm) {
            subscribeForm.addEventListener('submit', handleSubscribe);
        }

        // Close on outside click
        document.addEventListener('click', handleOutsideClick);

        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePopup();
            }
        });

        // Optional: Auto-show for first-time visitors
        // autoShowPopup();
    }

    // Start initialization
    init();

})();

