document.addEventListener('DOMContentLoaded', () => {
    // Enhanced search data with categories and better organization
    const searchableContent = [
        // Services
        {
            keyword: 'worldwide tours',
            displayText: 'Explore Worldwide Tours',
            type: 'navigate',
            target: '#services',
            icon: 'fa-globe',
            category: 'Services',
            suggestions: ['world', 'tours', 'vacation', 'travel', 'international', 'global', 'w']
        },
        {
            keyword: 'hotel reservation',
            displayText: 'Find Hotel Reservations',
            type: 'navigate',
            target: '#services',
            icon: 'fa-hotel',
            category: 'Services',
            suggestions: ['hotel', 'reservation', 'booking', 'stay', 'accommodation', 'lodge']
        },
        {
            keyword: 'travel guides',
            displayText: 'Get Expert Travel Guides',
            type: 'navigate',
            target: '#services',
            icon: 'fa-user-friends',
            category: 'Services',
            suggestions: ['guides', 'travel', 'assistance', 'expert', 'help', 'guide']
        },

        // Destinations
        {
            keyword: 'rome italy',
            displayText: 'Rome, Italy - Ancient Wonders',
            type: 'navigate',
            target: '#destinations',
            icon: 'fa-landmark',
            category: 'Destinations',
            suggestions: ['rome', 'italy', 'colosseum', 'ancient', 'history', 'europe']
        },
        {
            keyword: 'santorini greece',
            displayText: 'Santorini, Greece - Island Paradise',
            type: 'navigate',
            target: '#destinations',
            icon: 'fa-water',
            category: 'Destinations',
            suggestions: ['santorini', 'greece', 'island', 'sunset', 'blue', 'white', 'mediterranean']
        },
        {
            keyword: 'bali indonesia',
            displayText: 'Bali, Indonesia - Tropical Paradise',
            type: 'navigate',
            target: '#destinations',
            icon: 'fa-leaf',
            category: 'Destinations',
            suggestions: ['bali', 'indonesia', 'tropical', 'beach', 'temple', 'culture', 'asia']
        },

        // Information
        {
            keyword: 'about',
            displayText: 'Learn About TourBan',
            type: 'navigate',
            target: '#about',
            icon: 'fa-info-circle',
            category: 'Information',
            suggestions: ['about', 'who we are', 'company info', 'our mission', 'team']
        },
        {
            keyword: 'contact',
            displayText: 'Contact Us',
            type: 'navigate',
            target: '#contact',
            icon: 'fa-envelope',
            category: 'Information',
            suggestions: ['contact', 'support', 'help', 'phone', 'email', 'address']
        }
    ];

    // Popular destinations for quick access
    const popularDestinations = ['Paris', 'Tokyo', 'New York', 'London', 'Dubai', 'Thailand', 'Egypt', 'Australia'];

    // Get references to HTML elements
    const searchInput = document.getElementById('searchInput');
    const suggestionsBox = document.getElementById('suggestionsBox');
    const searchButton = document.querySelector('.search-button');

    // Search state management
    let currentSearchQuery = '';
    let selectedSuggestionIndex = -1;
    let isSearching = false;
    let searchTimeout = null;
    let recentSearches = JSON.parse(localStorage.getItem('tourban_recent_searches') || '[]');

    // Enhanced search functionality
    class SearchEnhancer {
        constructor() {
            this.initializeSearch();
            this.setupEventListeners();
        }

        initializeSearch() {
            // Add search icon to input
            this.addSearchIcon();
            // Show popular destinations on focus (when input is empty)
            this.setupInitialSuggestions();
        }

        addSearchIcon() {
            const searchContainer = document.querySelector('.search-container');
            if (searchContainer && !searchContainer.querySelector('.search-icon')) {
                const iconElement = document.createElement('i');
                iconElement.className = 'fas fa-search search-icon';
                iconElement.style.cssText = `
                    position: absolute;
                    left: 24px;
                    top: 50%;
                    transform: translateY(-50%);
                    color: #64748b;
                    font-size: 16px;
                    z-index: 10;
                    pointer-events: none;
                `;
                searchContainer.appendChild(iconElement);

                // Adjust input padding
                searchInput.style.paddingLeft = '54px';
            }
        }

        setupInitialSuggestions() {
            searchInput.addEventListener('focus', () => {
                if (!searchInput.value.trim()) {
                    this.showPopularDestinations();
                }
            });
        }

        showPopularDestinations() {
            const suggestions = popularDestinations.map((dest) => ({
                displayText: `Explore ${dest}`,
                icon: 'fa-star',
                type: 'search',
                keyword: dest.toLowerCase(),
                category: 'Popular Destinations'
            }));

            this.renderSuggestions(suggestions, 'Popular Destinations');
        }

        setupEventListeners() {
            // Enhanced input listener with debouncing
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                const query = e.target.value.toLowerCase().trim();

                if (query.length === 0) {
                    this.showPopularDestinations();
                    return;
                }

                // Show loading state
                this.showLoadingState();

                // Debounce search
                searchTimeout = setTimeout(() => {
                    this.performSearch(query);
                }, 150);
            });

            // Keyboard navigation
            searchInput.addEventListener('keydown', (e) => {
                this.handleKeyboardNavigation(e);
            });

            // Enhanced search button
            searchButton.addEventListener('click', () => {
                this.executeSearch();
            });

            // Close suggestions when clicking outside
            document.addEventListener('click', (e) => {
                if (!searchInput.contains(e.target) && !suggestionsBox.contains(e.target)) {
                    this.hideSuggestions();
                }
            });

            // Handle Escape key globally
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.hideSuggestions();
                    searchInput.blur();
                }
            });
        }

        showLoadingState() {
            suggestionsBox.innerHTML = `
                <div class="search-loading">
                    <span>Searching destinations...</span>
                </div>
            `;
            this.showSuggestions();
        }

        performSearch(query) {
            currentSearchQuery = query;

            // Fuzzy matching algorithm
            const filteredSuggestions = this.fuzzySearch(query, searchableContent);

            if (filteredSuggestions.length > 0) {
                this.renderSuggestions(filteredSuggestions);
            } else {
                this.showNoResults(query);
            }
        }

        fuzzySearch(query, items) {
            const results = [];

            items.forEach((item) => {
                let score = 0;

                // Exact keyword match (highest priority)
                if (item.keyword.toLowerCase().includes(query)) {
                    score += 100;
                }

                // Display text match
                if (item.displayText.toLowerCase().includes(query)) {
                    score += 80;
                }

                // Suggestions array match
                const suggestionMatch = item.suggestions.find((s) => s.toLowerCase().includes(query));
                if (suggestionMatch) {
                    score += 60;
                }

                // Character similarity (fuzzy matching)
                score += this.calculateSimilarity(query, item.keyword.toLowerCase()) * 30;

                if (score > 20) {
                    // Threshold for relevance
                    results.push({ ...item, score });
                }
            });

            // Sort by relevance score
            return results.sort((a, b) => b.score - a.score);
        }

        calculateSimilarity(str1, str2) {
            const longer = str1.length > str2.length ? str1 : str2;
            const shorter = str1.length > str2.length ? str2 : str1;

            if (longer.length === 0) return 1.0;

            const distance = this.levenshteinDistance(longer, shorter);
            return (longer.length - distance) / longer.length;
        }

        levenshteinDistance(str1, str2) {
            const matrix = [];

            for (let i = 0; i <= str2.length; i++) {
                matrix[i] = [i];
            }

            for (let j = 0; j <= str1.length; j++) {
                matrix[0][j] = j;
            }

            for (let i = 1; i <= str2.length; i++) {
                for (let j = 1; j <= str1.length; j++) {
                    if (str2.charAt(i - 1) === str1.charAt(j - 1)) {
                        matrix[i][j] = matrix[i - 1][j - 1];
                    } else {
                        matrix[i][j] = Math.min(matrix[i - 1][j - 1] + 1, matrix[i][j - 1] + 1, matrix[i - 1][j] + 1);
                    }
                }
            }

            return matrix[str2.length][str1.length];
        }

        renderSuggestions(suggestions, headerText = null) {
            let html = '';

            if (headerText) {
                html += `
                    <div class="suggestions-header">
                        <h4>${headerText}</h4>
                    </div>
                `;
            }

            // Group by category
            const grouped = this.groupByCategory(suggestions);

            Object.keys(grouped).forEach((category) => {
                if (category !== 'undefined' && grouped[category].length > 0) {
                    html += `<div class="suggestion-category">${category}</div>`;
                }

                grouped[category].forEach((item, index) => {
                    html += `
                        <div class="suggestion-item" 
                             data-index="${index}" 
                             data-item='${JSON.stringify(item)}'
                             role="option"
                             tabindex="-1"
                             aria-selected="false">
                            <i class="fas ${item.icon}" aria-hidden="true"></i>
                            <span>${this.highlightMatch(item.displayText, currentSearchQuery)}</span>
                        </div>
                    `;
                });
            });

            suggestionsBox.innerHTML = html;
            this.showSuggestions();
            this.attachSuggestionListeners();

            // Reset selection
            selectedSuggestionIndex = -1;
        }

        groupByCategory(suggestions) {
            return suggestions.reduce((groups, item) => {
                const category = item.category || 'Other';
                if (!groups[category]) {
                    groups[category] = [];
                }
                groups[category].push(item);
                return groups;
            }, {});
        }

        highlightMatch(text, query) {
            if (!query) return text;

            const regex = new RegExp(`(${query})`, 'gi');
            return text.replace(regex, '<strong style="color: var(--primary-color);">$1</strong>');
        }

        showNoResults(query) {
            suggestionsBox.innerHTML = `
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h4>No results found</h4>
                    <p>We couldn't find anything matching "${query}"</p>
                </div>
            `;
            this.showSuggestions();
        }

        showSuggestions() {
            suggestionsBox.style.display = 'block';
            suggestionsBox.setAttribute('aria-hidden', 'false');
            searchInput.setAttribute('aria-expanded', 'true');
            // Force reflow
            suggestionsBox.offsetHeight;
            suggestionsBox.classList.add('show');
        }

        hideSuggestions() {
            suggestionsBox.classList.remove('show');
            suggestionsBox.setAttribute('aria-hidden', 'true');
            searchInput.setAttribute('aria-expanded', 'false');
            setTimeout(() => {
                if (!suggestionsBox.classList.contains('show')) {
                    suggestionsBox.style.display = 'none';
                }
            }, 300);
        }

        attachSuggestionListeners() {
            const suggestionItems = suggestionsBox.querySelectorAll('.suggestion-item');

            suggestionItems.forEach((item, index) => {
                item.addEventListener('click', () => {
                    const itemData = JSON.parse(item.dataset.item);
                    this.handleSuggestionClick(itemData);
                });

                item.addEventListener('mouseenter', () => {
                    this.updateSelection(index);
                });
            });
        }

        handleKeyboardNavigation(e) {
            const suggestionItems = suggestionsBox.querySelectorAll('.suggestion-item');

            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    if (selectedSuggestionIndex < suggestionItems.length - 1) {
                        this.updateSelection(selectedSuggestionIndex + 1);
                    }
                    break;

                case 'ArrowUp':
                    e.preventDefault();
                    if (selectedSuggestionIndex > 0) {
                        this.updateSelection(selectedSuggestionIndex - 1);
                    }
                    break;

                case 'Enter':
                    e.preventDefault();
                    if (selectedSuggestionIndex >= 0 && suggestionItems[selectedSuggestionIndex]) {
                        const itemData = JSON.parse(suggestionItems[selectedSuggestionIndex].dataset.item);
                        this.handleSuggestionClick(itemData);
                    } else {
                        this.executeSearch();
                    }
                    break;

                case 'Tab':
                    if (selectedSuggestionIndex >= 0 && suggestionItems[selectedSuggestionIndex]) {
                        e.preventDefault();
                        const itemData = JSON.parse(suggestionItems[selectedSuggestionIndex].dataset.item);
                        searchInput.value = itemData.displayText;
                    }
                    break;
            }
        }

        updateSelection(index) {
            const suggestionItems = suggestionsBox.querySelectorAll('.suggestion-item');

            // Remove previous selection
            suggestionItems.forEach((item) => {
                item.classList.remove('keyboard-selected');
                item.setAttribute('aria-selected', 'false');
            });

            // Add new selection
            if (index >= 0 && index < suggestionItems.length) {
                selectedSuggestionIndex = index;
                suggestionItems[index].classList.add('keyboard-selected');
                suggestionItems[index].setAttribute('aria-selected', 'true');

                // Update aria-activedescendant
                searchInput.setAttribute('aria-activedescendant', `suggestion-${index}`);

                // Add ID for aria-activedescendant
                suggestionItems[index].id = `suggestion-${index}`;

                // Scroll into view
                suggestionItems[index].scrollIntoView({
                    block: 'nearest',
                    behavior: 'smooth'
                });
            } else {
                searchInput.removeAttribute('aria-activedescendant');
            }
        }

        handleSuggestionClick(item) {
            // Add to recent searches
            this.addToRecentSearches(item.displayText);

            // Update input value
            searchInput.value = item.displayText;

            // Hide suggestions
            this.hideSuggestions();

            // Execute action
            if (item.type === 'navigate') {
                this.navigateToSection(item.target);
            }

            // Add visual feedback
            this.showSearchSuccess();
        }

        executeSearch() {
            const query = searchInput.value.trim();

            if (query.length === 0) {
                this.showSearchError('Please enter a search term');
                return;
            }

            // Find best match
            const results = this.fuzzySearch(query.toLowerCase(), searchableContent);

            if (results.length > 0) {
                this.handleSuggestionClick(results[0]);
            } else {
                this.showSearchError('No destinations found. Try a different search term.');
            }
        }

        navigateToSection(target) {
            const targetElement = document.querySelector(target);
            if (targetElement) {
                // Smooth scroll with offset for fixed header
                const headerOffset = 100;
                const elementPosition = targetElement.getBoundingClientRect().top;
                const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });

                // Add highlight effect
                this.highlightSection(targetElement);
            }
        }

        highlightSection(element) {
            element.style.transition = 'box-shadow 0.3s ease';
            element.style.boxShadow = '0 0 20px rgba(59, 130, 246, 0.3)';

            setTimeout(() => {
                element.style.boxShadow = '';
            }, 2000);
        }

        showSearchSuccess() {
            const button = searchButton;
            const originalText = button.textContent;
            const originalBackground = button.style.background;

            button.textContent = '✓ Found!';
            button.style.background = 'linear-gradient(135deg, #10b981, #059669)';

            setTimeout(() => {
                button.textContent = originalText;
                button.style.background = originalBackground;
            }, 1500);
        }

        showSearchError(message) {
            // Create temporary error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'search-error-message';
            errorDiv.textContent = message;
            errorDiv.style.cssText = `
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: #fef2f2;
                color: #dc2626;
                padding: 12px 20px;
                border-radius: 12px;
                margin-top: 8px;
                font-size: 14px;
                border: 1px solid #fca5a5;
                z-index: 1001;
                animation: fadeInUp 0.3s ease;
            `;

            const searchContainer = document.querySelector('.search-container');
            searchContainer.appendChild(errorDiv);

            setTimeout(() => {
                errorDiv.remove();
            }, 3000);
        }

        addToRecentSearches(searchTerm) {
            // Remove if already exists
            recentSearches = recentSearches.filter((term) => term !== searchTerm);

            // Add to beginning
            recentSearches.unshift(searchTerm);

            // Keep only last 5 searches
            recentSearches = recentSearches.slice(0, 5);

            // Save to localStorage
            localStorage.setItem('tourban_recent_searches', JSON.stringify(recentSearches));
        }
    }

    // Initialize enhanced search if elements exist
    if (searchInput && suggestionsBox && searchButton) {
        new SearchEnhancer();
    }

    // Simplified animations for hero elements

    window.searchableContent = searchableContent;
    window.popularDestinations = popularDestinations;
});
