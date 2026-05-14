/**
 * Books & Interests Popup Widget
 * Amin Adineh Website
 */

(function() {
    'use strict';

    // Books and interests data
    const BOOKS_DATA = {
        books: [
            {
                title: 'Warped Passages',
                author: 'Lisa Randall',
                description: 'Unraveling the Mysteries of the Universe\'s Hidden Dimensions',
                image: 'img/books/Lisa.jpg',
                category: 'Physics'
            },
            {
                title: 'Core Energy: The Power of Renewables',
                author: 'Amin Adineh',
                description: 'A Comprehensive Guide to the Renewable Energy Industry',
                image: null,
                category: 'Energy'
            },
            {
                title: 'The Elegant Universe',
                author: 'Brian Greene',
                description: 'Superstrings, Hidden Dimensions, and the Quest for the Ultimate Theory',
                image: null,
                category: 'Physics'
            }
        ],
        interests: [
            {
                title: 'Quantum Computing',
                description: 'Exploring quantum algorithms and qubit technologies',
                icon: 'fas fa-atom',
                category: 'Technology'
            },
            {
                title: 'Robotics & AI',
                description: 'Humanoid robots and conversational AI systems',
                icon: 'fas fa-robot',
                category: 'Technology'
            },
            {
                title: 'Renewable Energy',
                description: 'Solar, wind, and sustainable power systems',
                icon: 'fas fa-solar-panel',
                category: 'Energy'
            },
            {
                title: 'Aviation',
                description: 'Private aviation and helicopter technology',
                icon: 'fas fa-helicopter',
                category: 'Aviation',
                link: 'https://www.hillhelicopters.com/'
            }
        ],
        concepts: [
            {
                title: 'Hill Helicopters HX50',
                description: 'Revolutionary personal helicopter design',
                icon: 'fas fa-helicopter',
                link: 'https://www.hillhelicopters.com/',
                category: 'Aviation'
            },
            {
                title: 'Cryogenic Systems',
                description: 'Ultra-low temperature measurement systems',
                icon: 'fas fa-thermometer-empty',
                category: 'Research'
            }
        ]
    };

    let currentCategory = 'all';

    // Create merged circular button (called first)
    function createMergedButton() {
        // Check if merged button already exists
        let mergedTrigger = document.getElementById('merged-popup-trigger');
        
        if (!mergedTrigger) {
            mergedTrigger = document.createElement('div');
            mergedTrigger.id = 'merged-popup-trigger';
            mergedTrigger.className = 'merged-popup-trigger';
            
            // Get notification count from LinkedIn activities if available
            const notificationCount = window.LINKEDIN_ACTIVITY_COUNT || 4;
            
            mergedTrigger.innerHTML = `
                <div class="merged-button-inner">
                    <div class="merged-icon-top">
                        <i class="fab fa-linkedin-in"></i>
                    </div>
                    <div class="merged-icon-bottom">
                        <i class="fas fa-book-reader"></i>
                    </div>
                    <span class="notification-badge" id="merged-notification-badge">${notificationCount}</span>
                </div>
            `;
            document.body.appendChild(mergedTrigger);
            
            // Add click handler for merged button
            mergedTrigger.addEventListener('click', function(e) {
                // Check which side was clicked (left = LinkedIn, right = Books)
                const rect = mergedTrigger.getBoundingClientRect();
                const clickX = e.clientX - rect.left;
                const centerX = rect.width / 2;
                
                // Check if clicked on specific icon
                const linkedinIcon = mergedTrigger.querySelector('.merged-icon-top');
                const booksIcon = mergedTrigger.querySelector('.merged-icon-bottom');
                
                if (linkedinIcon && linkedinIcon.contains(e.target)) {
                    // Clicked directly on LinkedIn icon
                    if (window.toggleLinkedInPopup) {
                        window.toggleLinkedInPopup();
                    }
                } else if (booksIcon && booksIcon.contains(e.target)) {
                    // Clicked directly on Books icon
                    togglePopup();
                } else if (clickX < centerX) {
                    // Left half - LinkedIn
                    if (window.toggleLinkedInPopup) {
                        window.toggleLinkedInPopup();
                    }
                } else {
                    // Right half - Books
                    togglePopup();
                }
            });
        }
        
        return mergedTrigger;
    }
    
    // Create popup HTML
    function createPopupHTML() {
        
        // Create books popup container
        let container = document.getElementById('books-popup-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'books-popup-container';
            document.body.appendChild(container);
        }
        
        container.innerHTML = `
            <!-- Popup Panel -->
            <div class="books-popup" id="books-popup">
                <div class="books-popup-header">
                    <h3><i class="fas fa-book-open"></i> Books & Interests</h3>
                    <button class="books-popup-close" id="books-close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="books-popup-content" id="books-content">
                    <div class="books-tabs" id="books-tabs">
                        <button class="books-tab active" data-category="all">All</button>
                        <button class="books-tab" data-category="books">Books</button>
                        <button class="books-tab" data-category="interests">Interests</button>
                        <button class="books-tab" data-category="concepts">Concepts</button>
                    </div>
                    <div id="books-list"></div>
                </div>

                <div class="books-popup-footer">
                    <p>✨ Explore my reading list & passions</p>
                </div>
            </div>
        `;

        return container;
    }

    // Render items based on category
    function renderItems(category) {
        const listContainer = document.getElementById('books-list');
        if (!listContainer) return;

        let items = [];

        if (category === 'all' || category === 'books') {
            BOOKS_DATA.books.forEach(book => {
                items.push({
                    ...book,
                    type: 'book'
                });
            });
        }

        if (category === 'all' || category === 'interests') {
            BOOKS_DATA.interests.forEach(interest => {
                items.push({
                    ...interest,
                    type: 'interest'
                });
            });
        }

        if (category === 'all' || category === 'concepts') {
            BOOKS_DATA.concepts.forEach(concept => {
                items.push({
                    ...concept,
                    type: 'concept'
                });
            });
        }

        let html = '';
        items.forEach(item => {
            const isLink = item.link ? `href="${item.link}" target="_blank" rel="noopener"` : '';
            const tag = item.link ? 'a' : 'div';
            
            html += `
                <${tag} class="books-item" ${isLink}>
                    <div class="books-item-img">
                        ${item.image 
                            ? `<img src="${item.image}" alt="${escapeHtml(item.title)}">`
                            : `<i class="${item.icon || 'fas fa-book'}"></i>`
                        }
                    </div>
                    <div class="books-item-text">
                        <h4>${escapeHtml(item.title)}</h4>
                        ${item.author ? `<p>by ${escapeHtml(item.author)}</p>` : ''}
                        <p>${escapeHtml(item.description)}</p>
                        <span class="books-item-category">${escapeHtml(item.category || item.type)}</span>
                    </div>
                </${tag}>
            `;
        });

        listContainer.innerHTML = html || '<p style="text-align:center;color:#888;padding:20px;">No items found</p>';
    }

    // Escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Toggle popup
    function togglePopup() {
        const popup = document.getElementById('books-popup');
        const linkedinPopup = document.getElementById('linkedin-popup');
        
        if (popup) {
            // Close LinkedIn popup if open
            if (linkedinPopup && linkedinPopup.classList.contains('active')) {
                linkedinPopup.classList.remove('active');
            }
            
            popup.classList.toggle('active');
        }
    }

    // Close popup
    function closePopup() {
        const popup = document.getElementById('books-popup');
        if (popup) {
            popup.classList.remove('active');
        }
    }

    // Handle tab clicks
    function handleTabClick(e) {
        if (!e.target.classList.contains('books-tab')) return;
        
        // Update active tab
        document.querySelectorAll('.books-tab').forEach(tab => tab.classList.remove('active'));
        e.target.classList.add('active');
        
        // Render items for category
        currentCategory = e.target.dataset.category;
        renderItems(currentCategory);
    }

    // Handle outside click
    function handleOutsideClick(e) {
        const popup = document.getElementById('books-popup');
        const mergedTrigger = document.getElementById('merged-popup-trigger');
        
        if (popup && popup.classList.contains('active')) {
            if (!popup.contains(e.target) && (!mergedTrigger || !mergedTrigger.contains(e.target))) {
                closePopup();
            }
        }
    }

    // Initialize
    function init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setup);
        } else {
            setup();
        }
    }

    function setup() {
        // Create merged button first
        createMergedButton();
        
        // Then create popup
        createPopupHTML();
        renderItems('all');

        // Event listeners
        const closeBtn = document.getElementById('books-close');
        const tabs = document.getElementById('books-tabs');

        if (closeBtn) closeBtn.addEventListener('click', closePopup);
        if (tabs) tabs.addEventListener('click', handleTabClick);

        document.addEventListener('click', handleOutsideClick);
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closePopup();
        });
    }

    init();
})();

