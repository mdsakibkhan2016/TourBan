
        const menuToggleButton = document.querySelector('.menu-toggle-button');
        const navLinks = document.querySelector('.nav-links');

        menuToggleButton.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });
    