document.addEventListener('DOMContentLoaded', () => {

    const searchableContent = [
        { keyword: 'worldwide tours', displayText: 'Explore Worldwide Tours', type: 'navigate', target: '#services', icon: 'fa-globe', suggestions: ['world', 'tours', 'vacation', 'w'] },
        { keyword: 'hotel reservation', displayText: 'Find Hotel Reservations', type: 'navigate', target: '#services', icon: 'fa-hotel', suggestions: ['hotel', 'reservation', 'booking', 'stay'] },
        { keyword: 'travel guides', displayText: 'Get Travel Guides', type: 'navigate', target: '#services', icon: 'fa-user-friends', suggestions: ['guides', 'travel', 'assistance'] },
        { keyword: 'event management', displayText: 'Plan with Event Management', type: 'navigate', target: '#services', icon: 'fa-cog', suggestions: ['event', 'management', 'planning'] },
        { keyword: 'about', displayText: 'Learn About Us', type: 'navigate', target: '#about', icon: 'fa-info-circle', suggestions: ['who we are', 'company info', 'our mission'] },
        { keyword: 'services', displayText: 'View All Our Services', type: 'navigate', target: '#services', icon: 'fa-concierge-bell', suggestions: ['service', 'offerings', 'what we do'] }
    ];

    // Get references to the HTML elements
    const searchInput = document.getElementById('searchInput');
    const suggestionsBox = document.getElementById('suggestionsBox');
    const searchButton = document.querySelector('.search-button');

    // Function to handle what happens when a suggestion is clicked
    function handleSuggestionClick(item) {
        searchInput.value = item.displayText;
        suggestionsBox.style.display = 'none';

        if (item.type === 'navigate') {
            const targetElement = document.querySelector(item.target);
            if (targetElement) {
                targetElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    }
    
    // Check if all necessary elements exist before adding listeners
    if (searchInput && suggestionsBox && searchButton) {
        // Listener for typing in the search box
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.toLowerCase().trim();
            suggestionsBox.innerHTML = '';

            if (query.length === 0) {
                suggestionsBox.style.display = 'none';
                return;
            }
            
            //user word to 
            const filteredSuggestions = searchableContent.filter(item => 
                item.keyword.toLowerCase().includes(query) ||
                item.suggestions.some(suggestion => suggestion.toLowerCase().includes(query))
            );

            if (filteredSuggestions.length > 0) {
                filteredSuggestions.forEach(item => {
                    const suggestionItem = document.createElement('div');
                    suggestionItem.className = 'suggestion-item';
                    suggestionItem.innerHTML = `<i class="fas ${item.icon}"></i> <span>${item.displayText}</span>`;
                    suggestionItem.addEventListener('click', () => handleSuggestionClick(item));
                    suggestionsBox.appendChild(suggestionItem);
                });
                suggestionsBox.style.display = 'block';
            } else {
                suggestionsBox.style.display = 'none';
            }
        });

        // Listener for clicking the search button
        searchButton.addEventListener('click', () => {
            const query = searchInput.value.toLowerCase().trim();
            if (query.length > 0) {
                const firstMatch = searchableContent.find(item => 
                    item.keyword.toLowerCase().includes(query) ||
                    item.suggestions.some(suggestion => suggestion.toLowerCase().includes(query))
                );

                if (firstMatch) {
                    handleSuggestionClick(firstMatch);
                } else {
                    alert("Sorry, no results found for your search.");
                }
            }
        });

        // Listener to hide suggestions when clicking outside
        document.addEventListener('click', (event) => {
            const isClickInside = searchInput.contains(event.target) || suggestionsBox.contains(event.target);
            if (!isClickInside) {
                suggestionsBox.style.display = 'none';
            }
        });
    }
});
